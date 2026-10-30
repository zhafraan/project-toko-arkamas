<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TubeLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = TubeLoan::with(['customer', 'product', 'transaction'])
            ->whereIn('status', ['dipinjam', 'sebagian_kembali']);

        if ($request->search) {
            $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
        }

        $loans = $query->latest('borrow_date')->paginate(20)->withQueryString();
        return view('loans.index', compact('loans'));
    }

    public function returnTube(Request $request, TubeLoan $loan)
    {
        $request->validate([
            'qty_return' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->qty_return;

        DB::transaction(function () use ($loan, $qty) {
            // Kunci baris pinjaman & produk agar dua kasir tidak mencatat pengembalian
            // yang sama bersamaan (stok dipinjam bisa menjadi minus).
            $fresh   = TubeLoan::lockForUpdate()->findOrFail($loan->id);
            $product = Product::lockForUpdate()->findOrFail($fresh->product_id);
            $left    = $fresh->qty_borrowed - $fresh->qty_returned;

            if ($qty > $left) {
                throw ValidationException::withMessages([
                    'qty_return' => "Jumlah pengembalian melebihi sisa pinjaman ({$left} tabung).",
                ]);
            }

            $newReturned = $fresh->qty_returned + $qty;
            $newStatus   = $newReturned >= $fresh->qty_borrowed ? 'dikembalikan' : 'sebagian_kembali';

            $fresh->update([
                'qty_returned' => $newReturned,
                'status'       => $newStatus,
                'return_date'  => $newStatus === 'dikembalikan' ? today() : $fresh->return_date,
            ]);

            // Update stok: borrowed -qty, empty +qty
            $product->decrement('stock_borrowed', $qty);
            $product->increment('stock_empty', $qty);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pengembalian {$qty} tabung berhasil dicatat.",
                'loan'    => $loan->fresh(['customer', 'product']),
            ]);
        }

        return back()->with('success', "Pengembalian {$qty} tabung berhasil dicatat.");
    }
}
