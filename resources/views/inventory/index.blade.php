@extends('layouts.app')
@section('title', 'Produk & Stok')
@section('page-title', auth()->user()->isOwner() ? 'Produk & Stok' : 'Stok Gas')
@section('page-sub', 'Ringkasan persediaan tabung isi, kosong, dan dalam pinjaman')

@section('topbar-actions')
    @if(auth()->user()->isOwner())
    <button type="button" class="btn btn-primary" data-modal-open="addProductModal">
        <x-icon name="plus" /> Tambah Produk
    </button>
    @endif
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
    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th>Produk</th>
                    @if(auth()->user()->isOwner())<th class="num">Harga beli</th>@endif
                    <th class="num">Harga jual</th><th class="num">Harga tabung</th>
                    <th>Stok</th><th>Status</th>
                    @if(auth()->user()->isOwner())<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td data-label="Produk">
                        <div class="cell-title">{{ $product->name }}</div>
                        <div class="cell-sub">SKU {{ $product->sku }}</div>
                    </td>
                    @if(auth()->user()->isOwner())<td class="num" data-label="Harga beli">@rupiah($product->price_buy)</td>@endif
                    <td class="num fw" data-label="Harga jual">@rupiah($product->price_sell)</td>
                    <td class="num" data-label="Harga tabung">@rupiah($product->price_tube)</td>
                    <td data-label="Stok">
                        <div class="row" style="gap:6px;">
                            <span class="qty-pill qty-filled" title="Isi">{{ $product->stock_filled }}</span>
                            <span class="qty-pill qty-empty" title="Kosong">{{ $product->stock_empty }}</span>
                            <span class="qty-pill qty-borrowed" title="Dipinjam">{{ $product->stock_borrowed }}</span>
                            @if($product->is_active && $product->stock_filled <= $lowStockLimit)
                                <span class="badge badge-warning">{{ $product->stock_filled <= 0 ? 'Habis' : 'Menipis' }}</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Status">
                        @if($product->is_active)
                            <span class="badge badge-success badge-dot">Aktif</span>
                        @else
                            <span class="badge badge-dot">Nonaktif</span>
                        @endif
                    </td>
                    @if(auth()->user()->isOwner())
                    <td data-label="Aksi">
                        <div class="actions">
                            <button type="button" class="btn btn-ghost btn-xs btn-icon" title="Edit produk" aria-label="Edit produk"
                                    data-modal-open="editProductModal"
                                    data-action="{{ route('inventory.update', $product) }}"
                                    data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                                    data-is-active="{{ $product->is_active ? 1 : 0 }}"
                                    data-price-buy="{{ $product->price_buy + 0 }}" data-price-sell="{{ $product->price_sell + 0 }}" data-price-tube="{{ $product->price_tube + 0 }}">
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
                            @if($product->transaction_details_count === 0)
                            <form method="POST" action="{{ route('inventory.destroy', $product) }}" data-confirm="Hapus produk {{ $product->name }}? Tindakan ini tidak dapat dibatalkan.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus produk (belum pernah terjual)" aria-label="Hapus produk"><x-icon name="trash" size="15" /></button>
                            </form>
                            @else
                            <span class="spacer"></span>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty">
                        <div class="empty-icon"><x-icon name="stock" size="24" /></div>
                        <div class="empty-title">Belum ada produk</div>
                        <div>Tambahkan produk gas pertama Anda.</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-foot">
        <div class="legend">
            <span><i class="dot dot-filled"></i> isi</span>
            <span><i class="dot dot-empty"></i> kosong</span>
            <span><i class="dot dot-borrowed"></i> dipinjam</span>
        </div>
    </div>
    {{ $products->links('vendor.pagination.simple') }}
</div>

@if(auth()->user()->isOwner())
{{-- Tambah produk --}}
<div class="modal-overlay {{ $reopen === 'addProductModal' ? 'active' : '' }}" id="addProductModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Tambah produk</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="{{ route('inventory.store') }}" class="modal-body">
            @csrf
            <input type="hidden" name="_modal" value="addProductModal">
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama produk <span class="req">*</span></label>
                    <input type="text" name="name" class="input" placeholder="Contoh: Bright Gas 5.5 Kg" value="{{ $reopen === 'addProductModal' ? old('name') : '' }}" required>
                </div>
                <div class="field">
                    <label class="label">Kode / SKU <span class="req">*</span></label>
                    <input type="text" name="sku" class="input" placeholder="BG-55" value="{{ $reopen === 'addProductModal' ? old('sku') : '' }}" required>
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
                    <input type="number" name="stock_filled" class="input" min="0" value="{{ $reopen === 'addProductModal' ? old('stock_filled') : '0' }}">
                </div>
                <div class="field">
                    <label class="label">Stok kosong awal</label>
                    <input type="number" name="stock_empty" class="input" min="0" value="{{ $reopen === 'addProductModal' ? old('stock_empty') : '0' }}">
                </div>
                <div class="field span-all">
                    <label class="label">Stok dipinjam awal</label>
                    <input type="number" name="stock_borrowed" class="input" min="0" value="{{ $reopen === 'addProductModal' ? old('stock_borrowed') : '0' }}">
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
        <form method="POST" action="" class="modal-body">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="editProductModal">
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama produk <span class="req">*</span></label>
                    <input type="text" name="name" class="input" required>
                </div>
                <div class="field">
                    <label class="label">Kode / SKU <span class="req">*</span></label>
                    <input type="text" name="sku" class="input" required>
                </div>
                <div class="field">
                    <label class="label">Status</label>
                    <select name="is_active" class="select">
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
                <input type="number" name="stock_filled" class="input" min="0" required value="{{ $reopen === 'adjustStockModal' ? old('stock_filled') : '' }}">
            </div>
            <div class="field">
                <label class="label"><i class="dot dot-empty"></i> Tabung kosong</label>
                <input type="number" name="stock_empty" class="input" min="0" required value="{{ $reopen === 'adjustStockModal' ? old('stock_empty') : '' }}">
            </div>
            <div class="field">
                <label class="label"><i class="dot dot-borrowed"></i> Tabung dipinjam</label>
                <input type="number" name="stock_borrowed" class="input" min="0" required value="{{ $reopen === 'adjustStockModal' ? old('stock_borrowed') : '' }}">
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
@endsection
