<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeletionLog;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TubeLoan;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index()
    {
        // Semua produk aktif ditampilkan; yang stok isinya 0 ditandai "Habis" di kasir.
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $lowStockLimit = Product::LOW_STOCK;
        return view('pos.index', compact('products', 'lowStockLimit'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.transaction_type' => 'required|in:refill,pinjam_tabung,plus_tabung',
            'items.*.qty'              => 'required|integer|min:1',
            'customer_id'              => 'nullable|exists:customers,id',
            'payment_method'           => 'required|in:cash,transfer',
            'payment_status'           => 'required|in:lunas,belum_lunas',
            'paid_amount'              => 'nullable|numeric|min:0',
            'payment_note'             => 'nullable|string|max:255',
        ]);

        $lines      = collect($data['items']);
        $customerId = $data['customer_id'] ?? null;

        // Pelanggan wajib untuk pinjam tabung atau piutang
        $hasPinjam = $lines->contains('transaction_type', 'pinjam_tabung');
        if (($hasPinjam || $data['payment_status'] === 'belum_lunas') && !$customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Pelanggan wajib dipilih untuk transaksi pinjaman tabung atau piutang.',
            ]);
        }

        $sale = function () use ($data, $lines, $customerId) {
            // Kunci baris produk agar dua kasir tidak menjual stok yang sama
            $products = Product::whereIn('id', $lines->pluck('product_id')->unique())
                ->lockForUpdate()->get()->keyBy('id');

            // Cek stok per produk (gabungan semua baris keranjang)
            foreach ($lines->groupBy('product_id') as $productId => $group) {
                $product = $products[$productId];
                $needed  = (int) $group->sum('qty');

                if (!$product->is_active) {
                    throw ValidationException::withMessages(['items' => "Produk {$product->name} sudah tidak aktif dan tidak dapat dijual."]);
                }
                if ($product->stock_filled < $needed) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak cukup (tersisa {$product->stock_filled}, diminta {$needed}).",
                    ]);
                }
            }

            // Hitung harga di server (harga dari browser tidak dipercaya)
            $total   = 0.0;
            $details = [];
            foreach ($lines as $line) {
                $product = $products[$line['product_id']];
                $qty     = (int) $line['qty'];
                $type    = $line['transaction_type'];
                $price = (float) $product->price_sell + ($type === 'plus_tabung' ? (float) $product->price_tube : 0);

                $total    += $price * $qty;
                $details[] = compact('product', 'qty', 'type', 'price');
            }

            // Pembayaran
            $status = $data['payment_status'];
            $paid   = isset($data['paid_amount']) ? (float) $data['paid_amount'] : null;

            if ($status === 'lunas') {
                $paid ??= $total; // kolom dikosongkan = uang pas
                if ($paid < $total) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Uang yang diterima kurang dari total tagihan. Pilih status "Piutang" jika pembayaran belum penuh.',
                    ]);
                }
            } else {
                $paid ??= 0;
            }

            $remaining = max(0, $total - $paid);
            $change    = max(0, $paid - $total);
            $finalStatus = $remaining <= 0 ? 'lunas' : 'belum_lunas';

            $transaction = Transaction::create([
                'invoice_number'   => Transaction::generateInvoiceNumber(),
                'user_id'          => auth()->id(),
                'customer_id'      => $customerId,
                'transaction_date' => now(),
                'total_amount'     => $total,
                'paid_amount'      => $paid,
                'remaining_debt'   => $remaining,
                'change_amount'    => $change,
                'payment_status'   => $finalStatus,
                'payment_method'   => $data['payment_method'],
                'payment_note'     => $data['payment_note'] ?? null,
                'paid_at'          => $finalStatus === 'lunas' ? now() : null,
            ]);

            foreach ($details as $d) {
                $product = $d['product'];

                TransactionDetail::create([
                    'transaction_id'   => $transaction->id,
                    'product_id'       => $product->id,
                    'transaction_type' => $d['type'],
                    'qty'              => $d['qty'],
                    'price'            => $d['price'],
                    'cost_price'       => $product->price_buy,
                    'subtotal'         => $d['price'] * $d['qty'],
                ]);

                // Matriks stok:
                //  refill        : isi -qty, kosong +qty (pelanggan menukar tabung kosongnya)
                //  pinjam_tabung : isi -qty, dipinjam +qty (tabung dibawa pelanggan, wajib kembali)
                //  plus_tabung   : isi -qty (tabung ikut terjual)
                $product->decrement('stock_filled', $d['qty']);
                if ($d['type'] === 'refill') {
                    $product->increment('stock_empty', $d['qty']);
                } elseif ($d['type'] === 'pinjam_tabung') {
                    $product->increment('stock_borrowed', $d['qty']);

                    TubeLoan::create([
                        'transaction_id' => $transaction->id,
                        'customer_id'    => $customerId,
                        'product_id'     => $product->id,
                        'qty_borrowed'   => $d['qty'],
                        'deposit_amount' => 0,
                        'status'         => 'dipinjam',
                        'borrow_date'    => today(),
                    ]);
                }
            }

            return $transaction;
        };

        // Dua kasir bisa menyimpan nota pada detik yang sama (nomor urut bentrok).
        // Bila terjadi, seluruh transaksi diulang sampai nomor nota berhasil.
        $attempts = 0;
        while (true) {
            try {
                $transaction = DB::transaction($sale);
                break;
            } catch (QueryException $e) {
                $bentrok = ($e->errorInfo[0] ?? null) === '23000'
                    && str_contains($e->getMessage(), 'invoice_number');
                if (!$bentrok || ++$attempts >= 3) {
                    throw $e;
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'        => true,
                'invoice_number' => $transaction->invoice_number,
                'receipt_url'    => route('pos.receipt', $transaction),
                'total_amount'   => (float) $transaction->total_amount,
                'paid_amount'    => (float) $transaction->paid_amount,
                'change_amount'  => (float) $transaction->change_amount,
                'remaining_debt' => (float) $transaction->remaining_debt,
            ]);
        }

        return redirect()->route('pos.receipt', $transaction)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load(['customer', 'user', 'details.product', 'tubeLoans.product']);
        return view('pos.receipt', compact('transaction'));
    }

    public function history(Request $request)
    {
        $onlyToday = auth()->user()->isKasir();
        $query     = Transaction::with(['customer', 'user'])->latest('transaction_date');

        if ($onlyToday) {
            $query->whereDate('transaction_date', today());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->paginate(20)->withQueryString();
        return view('pos.history', compact('transactions', 'onlyToday'));
    }

    /** Hapus nota (khusus owner) — stok dikembalikan & penghapusan dicatat di riwayat penghapusan. */
    public function destroy(Transaction $transaction)
    {
        if ($transaction->debtPayments()->exists()) {
            return back()->with('error', 'Nota ' . $transaction->invoice_number . ' tidak dapat dihapus karena sudah ada pembayaran piutang tercatat.');
        }

        DB::transaction(function () use ($transaction) {
            $transaction->load(['details.product', 'tubeLoans.product', 'customer', 'user']);

            $productIds = $transaction->details->pluck('product_id')
                ->concat($transaction->tubeLoans->pluck('product_id'))->unique();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            // Hitung pembalikan stok per produk:
            //  refill        : isi +qty, kosong -qty
            //  plus_tabung   : isi +qty
            //  pinjam_tabung : isi +qty, dipinjam -(sisa pinjaman), kosong -yang sudah kembali
            $giveFilled   = [];
            $needEmpty    = [];
            $needBorrowed = [];

            foreach ($transaction->details as $detail) {
                $giveFilled[$detail->product_id] = ($giveFilled[$detail->product_id] ?? 0) + $detail->qty;
                if ($detail->transaction_type === 'refill') {
                    $needEmpty[$detail->product_id] = ($needEmpty[$detail->product_id] ?? 0) + $detail->qty;
                }
            }
            foreach ($transaction->tubeLoans as $loan) {
                $outstanding = $loan->qty_borrowed - $loan->qty_returned;
                $needBorrowed[$loan->product_id] = ($needBorrowed[$loan->product_id] ?? 0) + $outstanding;
                if ($loan->qty_returned > 0) {
                    $needEmpty[$loan->product_id] = ($needEmpty[$loan->product_id] ?? 0) + $loan->qty_returned;
                }
            }

            foreach ($needEmpty as $productId => $qty) {
                $product = $products[$productId];
                if ($product->stock_empty < $qty) {
                    throw ValidationException::withMessages([
                        'transactions' => "Stok tabung kosong {$product->name} hanya {$product->stock_empty}, tidak mencukupi untuk membalik penghapusan nota ini (butuh {$qty}). Sesuaikan stok terlebih dahulu.",
                    ]);
                }
            }
            foreach ($needBorrowed as $productId => $qty) {
                $product = $products[$productId];
                if ($product->stock_borrowed < $qty) {
                    throw ValidationException::withMessages([
                        'transactions' => "Stok tabung dipinjam {$product->name} hanya {$product->stock_borrowed}, tidak mencukupi untuk membalik penghapusan nota ini (butuh {$qty}).",
                    ]);
                }
            }

            foreach ($giveFilled as $productId => $qty) {
                $products[$productId]->increment('stock_filled', $qty);
            }
            foreach ($needEmpty as $productId => $qty) {
                $products[$productId]->decrement('stock_empty', $qty);
            }
            foreach ($needBorrowed as $productId => $qty) {
                $products[$productId]->decrement('stock_borrowed', $qty);
            }

            // Snapshot untuk riwayat penghapusan
            DeletionLog::record(
                $transaction,
                "Nota {$transaction->invoice_number} — Rp " . number_format((float) $transaction->total_amount, 0, ',', '.'),
                [
                    'invoice_number'   => $transaction->invoice_number,
                    'transaction_date' => $transaction->transaction_date->toDateTimeString(),
                    'customer'         => $transaction->customer?->name ?? 'Umum',
                    'total_amount'     => (float) $transaction->total_amount,
                    'paid_amount'      => (float) $transaction->paid_amount,
                    'remaining_debt'   => (float) $transaction->remaining_debt,
                    'payment_status'   => $transaction->payment_status,
                    'payment_method'   => $transaction->payment_method,
                    'kasir'            => $transaction->user?->name,
                    'items'            => $transaction->details->map(fn ($d) => [
                        'product' => $d->product->name,
                        'type'    => $d->transaction_type,
                        'qty'     => $d->qty,
                        'price'   => (float) $d->price,
                        'subtotal'=> (float) $d->subtotal,
                    ])->all(),
                    'loans'            => $transaction->tubeLoans->map(fn ($l) => [
                        'product'      => $l->product->name,
                        'qty_borrowed' => $l->qty_borrowed,
                        'qty_returned' => $l->qty_returned,
                        'status'       => $l->status,
                    ])->all(),
                ]
            );

            $transaction->tubeLoans()->delete();
            $transaction->delete(); // details ikut terhapus (cascade)
        });

        return back()->with('success', 'Nota berhasil dihapus dan stok telah dikembalikan. Penghapusan tercatat di riwayat penghapusan.');
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'nik'           => 'nullable|string|max:16|unique:customers,nik',
            'customer_type' => 'required|in:rumah_tangga,umkm,pengecer',
            'address'       => 'nullable|string',
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'success'  => true,
            'customer' => $customer->only(['id', 'name', 'phone', 'customer_type']) + ['debt' => 0],
        ]);
    }
}
