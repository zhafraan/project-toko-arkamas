<?php

namespace App\Http\Controllers;

use App\Models\AgentDelivery;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->paginate(20);
        return view('inventory.index', compact('products'));
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

        $data['price_buy']      = $data['price_buy'] ?? 0;
        $data['price_tube']     = $data['price_tube'] ?? 0;
        $data['stock_filled']   = $data['stock_filled'] ?? 0;
        $data['stock_empty']    = $data['stock_empty'] ?? 0;
        $data['stock_borrowed'] = $data['stock_borrowed'] ?? 0;
        $data['is_active']      = true;

        $product = Product::create($data);
        return back()->with('success', 'Produk "' . $product->name . '" berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'required|string|max:50|unique:products,sku,' . $product->id,
            'price_buy'      => 'nullable|numeric|min:0',
            'price_sell'     => 'required|numeric|min:0',
            'price_tube'     => 'nullable|numeric|min:0',
            'is_active'      => 'nullable',
            'stock_filled'   => 'nullable|integer|min:0',
            'stock_empty'    => 'nullable|integer|min:0',
            'stock_borrowed' => 'nullable|integer|min:0',
        ]);

        $data['price_buy']  = $data['price_buy'] ?? 0;
        $data['price_tube'] = $data['price_tube'] ?? 0;
        $data['is_active']  = $request->filled('is_active') ? (bool)$request->is_active : $product->is_active;

        if ($request->has('stock_filled') && $request->stock_filled !== null) {
            $data['stock_filled'] = (int)$request->stock_filled;
        }
        if ($request->has('stock_empty') && $request->stock_empty !== null) {
            $data['stock_empty'] = (int)$request->stock_empty;
        }
        if ($request->has('stock_borrowed') && $request->stock_borrowed !== null) {
            $data['stock_borrowed'] = (int)$request->stock_borrowed;
        }

        $product->update($data);
        return back()->with('success', 'Produk "' . $product->name . '" berhasil diperbarui!');
    }

    public function destroy(Product $product)
    {
        $name = $product->name;

        DB::transaction(function () use ($product) {
            $product->transactionDetails()->delete();
            $product->agentDeliveries()->delete();
            $product->tubeLoans()->delete();
            $product->delete();
        });

        return back()->with('success', 'Produk "' . $name . '" berhasil dihapus!');
    }

    // Stock Opname / Adjustment
    public function adjustStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'stock_filled'   => 'required|integer|min:0',
            'stock_empty'    => 'required|integer|min:0',
            'stock_borrowed' => 'required|integer|min:0',
        ]);
        $product->update($data);
        return back()->with('success', 'Stok berhasil disesuaikan (Stock Opname).');
    }

    // Deliveries
    public function deliveries()
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $deliveries = AgentDelivery::with(['product', 'user'])
            ->latest('delivery_date')->paginate(20);
        return view('inventory.deliveries', compact('products', 'deliveries'));
    }

    public function storeDelivery(Request $request)
    {
        $data = $request->validate([
            'product_id'          => 'required|exists:products,id',
            'delivery_date'       => 'required|date',
            'qty_received'        => 'required|integer|min:1',
            'buy_price_per_unit'  => 'required|numeric|min:0',
            'notes'               => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $data['user_id']    = auth()->id();
            $data['total_cost'] = $data['qty_received'] * $data['buy_price_per_unit'];

            AgentDelivery::create($data);

            // Logika stok: isi +qty, kosong -qty
            $product = Product::findOrFail($data['product_id']);
            $product->increment('stock_filled', $data['qty_received']);
            $product->decrement('stock_empty',  $data['qty_received']);
        });

        return back()->with('success', 'Pengiriman agen berhasil dicatat!');
    }
}
