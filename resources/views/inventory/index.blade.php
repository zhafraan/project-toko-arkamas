@extends('layouts.app')
@section('title', 'Produk & Stok')
@section('page-title', 'Produk & Stok Gas')

@section('topbar-actions')
    @if(auth()->user()->isOwner())
    <button onclick="document.getElementById('addProductModal').classList.add('active')" class="btn-topbar-action">
        <i class="fa-solid fa-plus"></i> Tambah Produk
    </button>
    @endif
@endsection

@section('content')

@if(session('success'))
<div style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:12px 16px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:600;font-size:13.5px;">
    <i class="fa-solid fa-circle-check" style="font-size:17px;color:#10b981;"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:12px 16px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:600;font-size:13.5px;">
    <i class="fa-solid fa-circle-exclamation" style="font-size:17px;color:#f43f5e;"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

@if(isset($errors) && $errors->any())
<div style="background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:13px;">
    <div style="display:flex;align-items:center;gap:8px;font-weight:700;margin-bottom:6px;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#f43f5e;"></i>
        <span>Ada kesalahan pada pengisian form:</span>
    </div>
    <ul style="margin:0;padding-left:22px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Stock Summary --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fa-solid fa-fire-flame-curved"></i></div>
        <div class="stat-info">
            <div class="stat-value">{{ $products->sum('stock_filled') }}</div>
            <div class="stat-label">Total Tabung Isi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#94a3b8,#64748b);"><i class="fa-solid fa-box-open"></i></div>
        <div class="stat-info">
            <div class="stat-value">{{ $products->sum('stock_empty') }}</div>
            <div class="stat-label">Total Tabung Kosong</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fa-solid fa-right-left"></i></div>
        <div class="stat-info">
            <div class="stat-value">{{ $products->sum('stock_borrowed') }}</div>
            <div class="stat-label">Total Dipinjam</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-cubes-stacked" style="color:var(--primary);margin-right:8px;"></i> Daftar Produk Gas</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Produk / SKU</th>
                    <th>Harga Beli</th><th>Harga Jual</th><th>Harga Tabung</th>
                    <th style="text-align:center">Isi</th>
                    <th style="text-align:center">Kosong</th>
                    <th style="text-align:center">Dipinjam</th>
                    <th>Status</th>
                    @if(auth()->user()->isOwner())<th>Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <div style="font-weight:700;">{{ $product->name }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">SKU: {{ $product->sku }}</div>
                    </td>
                    <td>Rp {{ number_format($product->price_buy,0,',','.') }}</td>
                    <td style="font-weight:600;color:var(--primary);">Rp {{ number_format($product->price_sell,0,',','.') }}</td>
                    <td>Rp {{ number_format($product->price_tube,0,',','.') }}</td>
                    <td style="text-align:center;">
                        <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:20px;font-weight:700;">{{ $product->stock_filled }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="background:#f1f5f9;color:#475569;padding:4px 12px;border-radius:20px;font-weight:700;">{{ $product->stock_empty }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span style="background:#ffedd5;color:#9a3412;padding:4px 12px;border-radius:20px;font-weight:700;">{{ $product->stock_borrowed }}</span>
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-gray">Non-Aktif</span>
                        @endif
                    </td>
                    @if(auth()->user()->isOwner())
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <button type="button" onclick='openEditProduct(@json($product))' class="btn btn-outline btn-xs" title="Edit Produk">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" onclick='openAdjustStock(@json($product))' class="btn btn-outline btn-xs" style="color:var(--warning);border-color:var(--warning);" title="Stock Opname (Sesuaikan Stok)">
                                <i class="fa-solid fa-sliders"></i>
                            </button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada produk</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:0 16px;">{{ $products->links('vendor.pagination.simple') }}</div>
</div>

@if(auth()->user()->isOwner())
{{-- Add Product Modal --}}
<div class="modal-overlay" id="addProductModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-plus-circle" style="color:var(--primary);margin-right:6px;"></i> Tambah Produk Baru</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Nama Produk Gas *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Bright Gas 5.5 Kg" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kode / SKU *</label>
                    <input type="text" name="sku" class="form-control" placeholder="BG-55" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Beli Modal (HPP)</label>
                    <input type="number" name="price_buy" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Jual Refill *</label>
                    <input type="number" name="price_sell" class="form-control" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Tabung Fisik</label>
                    <input type="number" name="price_tube" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Tabung Isi</label>
                    <input type="number" name="stock_filled" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Tabung Kosong</label>
                    <input type="number" name="stock_empty" class="form-control" value="0" min="0">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Stok Dipinjam Awal</label>
                    <input type="number" name="stock_borrowed" class="form-control" value="0" min="0">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:12px;padding:12px;">
                <i class="fa-solid fa-check"></i> Simpan & Tambahkan Produk
            </button>
        </form>
    </div>
</div>

{{-- Edit Product Modal --}}
<div class="modal-overlay" id="editProductModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);margin-right:6px;"></i> Edit Produk Gas</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editProductForm" method="POST">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Nama Produk *</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kode / SKU *</label>
                    <input type="text" name="sku" id="editSku" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Produk *</label>
                    <select name="is_active" id="editIsActive" class="form-control" required>
                        <option value="1">Aktif (Dijual & Tampil di Kasir)</option>
                        <option value="0">Non-Aktif (Sembunyikan / Diarsipkan)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Beli Modal (HPP)</label>
                    <input type="number" name="price_buy" id="editPriceBuy" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Jual Refill *</label>
                    <input type="number" name="price_sell" id="editPriceSell" class="form-control" min="0" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Harga Tabung Fisik</label>
                    <input type="number" name="price_tube" id="editPriceTube" class="form-control" min="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Stok Tabung Isi</label>
                    <input type="number" name="stock_filled" id="editStockFilled" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Tabung Kosong</label>
                    <input type="number" name="stock_empty" id="editStockEmpty" class="form-control" min="0">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Stok Dipinjam</label>
                    <input type="number" name="stock_borrowed" id="editStockBorrowed" class="form-control" min="0">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;padding:12px;">
                    <i class="fa-solid fa-save"></i> Simpan Perubahan Produk
                </button>
            </div>
        </form>
    </div>
</div>


{{-- Stock Opname Modal --}}
<div class="modal-overlay" id="adjustStockModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-sliders" style="color:var(--warning);margin-right:6px;"></i> Penyesuaian Stok (Stock Opname)</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <p id="adjustProductName" style="margin-bottom:16px;font-weight:700;color:var(--primary);font-size:14px;"></p>
        <form id="adjustStockForm" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Stok Isi Saat Ini</label>
                    <input type="number" name="stock_filled" id="adjFilled" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Kosong Saat Ini</label>
                    <input type="number" name="stock_empty" id="adjEmpty" class="form-control" min="0" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Stok Dipinjam Saat Ini</label>
                    <input type="number" name="stock_borrowed" id="adjBorrowed" class="form-control" min="0" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:12px;padding:12px;">
                <i class="fa-solid fa-check"></i> Simpan Penyesuaian Stok
            </button>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function openEditProduct(product) {
    document.getElementById('editProductForm').action = '/inventory/' + product.id;
    document.getElementById('editName').value = product.name || '';
    document.getElementById('editSku').value = product.sku || '';
    document.getElementById('editPriceBuy').value = Math.round(product.price_buy || 0);
    document.getElementById('editPriceSell').value = Math.round(product.price_sell || 0);
    document.getElementById('editPriceTube').value = Math.round(product.price_tube || 0);
    document.getElementById('editIsActive').value = product.is_active ? "1" : "0";
    document.getElementById('editStockFilled').value = product.stock_filled ?? 0;
    document.getElementById('editStockEmpty').value = product.stock_empty ?? 0;
    document.getElementById('editStockBorrowed').value = product.stock_borrowed ?? 0;
    document.getElementById('editProductModal').classList.add('active');
}

function openAdjustStock(product) {
    document.getElementById('adjustStockForm').action = '/inventory/' + product.id + '/adjust-stock';
    document.getElementById('adjustProductName').textContent = 'Produk: ' + (product.name || '');
    document.getElementById('adjFilled').value = product.stock_filled ?? 0;
    document.getElementById('adjEmpty').value = product.stock_empty ?? 0;
    document.getElementById('adjBorrowed').value = product.stock_borrowed ?? 0;
    document.getElementById('adjustStockModal').classList.add('active');
}
</script>
@endpush
@endsection
