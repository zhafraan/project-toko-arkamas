@extends('layouts.app')
@section('title', 'Kasir')
@section('page-title', 'Kasir')
@section('page-sub', 'Pilih produk untuk menambahkannya ke keranjang')

@section('content')
<div class="pos-grid">

    {{-- ===== Produk ===== --}}
    <section>
        <div class="search" style="max-width:none;margin-bottom:14px;">
            <x-icon name="search" size="17" />
            <input type="search" id="productSearch" class="input" placeholder="Cari nama produk atau kode SKU" autocomplete="off">
        </div>

        <div class="product-grid" id="productGrid">
            @foreach($products as $product)
            <button type="button" class="product-card" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                    data-price="{{ (float) $product->price_sell }}" data-tube="{{ (float) $product->price_tube }}" data-stock="{{ $product->stock_filled }}"
                    @if($product->stock_filled <= 0) disabled @endif>
                <span class="in-cart" hidden></span>
                <span class="product-name">{{ $product->name }}</span>
                <span class="product-sku">SKU {{ $product->sku }}</span>
                <span class="product-price">@rupiah($product->price_sell)</span>
                <span class="product-stock">
                    @if($product->stock_filled <= 0)
                        <span class="badge badge-danger">Habis</span>
                    @elseif($product->stock_filled <= $lowStockLimit)
                        <span class="badge badge-warning">Sisa {{ $product->stock_filled }} tabung</span>
                    @else
                        <span class="badge badge-success">Stok {{ $product->stock_filled }} tabung</span>
                    @endif
                    <span class="muted">Tabung kosong: {{ $product->stock_empty }}</span>
                </span>
            </button>
            @endforeach
        </div>

        @if($products->count() === 0)
        <div class="card"><div class="empty">
            <div class="empty-icon"><x-icon name="stock" size="24" /></div>
            <div class="empty-title">Belum ada produk aktif</div>
            <div>Tambahkan produk di menu Produk &amp; Stok.</div>
        </div></div>
        @endif


    </section>

    {{-- ===== Keranjang & pembayaran ===== --}}
    <aside class="card cart">
        <div class="card-head">
            <span class="card-title"><x-icon name="cart" /> Keranjang <span class="badge badge-info" id="cartCount">0</span></span>
            <button type="button" class="btn btn-ghost btn-sm" id="btnClear"><x-icon name="trash" size="15" /> Hapus semua</button>
        </div>

        <div class="cart-items" id="cartItems"></div>

        <div class="cart-pay">
            <div class="field">
                <label class="label" for="custInput">Pelanggan <span class="req" id="custReq" hidden>(wajib untuk pinjaman tabung atau piutang)</span></label>
                <div class="combo" id="custCombo">
                    <div class="row" style="gap:8px;">
                        <div class="grow">
                            <input type="text" id="custInput" class="input" placeholder="Pelanggan umum. Cari nama atau nomor telepon" autocomplete="off">
                            <div class="chip" id="custChip" hidden>
                                <span class="grow" id="custChipName"></span>
                                <button type="button" class="btn btn-ghost btn-icon btn-xs" id="custClear" title="Ganti pelanggan"><x-icon name="x" size="15" /></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline btn-icon" data-modal-open="addCustomerModal" title="Tambah pelanggan baru" aria-label="Tambah pelanggan baru"><x-icon name="user-plus" /></button>
                    </div>
                    <div class="combo-list" id="custList" hidden></div>
                </div>
                <div class="hint text-danger" id="custDebt" hidden></div>
            </div>

            <div class="pay-row">
                <div>
                    <span class="label">Metode</span>
                    <div class="seg" id="segMethod">
                        <button type="button" data-v="cash" aria-pressed="true">Tunai</button>
                        <button type="button" data-v="transfer" aria-pressed="false">Transfer</button>
                    </div>
                </div>
                <div>
                    <span class="label">Status</span>
                    <div class="seg" id="segStatus">
                        <button type="button" data-v="lunas" aria-pressed="true">Lunas</button>
                        <button type="button" data-v="belum_lunas" aria-pressed="false">Piutang</button>
                    </div>
                </div>
            </div>

            <div class="field" id="noteField" hidden>
                <label class="label" for="payNote">Nomor referensi transfer atau QRIS</label>
                <input type="text" id="payNote" class="input" placeholder="Contoh: BCA 12345" maxlength="255">
            </div>

            <div class="field">
                <label class="label" for="paidInput" id="paidLabel">Uang diterima</label>
                <div class="input-affix">
                    <span class="affix">Rp</span>
                    <input type="text" id="paidInput" class="input num" inputmode="numeric" placeholder="Kosongkan jika uang pas" autocomplete="off">
                </div>
                <div class="quick-cash" id="quickCash">
                    <button type="button" class="btn btn-outline btn-xs" data-cash="exact">Uang pas</button>
                    <button type="button" class="btn btn-outline btn-xs" data-cash="50000">50 rb</button>
                    <button type="button" class="btn btn-outline btn-xs" data-cash="100000">100 rb</button>
                    <button type="button" class="btn btn-outline btn-xs" data-cash="200000">200 rb</button>
                </div>
            </div>

            <div class="pay-line"><span id="resultLabel">Kembalian</span><strong class="num" id="resultValue">Rp 0</strong></div>
            <div class="pay-total"><span class="fw">Total</span><strong id="grandTotal">Rp 0</strong></div>
            <div class="field-error" id="payError" hidden style="margin:-4px 0 10px;"></div>

            <button type="button" id="btnCheckout" class="btn btn-accent btn-lg btn-block" disabled>
                <x-icon name="check" /> <span id="btnCheckoutText">Selesaikan Transaksi</span>
            </button>
        </div>
    </aside>
</div>

{{-- Modal: transaksi berhasil --}}
<div class="modal-overlay" id="successModal">
    <div class="modal sm">
        <div class="modal-body center">
            <div class="empty-icon" style="background:var(--success-soft);color:var(--success-ink);width:60px;height:60px;"><x-icon name="check" size="28" /></div>
            <h2 style="font-size:19px;font-weight:800;">Transaksi berhasil</h2>
            <p class="muted" id="successInvoice" style="margin-bottom:16px;"></p>
            <div class="modal-note" style="text-align:left;">
                <div class="pay-line"><span>Total</span><strong class="num" id="successTotal"></strong></div>
                <div class="pay-line"><span>Dibayar</span><strong class="num" id="successPaid"></strong></div>
                <div class="pay-line" id="successChangeRow"><span>Kembalian</span><strong class="num text-success" id="successChange"></strong></div>
                <div class="pay-line" id="successDebtRow" hidden><span>Sisa piutang</span><strong class="num text-danger" id="successDebt"></strong></div>
            </div>
            <div class="grid cols-2" style="gap:10px;">
                <a href="#" id="btnReceipt" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="printer" /> Cetak nota</a>
                <button type="button" class="btn btn-primary" data-modal-close><x-icon name="plus" /> Transaksi baru</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: pelanggan baru --}}
<div class="modal-overlay" id="addCustomerModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Pelanggan baru</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label" for="ncName">Nama <span class="req">*</span></label>
                    <input type="text" id="ncName" class="input" maxlength="255">
                </div>
                <div class="field">
                    <label class="label" for="ncPhone">Nomor telepon</label>
                    <input type="text" id="ncPhone" class="input" placeholder="08xxxxxxxxxx" maxlength="20">
                </div>
                <div class="field">
                    <label class="label" for="ncType">Tipe pelanggan</label>
                    <select id="ncType" class="select">
                        <option value="rumah_tangga">Rumah tangga</option>
                        <option value="umkm">UMKM</option>
                        <option value="pengecer">Pengecer</option>
                    </select>
                </div>
                <div class="field span-all">
                    <label class="label" for="ncNik">NIK (opsional)</label>
                    <input type="text" id="ncNik" class="input" maxlength="16" inputmode="numeric">
                </div>
            </div>
            <div class="field-error" id="ncError" hidden style="margin-bottom:12px;"></div>
            <button type="button" class="btn btn-primary btn-block" id="btnSaveCustomer"><x-icon name="check" /> Simpan dan pilih</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var URLS = {
        search: '{{ route("customers.search") }}',
        store: '{{ route("pos.store") }}',
        newCustomer: '{{ route("pos.storeCustomer") }}'
    };
    var LOW = {{ $lowStockLimit }};
    var STORE_KEY = 'arkamas_pos_cart_v3';
    var TYPES = { refill: 'Isi ulang (tukar tabung)', pinjam_tabung: 'Pinjam tabung', plus_tabung: 'Beli tabung dan isi' };

    var $ = function (id) { return document.getElementById(id); };
    var ICON = {
        plus: '<path d="M5 12h14"/><path d="M12 5v14"/>',
        minus: '<path d="M5 12h14"/>',
        trash: '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>'
    };
    function svg(name) {
        return '<svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICON[name] + '</svg>';
    }

    // Interpretasi angka aman: "25.000" = 25000, "25000.50" = 25000.5,
    // "1.23.4" = tidak valid. Mencegah nilai jadi 100× salah karena titik dibuang.
    function parseAmount(raw) {
        var s = String(raw || '').replace(/\s+/g, '');
        if (s === '') return { ok: true, value: null };
        var m = s.match(/^(\d{1,3}(?:[.,]\d{3})+|\d+)([.,]\d{1,2})?$/);
        if (!m) return { ok: false };
        var dec = m[2] ? m[2].replace(',', '.') : '';
        return { ok: true, value: parseFloat(m[1].replace(/[.,]/g, '') + dec) };
    }

    /* ---------- State ---------- */
    var products = {};              // id -> {id,name,sku,price,tube,stock}
    var cart = [];                  // {id,type,qty}
    var customer = null;            // {id,name,phone,debt}
    var method = 'cash', status = 'lunas';

    document.querySelectorAll('.product-card').forEach(function (el) {
        products[el.dataset.id] = {
            id: el.dataset.id, name: el.dataset.name, sku: el.dataset.sku,
            price: parseFloat(el.dataset.price) || 0, tube: parseFloat(el.dataset.tube) || 0,
            stock: parseInt(el.dataset.stock, 10) || 0, el: el
        };
    });

    function unitPrice(line) { var p = products[line.id]; return p.price + (line.type === 'plus_tabung' ? p.tube : 0); }
    function grandTotal() { return cart.reduce(function (s, l) { return s + unitPrice(l) * l.qty; }, 0); }
    function qtyOfProduct(id) { return cart.reduce(function (s, l) { return l.id === id ? s + l.qty : s; }, 0); }
    function needCustomer() { return status === 'belum_lunas' || cart.some(function (l) { return l.type === 'pinjam_tabung'; }); }

    // Kunci keamanan UI: total qty per produk di keranjang tidak boleh melebihi
    // stok — tombol "Selesaikan Transaksi" dinonaktifkan sampai jumlah dikurangi.
    // (Server tetap memeriksa ulang dengan kunci baris; ini mencegah percobaan sia-sia.)
    function stockShortage() {
        var found = null;
        Object.keys(products).forEach(function (id) {
            if (found) return;
            var p = products[id], n = qtyOfProduct(id);
            if (n > p.stock) found = { p: p, n: n };
        });
        return found;
    }
    function stockMsg(s) {
        return 'Stok ' + s.p.name + ' tidak cukup — di keranjang ' + s.n + ', tersisa ' + s.p.stock + '. Kurangi jumlah sebelum menyelesaikan transaksi.';
    }

    function save() {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(cart)); } catch (e) {}
    }
    function restore() {
        try {
            var saved = JSON.parse(localStorage.getItem(STORE_KEY) || '[]');
            if (!Array.isArray(saved)) return;
            saved.forEach(function (l) {
                var p = products[l.id];
                if (!p || p.stock <= 0 || !TYPES[l.type]) return;
                var room = p.stock - qtyOfProduct(l.id);
                var qty = Math.min(parseInt(l.qty, 10) || 1, room);
                if (qty > 0) cart.push({ id: l.id, type: l.type, qty: qty });
            });
        } catch (e) {}
    }

    /* ---------- Keranjang ---------- */
    function addProduct(id) {
        var p = products[id];
        if (!p || p.stock <= 0) return;
        if (qtyOfProduct(id) >= p.stock) { toast('Stok ' + p.name + ' hanya tersisa ' + p.stock, 'error'); return; }
        var line = cart.find(function (l) { return l.id === id && l.type === 'refill'; });
        if (line) {
            line.qty += 1;
        } else {
            cart.push({ id: id, type: 'refill', qty: 1 });
        }
        render();
    }
    function changeQty(idx, delta) {
        var l = cart[idx], p = products[l.id];
        if (delta > 0 && qtyOfProduct(l.id) >= p.stock) { toast('Stok ' + p.name + ' hanya tersisa ' + p.stock, 'error'); return; }
        l.qty += delta;
        if (l.qty <= 0) removeLine(idx); else render();
    }
    function changeType(idx, type) {
        var l = cart[idx];
        var same = cart.find(function (o, i) { return i !== idx && o.id === l.id && o.type === type; });
        if (same) { same.qty += l.qty; cart.splice(idx, 1); } else { l.type = type; }
        render();
    }
    function removeLine(idx) { cart.splice(idx, 1); render(); }

    function render() {
        var box = $('cartItems');
        if (cart.length === 0) {
            box.innerHTML = '<div class="cart-empty"><div class="empty-icon" style="width:44px;height:44px;margin-bottom:8px;">' +
                '<svg class="icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg></div>' +
                'Keranjang kosong.<br>Pilih produk untuk memulai transaksi.</div>';
        } else {
            box.innerHTML = cart.map(function (l, i) {
                var p = products[l.id];
                var room = Math.max(1, p.stock - (qtyOfProduct(l.id) - l.qty));
                var opts = Object.keys(TYPES).map(function (t) {
                    var label = TYPES[t] + (t === 'plus_tabung' && p.tube > 0 ? ' (+' + rupiah(p.tube) + ')' : '');
                    return '<option value="' + t + '"' + (t === l.type ? ' selected' : '') + '>' + esc(label) + '</option>';
                }).join('');
                return '<div class="cart-item">' +
                    '<div style="min-width:0;"><div class="cart-item-name">' + esc(p.name) + '</div>' +
                    '<select class="select select-sm" data-act="type" data-idx="' + i + '" style="margin-top:6px;" aria-label="Jenis transaksi">' + opts + '</select></div>' +
                    '<div style="text-align:right;"><div class="cart-item-price">' + rupiah(unitPrice(l)) + '</div>' +
                    '<div class="stepper" style="margin-top:8px;">' +
                    '<button type="button" class="btn btn-outline btn-xs btn-icon" data-act="dec" data-idx="' + i + '" aria-label="Kurangi">' + svg('minus') + '</button>' +
                    '<input type="number" class="qty-input" data-act="qty" data-idx="' + i + '" value="' + l.qty + '" min="1" max="' + room + '" aria-label="Jumlah">' +
                    '<button type="button" class="btn btn-outline btn-xs btn-icon" data-act="inc" data-idx="' + i + '" aria-label="Tambah">' + svg('plus') + '</button>' +
                    '<button type="button" class="btn btn-ghost btn-xs btn-icon" data-act="del" data-idx="' + i + '" title="Hapus" aria-label="Hapus">' + svg('trash') + '</button>' +
                    '</div></div></div>';
            }).join('');
        }

        // lencana jumlah di kartu produk
        Object.keys(products).forEach(function (id) {
            var badge = products[id].el.querySelector('.in-cart'), n = qtyOfProduct(id);
            badge.hidden = n === 0; badge.textContent = n;
        });
        $('cartCount').textContent = cart.reduce(function (s, l) { return s + l.qty; }, 0);
        $('grandTotal').textContent = rupiah(grandTotal());
        $('custReq').hidden = !needCustomer();
        save();
        recalc();
    }

    /* ---------- Pembayaran ---------- */
    function paidValue() {
        var d = $('paidInput').value.replace(/\D/g, '');
        return d === '' ? null : parseInt(d, 10);
    }
    function recalc() {
        var total = grandTotal(), paid = paidValue(), ok = cart.length > 0;
        var label = $('resultLabel'), value = $('resultValue');
        value.className = 'num'; $('payError').hidden = true;

        if (status === 'lunas') {
            $('paidLabel').textContent = 'Uang diterima';
            var eff = paid === null ? total : paid;
            if (eff >= total) { label.textContent = 'Kembalian'; value.textContent = rupiah(eff - total); }
            else { label.textContent = 'Kurang'; value.textContent = rupiah(total - eff); value.classList.add('text-danger'); ok = false; }
        } else {
            $('paidLabel').textContent = 'Uang muka (DP)';
            label.textContent = 'Sisa piutang';
            value.textContent = rupiah(Math.max(0, total - (paid || 0)));
            if (total > 0) value.classList.add('text-danger');
        }

        // Stok kurang dari isi keranjang -> transaksi tidak bisa diselesaikan
        var short = stockShortage();
        if (short) {
            $('payError').textContent = stockMsg(short);
            $('payError').hidden = false;
            ok = false;
        }
        $('btnCheckout').disabled = !ok;
    }
    function setSeg(box, value, cb) {
        box.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.v === value ? 'true' : 'false'); });
        cb(value);
    }
    $('segMethod').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        setSeg(this, b.dataset.v, function (v) { method = v; $('noteField').hidden = v !== 'transfer'; });
    });
    $('segStatus').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        setSeg(this, b.dataset.v, function (v) { status = v; });
        $('custReq').hidden = !needCustomer();
        recalc();
    });
    $('paidInput').addEventListener('input', function () {
        var s = this.value.replace(/[^\d.,]/g, '');
        var r = parseAmount(s);
        // Angka ribuan yang valid dipakai apa adanya; input aneh (mis. "1.23.4")
        // tidak boleh dikalikan — buang pemisahnya dan pakai angka murninya.
        var n = r.ok && r.value !== null ? Math.floor(r.value) : parseInt(s.replace(/\D/g, ''), 10);
        this.value = isNaN(n) || n < 0 ? '' : n.toLocaleString('id-ID');
        recalc();
    });
    $('quickCash').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var v = b.dataset.cash === 'exact' ? grandTotal() : parseInt(b.dataset.cash, 10);
        $('paidInput').value = v ? v.toLocaleString('id-ID') : '';
        recalc();
    });

    /* ---------- Event produk & keranjang ---------- */
    $('productGrid').addEventListener('click', function (e) {
        var card = e.target.closest('.product-card');
        if (card && !card.disabled) addProduct(card.dataset.id);
    });
    $('cartItems').addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]'); if (!b || b.tagName === 'SELECT' || b.tagName === 'INPUT') return;
        var i = parseInt(b.dataset.idx, 10);
        if (b.dataset.act === 'inc') changeQty(i, 1);
        if (b.dataset.act === 'dec') changeQty(i, -1);
        if (b.dataset.act === 'del') { if (confirm('Hapus item ini dari keranjang?')) removeLine(i); }
    });
    $('cartItems').addEventListener('change', function (e) {
        if (e.target.dataset.act === 'type') changeType(parseInt(e.target.dataset.idx, 10), e.target.value);
        if (e.target.dataset.act === 'qty') {
            var i = parseInt(e.target.dataset.idx, 10);
            var l = cart[i], p = products[l.id];
            var val = parseInt(e.target.value, 10) || 1;
            var max = p.stock - (qtyOfProduct(l.id) - l.qty);
            if (val > max) { toast('Stok ' + p.name + ' hanya tersisa ' + p.stock, 'error'); val = max; }
            l.qty = Math.max(1, val);
            render();
        }
    });
    $('btnClear').addEventListener('click', function () {
        if (cart.length && confirm('Hapus semua item dari keranjang?')) { cart = []; render(); }
    });
    $('productSearch').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase(), shown = 0;
        Object.keys(products).forEach(function (id) {
            var p = products[id], hit = !q || p.name.toLowerCase().indexOf(q) > -1 || p.sku.toLowerCase().indexOf(q) > -1;
            p.el.hidden = !hit; if (hit) shown++;
        });
        $('noProductMatch').hidden = shown > 0;
    });

    /* ---------- Pelanggan (combobox) ---------- */
    var timer = null, results = [], focusIdx = -1;
    function showCustomer(c) {
        customer = c;
        $('custInput').hidden = !!c; $('custChip').hidden = !c;
        if (c) {
            $('custChipName').textContent = c.name + (c.phone ? ' · ' + c.phone : '');
            $('custList').hidden = true;
            $('custDebt').hidden = !(c.debt > 0);
            $('custDebt').textContent = c.debt > 0 ? 'Pelanggan ini masih memiliki piutang sebesar ' + rupiah(c.debt) + '.' : '';
        } else {
            $('custInput').value = ''; $('custDebt').hidden = true;
        }
        $('payError').hidden = true;
    }
    function drawList() {
        var list = $('custList');
        if (!results.length) { list.innerHTML = '<div class="combo-empty">Pelanggan tidak ditemukan. Periksa nama atau nomor telepon.</div>'; list.hidden = false; return; }
        list.innerHTML = results.map(function (c, i) {
            return '<button type="button" class="combo-option' + (i === focusIdx ? ' focus' : '') + '" data-i="' + i + '">' +
                '<strong>' + esc(c.name) + '</strong>' + (c.phone ? ' <span class="muted">· ' + esc(c.phone) + '</span>' : '') +
                (c.debt > 0 ? ' <span class="text-danger text-sm">· piutang ' + rupiah(c.debt) + '</span>' : '') + '</button>';
        }).join('');
        list.hidden = false;
    }
    function search(q) {
        fetch(URLS.search + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) { results = rows; focusIdx = rows.length ? 0 : -1; drawList(); })
            .catch(function () { $('custList').hidden = true; });
    }
    $('custInput').addEventListener('focus', function () { search(this.value.trim()); });
    $('custInput').addEventListener('input', function () {
        var q = this.value.trim(); clearTimeout(timer);
        timer = setTimeout(function () { search(q); }, 200);
    });
    $('custInput').addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!results.length) return;
            focusIdx = (focusIdx + (e.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length; drawList();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (results[focusIdx]) showCustomer(results[focusIdx]);
        } else if (e.key === 'Escape') { $('custList').hidden = true; }
    });
    $('custList').addEventListener('click', function (e) {
        var b = e.target.closest('.combo-option'); if (b) showCustomer(results[parseInt(b.dataset.i, 10)]);
    });
    $('custClear').addEventListener('click', function () { showCustomer(null); $('custInput').focus(); });
    document.addEventListener('click', function (e) { if (!e.target.closest('#custCombo')) $('custList').hidden = true; });

    /* ---------- Pelanggan baru ---------- */
    $('btnSaveCustomer').addEventListener('click', function () {
        var btn = this, err = $('ncError'), name = $('ncName').value.trim();
        err.hidden = true;
        if (!name) { err.textContent = 'Nama pelanggan wajib diisi.'; err.hidden = false; return; }
        btn.disabled = true;
        postJSON(URLS.newCustomer, {
            name: name, phone: $('ncPhone').value.trim(), nik: $('ncNik').value.trim(), customer_type: $('ncType').value
        }).then(function (res) {
            showCustomer(res.customer);
            ['ncName', 'ncPhone', 'ncNik'].forEach(function (id) { $(id).value = ''; });
            closeModal('addCustomerModal');
        }).catch(function (e) { err.textContent = errorText(e); err.hidden = false; })
          .then(function () { btn.disabled = false; });
    });

    /* ---------- Checkout ---------- */
    $('btnCheckout').addEventListener('click', function () {
        var btn = this, txt = $('btnCheckoutText'), err = $('payError');
        if (!cart.length) return;
        if (needCustomer() && !customer) {
            err.textContent = 'Pilih pelanggan terlebih dahulu. Pelanggan wajib diisi untuk pinjaman tabung atau piutang.'; err.hidden = false;
            $('custInput').focus(); return;
        }
        var short = stockShortage();
        if (short) {
            err.textContent = stockMsg(short); err.hidden = false;
            toast('Stok ' + short.p.name + ' tidak cukup', 'error');
            return;
        }
        var label = txt.textContent;
        btn.disabled = true; txt.textContent = 'Memproses…';

        postJSON(URLS.store, {
            items: cart.map(function (l) { return { product_id: l.id, transaction_type: l.type, qty: l.qty }; }),
            customer_id: customer ? customer.id : null,
            payment_method: method, payment_status: status,
            paid_amount: paidValue(),
            payment_note: method === 'transfer' ? $('payNote').value.trim() : null
        }).then(function (res) {
            $('successInvoice').textContent = res.invoice_number;
            $('successTotal').textContent = rupiah(res.total_amount);
            $('successPaid').textContent = rupiah(res.paid_amount);
            $('successChange').textContent = rupiah(res.change_amount);
            $('successChangeRow').hidden = !(res.change_amount > 0);
            $('successDebt').textContent = rupiah(res.remaining_debt);
            $('successDebtRow').hidden = !(res.remaining_debt > 0);
            $('btnReceipt').href = res.receipt_url;
            cart = []; save();
            openModal('successModal');
        }).catch(function (e) {
            err.textContent = errorText(e); err.hidden = false;
            toast(errorText(e).split('\n')[0], 'error');
            btn.disabled = false;
        }).then(function () { txt.textContent = label; });
    });

    // Setelah modal sukses ditutup (tombol / Esc / klik luar), muat ulang agar stok di kartu produk akurat.
    new MutationObserver(function () {
        if (!$('successModal').classList.contains('active')) location.reload();
    }).observe($('successModal'), { attributes: true, attributeFilter: ['class'] });

    restore();
    render();
})();
</script>
@endpush
