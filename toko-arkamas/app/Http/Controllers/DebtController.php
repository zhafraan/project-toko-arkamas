<?php

namespace App\Http\Controllers;

use App\Models\DebtPayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'user'])
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

        DB::transaction(function () use ($request, $transaction) {
            $amount = (float) $request->amount_paid;

            DebtPayment::create([
                'transaction_id' => $transaction->id,
                'user_id'        => auth()->id(),
                'payment_date'   => now(),
                'amount_paid'    => $amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
            ]);

            $newRemaining = $transaction->remaining_debt - $amount;
            $newPaid      = $transaction->paid_amount + $amount;

            $transaction->update([
                'paid_amount'    => $newPaid,
                'remaining_debt' => $newRemaining,
                'payment_status' => $newRemaining <= 0 ? 'lunas' : 'belum_lunas',
                'paid_at'        => $newRemaining <= 0 ? now() : null,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Pembayaran piutang berhasil dicatat.',
                'transaction'    => $transaction->fresh(['customer', 'user']),
                'remaining_debt' => $transaction->fresh()->remaining_debt,
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }
}
