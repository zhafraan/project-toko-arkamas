<?php

namespace App\Http\Controllers;

use App\Models\AgentDelivery;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::withCount('transactionDetails')->orderBy('name')->paginate(20);

        // Total stok dihitung dari semua produk, bukan hanya halaman yang tampil
        $totals = Product::selectRaw('COALESCE(SUM(stock_filled),0) as filled, COALESCE(SUM(stock_empty),0) as empty_, COALESCE(SUM(stock_borrowed),0) as borrowed')->first();
        $totalFilled   = (int) $totals->filled;
        $totalEmpty    = (int) $totals->empty_;
        $totalBorrowed = (int) $totals->borrowed;

        $lowStockLimit = Product::LOW_STOCK;

        return view('inventory.index', compact('products', 'totalFilled', 'totalEmpty', 'totalBorrowed', 'lowStockLimit'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'required|string|max:50|unique:products,sku',
            'price_buy'      => 'nullable|numeric|min:0',
            'price_sell'     => 'required|numeric|min:0',
            'price_tube'     => 'nullable|numeric|min:0',
            'stock_filled'   => 'nullable|integer|min:0',
            'stock_empty'    => 'nullable|integer|min:0',
            'stock_borrowed' => 'nullable|integer|min:0',
        ]);

        foreach (['price_buy', 'price_tube', 'stock_filled', 'stock_empty', 'stock_borrowed'] as $key) {
            $data[$key] = $data[$key] ?? 0;
        }
        $data['is_active'] = true;

        $product = Product::create($data);
        return back()->with('success', 'Produk "' . $product->name . '" berhasil ditambahkan.');
    }

    /** Edit data produk. Stok diubah lewat penyesuaian stok (adjustStock), bukan di sini. */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'sku'        => 'required|string|max:50|unique:products,sku,' . $product->id,
            'price_buy'  => 'nullable|numeric|min:0',
            'price_sell' => 'required|numeric|min:0',
            'price_tube' => 'nullable|numeric|min:0',
            'is_active'  => 'required|boolean',
        ]);

        $data['price_buy']  = $data['price_buy'] ?? 0;
        $data['price_tube'] = $data['price_tube'] ?? 0;

        $product->update($data);
        return back()->with('success', 'Produk "' . $product->name . '" berhasil diperbarui.');
    }

    /** Hanya produk yang belum pernah dipakai transaksi yang boleh dihapus; selain itu cukup dinonaktifkan. */
    public function destroy(Product $product)
    {
        if ($product->transactionDetails()->exists() || $product->tubeLoans()->exists()) {
            return back()->with('error', 'Produk "' . $product->name . '" memiliki riwayat transaksi sehingga tidak dapat dihapus. Ubah statusnya menjadi Nonaktif.');
        }

        $name = $product->name;
        DB::transaction(function () use ($product) {
            $product->agentDeliveries()->delete();
            $product->delete();
        });

        return back()->with('success', 'Produk "' . $name . '" berhasil dihapus.');
    }

    // Penyesuaian stok
    public function adjustStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'stock_filled'   => 'required|integer|min:0',
            'stock_empty'    => 'required|integer|min:0',
            'stock_borrowed' => 'required|integer|min:0',
        ]);
        $product->update($data);
        return back()->with('success', 'Stok "' . $product->name . '" berhasil disesuaikan.');
    }

    // Kiriman agen
    public function deliveries()
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $deliveries = AgentDelivery::with(['product', 'user'])->latest('delivery_date')->latest('id')->paginate(20);
        return view('inventory.deliveries', compact('products', 'deliveries'));
    }

    public function storeDelivery(Request $request)
    {
        $data = $request->validate([
            'product_id'         => 'required|exists:products,id',
            'delivery_date'      => 'required|date',
            'qty_received'       => 'required|integer|min:1',
            'buy_price_per_unit' => 'required|numeric|min:0',
            'notes'              => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            // Tukar tabung: isi masuk, kosong keluar ke agen
            if ($product->stock_empty < $data['qty_received']) {
                throw ValidationException::withMessages([
                    'qty_received' => "Tabung kosong {$product->name} hanya {$product->stock_empty}, kurang dari {$data['qty_received']} yang akan ditukar. Lakukan penyesuaian stok terlebih dahulu bila angka stok belum sesuai.",
                ]);
            }

            AgentDelivery::create($data + [
                'user_id'    => auth()->id(),
                'total_cost' => $data['qty_received'] * $data['buy_price_per_unit'],
            ]);

            $product->increment('stock_filled', $data['qty_received']);
            $product->decrement('stock_empty', $data['qty_received']);
        });

        return back()->with('success', 'Penerimaan dari agen berhasil dicatat.');
    }
}
