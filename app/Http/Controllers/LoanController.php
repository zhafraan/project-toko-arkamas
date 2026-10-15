<?php

namespace App\Http\Controllers;

use App\Models\TubeLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'qty_return' => 'required|integer|min:1|max:' . ($loan->qty_borrowed - $loan->qty_returned),
        ]);

        $qty = (int) $request->qty_return;

        DB::transaction(function () use ($loan, $qty) {
            $newReturned = $loan->qty_returned + $qty;
            $newStatus   = $newReturned >= $loan->qty_borrowed ? 'dikembalikan' : 'sebagian_kembali';

            $loan->update([
                'qty_returned' => $newReturned,
                'status'       => $newStatus,
                'return_date'  => $newStatus === 'dikembalikan' ? today() : $loan->return_date,
            ]);

            // Update stok: borrowed -qty, empty +qty
            $loan->product->decrement('stock_borrowed', $qty);
            $loan->product->increment('stock_empty', $qty);
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Pengembalian {$qty} tabung berhasil dicatat via AJAX.",
                'loan'    => $loan->fresh(['customer', 'product']),
            ]);
        }

        return back()->with('success', "Pengembalian {$qty} tabung berhasil dicatat.");
    }
}
