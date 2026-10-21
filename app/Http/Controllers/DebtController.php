<?php

namespace App\Http\Controllers;

use App\Models\DebtPayment;
use App\Models\DeletionLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'user', 'debtPayments.user'])
            ->where('payment_status', 'belum_lunas');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->latest('transaction_date')->paginate(20)->withQueryString();
        $totalPiutang = Transaction::where('payment_status', 'belum_lunas')->sum('remaining_debt');

        return view('debts.index', compact('transactions', 'totalPiutang'));
    }

    public function pay(Request $request, Transaction $transaction)
    {
        $request->validate([
            'amount_paid'    => 'required|numeric|min:1|max:' . (float) $transaction->remaining_debt,
            'payment_method' => 'required|in:cash,transfer',
            'notes'          => 'nullable|string|max:255',
        ]);

        $lunas = false;
        DB::transaction(function () use ($request, $transaction, &$lunas) {
            // Kunci baris saat pembayaran agar dua kasir tidak bisa membayar dua kali
            // bersamaan (sisa piutang bisa menjadi minus).
            $fresh   = Transaction::lockForUpdate()->findOrFail($transaction->id);
            $amount  = (float) $request->amount_paid;
            $balance = (float) $fresh->remaining_debt;

            if ($amount > $balance) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount_paid' => 'Jumlah pembayaran melebihi sisa piutang (Rp ' . number_format($balance, 0, ',', '.') . ').',
                ]);
            }

            DebtPayment::create([
                'transaction_id' => $fresh->id,
                'user_id'        => auth()->id(),
                'payment_date'   => now(),
                'amount_paid'    => $amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
            ]);

            $newRemaining = $balance - $amount;
            $newPaid      = (float) $fresh->paid_amount + $amount;
            $lunas        = $newRemaining <= 0;

            $fresh->update([
                'paid_amount'    => $newPaid,
                'remaining_debt' => $newRemaining,
                'payment_status' => $lunas ? 'lunas' : 'belum_lunas',
                'paid_at'        => $lunas ? now() : null,
            ]);
        });

        $message = $lunas
            ? 'Pembayaran tercatat. Nota ' . $transaction->invoice_number . ' sudah LUNAS.'
            : 'Pembayaran sebagian tercatat. Sisa piutang: Rp ' . number_format((float) $transaction->fresh()->remaining_debt, 0, ',', '.') . '.';

        if ($request->expectsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => $message,
                'lunas'          => $lunas,
                'transaction'    => $transaction->fresh(['customer', 'user']),
                'remaining_debt' => $transaction->fresh()->remaining_debt,
            ]);
        }

        return back()->with('success', $message);
    }

    /** Batalkan/void satu pembayaran piutang (khusus pemilik). Saldo nota dikembalikan. */
    public function voidPayment(Request $request, DebtPayment $payment)
    {
        $invoice = null;

        DB::transaction(function () use ($payment, &$invoice) {
            $fresh     = DebtPayment::lockForUpdate()->findOrFail($payment->id);
            $trx       = Transaction::lockForUpdate()->findOrFail($fresh->transaction_id);
            $invoice   = $trx->invoice_number;
            $amount    = (float) $fresh->amount_paid;

            DeletionLog::record(
                $fresh,
                "Pembayaran piutang nota {$invoice} sebesar Rp " . number_format($amount, 0, ',', '.'),
                [
                    'invoice'        => $invoice,
                    'amount_paid'    => $amount,
                    'payment_method' => $fresh->payment_method,
                    'payment_date'   => $fresh->payment_date?->toDateTimeString(),
                    'notes'          => $fresh->notes,
                ]
            );

            $newPaid      = max(0, (float) $trx->paid_amount - $amount);
            $newRemaining = (float) $trx->remaining_debt + $amount;

            $fresh->delete();

            $trx->update([
                'paid_amount'    => $newPaid,
                'remaining_debt' => $newRemaining,
                'payment_status' => $newRemaining <= 0.00001 ? 'lunas' : 'belum_lunas',
                'paid_at'        => $newRemaining <= 0.00001 ? ($trx->paid_at ?? now()) : null,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran nota ' . $invoice . ' dibatalkan.',
            ]);
        }

        return back()->with('success', 'Pembayaran dibatalkan. Saldo piutang nota ' . $invoice . ' dikembalikan.');
    }
}
