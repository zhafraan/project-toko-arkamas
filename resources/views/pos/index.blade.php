@extends('layouts.app')
@section('title', 'Kasir / POS')
@section('page-title', 'Kasir / Point of Sale')

@push('styles')
<style>
    .pos-grid { display: grid; grid-template-columns: 1fr 380px; gap: 20px; align-items: start; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
    .product-card {
        background: #fff; border: 1.5px solid var(--border);
        border-radius: 14px; padding: 18px; cursor: pointer;
        transition: all .2s; user-select: none;
        box-shadow: 0 4px 14px rgba(34, 58, 94, 0.04);
    }
    .product-card:hover {
        border-color: var(--holst-3);
        transform: translateY(-3px);
        box-shadow: 0 10px 28px rgba(34, 58, 94, 0.12);
    }
    .product-card.selected { border-color: var(--holst-2); background: #eef5fc; }
    .product-name { font-size: 14.5px; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
    .product-price { font-size: 16px; font-weight: 800; color: var(--holst-2); }
    .product-stock { font-size: 11.5px; margin-top: 8px; color: var(--text-muted); }
    .cart-section {
        background: #fff; border-radius: 14px;
        box-shadow: var(--shadow); position: sticky; top: 80px;
        border: 1px solid var(--border); overflow: hidden;
    }
    .cart-header {
        padding: 16px 18px;
        border-bottom: 1px solid var(--border);
        background: var(--grad-card-header);
    }
    .cart-body { padding: 14px 18px; max-height: 400px; overflow-y: auto; }
    .cart-item { display: flex; align-items: start; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .cart-item:last-child { border-bottom: none; }
    .cart-item-name { font-size: 13.5px; font-weight: 600; color: var(--primary); }
    .cart-item-type { font-size: 11px; color: var(--text-muted); }
    .cart-item-price { font-size: 13.5px; font-weight: 700; color: var(--holst-2); }
    .cart-footer { padding: 16px 18px; border-top: 1px solid var(--border); background: #F3F8FD; border-radius: 0 0 14px 14px; }
    .qty-control { display: flex; align-items: center; gap: 6px; }
    .qty-btn {
        width: 26px; height: 26px; border-radius: 6px; border: 1.5px solid var(--border);
        background: #fff; cursor: pointer; font-weight: 700; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary);
        transition: all .15s;
    }
    .qty-btn:hover { background: var(--holst-2); color: #fff; border-color: var(--holst-2); }
    .qty-value { width: 32px; text-align: center; font-weight: 700; font-size: 14px; color: var(--primary); }
    .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .total-label { font-size: 13px; color: var(--text-muted); }
    .total-value { font-size: 13px; font-weight: 600; }
    .grand-total { font-size: 21px; font-weight: 800; color: var(--primary); }
    .change-display {
        background: #e1ecf7; color: var(--holst-1);
        padding: 9px 12px; border-radius: 8px;
        font-weight: 700; font-size: 14px;
        border: 1px solid var(--holst-5);
    }
    .type-select {
        width: 100%; padding: 6px 8px; border: 1.5px solid var(--border);
        border-radius: 7px; font-size: 12px; background: #fff;
        color: var(--primary); font-family: 'Inter', sans-serif;
    }
</style>
@endpush

@section('content')
<div class="pos-grid">

    {{-- Products Section --}}
    <div>
        {{-- Search & Filter --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:14px;">
                <input type="text" id="productSearch" class="form-control" placeholder="🔍 Cari produk gas...">
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="product-grid" id="productGrid">
            @foreach($products as $product)
            <div class="product-card" data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                 data-price-sell="{{ $product->price_sell }}" data-price-tube="{{ $product->price_tube }}"
                 data-cost="{{ $product->price_buy }}" data-stock="{{ $product->stock_filled }}"
                 onclick="addToCart(this)">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px;">
                    <div class="product-name">{{ $product->name }}</div>
                    <span style="font-size:10px;background:var(--success);color:#fff;padding:2px 7px;border-radius:20px;">
                        {{ $product->stock_filled }}
                    </span>
                </div>
                <div class="product-price">Rp {{ number_format($product->price_sell, 0, ',', '.') }}</div>
                <div class="product-stock">
                    <span style="color:var(--text-muted);">Kosong: {{ $product->stock_empty }}</span>
                </div>
                <div style="margin-top:8px;font-size:11px;color:var(--text-muted);">SKU: {{ $product->sku }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Cart Section --}}
    <div class="cart-section">
        {{-- Cart Header --}}
        <div class="cart-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:15px;font-weight:700;color:var(--primary);">
                <i class="fa-solid fa-shopping-cart" style="color:var(--accent);margin-right:6px;"></i>
                Keranjang
            </div>
            <button onclick="clearCart()" class="btn btn-outline btn-xs" style="color:var(--danger);border-color:var(--danger);">
                <i class="fa-solid fa-trash"></i> Kosongkan
            </button>
        </div>

        {{-- Cart Items --}}
        <div class="cart-body" id="cartBody">
            <div id="emptyCart" style="text-align:center;padding:30px;color:var(--text-muted);">
                <i class="fa-solid fa-fire" style="font-size:28px;opacity:.2;display:block;margin-bottom:8px;"></i>
                <div style="font-size:13px;">Pilih produk untuk menambahkan ke keranjang</div>
            </div>
        </div>

        {{-- Cart Footer / Payment --}}
        <div class="cart-footer">
            {{-- Customer --}}
            <div class="form-group">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                    <label class="form-label" style="margin-bottom:0;">Pelanggan <span id="customerRequired" style="color:var(--danger);display:none;">*Wajib</span></label>
                </div>
                <div style="display:flex;gap:6px;margin-bottom:6px;">
                    <input type="text" id="customerSearchInput" class="form-control" style="font-size:12px;padding:6px 10px;"
                           placeholder="Ketik nama / no. HP pelanggan..." oninput="searchCustomersAjax(this.value)">
                    <button type="button" onclick="openAddCustomer()" class="btn btn-outline btn-icon" title="Tambah Pelanggan Baru">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <select name="customer_id" id="customerSelect" class="form-control" form="posForm">
                    <option value="">-- Umum / Tanpa Nama --</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }} {{ $customer->phone ? '('.$customer->phone.')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Method --}}
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="payment_method" class="form-control" form="posForm" id="paymentMethod" onchange="updatePaymentUI()">
                    <option value="cash">💵 Cash / Tunai</option>
                    <option value="transfer">📱 Transfer / QRIS</option>
                </select>
            </div>

            {{-- Payment Note (for transfer) --}}
            <div class="form-group" id="paymentNoteGroup" style="display:none;">
                <label class="form-label">No. Referensi / Bank</label>
                <input type="text" name="payment_note" class="form-control" form="posForm"
                       placeholder="Contoh: BCA #12345">
            </div>

            {{-- Status --}}
            <div class="form-group">
                <label class="form-label">Status Bayar</label>
                <select name="payment_status" class="form-control" form="posForm" id="paymentStatus" onchange="updatePaymentUI()">
                    <option value="lunas">✅ Lunas</option>
                    <option value="belum_lunas">🔴 Belum Lunas (Kasbon)</option>
                </select>
            </div>

            {{-- Amount Paid with Quick Cash Presets --}}
            <div class="form-group" id="paidAmountGroup">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <label class="form-label">Uang Diterima (Rp)</label>
                    <span style="font-size:10px;color:var(--text-muted);">Pecahan Cepat:</span>
                </div>
                <input type="number" name="paid_amount" id="paidAmount" class="form-control" form="posForm"
                       placeholder="0" min="0" oninput="calcChange()">

                {{-- Preset Buttons --}}
                <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:6px;" id="quickCashButtons">
                    <button type="button" class="btn btn-outline btn-xs" onclick="setExactCash()">Uang Pas</button>
                    <button type="button" class="btn btn-outline btn-xs" onclick="addCash(20000)">+20k</button>
                    <button type="button" class="btn btn-outline btn-xs" onclick="addCash(50000)">+50k</button>
                    <button type="button" class="btn btn-outline btn-xs" onclick="addCash(100000)">+100k</button>
                    <button type="button" class="btn btn-outline btn-xs" onclick="setCash(100000)">100k</button>
                    <button type="button" class="btn btn-outline btn-xs" onclick="setCash(200000)">200k</button>
                </div>
            </div>

            {{-- Change --}}
            <div class="form-group" id="changeGroup">
                <label class="form-label">Kembalian</label>
                <div class="change-display" id="changeDisplay">Rp 0</div>
            </div>

            {{-- Totals --}}
            <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:8px;margin-bottom:12px;">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value" id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="total-row">
                    <span style="font-size:16px;font-weight:700;">TOTAL</span>
                    <span class="grand-total" id="grandTotalDisplay">Rp 0</span>
                </div>
            </div>

            <form id="posForm" method="POST" action="{{ route('pos.store') }}" onsubmit="handlePosCheckout(event)">
                @csrf
                <div id="cartInputs"></div>
                <button type="submit" id="btnCheckout" class="btn btn-accent" style="width:100%;justify-content:center;padding:13px;font-size:15px;" disabled>
                    <span id="btnCheckoutText"><i class="fa-solid fa-check-circle"></i> Proses Transaksi</span>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Sukses Transaksi --}}
<div class="modal-overlay" id="checkoutSuccessModal">
    <div class="modal" style="text-align:center;max-width:440px;">
        <div style="width:64px;height:64px;background:#d1fae5;color:#059669;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 16px;">
            <i class="fa-solid fa-check"></i>
        </div>
        <h3 style="font-size:18px;font-weight:800;color:var(--text);margin-bottom:6px;">Transaksi Berhasil!</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;" id="successInvoiceText">Invoice #TRX-20261015-0000</p>

        <div style="background:var(--bg);padding:14px;border-radius:10px;text-align:left;font-size:13px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:var(--text-muted);">Total Tagihan:</span>
                <strong id="successTotal">Rp 0</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:var(--text-muted);">Jumlah Dibayar:</span>
                <strong id="successPaid">Rp 0</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:var(--text-muted);">Kembalian:</span>
                <strong style="color:var(--success);" id="successChange">Rp 0</strong>
            </div>
            <div style="display:flex;justify-content:space-between;" id="successDebtRow">
                <span style="color:var(--danger);">Sisa Kasbon:</span>
                <strong style="color:var(--danger);" id="successDebt">Rp 0</strong>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <a href="#" id="btnReceiptLink" target="_blank" class="btn btn-primary" style="justify-content:center;padding:11px;">
                <i class="fa-solid fa-print"></i> Cetak Struk
            </a>
            <button onclick="closeSuccessModal()" class="btn btn-accent" style="justify-content:center;padding:11px;">
                <i class="fa-solid fa-plus-circle"></i> Transaksi Baru
            </button>
        </div>
    </div>
</div>

{{-- Modal: Tambah Pelanggan Cepat --}}
<div class="modal-overlay" id="addCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Tambah Pelanggan Baru</div>
            <button class="modal-close" onclick="closeAddCustomer()"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" id="newCustName" class="form-control" placeholder="Nama pelanggan">
            </div>
            <div class="form-group">
                <label class="form-label">No. HP</label>
                <input type="text" id="newCustPhone" class="form-control" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label class="form-label">NIK (opsional)</label>
                <input type="text" id="newCustNik" class="form-control" placeholder="16 digit NIK">
            </div>
            <div class="form-group">
                <label class="form-label">Tipe Pelanggan</label>
                <select id="newCustType" class="form-control">
                    <option value="rumah_tangga">Rumah Tangga</option>
                    <option value="umkm">UMKM</option>
                    <option value="pengecer">Pengecer</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Alamat (opsional)</label>
            <textarea id="newCustAddress" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
        </div>
        <button onclick="saveNewCustomer()" id="btnSaveCust" class="btn btn-primary" style="width:100%;justify-content:center;">
            <i class="fa-solid fa-save"></i> Simpan Pelanggan
        </button>
    </div>
</div>

@push('scripts')
<script>
// ==================== KERANJANG BELANJA (DENGAN LOCALSTORAGE / COOKIE BACKUP) ====================
let cart = [];

function saveCartToStorage() {
    try {
        localStorage.setItem('simlpg_pos_cart', JSON.stringify(cart));
    } catch(e){}
}

function loadCartFromStorage() {
    try {
        const saved = localStorage.getItem('simlpg_pos_cart');
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed) && parsed.length > 0) {
                cart = parsed;
                renderCart();
            }
        }
    } catch(e){}
}

function addToCart(el) {
    const id    = el.dataset.id;
    const name  = el.dataset.name;
    const price = parseFloat(el.dataset.priceSell);
    const tube  = parseFloat(el.dataset.priceTube);
    const cost  = parseFloat(el.dataset.cost);
    const stock = parseInt(el.dataset.stock);

    if (stock <= 0) { alert('Stok gas isi sedang habis!'); return; }

    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, tube, cost, qty: 1, type: 'refill', deposit: 0 });
    }
    renderCart();
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function updateQty(idx, delta) {
    cart[idx].qty = Math.max(1, cart[idx].qty + delta);
    renderCart();
}

function updateType(idx, type) {
    cart[idx].type = type;
    renderCart();
}

function calcItemPrice(item) {
    return item.type === 'plus_tabung' ? item.price + item.tube : item.price;
}

function getGrandTotal() {
    return cart.reduce((s, i) => s + calcItemPrice(i) * i.qty, 0);
}

function renderCart() {
    const body = document.getElementById('cartBody');
    const empty = document.getElementById('emptyCart');
    const inputs = document.getElementById('cartInputs');

    if (cart.length === 0) {
        if (empty) empty.style.display = 'block';
        document.getElementById('btnCheckout').disabled = true;
        document.getElementById('subtotalDisplay').textContent = 'Rp 0';
        document.getElementById('grandTotalDisplay').textContent = 'Rp 0';
        body.innerHTML = '';
        if (empty) body.appendChild(empty);
        inputs.innerHTML = '';
        saveCartToStorage();
        calcChange();
        return;
    }

    if (empty) empty.style.display = 'none';
    document.getElementById('btnCheckout').disabled = false;

    let html = '';
    let total = 0;
    let hiddenInputs = '';
    let hasPinjam = false;

    cart.forEach((item, idx) => {
        const unitPrice = calcItemPrice(item);
        const subtotal = unitPrice * item.qty;
        total += subtotal;
        if (item.type === 'pinjam_tabung') hasPinjam = true;

        html += `<div class="cart-item">
            <div style="flex:1;">
                <div class="cart-item-name">${item.name}</div>
                <select class="type-select" onchange="updateType(${idx}, this.value)" style="margin-top:4px;">
                    <option value="refill" ${item.type==='refill'?'selected':''}>🔄 Refill (Tukar Tabung)</option>
                    <option value="pinjam_tabung" ${item.type==='pinjam_tabung'?'selected':''}>📦 Pinjam Tabung</option>
                    <option value="plus_tabung" ${item.type==='plus_tabung'?'selected':''}>🆕 Beli + Tabung</option>
                </select>
                ${item.type === 'plus_tabung' ? `<div style="font-size:11px;color:var(--text-muted);margin-top:3px;">+Tabung Rp ${formatNum(item.tube)}</div>` : ''}
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div class="cart-item-price" style="margin-bottom:4px;">Rp ${formatNum(subtotal)}</div>
                <div class="qty-control">
                    <button class="qty-btn" type="button" onclick="updateQty(${idx}, -1)">−</button>
                    <span class="qty-value">${item.qty}</span>
                    <button class="qty-btn" type="button" onclick="updateQty(${idx}, +1)">+</button>
                    <button class="qty-btn" type="button" onclick="removeFromCart(${idx})" style="color:var(--danger);">✕</button>
                </div>
            </div>
        </div>`;

        hiddenInputs += `<input type="hidden" name="items[${idx}][product_id]" value="${item.id}">
            <input type="hidden" name="items[${idx}][transaction_type]" value="${item.type}">
            <input type="hidden" name="items[${idx}][qty]" value="${item.qty}">`;
    });

    body.innerHTML = html;
    inputs.innerHTML = hiddenInputs;

    document.getElementById('subtotalDisplay').textContent = 'Rp ' + formatNum(total);
    document.getElementById('grandTotalDisplay').textContent = 'Rp ' + formatNum(total);

    // Show customer required hint if pinjam tabung or belum lunas
    const status = document.getElementById('paymentStatus').value;
    document.getElementById('customerRequired').style.display = (hasPinjam || status === 'belum_lunas') ? 'inline' : 'none';

    saveCartToStorage();
    calcChange();
}

// ==================== QUICK CASH / PEMBAYARAN CEPAT ====================
function setExactCash() {
    const total = getGrandTotal();
    document.getElementById('paidAmount').value = total;
    calcChange();
}

function addCash(amount) {
    const cur = parseFloat(document.getElementById('paidAmount').value) || 0;
    document.getElementById('paidAmount').value = cur + amount;
    calcChange();
}

function setCash(amount) {
    document.getElementById('paidAmount').value = amount;
    calcChange();
}

function calcChange() {
    const total = getGrandTotal();
    const paid  = parseFloat(document.getElementById('paidAmount').value) || 0;
    const change = Math.max(0, paid - total);
    document.getElementById('changeDisplay').textContent = 'Rp ' + formatNum(change);
}

function updatePaymentUI() {
    const method = document.getElementById('paymentMethod').value;
    const status = document.getElementById('paymentStatus').value;
    document.getElementById('paymentNoteGroup').style.display = method === 'transfer' ? 'block' : 'none';
    const paidGroup = document.getElementById('paidAmountGroup');

    if (status === 'belum_lunas') {
        paidGroup.querySelector('label').textContent = 'Uang Muka (DP) / Dibayar (Rp)';
        document.getElementById('customerRequired').style.display = 'inline';
    } else {
        paidGroup.querySelector('label').textContent = 'Uang Diterima (Rp)';
        const hasPinjam = cart.some(i => i.type === 'pinjam_tabung');
        document.getElementById('customerRequired').style.display = hasPinjam ? 'inline' : 'none';
    }
    calcChange();
}

function clearCart() {
    if (cart.length && confirm('Kosongkan keranjang belanja?')) {
        cart = [];
        renderCart();
    }
}

function formatNum(n) {
    return Math.round(n).toLocaleString('id-ID');
}

// ==================== AJAX LIVE SEARCH PELANGGAN ====================
let searchTimer = null;
function searchCustomersAjax(query) {
    clearTimeout(searchTimer);
    if (!query || query.trim().length === 0) return;

    searchTimer = setTimeout(() => {
        fetch('{{ route("customers.search") }}?q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(list => {
            const sel = document.getElementById('customerSelect');
            sel.innerHTML = '<option value="">-- Umum / Tanpa Nama --</option>';
            list.forEach(c => {
                const opt = new Option(c.name + (c.phone ? ' (' + c.phone + ')' : '') + ' [' + c.customer_type + ']', c.id, true, true);
                sel.appendChild(opt);
            });
        })
        .catch(err => console.error('Error live search customer:', err));
    }, 250);
}

// ==================== AJAX TAMBAH PELANGGAN CEPAT ====================
function openAddCustomer() { document.getElementById('addCustomerModal').classList.add('active'); }
function closeAddCustomer() { document.getElementById('addCustomerModal').classList.remove('active'); }

function saveNewCustomer() {
    const btn = document.getElementById('btnSaveCust');
    const origText = btn.innerHTML;
    const data = {
        name:          document.getElementById('newCustName').value.trim(),
        phone:         document.getElementById('newCustPhone').value.trim(),
        nik:           document.getElementById('newCustNik').value.trim(),
        customer_type: document.getElementById('newCustType').value,
        address:       document.getElementById('newCustAddress').value.trim(),
        _token:        '{{ csrf_token() }}'
    };

    if (!data.name) { alert('Nama pelanggan wajib diisi!'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    fetch('{{ route("pos.storeCustomer") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            const sel = document.getElementById('customerSelect');
            const opt = new Option(res.customer.name + (res.customer.phone ? ' ('+res.customer.phone+')' : ''), res.customer.id, true, true);
            sel.appendChild(opt);
            document.getElementById('newCustName').value = '';
            document.getElementById('newCustPhone').value = '';
            document.getElementById('newCustNik').value = '';
            document.getElementById('newCustAddress').value = '';
            closeAddCustomer();
        } else {
            alert(res.message || 'Gagal menyimpan pelanggan.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        alert('Terjadi kesalahan saat menyimpan pelanggan.');
    });
}

// ==================== AJAX POS CHECKOUT ====================
function handlePosCheckout(e) {
    e.preventDefault();

    if (cart.length === 0) {
        alert('Keranjang belanja masih kosong!');
        return;
    }

    const form = document.getElementById('posForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnCheckout');
    const btnText = document.getElementById('btnCheckoutText');
    const origContent = btnText.innerHTML;

    btn.disabled = true;
    btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Transaksi...';

    fetch('{{ route("pos.store") }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(r => {
        if (!r.ok) {
            return r.json().then(errData => { throw errData; });
        }
        return r.json();
    })
    .then(res => {
        btn.disabled = false;
        btnText.innerHTML = origContent;

        if (res.success) {
            // Tampilkan popup sukses
            document.getElementById('successInvoiceText').textContent = 'Invoice: ' + res.invoice_number;
            document.getElementById('successTotal').textContent = 'Rp ' + formatNum(res.total_amount);
            document.getElementById('successPaid').textContent = 'Rp ' + formatNum(res.paid_amount);
            document.getElementById('successChange').textContent = 'Rp ' + formatNum(res.change_amount);

            const debtRow = document.getElementById('successDebtRow');
            if (res.remaining_debt > 0) {
                debtRow.style.display = 'flex';
                document.getElementById('successDebt').textContent = 'Rp ' + formatNum(res.remaining_debt);
            } else {
                debtRow.style.display = 'none';
            }

            // Link Cetak Nota
            document.getElementById('btnReceiptLink').href = res.receipt_url;

            // Buka Modal
            document.getElementById('checkoutSuccessModal').classList.add('active');

            // Reset keranjang lokal & storage
            cart = [];
            localStorage.removeItem('simlpg_pos_cart');
            renderCart();
            document.getElementById('paidAmount').value = '';
            document.getElementById('changeDisplay').textContent = 'Rp 0';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btnText.innerHTML = origContent;
        if (err && err.errors) {
            const msgs = Object.values(err.errors).flat().join('\n');
            alert('Validasi Gagal:\n' + msgs);
        } else {
            alert(err.message || 'Terjadi kesalahan sistem saat memproses transaksi.');
        }
    });
}

function closeSuccessModal() {
    document.getElementById('checkoutSuccessModal').classList.remove('active');
}

// ==================== INISIALISASI ====================
// Filter produk realtime
document.getElementById('productSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.display = card.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Load cart jika sebelumnya ada di storage
loadCartFromStorage();
</script>
@endpush
@endsection
