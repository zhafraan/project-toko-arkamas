@extends('layouts.app')
@section('title', 'Pinjaman Tabung')
@section('page-title', 'Pinjaman Tabung')
@section('page-sub', 'Daftar tabung yang masih berada di pelanggan')

@section('content')
<div class="card">
    <form method="GET" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari nama pelanggan…">
        </div>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search'))
            <a href="{{ route('loans.index') }}" class="btn btn-ghost">Reset</a>
        @endif
        <span class="badge badge-warning" style="margin-left:auto;">{{ $loans->total() }} pinjaman aktif</span>
    </form>

    @if($loans->count() > 0)
    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th>Pelanggan</th><th>Produk</th>
                    <th class="num">Dipinjam</th><th class="num">Kembali</th><th class="num">Belum kembali</th>
                    <th>Status</th><th>Tgl pinjam</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($loans as $loan)
                <tr>
                    <td data-label="Pelanggan">
                        @if($loan->customer)
                        <a href="{{ route('customers.show', $loan->customer) }}" class="cell-title">{{ $loan->customer->name }}</a>
                        @else
                        <span class="cell-title">Pelanggan dihapus</span>
                        @endif
                        <div class="cell-sub">{{ $loan->customer?->phone ?? '-' }}</div>
                    </td>
                    <td data-label="Produk">{{ $loan->product->name }}</td>
                    <td class="num" data-label="Dipinjam">{{ $loan->qty_borrowed }}</td>
                    <td class="num text-success" data-label="Kembali">{{ $loan->qty_returned }}</td>
                    <td class="num" data-label="Belum kembali"><span class="qty-pill qty-borrowed">{{ $loan->remaining_qty }}</span></td>
                    <td data-label="Status">
                        @if($loan->status === 'dipinjam')
                            <span class="badge badge-warning badge-dot">Dipinjam</span>
                        @else
                            <span class="badge badge-warning badge-dot">Sebagian kembali</span>
                        @endif
                    </td>
                    <td class="muted" data-label="Tgl pinjam">{{ $loan->borrow_date->isoFormat('D MMM Y') }}</td>
                    <td data-label="Aksi">
                        <div class="actions">
                            <button type="button" class="btn btn-success btn-sm"
                                    data-modal-open="returnModal"
                                    data-action="{{ route('loans.return', $loan) }}"
                                    data-subject="{{ $loan->customer?->name ?? 'Pelanggan dihapus' }} — {{ $loan->product->name }}"
                                    data-remaining="{{ $loan->remaining_qty }}"
                                    data-qty-return="{{ $loan->remaining_qty }}">
                                <x-icon name="undo" size="15" /> Catat pengembalian
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty">
        <div class="empty-icon"><x-icon name="package" size="24" /></div>
        <div class="empty-title">{{ request('search') ? 'Tidak ditemukan' : 'Tidak ada pinjaman aktif' }}</div>
        <div>{{ request('search') ? 'Coba nama pelanggan lain.' : 'Seluruh tabung sudah dikembalikan.' }}</div>
    </div>
    @endif
    {{ $loans->links('vendor.pagination.simple') }}
</div>

{{-- Modal pengembalian --}}
<div class="modal-overlay" id="returnModal">
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-title">Pengembalian tabung</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" id="returnForm" class="modal-body">
            @csrf
            <div class="modal-note"><strong data-modal-subject></strong><div class="muted" id="returnInfo" style="margin-top:2px;"></div></div>
            <div class="field">
                <label class="label" for="returnQty">Jumlah tabung dikembalikan</label>
                <input type="number" name="qty_return" id="returnQty" class="input" min="1" step="1" required>
                <div class="hint">Tabung yang kembali masuk ke stok tabung kosong.</div>
            </div>
            <div class="field-error" id="returnError" hidden style="margin-bottom:12px;"></div>
            <button type="submit" class="btn btn-success btn-block" id="btnReturn"><x-icon name="check" /> Konfirmasi pengembalian</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-modal-open="returnModal"]');
    if (!b) return;
    var qty = document.getElementById('returnQty');
    qty.max = b.dataset.remaining;
    document.getElementById('returnInfo').textContent = 'Belum kembali: ' + b.dataset.remaining + ' tabung';
    document.getElementById('returnError').hidden = true;
});

document.getElementById('returnForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = this, btn = document.getElementById('btnReturn'), err = document.getElementById('returnError');
    err.hidden = true; btn.disabled = true;
    postJSON(form.action, { qty_return: document.getElementById('returnQty').value })
        .then(function (res) { toastAfterReload(res.message, 'success'); location.reload(); })
        .catch(function (ex) { err.textContent = errorText(ex); err.hidden = false; btn.disabled = false; });
});
</script>
@endpush
