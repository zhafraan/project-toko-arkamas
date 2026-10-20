<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('transactions')
            ->withSum(['transactions as total_debt' => function ($q) {
                $q->where('payment_status', 'belum_lunas');
            }], 'remaining_debt');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'nik'           => 'nullable|string|max:16|unique:customers,nik',
            'customer_type' => 'required|in:rumah_tangga,umkm,pengecer',
            'address'       => 'nullable|string',
        ]);
        Customer::create($data);
        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'nik'           => 'nullable|string|max:16|unique:customers,nik,' . $customer->id,
            'customer_type' => 'required|in:rumah_tangga,umkm,pengecer',
            'address'       => 'nullable|string',
        ]);
        $customer->update($data);
        return back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->transactions()->exists()) {
            return back()->with('error', 'Pelanggan tidak dapat dihapus karena memiliki riwayat transaksi.');
        }
        $customer->delete();
        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'transactions' => fn ($q) => $q->latest('transaction_date'),
            'tubeLoans.product',
        ]);
        $totalDebt = $customer->transactions->where('payment_status', 'belum_lunas')->sum('remaining_debt');
        $activeLoanCount = $customer->tubeLoans->whereIn('status', ['dipinjam', 'sebagian_kembali'])->count();
        return view('customers.show', compact('customer', 'totalDebt', 'activeLoanCount'));
    }

    /** Pencarian cepat untuk kasir. Tanpa kata kunci -> 8 pelanggan pertama. */
    public function search(Request $request)
    {
        $search = trim((string) $request->q);

        $customers = Customer::query()
            ->withSum(['transactions as debt' => fn ($q) => $q->where('payment_status', 'belum_lunas')], 'remaining_debt')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'phone', 'customer_type'])
            ->map(fn ($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'phone'         => $c->phone,
                'customer_type' => $c->customer_type,
                'debt'          => (float) ($c->debt ?? 0),
            ]);

        return response()->json($customers);
    }
}
