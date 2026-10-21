@extends('layouts.app')
@section('title', 'Penerimaan dari Agen')
@section('page-title', 'Penerimaan dari Agen')
@section('page-sub', 'Catat tabung isi yang diterima dari agen')

@section('content')
<div class="layout-side side-left">

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="truck" /> Catat penerimaan baru</span></div>
        <form method="POST" action="{{ route('inventory.storeDelivery') }}" class="card-body">
            @csrf
            <div class="field">
                <label class="label">Produk <span class="req">*</span></label>
                <select name="product_id" class="select" required>
                    <option value="">Pilih produk…</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Tanggal penerimaan <span class="req">*</span></label>
                <input type="date" name="delivery_date" class="input" value="{{ old('delivery_date', today()->toDateString()) }}" required>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label">Jumlah diterima <span class="req">*</span></label>
                    <input type="number" name="qty_received" class="input" min="1" placeholder="0" value="{{ old('qty_received') }}" required>
                </div>
                <div class="field">
                    <label class="label">Harga dari agen<span class="req">*</span></label>
                    <input type="number" name="buy_price_per_unit" class="input" min="0" placeholder="0" value="{{ old('buy_price_per_unit') }}" required>
                </div>
            </div>
            <div class="field">
                <label class="label">Uang admin pengiriman</label>
                <div class="input-affix">
                    <span class="affix">Rp</span>
                    <input type="number" name="admin_fee" class="input" min="0" placeholder="0 (kosongkan jika tidak ada)" value="{{ old('admin_fee', 0) }}">
                </div>
                <div class="hint">Biaya admin atau ongkos kirim dari agen (di luar harga tabung).</div>
            </div>
            <div class="field">
                <label class="label">Catatan (nomor DO atau nama sopir)</label>
                <input type="text" name="notes" class="input" maxlength="255" placeholder="Opsional" value="{{ old('notes') }}">
            </div>
            <div class="modal-note">
                <strong>Stok diperbarui otomatis:</strong> tabung isi <span class="text-success fw">bertambah</span> dan tabung kosong <span class="text-danger fw">berkurang</span> sebanyak jumlah yang diterima.
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Catat penerimaan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="history" /> Riwayat penerimaan</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tanggal</th><th>Produk</th><th class="num">Jumlah</th><th class="num">Harga tebus</th><th class="num">Total biaya</th><th class="num">Admin kirim</th><th>Pencatat</th></tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                    <tr>
                        <td class="nowrap">{{ $delivery->delivery_date->isoFormat('D MMM Y') }}</td>
                        <td>
                            <div class="cell-title">{{ $delivery->product->name }}</div>
                            @if($delivery->notes)<div class="cell-sub">{{ $delivery->notes }}</div>@endif
                        </td>
                        <td class="num"><span class="qty-pill qty-filled">{{ $delivery->qty_received }}</span></td>
                        <td class="num">@rupiah($delivery->buy_price_per_unit)</td>
                        <td class="num fw">@rupiah($delivery->total_cost)</td>
                        <td class="num">
                            @if($delivery->admin_fee > 0)
                                @rupiah($delivery->admin_fee)
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="muted">{{ $delivery->user->name }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7">
                        <div class="empty">
                            <div class="empty-icon"><x-icon name="truck" size="24" /></div>
                            <div class="empty-title">Belum ada penerimaan tercatat</div>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $deliveries->links('vendor.pagination.simple') }}
    </div>
</div>
@endsection
