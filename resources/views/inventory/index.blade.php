@extends('layouts.app')
@php
    $user = auth()->user();
    $pageTitle = $user->isOwner() ? 'Produk & Stok' : 'Stok';
    $pageSub = 'Ringkasan persediaan pangkalan gas LPG';
@endphp
@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@section('topbar-actions')
    @if($user->isOwner())
    <button type="button" class="btn btn-primary" data-modal-open="addProductModal" title="Tambah Produk" aria-label="Tambah Produk">
        <x-icon name="plus" /> <span class="btn-text">Tambah Produk</span>
    </button>
    @endif

@push('scripts')
<script>
// --- Foto Produk: preview & remove ---
function previewProductPhoto(input, previewId, imgId, placeholderId) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById(imgId).src = e.target.result;
        document.getElementById(previewId).hidden = false;
        document.getElementById(placeholderId).hidden = true;
        // Reset remove_photo jika ada
        var rm = document.getElementById('editRemovePhoto');
        if (rm) rm.value = '0';
    };
    reader.readAsDataURL(file);
}

function removeProductPhoto(previewId, placeholderId, inputId, removeHiddenId) {
    document.getElementById(previewId).hidden = true;
    document.getElementById(placeholderId).hidden = false;
    var inp = document.getElementById(inputId);
    if (inp) inp.value = '';
    var rm = document.getElementById(removeHiddenId);
    if (rm) rm.value = '1';
    // Reset img src
    var img = document.getElementById(previewId).querySelector('img');
    if (img) img.src = '';
}

// --- Edit modal: isi data dari button ---
document.addEventListener('DOMContentLoaded', function () {
    var editBtns = document.querySelectorAll('[data-modal-open="editProductModal"]');
    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('editProductModal');
            if (!modal) return;
            var form = modal.querySelector('form');
            form.action = btn.dataset.action;
            form.querySelector('[name="name"]').value = btn.dataset.name || '';
            form.querySelector('[name="sku"]').value = btn.dataset.sku || '';
            form.querySelector('[name="price_buy"]').value = btn.dataset.priceBuy || '0';
            form.querySelector('[name="price_sell"]').value = btn.dataset.priceSell || '';
            form.querySelector('[name="price_tube"]').value = btn.dataset.priceTube || '0';
            var statusSel = form.querySelector('[name="is_active"]');
            if (statusSel) statusSel.value = btn.dataset.isActive || '1';

            // Foto: tampilkan foto saat ini atau placeholder
            var photoUrl = btn.dataset.photo || '';
            var hasPhoto = btn.dataset.hasPhoto === '1';
            var preview = document.getElementById('editPhotoPreview');
            var placeholder = document.getElementById('editPhotoPlaceholder');
            var img = document.getElementById('editPhotoImg');
            var rmInput = document.getElementById('editRemovePhoto');
            var fileInput = document.getElementById('editPhotoInput');

            if (rmInput) rmInput.value = '0';
            if (fileInput) fileInput.value = '';

            if (hasPhoto && photoUrl) {
                if (img) img.src = photoUrl;
                if (preview) preview.hidden = false;
                if (placeholder) placeholder.hidden = true;
            } else {
                if (preview) preview.hidden = true;
                if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
                if (img) img.src = '';
            }
        });
    });

    // Add modal: reset photo preview on open
    var addModalTriggers = document.querySelectorAll('[data-modal-open="addProductModal"]');
    addModalTriggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preview = document.getElementById('addPhotoPreview');
            var placeholder = document.getElementById('addPhotoPlaceholder');
            var img = document.getElementById('addPhotoImg');
            var fileInput = document.getElementById('addPhotoInput');
            if (preview) preview.hidden = true;
            if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
            if (img) img.src = '';
            if (fileInput) fileInput.value = '';
        });
    });

    // Remove photo on add modal
    var addRemoveBtn = document.getElementById('addPhotoRemove');
    if (addRemoveBtn) {
        addRemoveBtn.addEventListener('click', function () {
            var preview = document.getElementById('addPhotoPreview');
            var placeholder = document.getElementById('addPhotoPlaceholder');
            var fileInput = document.getElementById('addPhotoInput');
            if (preview) preview.hidden = true;
            if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
            if (fileInput) fileInput.value = '';
        });
    }
});
</script>
@endpush
@endsection

@section('content')
@php $reopen = old('_modal'); @endphp

<div class="summary mb-2">
    <div class="summary-item">
        <div class="summary-label"><i class="dot dot-filled"></i> Tabung isi</div>
        <div class="summary-value">{{ number_format($totalFilled) }}</div>
        <div class="summary-note">siap dijual</div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><i class="dot dot-empty"></i> Tabung kosong</div>
        <div class="summary-value">{{ number_format($totalEmpty) }}</div>
        <div class="summary-note">menunggu penukaran ke agen</div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><i class="dot dot-borrowed"></i> Tabung dipinjam</div>
        <div class="summary-value">{{ number_format($totalBorrowed) }}</div>
        <div class="summary-note">sedang di pelanggan</div>
    </div>
</div>

<div class="card">
    @if($products->count() > 0)
    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th>Produk</th>
                    @if($user->isOwner())<th class="num">Harga beli</th>@endif
                    <th class="num">Harga jual</th>
                    <th class="num">Harga tabung</th>
                    <th>Stok</th>
                    <th>Status</th>
                    @if($user->isOwner())<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td data-label="Produk">
                        <div style="display:flex;align-items:center;gap:10px;">
                            @if($product->photo)
                                <img src="{{ Storage::url($product->photo) }}" alt="{{ $product->name }}" class="product-thumb" loading="lazy">
                            @else
                                <div class="product-thumb-placeholder"><x-icon name="package" size="16" /></div>
                            @endif
                            <div>
                                <div class="cell-title">{{ $product->name }}</div>
                                <div class="cell-sub">SKU {{ $product->sku }}</div>
                            </div>
                        </div>
                    </td>
                    @if($user->isOwner())<td class="num" data-label="Harga beli">@rupiah($product->price_buy)</td>@endif
                    <td class="num fw" data-label="Harga jual">@rupiah($product->price_sell)</td>
                    <td class="num" data-label="Harga tabung">@rupiah($product->price_tube)</td>
                    <td data-label="Stok">
                        <div class="row" style="gap:6px;">
                            <span class="qty-pill qty-filled" title="Isi">{{ $product->stock_filled }}</span>
                            <span class="qty-pill qty-empty" title="Kosong">{{ $product->stock_empty }}</span>
                            <span class="qty-pill qty-borrowed" title="Dipinjam">{{ $product->stock_borrowed }}</span>
                            @if($product->is_active && $product->stock_filled <= $lowStockLimit)
                                @if($product->stock_filled <= 0)
                                    <span class="badge badge-danger">Habis</span>
                                @else
                                    <span class="badge badge-warning">Menipis</span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td data-label="Status">
                        @if($product->is_active)
                            <span class="badge badge-success badge-dot">Aktif</span>
                        @else
                            <span class="badge badge-danger badge-dot">Nonaktif</span>
                        @endif
                    </td>
                    @if($user->isOwner())
                    <td data-label="Aksi">
                        <div class="actions">
                            <button type="button" class="btn btn-ghost btn-xs btn-icon" title="Edit produk" aria-label="Edit produk"
                                    data-modal-open="editProductModal"
                                    data-action="{{ route('inventory.update', $product) }}"
                                    data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                                    data-is-active="{{ $product->is_active ? 1 : 0 }}"
                                    data-price-buy="{{ $product->price_buy + 0 }}" data-price-sell="{{ $product->price_sell + 0 }}" data-price-tube="{{ $product->price_tube + 0 }}"
                                    data-photo="{{ $product->photo ? Storage::url($product->photo) : '' }}"
                                    data-has-photo="{{ $product->photo ? '1' : '0' }}">
                                <x-icon name="pencil" size="15" />
                            </button>
                            <button type="button" class="btn btn-ghost btn-xs btn-icon" title="Penyesuaian stok" aria-label="Penyesuaian stok"
                                    data-modal-open="adjustStockModal"
                                    data-action="{{ route('inventory.adjustStock', $product) }}"
                                    data-subject="{{ $product->name }}"
                                    data-stock-filled="{{ $product->stock_filled }}" data-stock-empty="{{ $product->stock_empty }}" data-stock-borrowed="{{ $product->stock_borrowed }}">
                                <x-icon name="sliders" size="15" />
                            </button>
                            <form method="POST" action="{{ route('inventory.toggleActive', $product) }}"
                                  data-confirm="{{ $product->is_active ? 'Nonaktifkan produk ' . $product->name . '? Produk tidak akan tampil di kasir.' : 'Aktifkan kembali produk ' . $product->name . '?' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs btn-icon {{ $product->is_active ? 'text-warning' : 'text-success' }}"
                                        title="{{ $product->is_active ? 'Nonaktifkan produk' : 'Aktifkan produk' }}"
                                        aria-label="{{ $product->is_active ? 'Nonaktifkan produk' : 'Aktifkan produk' }}">
                                    <x-icon :name="$product->is_active ? 'eye-off' : 'eye'" size="15" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('inventory.destroy', $product) }}"
                                  data-confirm="Hapus produk {{ $product->name }}? Produk yang sudah memiliki riwayat transaksi tidak dapat dihapus.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus produk" aria-label="Hapus produk">
                                    <x-icon name="trash" size="15" />
                                </button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty">
        <div class="empty-icon"><x-icon name="package" size="24" /></div>
        <div class="empty-title">Belum ada produk</div>
        @if($user->isOwner())
        <div class="empty-sub">Tambahkan produk gas pertama Anda.</div>
        @endif
    </div>
    @endif
    {{ $products->links('vendor.pagination.simple') }}
</div>

<div class="legend legend-center mt-2" style="justify-content:center;text-align:center;">
    <span><i class="dot dot-filled"></i> Tabung isi</span>
    <span><i class="dot dot-empty"></i> Tabung kosong</span>
    <span><i class="dot dot-borrowed"></i> Tabung dipinjam</span>
</div>

@if($user->isOwner())
{{-- Tambah produk --}}
<div class="modal-overlay {{ $reopen === 'addProductModal' ? 'active' : '' }}" id="addProductModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Tambah produk baru</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="{{ route('inventory.store') }}" class="modal-body" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_modal" value="addProductModal">
            {{-- Foto Produk --}}
            <div class="field span-all">
                <label class="label">Foto Produk <span class="muted" style="font-weight:400;">(opsional, maks. 2 MB)</span></label>
                <div class="product-photo-upload" id="addPhotoUpload">
                    <div class="product-photo-preview" id="addPhotoPreview" hidden>
                        <img id="addPhotoImg" src="" alt="Pratinjau foto">
                        <button type="button" class="product-photo-remove" id="addPhotoRemove" title="Hapus foto"><x-icon name="x" size="14" /></button>
                    </div>
                    <label class="product-photo-placeholder" id="addPhotoPlaceholder" for="addPhotoInput">
                        <x-icon name="image" size="22" />
                        <span>Klik untuk pilih foto</span>
                        <span class="muted" style="font-size:11.5px;">JPG, PNG, WebP</span>
                    </label>
                    <input type="file" name="photo" id="addPhotoInput" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="previewProductPhoto(this,'addPhotoPreview','addPhotoImg','addPhotoPlaceholder')">
                </div>
            </div>
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama produk <span class="req">*</span></label>
                    <input type="text" name="name" class="input" maxlength="255" placeholder="Contoh: LPG 3 Kg (Subsidi)" value="{{ $reopen === 'addProductModal' ? old('name') : '' }}" required>
                </div>
                <div class="field">
                    <label class="label">Kode SKU <span class="req">*</span></label>
                    <input type="text" name="sku" class="input" maxlength="50" placeholder="Contoh: LPG-03" value="{{ $reopen === 'addProductModal' ? old('sku') : '' }}" required>
                </div>
                <div class="field">
                    <label class="label">Harga beli (modal)</label>
                    <input type="number" name="price_buy" class="input" min="0" step="any" value="{{ $reopen === 'addProductModal' ? old('price_buy') : '0' }}">
                </div>
                <div class="field">
                    <label class="label">Harga jual isi ulang <span class="req">*</span></label>
                    <input type="number" name="price_sell" class="input" min="0" step="any" value="{{ $reopen === 'addProductModal' ? old('price_sell') : '' }}" required>
                </div>
                <div class="field">
                    <label class="label">Harga tabung fisik</label>
                    <input type="number" name="price_tube" class="input" min="0" step="any" value="{{ $reopen === 'addProductModal' ? old('price_tube') : '0' }}">
                </div>
                <div class="field">
                    <label class="label">Stok isi awal</label>
                    <input type="number" name="stock_filled" class="input" min="0" step="1" value="{{ $reopen === 'addProductModal' ? old('stock_filled') : '0' }}">
                </div>
                <div class="field">
                    <label class="label">Stok kosong awal</label>
                    <input type="number" name="stock_empty" class="input" min="0" step="1" value="{{ $reopen === 'addProductModal' ? old('stock_empty') : '0' }}">
                </div>
                <div class="field">
                    <label class="label">Stok dipinjam awal</label>
                    <input type="number" name="stock_borrowed" class="input" min="0" step="1" value="{{ $reopen === 'addProductModal' ? old('stock_borrowed') : '0' }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan produk</button>
        </form>
    </div>
</div>

{{-- Edit produk (tanpa stok — gunakan Penyesuaian stok) --}}
<div class="modal-overlay" id="editProductModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Edit produk</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" class="modal-body" enctype="multipart/form-data">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="editProductModal">
            <input type="hidden" name="remove_photo" id="editRemovePhoto" value="0">
            {{-- Foto Produk --}}
            <div class="field span-all">
                <label class="label">Foto Produk <span class="muted" style="font-weight:400;">(opsional, maks. 2 MB)</span></label>
                <div class="product-photo-upload" id="editPhotoUpload">
                    <div class="product-photo-preview" id="editPhotoPreview" hidden>
                        <img id="editPhotoImg" src="" alt="Pratinjau foto">
                        <button type="button" class="product-photo-remove" id="editPhotoRemove" title="Hapus foto" onclick="removeProductPhoto('editPhotoPreview','editPhotoPlaceholder','editPhotoInput','editRemovePhoto')"><x-icon name="x" size="14" /></button>
                    </div>
                    <label class="product-photo-placeholder" id="editPhotoPlaceholder" for="editPhotoInput">
                        <x-icon name="image" size="22" />
                        <span>Klik untuk pilih foto</span>
                        <span class="muted" style="font-size:11.5px;">JPG, PNG, WebP</span>
                    </label>
                    <input type="file" name="photo" id="editPhotoInput" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="previewProductPhoto(this,'editPhotoPreview','editPhotoImg','editPhotoPlaceholder')">
                </div>
            </div>
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama produk <span class="req">*</span></label>
                    <input type="text" name="name" class="input" required>
                </div>
                <div class="field">
                    <label class="label">Kode SKU <span class="req">*</span></label>
                    <input type="text" name="sku" class="input" required>
                </div>
                <div class="field">
                    <label class="label">Status</label>
                    <select name="is_active" class="select" required>
                        <option value="1">Aktif — tampil di kasir</option>
                        <option value="0">Nonaktif — disembunyikan</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Harga beli (modal)</label>
                    <input type="number" name="price_buy" class="input" min="0" step="any">
                </div>
                <div class="field">
                    <label class="label">Harga jual isi ulang <span class="req">*</span></label>
                    <input type="number" name="price_sell" class="input" min="0" step="any" required>
                </div>
                <div class="field span-all">
                    <label class="label">Harga tabung fisik</label>
                    <input type="number" name="price_tube" class="input" min="0" step="any">
                    <div class="hint">Jumlah stok diubah melalui tombol Penyesuaian stok pada daftar produk.</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan perubahan</button>
        </form>
    </div>
</div>

{{-- Penyesuaian stok --}}
<div class="modal-overlay {{ $reopen === 'adjustStockModal' ? 'active' : '' }}" id="adjustStockModal">
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-title">Penyesuaian stok</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" class="modal-body">
            @csrf
            <input type="hidden" name="_modal" value="adjustStockModal">
            <div class="modal-note"><strong data-modal-subject></strong><div class="muted">Masukkan jumlah hasil penghitungan fisik di gudang. Setiap penyesuaian wajib beralasan dan tercatat di riwayat audit.</div></div>
            <div class="field">
                <label class="label"><i class="dot dot-filled"></i> Tabung isi</label>
                <input type="number" name="stock_filled" class="input" min="0" step="1" required value="{{ $reopen === 'adjustStockModal' ? old('stock_filled') : '' }}">
            </div>
            <div class="field">
                <label class="label"><i class="dot dot-empty"></i> Tabung kosong</label>
                <input type="number" name="stock_empty" class="input" min="0" step="1" required value="{{ $reopen === 'adjustStockModal' ? old('stock_empty') : '' }}">
            </div>
            <div class="field">
                <label class="label"><i class="dot dot-borrowed"></i> Tabung dipinjam</label>
                <input type="number" name="stock_borrowed" class="input" min="0" step="1" required value="{{ $reopen === 'adjustStockModal' ? old('stock_borrowed') : '' }}">
            </div>
            <div class="field">
                <label class="label">Alasan penyesuaian <span class="req">*</span></label>
                <input type="text" name="reason" class="input" maxlength="255" placeholder="Contoh: hasil stok opname mingguan" required value="{{ $reopen === 'adjustStockModal' ? old('reason') : '' }}">
                <div class="hint">Alasan disimpan bersama perubahan angka stok Anda.</div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan penyesuaian</button>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
// --- Foto Produk: preview & remove ---
function previewProductPhoto(input, previewId, imgId, placeholderId) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById(imgId).src = e.target.result;
        document.getElementById(previewId).hidden = false;
        document.getElementById(placeholderId).hidden = true;
        // Reset remove_photo jika ada
        var rm = document.getElementById('editRemovePhoto');
        if (rm) rm.value = '0';
    };
    reader.readAsDataURL(file);
}

function removeProductPhoto(previewId, placeholderId, inputId, removeHiddenId) {
    document.getElementById(previewId).hidden = true;
    document.getElementById(placeholderId).hidden = false;
    var inp = document.getElementById(inputId);
    if (inp) inp.value = '';
    var rm = document.getElementById(removeHiddenId);
    if (rm) rm.value = '1';
    // Reset img src
    var img = document.getElementById(previewId).querySelector('img');
    if (img) img.src = '';
}

// --- Edit modal: isi data dari button ---
document.addEventListener('DOMContentLoaded', function () {
    var editBtns = document.querySelectorAll('[data-modal-open="editProductModal"]');
    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('editProductModal');
            if (!modal) return;
            var form = modal.querySelector('form');
            form.action = btn.dataset.action;
            form.querySelector('[name="name"]').value = btn.dataset.name || '';
            form.querySelector('[name="sku"]').value = btn.dataset.sku || '';
            form.querySelector('[name="price_buy"]').value = btn.dataset.priceBuy || '0';
            form.querySelector('[name="price_sell"]').value = btn.dataset.priceSell || '';
            form.querySelector('[name="price_tube"]').value = btn.dataset.priceTube || '0';
            var statusSel = form.querySelector('[name="is_active"]');
            if (statusSel) statusSel.value = btn.dataset.isActive || '1';

            // Foto: tampilkan foto saat ini atau placeholder
            var photoUrl = btn.dataset.photo || '';
            var hasPhoto = btn.dataset.hasPhoto === '1';
            var preview = document.getElementById('editPhotoPreview');
            var placeholder = document.getElementById('editPhotoPlaceholder');
            var img = document.getElementById('editPhotoImg');
            var rmInput = document.getElementById('editRemovePhoto');
            var fileInput = document.getElementById('editPhotoInput');

            if (rmInput) rmInput.value = '0';
            if (fileInput) fileInput.value = '';

            if (hasPhoto && photoUrl) {
                if (img) img.src = photoUrl;
                if (preview) preview.hidden = false;
                if (placeholder) placeholder.hidden = true;
            } else {
                if (preview) preview.hidden = true;
                if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
                if (img) img.src = '';
            }
        });
    });

    // Add modal: reset photo preview on open
    var addModalTriggers = document.querySelectorAll('[data-modal-open="addProductModal"]');
    addModalTriggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preview = document.getElementById('addPhotoPreview');
            var placeholder = document.getElementById('addPhotoPlaceholder');
            var img = document.getElementById('addPhotoImg');
            var fileInput = document.getElementById('addPhotoInput');
            if (preview) preview.hidden = true;
            if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
            if (img) img.src = '';
            if (fileInput) fileInput.value = '';
        });
    });

    // Remove photo on add modal
    var addRemoveBtn = document.getElementById('addPhotoRemove');
    if (addRemoveBtn) {
        addRemoveBtn.addEventListener('click', function () {
            var preview = document.getElementById('addPhotoPreview');
            var placeholder = document.getElementById('addPhotoPlaceholder');
            var fileInput = document.getElementById('addPhotoInput');
            if (preview) preview.hidden = true;
            if (placeholder) { placeholder.hidden = false; placeholder.style.display = ''; }
            if (fileInput) fileInput.value = '';
        });
    }
});
</script>
@endpush
@endsection
