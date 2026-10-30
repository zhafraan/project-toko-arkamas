<?php

namespace App\Http\Controllers;

use App\Models\AgentDelivery;
use App\Models\DeletionLog;
use App\Models\Product;
use App\Models\StockAdjustment;
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

    /** Aktif/nonaktifkan produk langsung dari daftar (khusus pemilik). */
    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active
            ? 'Produk "' . $product->name . '" diaktifkan kembali.'
            : 'Produk "' . $product->name . '" dinonaktifkan. Produk tidak tampil di kasir.');
    }

    /** Hanya produk yang belum pernah dipakai transaksi yang boleh dihapus; selain itu cukup dinonaktifkan. */
    public function destroy(Product $product)
    {
        if ($product->transactionDetails()->exists() || $product->tubeLoans()->exists()) {
            return back()->with('error', 'Produk "' . $product->name . '" memiliki riwayat transaksi sehingga tidak dapat dihapus. Ubah statusnya menjadi Nonaktif.');
        }

        $name = $product->name;
        DB::transaction(function () use ($product) {
            DeletionLog::record(
                $product,
                "Produk {$product->name} (SKU {$product->sku})",
                [
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'price_buy'      => (float) $product->price_buy,
                    'price_sell'     => (float) $product->price_sell,
                    'price_tube'     => (float) $product->price_tube,
                    'stock_filled'   => (int) $product->stock_filled,
                    'stock_empty'    => (int) $product->stock_empty,
                    'stock_borrowed' => (int) $product->stock_borrowed,
                    'delivery_count' => $product->agentDeliveries()->count(),
                ]
            );

            $product->agentDeliveries()->delete();
            $product->delete();
        });

        return back()->with('success', 'Produk "' . $name . '" berhasil dihapus.');
    }

    // Penyesuaian stok — setiap perubahan wajib beralasan dan tercatat di riwayat audit
    public function adjustStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'stock_filled'   => 'required|integer|min:0',
            'stock_empty'    => 'required|integer|min:0',
            'stock_borrowed' => 'required|integer|min:0',
            'reason'         => 'required|string|max:255',
        ]);

        $before = [
            'filled'   => (int) $product->stock_filled,
            'empty'    => (int) $product->stock_empty,
            'borrowed' => (int) $product->stock_borrowed,
        ];

        if ($before['filled'] === $data['stock_filled']
            && $before['empty'] === $data['stock_empty']
            && $before['borrowed'] === $data['stock_borrowed']) {
            return back()->with('info', 'Tidak ada perubahan angka stok, penyesuaian tidak disimpan.');
        }

        DB::transaction(function () use ($product, $data, $before) {
            $product->update([
                'stock_filled'   => $data['stock_filled'],
                'stock_empty'    => $data['stock_empty'],
                'stock_borrowed' => $data['stock_borrowed'],
            ]);

            StockAdjustment::create([
                'product_id'      => $product->id,
                'user_id'         => auth()->id(),
                'filled_before'   => $before['filled'],
                'filled_after'    => $data['stock_filled'],
                'empty_before'    => $before['empty'],
                'empty_after'     => $data['stock_empty'],
                'borrowed_before' => $before['borrowed'],
                'borrowed_after'  => $data['stock_borrowed'],
                'reason'          => $data['reason'],
            ]);
        });

        return back()->with('success', 'Stok "' . $product->name . '" berhasil disesuaikan. Perubahan tercatat di riwayat penyesuaian stok.');
    }

    // Kiriman agen
    public function deliveries()
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $deliveries = AgentDelivery::with(['product', 'user'])->latest('delivery_date')->latest('id')->paginate(20);

        // Harga agen terakhir per produk dipakai sebagai nilai default di form
        // (pemilik bisa menggantinya bila ada kenaikan harga dari agen).
        $lastPrices = AgentDelivery::whereIn('product_id', $products->pluck('id'))
            ->orderBy('delivery_date')->orderBy('id')
            ->get(['product_id', 'buy_price_per_unit'])
            ->groupBy('product_id')
            ->map(fn ($rows) => (float) $rows->last()->buy_price_per_unit);

        return view('inventory.deliveries', compact('products', 'deliveries', 'lastPrices'));
    }

    public function storeDelivery(Request $request)
    {
        $data = $request->validate([
            'product_id'         => 'required|exists:products,id',
            'delivery_date'      => 'required|date|after_or_equal:today',
            'qty_received'       => 'required|integer|min:1',
            'buy_price_per_unit' => 'nullable|numeric|min:0',
            'admin_fee'          => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string|max:255',
        ]);

        // Harga hanya boleh ditentukan pemilik. Kasir memakai tarif terakhir produk
        // (atau default 14.500) — input harga dari kasir diabaikan.
        if (auth()->user()->isOwner()) {
            $data['buy_price_per_unit'] = $data['buy_price_per_unit'] ?? Product::DEFAULT_BUY_PRICE;
        } else {
            $lastPrice = AgentDelivery::where('product_id', $data['product_id'])
                ->orderByDesc('delivery_date')->orderByDesc('id')->value('buy_price_per_unit');

            $data['buy_price_per_unit'] = (float) ($lastPrice ?? Product::DEFAULT_BUY_PRICE);
        }
        $data['admin_fee'] = $data['admin_fee'] ?? 0;

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
                'admin_fee'  => $data['admin_fee'],
            ]);

            $product->increment('stock_filled', $data['qty_received']);
            $product->decrement('stock_empty', $data['qty_received']);
        });

        return back()->with('success', 'Penerimaan dari agen berhasil dicatat.');
    }

    /** Hapus penerimaan dari agen (khusus owner) — stok dibalik & penghapusan dicatat di riwayat penghapusan. */
    public function destroyDelivery(AgentDelivery $delivery)
    {
        DB::transaction(function () use ($delivery) {
            $delivery->load(['product', 'user']);
            $product = Product::lockForUpdate()->findOrFail($delivery->product_id);

            DeletionLog::record(
                $delivery,
                "Penerimaan {$delivery->product->name} × {$delivery->qty_received} tabung — Rp " . number_format((float) $delivery->total_cost, 0, ',', '.'),
                [
                    'delivery_date'      => $delivery->delivery_date->toDateString(),
                    'product'            => $delivery->product->name,
                    'qty_received'       => $delivery->qty_received,
                    'buy_price_per_unit' => (float) $delivery->buy_price_per_unit,
                    'total_cost'         => (float) $delivery->total_cost,
                    'admin_fee'          => (float) $delivery->admin_fee,
                    'notes'              => $delivery->notes,
                    'recorded_by'        => $delivery->user?->name,
                ]
            );

            // Balik stok persis seperti waktu pencatatan (isi -take, kosong +take),
            // dibatasi stok isi yang tersisa agar jumlah total tabung tidak pernah berubah
            $take = min($product->stock_filled, $delivery->qty_received);
            $product->stock_filled -= $take;
            $product->stock_empty += $take;
            $product->save();

            $delivery->delete();
        });

        return back()->with('success', 'Penerimaan dari agen berhasil dihapus dan stok telah dikembalikan. Penghapusan tercatat di riwayat penghapusan.');
    }
}
