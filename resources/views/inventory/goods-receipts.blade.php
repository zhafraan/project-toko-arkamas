@extends('layouts.app')
@section('title', 'Stok Masuk Telur')
@section('page-title', 'Stok Masuk Telur')
@section('page-sub', 'Catat penerimaan telur dari supplier')

@push('styles')
<style>
    .layout-side.side-left { grid-template-columns: 355px minmax(0, 1fr); }
    @media (max-width: 1100px) {
        .layout-side.side-left { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="layout-side side-left">

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="truck" /> Catat stok masuk baru</span></div>
        <form method="POST" action="{{ route('inventory.storeGoodsReceipt') }}" class="card-body">
            @csrf
            <div class="field">
                <label class="label">Produk <span class="req">*</span></label>
                <select name="product_id" id="receiptProduct" class="select" required>
                    <option value="">Pilih produk telur…</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-last-price="{{ $lastPrices[$p->id] ?? \App\Models\Product::DEFAULT_BUY_PRICE_TELUR }}"
                            {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Tanggal stok masuk <span class="req">*</span></label>
                <input type="date" name="receipt_date" class="input" min="{{ today()->toDateString() }}" value="{{ old('receipt_date', today()->toDateString()) }}" required>
            </div>
            <div class="form-grid">
                <div class="field {{ auth()->user()->isOwner() ? '' : 'span-all' }}">
                    <label class="label">Jumlah stok masuk <span class="req">*</span></label>
                    <input type="text" name="qty" id="qtyReceipt" class="input" inputmode="numeric" placeholder="Ketik jumlah telur" value="{{ old('qty') }}" autocomplete="off" required>
                    @unless(auth()->user()->isOwner())
                    <div class="hint">Masukkan jumlah telur yang diterima. Harga beli diatur otomatis.</div>
                    @endunless
                </div>
                @if(auth()->user()->isOwner())
                <div class="field">
                    <label class="label">Harga beli per unit <span class="req">*</span></label>
                    <div class="input-affix">
                        <span class="affix">Rp</span>
                        <input type="text" name="buy_price_per_unit" id="buyPrice" class="input num" inputmode="numeric" placeholder="Ketik harga beli" value="{{ old('buy_price_per_unit') }}" autocomplete="off" required>
                    </div>
                </div>
                @endif
            </div>
            @if(auth()->user()->isOwner())
            <div class="hint" style="margin-top:-8px;margin-bottom:14px;">Isi harga beli untuk mencatat biaya pembelian. Nilai ini menjadi default berikutnya.</div>
            @endif
            <div class="field">
                <label class="label">Supplier</label>
                <input type="text" name="supplier" class="input" maxlength="255" placeholder="Nama supplier (opsional)" value="{{ old('supplier') }}">
            </div>
            <div class="field">
                <label class="label">Catatan</label>
                <input type="text" name="notes" class="input" maxlength="255" placeholder="Opsional" value="{{ old('notes') }}">
            </div>
            <div class="modal-note">
                <strong>Stok diperbarui secara otomatis!</strong><br>
                Stok telur akan <span class="text-success fw">bertambah</span> sebanyak jumlah yang diterima.<br>
                <span class="muted">Harga beli otomatis diambil dari harga terakhir bila tidak diisi oleh pemilik.</span>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Catat stok masuk</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><span class="card-title"><x-icon name="history" /> Riwayat stok masuk</span></div>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Produk</th><th>Tanggal</th><th class="num">Qty</th><th class="num">Harga beli</th><th class="num">Total</th><th>Supplier</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($goodsReceipts as $receipt)
                    <tr>
                        <td class="nowrap" data-label="Produk">{{ $receipt->product->name }}</td>
                        <td data-label="Tanggal">{{ $receipt->receipt_date->isoFormat('D MMM Y') }}</td>
                        <td class="num" data-label="Qty"><span class="qty-pill qty-filled">{{ $receipt->qty }}</span> {{ $receipt->product->getUnitLabel() }}</td>
                        <td class="num" data-label="Harga beli">@rupiah($receipt->buy_price_per_unit)</td>
                        <td class="num fw" data-label="Total">@rupiah($receipt->total_cost)</td>
                        <td class="muted" data-label="Supplier">{{ $receipt->supplier ?: '-' }}</td>
                        <td data-label="Aksi">
                            <div class="actions">
                                @if(auth()->user()->isOwner())
                                <form method="POST" action="{{ route('inventory.destroyGoodsReceipt', $receipt) }}" data-confirm="Hapus stok masuk {{ $receipt->product->name }} tanggal {{ $receipt->receipt_date->isoFormat('D MMM Y') }}? Stok akan dikurangi dan penghapusan tercatat di riwayat penghapusan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus stok masuk" aria-label="Hapus stok masuk"><x-icon name="trash" size="15" /></button>
                                </form>
                                @else
                                <span class="spacer"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7">
                        <div class="empty">
                            <div class="empty-icon"><x-icon name="truck" size="24" /></div>
                            <div class="empty-title">Belum ada stok masuk tercatat</div>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $goodsReceipts->links('vendor.pagination.simple') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[action="{{ route('inventory.storeGoodsReceipt') }}"]');
    if (!form) return;

    var qty = document.getElementById('qtyReceipt');
    var price = document.getElementById('buyPrice');
    var productSelect = document.getElementById('receiptProduct');

    if (productSelect && price) {
        productSelect.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            var last = opt ? opt.dataset.lastPrice : '';
            if (last) price.value = formatAmount(parseFloat(last) || 0);
            markInvalid(price, false);
        });
    }

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

    if (qty) qty.addEventListener('input', function () { qty.value = qty.value.replace(/\D/g, ''); });

    if (price) {
        price.addEventListener('input', function () {
            var s = price.value.replace(/[^\d.,]/g, '');
            if (s !== price.value) price.value = s;
            markInvalid(price, !parseAmount(price.value).ok);
        });
        price.addEventListener('blur', function () {
            var r = parseAmount(price.value);
            if (r.ok) { price.value = r.value === null ? '' : formatAmount(r.value); markInvalid(price, false); }
            else { markInvalid(price, true); }
        });
        var awal = parseAmount(price.value);
        if (awal.ok && awal.value !== null) price.value = formatAmount(awal.value);
    }

    form.addEventListener('submit', function (e) {
        var masalah = [];
        if (price) {
            if (price.value.trim() === '') { markInvalid(price, false); }
            else {
                var r = parseAmount(price.value);
                if (!r.ok) {
                    masalah.push('Format angka tidak valid pada "Harga beli per unit".');
                    markInvalid(price, true);
                } else {
                    price.value = String(r.value);
                }
            }
        }
        if (qty) {
            var q = parseFloat(qty.value.replace(/\D/g, '.')) || 0;
            if (q < 0.1) masalah.push('Jumlah stok masuk minimal 0.1.');
        }
        if (masalah.length) { e.preventDefault(); alert(masalah.join('\n')); }
    });
})();
</script>
@endpush
