@extends('layouts.app')
@section('title', 'Piutang / Kasbon')
@section('page-title', 'Manajemen Piutang & Kasbon')

@section('content')

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card red" style="grid-column:span 2;">
        <div class="stat-icon red"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
            <div class="stat-label">Total Piutang Belum Lunas</div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px;">
        <form method="GET" style="display:flex;gap:10px;">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                   placeholder="🔍 No. Invoice / Nama Pelanggan..." style="max-width:350px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('debts.index') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fa-solid fa-money-bill-wave" style="color:var(--danger);margin-right:6px;"></i>
            Nota Belum Lunas
        </span>
        <span class="badge badge-danger">{{ $transactions->total() }} nota</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th>Kasir</th>
                    <th>Total Tagihan</th><th>Sudah Bayar</th><th>Sisa</th>
                    <th>Metode</th><th>Tgl Transaksi</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td style="font-size:12px;font-weight:700;color:var(--primary-light);">{{ $trx->invoice_number }}</td>
                    <td>
                        @if($trx->customer)
                        <a href="{{ route('customers.show', $trx->customer) }}" style="font-weight:700;text-decoration:none;color:var(--text);">
                            {{ $trx->customer->name }}
                        </a>
                        @else <em style="color:var(--text-muted);">Umum</em> @endif
                    </td>
                    <td style="font-size:12px;">{{ $trx->user->name }}</td>
                    <td style="font-weight:700;">Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                    <td style="color:var(--success);font-weight:600;">Rp {{ number_format($trx->paid_amount,0,',','.') }}</td>
                    <td style="color:var(--danger);font-weight:800;font-size:15px;">Rp {{ number_format($trx->remaining_debt,0,',','.') }}</td>
                    <td>
                        @if($trx->payment_method === 'cash')
                            <span class="badge badge-info">Cash</span>
                        @else
                            <span class="badge badge-gray">Transfer</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button onclick='openPayModal({{ $trx->id }}, "{{ $trx->invoice_number }}", {{ $trx->remaining_debt }})'
                                    class="btn btn-success btn-sm">
                                <i class="fa-solid fa-money-bill"></i> Bayar
                            </button>
                            <a href="{{ route('pos.receipt', $trx) }}" class="btn btn-outline btn-sm" target="_blank">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                        <i class="fa-solid fa-check-circle" style="font-size:32px;color:var(--success);opacity:.4;display:block;margin-bottom:8px;"></i>
                        Semua nota sudah lunas! 🎉
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:0 16px;">{{ $transactions->links('vendor.pagination.simple') }}</div>
</div>

{{-- Pay Modal --}}
<div class="modal-overlay" id="payModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-money-bill"></i> Catat Pembayaran</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div id="payInfo" style="background:#fef3c7;border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;border-left:4px solid var(--warning);"></div>
        <form id="payForm" method="POST" onsubmit="handlePaySubmit(event)">
            @csrf
            <div class="form-group">
                <label class="form-label">Jumlah Dibayar (Rp) *</label>
                <input type="number" name="amount_paid" id="payAmount" class="form-control" min="1" step="1000" required>
            </div>
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="payment_method" id="payMethod" class="form-control">
                    <option value="cash">💵 Cash</option>
                    <option value="transfer">📱 Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan (opsional)</label>
                <input type="text" name="notes" id="payNotes" class="form-control" placeholder="No. referensi / keterangan">
            </div>
            <button type="submit" id="btnConfirmPay" class="btn btn-success" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-check"></i> Simpan Pembayaran
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openPayModal(trxId, invoiceNum, remaining) {
    document.getElementById('payForm').action = '/debts/' + trxId + '/pay';
    document.getElementById('payAmount').max   = remaining;
    document.getElementById('payAmount').value = remaining;
    document.getElementById('payInfo').innerHTML = `
        Nota: <strong>${invoiceNum}</strong><br>
        Sisa tagihan: <strong style="color:#ef4444;">Rp ${remaining.toLocaleString('id-ID')}</strong>
    `;
    document.getElementById('payModal').classList.add('active');
}

function handlePaySubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btnConfirmPay');
    const origText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    const data = {
        amount_paid: document.getElementById('payAmount').value,
        payment_method: document.getElementById('payMethod').value,
        notes: document.getElementById('payNotes').value,
    };

    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(r => {
        if (!r.ok) return r.json().then(e => { throw e; });
        return r.json();
    })
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            alert(res.message);
            document.getElementById('payModal').classList.remove('active');
            window.location.reload();
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (err && err.errors) {
            alert(Object.values(err.errors).flat().join('\n'));
        } else {
            alert(err.message || 'Terjadi kesalahan saat memproses pembayaran piutang.');
        }
    });
}
</script>
@endpush
@endsection
