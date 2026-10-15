@extends('layouts.app')
@section('title', 'Pinjaman Tabung')
@section('page-title', 'Manajemen Pinjaman Tabung')

@section('content')

<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px;">
        <form method="GET" style="display:flex;gap:10px;">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                   placeholder="🔍 Nama Pelanggan..." style="max-width:300px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('loans.index') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fa-solid fa-box" style="color:var(--accent);margin-right:6px;"></i>
            Daftar Pinjaman Aktif
        </span>
        <span class="badge badge-danger">{{ $loans->total() }} aktif</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th><th>Produk</th><th>Dipinjam</th><th>Dikembalikan</th>
                    <th>Sisa</th><th>Deposit</th><th>Status</th><th>Tgl Pinjam</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                <tr>
                    <td>
                        <a href="{{ route('customers.show', $loan->customer) }}" style="font-weight:700;color:var(--primary-light);text-decoration:none;">
                            {{ $loan->customer->name }}
                        </a>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $loan->customer->phone ?? '-' }}</div>
                    </td>
                    <td style="font-weight:600;">{{ $loan->product->name }}</td>
                    <td style="text-align:center;font-weight:700;">{{ $loan->qty_borrowed }}</td>
                    <td style="text-align:center;color:var(--success);font-weight:700;">{{ $loan->qty_returned }}</td>
                    <td style="text-align:center;">
                        <span style="background:{{ $loan->remaining_qty > 0 ? '#fee2e2' : '#d1fae5' }};color:{{ $loan->remaining_qty > 0 ? '#991b1b' : '#065f46' }};padding:3px 10px;border-radius:20px;font-weight:700;">
                            {{ $loan->remaining_qty }}
                        </span>
                    </td>
                    <td>Rp {{ number_format($loan->deposit_amount, 0, ',', '.') }}</td>
                    <td>
                        @if($loan->status === 'dipinjam')
                            <span class="badge badge-danger"><i class="fa-solid fa-circle"></i> Dipinjam</span>
                        @else
                            <span class="badge badge-warning"><i class="fa-solid fa-circle-half-stroke"></i> Sebagian</span>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $loan->borrow_date->isoFormat('D MMM Y') }}</td>
                    <td>
                        <button onclick='openReturnModal({{ $loan->id }}, "{{ $loan->customer->name }}", "{{ $loan->product->name }}", {{ $loan->remaining_qty }})' 
                                class="btn btn-success btn-sm">
                            <i class="fa-solid fa-rotate-left"></i> Kembalikan
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                        <i class="fa-solid fa-box-open" style="font-size:32px;opacity:.2;display:block;margin-bottom:8px;"></i>
                        Tidak ada pinjaman tabung aktif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:0 16px;">{{ $loans->links('vendor.pagination.simple') }}</div>
</div>

{{-- Return Modal --}}
<div class="modal-overlay" id="returnModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-rotate-left"></i> Pengembalian Tabung</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div id="returnInfo" style="background:#f0f9ff;border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;"></div>
        <form id="returnForm" method="POST" onsubmit="handleReturnSubmit(event)">
            @csrf
            <div class="form-group">
                <label class="form-label">Jumlah Tabung Dikembalikan</label>
                <input type="number" name="qty_return" id="returnQty" class="form-control" min="1" required>
            </div>
            <button type="submit" id="btnConfirmReturn" class="btn btn-success" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-check"></i> Konfirmasi Pengembalian
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
let currentLoanId = null;

function openReturnModal(loanId, custName, productName, remaining) {
    currentLoanId = loanId;
    document.getElementById('returnForm').action = '/loans/' + loanId + '/return';
    document.getElementById('returnQty').max     = remaining;
    document.getElementById('returnQty').value   = remaining;
    document.getElementById('returnInfo').innerHTML = `
        <strong>${custName}</strong> — ${productName}<br>
        Tabung belum kembali: <strong style="color:#ef4444;">${remaining}</strong>
    `;
    document.getElementById('returnModal').classList.add('active');
}

function handleReturnSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btnConfirmReturn');
    const origText = btn.innerHTML;
    const qty = document.getElementById('returnQty').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ qty_return: qty })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            alert(res.message);
            document.getElementById('returnModal').classList.remove('active');
            window.location.reload();
        } else {
            alert(res.message || 'Gagal memproses pengembalian.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        alert('Terjadi kesalahan saat memproses pengembalian tabung.');
    });
}
</script>
@endpush
@endsection
