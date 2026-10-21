@extends('layouts.app')
@section('title', 'Penerimaan dari Agen')
@section('page-title', 'Penerimaan dari Agen')
@section('page-sub', 'Catat tabung isi yang diterima dari agen')

@push('styles')
<style>
    .layout-side.side-left { grid-template-columns: 355px minmax(0, 1fr); }
</style>
@endpush

@section('content')
<div class="layout-side side-left">

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="truck" /> Catat penerimaan baru</span></div>
        <form method="POST" action="{{ route('inventory.storeDelivery') }}" class="card-body">
            @csrf
            <div class="field">
                <label class="label">Produk <span class="req">*</span></label>
                <select name="product_id" id="deliveryProduct" class="select" required>
                    <option value="">Pilih produk…</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-last-price="{{ $lastPrices[$p->id] ?? \App\Models\Product::DEFAULT_BUY_PRICE }}"
                            {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} — stok kosong: {{ $p->stock_empty }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Tanggal penerimaan <span class="req">*</span></label>
                <input type="date" name="delivery_date" class="input" min="{{ today()->toDateString() }}" value="{{ old('delivery_date', today()->toDateString()) }}" required>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label">Jumlah diterima <span class="req">*</span></label>
                    <input type="text" name="qty_received" id="qtyReceived" class="input" inputmode="numeric" placeholder="Ketik jumlah tabung" value="{{ old('qty_received') }}" autocomplete="off" required>
                </div>
                @if(auth()->user()->isOwner())
                <div class="field">
                    <label class="label">Harga dari agen</label>
                    <div class="input-affix">
                        <span class="affix">Rp</span>
                        <input type="text" name="buy_price_per_unit" id="buyPrice" class="input num" inputmode="numeric" placeholder="Kosongkan untuk 14.500" value="{{ old('buy_price_per_unit') }}" autocomplete="off">
                    </div>
                    <div class="hint">Kosongkan untuk memakai tarif default <strong>Rp 14.500</strong>. Isi bila ada kenaikan harga dari agen — nilai ini menjadi default berikutnya.</div>
                </div>
                @else
                <div class="field">
                    <label class="label">Harga dari agen</label>
                    <input type="text" class="input" value="{{ number_format(\App\Models\Product::DEFAULT_BUY_PRICE, 0, ',', '.') }} (otomatis)" disabled>
                    <div class="hint">Harga ditentukan pemilik. Penerimaan dicatat memakai harga agen terakhir produk ini.</div>
                </div>
                @endif
            </div>
            <div class="field">
                <label class="label">Uang admin pengiriman</label>
                <div class="input-affix">
                    <span class="affix">Rp</span>
                    <input type="text" name="admin_fee" id="adminFee" class="input num" inputmode="numeric" placeholder="0 (kosongkan jika tidak ada)" value="{{ old('admin_fee', 0) }}" autocomplete="off">
                </div>
                <div class="hint">Biaya admin pengataran dari agen (sopir).</div>
            </div>
            <div class="field">
                <label class="label">Catatan (Dikirim oleh sopir siapa)</label>
                <input type="text" name="notes" class="input" maxlength="255" placeholder="Opsional" value="{{ old('notes') }}">
            </div>
            <div class="modal-note">
                <strong>Stok diperbarui secara otomatis!</strong><br>
                tabung isi <span class="text-success fw">bertambah</span> dan tabung kosong <span class="text-danger fw">berkurang</span> sebanyak jumlah yang diterima.<br>
                <span class="muted">Stok tabung kosong produk harus minimal sama dengan jumlah diterima (tabung kosong ditukar ke agen).</span>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Catat penerimaan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="history" /> Riwayat penerimaan</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tanggal</th><th>Produk</th><th class="num">Jumlah</th><th class="num">Harga beli</th><th class="num">Total biaya</th><th class="num">Admin kirim</th><th>Pencatat</th><th></th></tr>
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
                        <td>
                            <div class="actions">
                                @if(auth()->user()->isOwner())
                                <form method="POST" action="{{ route('inventory.destroyDelivery', $delivery) }}" data-confirm="Hapus penerimaan {{ $delivery->product->name }} tanggal {{ $delivery->delivery_date->isoFormat('D MMM Y') }}? Stok akan dikembalikan dan penghapusan tercatat di riwayat penghapusan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus penerimaan" aria-label="Hapus penerimaan"><x-icon name="trash" size="15" /></button>
                                </form>
                                @else
                                <span class="spacer"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8">
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

@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[action="{{ route('inventory.storeDelivery') }}"]');
    if (!form) return;

    var qty = document.getElementById('qtyReceived');
    var price = document.getElementById('buyPrice');
    var adminFee = document.getElementById('adminFee');
    var productSelect = document.getElementById('deliveryProduct');

    // Saat produk dipilih, tampilkan harga agen terakhir produk itu sebagai
    // default di field harga (pemilik tetap bisa menggantinya).
    if (productSelect && price) {
        productSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            var last = opt ? opt.dataset.lastPrice : '';
            if (last) price.value = formatAmount(parseFloat(last) || 0);
            markInvalid(price, false);
        });
    }

    // Interpretasi angka: "25.000" / "25000" / "25.000,50" / "25000.50" -> nilai.
    // Kelompok 3 digit setelah titik/koma = pemisah ribuan; sisanya = desimal.
    // Contoh salah yang dicegah: "25000.50" tidak boleh berubah jadi 2500050.
    function parseAmount(raw) {
        var s = String(raw || '').replace(/\s+/g, '');
        if (s === '') return { ok: true, value: null };
        var m = s.match(/^(\d{1,3}(?:[.,]\d{3})+|\d+)([.,]\d{1,2})?$/);
        if (!m) return { ok: false };
        var intPart = m[1].replace(/[.,]/g, '');
        var dec = m[2] ? m[2].replace(',', '.') : '';
        return { ok: true, value: parseFloat(intPart + dec) };
    }
    function formatAmount(n) { return n.toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function markInvalid(el, invalid) { el.style.borderColor = invalid ? '#dc2626' : ''; }

    // Saat mengetik: buang hanya karakter terlarang — titik/koma dibiarkan agar desimal tidak rusak.
    function sanitize(el) {
        var s = el.value.replace(/[^\d.,]/g, '');
        if (s !== el.value) el.value = s;
    }

    if (qty) qty.addEventListener('input', function () { qty.value = qty.value.replace(/\D/g, ''); });

    [price, adminFee].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () {
            sanitize(el);
            markInvalid(el, !parseAmount(el.value).ok);
        });
        el.addEventListener('blur', function () {
            var r = parseAmount(el.value);
            if (r.ok) { el.value = r.value === null ? '' : formatAmount(r.value); markInvalid(el, false); }
            else { markInvalid(el, true); }
        });
        var awal = parseAmount(el.value);
        if (awal.ok && awal.value !== null) el.value = formatAmount(awal.value);
    });

    form.addEventListener('submit', function (e) {
        var masalah = [];
        [price, adminFee].forEach(function (el) {
            if (!el) return;
            // Harga boleh dikosongkan (server memakai default 14.500); hanya format salah yang ditolak.
            if (el.value.trim() === '') { markInvalid(el, false); return; }
            var r = parseAmount(el.value);
            if (!r.ok) {
                masalah.push('Format angka tidak valid pada "' + (el === price ? 'Harga dari agen' : 'Uang admin pengiriman') + '".');
                markInvalid(el, true);
            } else if (r.value !== null) {
                el.value = String(r.value); // kirim angka polos, tanpa pemisah ribuan
            }
        });
        if (qty) {
            var q = parseInt(qty.value, 10) || 0;
            if (q < 1) masalah.push('Jumlah diterima minimal 1 tabung.');
        }
        if (masalah.length) { e.preventDefault(); alert(masalah.join('\n')); }
    });
})();
</script>
@endpush
