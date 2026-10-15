<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TubeLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $products  = Product::where('is_active', true)->where('stock_filled', '>', 0)->get();
        $customers = Customer::orderBy('name')->get();
        return view('pos.index', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.transaction_type' => 'required|in:refill,pinjam_tabung,plus_tabung',
            'items.*.qty'              => 'required|integer|min:1',
            'payment_method'           => 'required|in:cash,transfer',
            'payment_status'           => 'required|in:lunas,belum_lunas',
            'paid_amount'              => 'required|numeric|min:0',
        ]);

        // Validasi: pelanggan wajib jika ada pinjam tabung / belum lunas
        $hasPinjam  = collect($request->items)->contains('transaction_type', 'pinjam_tabung');
        $isBelumLunas = $request->payment_status === 'belum_lunas';

        if (($hasPinjam || $isBelumLunas) && !$request->customer_id) {
            return back()->withErrors(['customer_id' => 'Pelanggan wajib dipilih untuk transaksi pinjam tabung atau kasbon.'])->withInput();
        }

        DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty     = (int) $item['qty'];
                $type    = $item['transaction_type'];

                // Hitung harga
                $price = $type === 'plus_tabung'
                    ? $product->price_sell + $product->price_tube
                    : $product->price_sell;

                $subtotal     = $price * $qty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product'  => $product,
                    'qty'      => $qty,
                    'type'     => $type,
                    'price'    => $price,
                    'cost'     => $product->price_buy,
                    'subtotal' => $subtotal,
                ];
            }

            $paidAmount    = (float) $request->paid_amount;
            $remainingDebt = max(0, $totalAmount - $paidAmount);
            $changeAmount  = max(0, $paidAmount - $totalAmount);
            $status        = $remainingDebt <= 0 ? 'lunas' : 'belum_lunas';

            // Buat transaksi
            $transaction = Transaction::create([
                'invoice_number'   => Transaction::generateInvoiceNumber(),
                'user_id'          => auth()->id(),
                'customer_id'      => $request->customer_id ?: null,
                'transaction_date' => now(),
                'total_amount'     => $totalAmount,
                'paid_amount'      => $paidAmount,
                'remaining_debt'   => $remainingDebt,
                'change_amount'    => $changeAmount,
                'payment_status'   => $status,
                'payment_method'   => $request->payment_method,
                'payment_note'     => $request->payment_note,
                'paid_at'          => $status === 'lunas' ? now() : null,
            ]);

            // Buat detail & update stok
            foreach ($itemsData as $item) {
                $product = $item['product'];
                $qty     = $item['qty'];
                $type    = $item['type'];

                TransactionDetail::create([
                    'transaction_id'   => $transaction->id,
                    'product_id'       => $product->id,
                    'transaction_type' => $type,
                    'qty'              => $qty,
                    'price'            => $item['price'],
                    'cost_price'       => $item['cost'],
                    'subtotal'         => $item['subtotal'],
                ]);

                // Logika stok berdasarkan matriks PRD
                match ($type) {
                    'refill' => $product->decrement('stock_filled', $qty) || $product->increment('stock_empty', $qty),
                    'pinjam_tabung' => $product->decrement('stock_filled', $qty) || $product->increment('stock_borrowed', $qty),
                    'plus_tabung' => $product->decrement('stock_filled', $qty),
                };

                // Catat pinjaman tabung
                if ($type === 'pinjam_tabung') {
                    TubeLoan::create([
                        'transaction_id' => $transaction->id,
                        'customer_id'    => $request->customer_id,
                        'product_id'     => $product->id,
                        'qty_borrowed'   => $qty,
                        'deposit_amount' => $item['deposit_amount'] ?? 0,
                        'status'         => 'dipinjam',
                        'borrow_date'    => today(),
                    ]);
                }
            }

            session(['last_transaction_id' => $transaction->id]);
            session()->increment('session_tx_count');
        });

        $transactionId = session('last_transaction_id');
        $transaction   = Transaction::with(['customer', 'details.product'])->find($transactionId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'        => true,
                'message'        => 'Transaksi berhasil diproses via AJAX!',
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'receipt_url'    => route('pos.receipt', $transaction->id),
                'total_amount'   => $transaction->total_amount,
                'paid_amount'    => $transaction->paid_amount,
                'change_amount'  => $transaction->change_amount,
                'remaining_debt' => $transaction->remaining_debt,
                'payment_status' => $transaction->payment_status,
                'session_tx'     => session('session_tx_count', 1),
            ]);
        }

        return redirect()->route('pos.receipt', $transactionId)
            ->with('success', 'Transaksi berhasil disimpan!');
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load(['customer', 'user', 'details.product', 'tubeLoans.product']);
        return view('pos.receipt', compact('transaction'));
    }

    public function history(Request $request)
    {
        $user  = auth()->user();
        $query = Transaction::with(['customer', 'user'])->latest('transaction_date');

        if ($user->isKasir()) {
            $query->whereDate('transaction_date', today());
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->paginate(20)->withQueryString();
        return view('pos.history', compact('transactions'));
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'nik'           => 'nullable|string|max:16',
            'customer_type' => 'required|in:rumah_tangga,umkm,pengecer',
            'address'       => 'nullable|string',
        ]);

        $customer = Customer::create($data);
        return response()->json(['success' => true, 'customer' => $customer]);
    }
}
