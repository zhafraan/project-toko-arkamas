@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', 'Detail Pelanggan')

@section('content')
<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start;">

    {{-- Profile Card --}}
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));padding:24px;text-align:center;">
                <div style="width:64px;height:64px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:26px;font-weight:800;color:#fff;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <div style="color:#fff;font-size:18px;font-weight:800;">{{ $customer->name }}</div>
                @php $types=['rumah_tangga'=>['Rmh Tangga','#3b82f6'],'umkm'=>['UMKM','#f59e0b'],'pengecer'=>['Pengecer','#f97316']]; @endphp
                <span style="background:{{ $types[$customer->customer_type][1] }};color:#fff;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;margin-top:6px;display:inline-block;">
                    {{ $types[$customer->customer_type][0] }}
                </span>
            </div>
            <div class="card-body" style="padding:16px;">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <i class="fa-solid fa-phone" style="color:var(--text-muted);width:16px;"></i>
                        <span>{{ $customer->phone ?? 'Belum diisi' }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                        <i class="fa-solid fa-id-card" style="color:var(--text-muted);width:16px;"></i>
                        <span style="font-family:monospace;">{{ $customer->nik ?? 'Belum diisi' }}</span>
                    </div>
                    <div style="display:flex;align-items:start;gap:10px;font-size:13px;">
                        <i class="fa-solid fa-location-dot" style="color:var(--text-muted);width:16px;margin-top:2px;"></i>
                        <span>{{ $customer->address ?? 'Belum diisi' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="card">
            <div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f0f9ff;border-radius:8px;">
                    <span style="font-size:12px;color:var(--text-muted);">Total Transaksi</span>
                    <strong>{{ $customer->transactions->count() }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:{{ $totalDebt > 0 ? '#fee2e2' : '#f0fdf4' }};border-radius:8px;">
                    <span style="font-size:12px;color:var(--text-muted);">Total Piutang</span>
                    <strong style="color:{{ $totalDebt > 0 ? 'var(--danger)' : 'var(--success)' }};">
                        Rp {{ number_format($totalDebt, 0, ',', '.') }}
                    </strong>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:{{ $activeLoanCount > 0 ? '#ffedd5' : '#f0fdf4' }};border-radius:8px;">
                    <span style="font-size:12px;color:var(--text-muted);">Pinjaman Aktif</span>
                    <strong style="color:{{ $activeLoanCount > 0 ? 'var(--warning)' : 'var(--success)' }};">
                        {{ $activeLoanCount }} tabung
                    </strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions & Loans --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Tube Loans --}}
        @if($customer->tubeLoans->count() > 0)
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-box" style="color:var(--accent);margin-right:6px;"></i> Riwayat Pinjaman Tabung</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Produk</th><th>Pinjam</th><th>Kembali</th><th>Deposit</th><th>Status</th><th>Tgl Pinjam</th></tr>
                    </thead>
                    <tbody>
                        @foreach($customer->tubeLoans as $loan)
                        <tr>
                            <td style="font-weight:600;">{{ $loan->product->name }}</td>
                            <td>{{ $loan->qty_borrowed }}</td>
                            <td>{{ $loan->qty_returned }}</td>
                            <td>Rp {{ number_format($loan->deposit_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($loan->status === 'dikembalikan')
                                    <span class="badge badge-success">Dikembalikan</span>
                                @elseif($loan->status === 'sebagian_kembali')
                                    <span class="badge badge-warning">Sebagian</span>
                                @else
                                    <span class="badge badge-danger">Dipinjam</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">{{ $loan->borrow_date->isoFormat('D MMM Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Transaction History --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-receipt" style="color:var(--primary-light);margin-right:6px;"></i> Riwayat Transaksi</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Invoice</th><th>Total</th><th>Sisa</th><th>Status</th><th>Tanggal</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($customer->transactions->sortByDesc('transaction_date') as $trx)
                        <tr>
                            <td style="font-size:12px;font-weight:600;color:var(--primary-light);">{{ $trx->invoice_number }}</td>
                            <td style="font-weight:700;">Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                            <td style="{{ $trx->remaining_debt > 0 ? 'color:var(--danger);font-weight:700;' : '' }}">
                                Rp {{ number_format($trx->remaining_debt,0,',','.') }}
                            </td>
                            <td>
                                @if($trx->payment_status === 'lunas')
                                    <span class="badge badge-success">Lunas</span>
                                @else
                                    <span class="badge badge-danger">Kasbon</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                            <td>
                                <a href="{{ route('pos.receipt', $trx) }}" class="btn btn-outline btn-xs" target="_blank">Nota</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">Belum ada transaksi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
