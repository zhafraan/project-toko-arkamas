@extends('layouts.app')
@section('title', 'Kiriman Agen')
@section('page-title', 'Penerimaan Kiriman Gas dari Agen')

@section('content')
<div style="display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start;">

    {{-- Form Catat Kiriman --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-truck" style="color:var(--accent);margin-right:6px;"></i> Catat Kiriman Baru</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.storeDelivery') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Produk Gas *</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Pengiriman *</label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Tabung Diterima *</label>
                    <input type="number" name="qty_received" class="form-control" placeholder="0" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Tebus per Tabung (Rp) *</label>
                    <input type="number" name="buy_price_per_unit" class="form-control" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (No. DO / Nama Sopir)</label>
                    <input type="text" name="notes" class="form-control" placeholder="Opsional">
                </div>
                <div style="background:#f0f9ff;border-radius:8px;padding:12px;margin-bottom:14px;font-size:12px;">
                    <i class="fa-solid fa-circle-info" style="color:var(--info);"></i>
                    <strong>Logika Otomatis:</strong> Tabung Isi <span style="color:var(--success);">+Qty</span>, Tabung Kosong <span style="color:var(--danger);">−Qty</span>
                </div>
                <button type="submit" class="btn btn-accent" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-truck"></i> Catat Pengiriman
                </button>
            </form>
        </div>
    </div>

    {{-- Delivery History --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary-light);margin-right:6px;"></i> Riwayat Kiriman Agen</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th><th>Produk</th><th>Qty</th>
                        <th>Harga Tebus/Unit</th><th>Total Biaya</th>
                        <th>Pencatat</th><th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                    <tr>
                        <td>{{ $delivery->delivery_date->isoFormat('D MMM Y') }}</td>
                        <td style="font-weight:600;">{{ $delivery->product->name }}</td>
                        <td><span style="background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:20px;font-weight:700;">{{ $delivery->qty_received }}</span></td>
                        <td>Rp {{ number_format($delivery->buy_price_per_unit,0,',','.') }}</td>
                        <td style="font-weight:700;color:var(--primary);">Rp {{ number_format($delivery->total_cost,0,',','.') }}</td>
                        <td style="font-size:12px;">{{ $delivery->user->name }}</td>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $delivery->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada riwayat kiriman</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:0 16px;">{{ $deliveries->links('vendor.pagination.simple') }}</div>
    </div>
</div>
@endsection
