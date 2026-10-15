@extends('layouts.app')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-receipt" style="color:var(--primary-light);margin-right:6px;"></i> Riwayat Penjualan</span>
    </div>
    <div class="card-body" style="padding:14px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <div style="flex:2;min-width:200px;">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="🔍 No. Invoice / Nama Pelanggan">
            </div>
            <div>
                <select name="payment_status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="lunas" {{ request('payment_status')=='lunas'?'selected':'' }}>Lunas</option>
                    <option value="belum_lunas" {{ request('payment_status')=='belum_lunas'?'selected':'' }}>Belum Lunas</option>
                </select>
            </div>
            <div>
                <select name="payment_method" class="form-control">
                    <option value="">Semua Metode</option>
                    <option value="cash" {{ request('payment_method')=='cash'?'selected':'' }}>Cash</option>
                    <option value="transfer" {{ request('payment_method')=='transfer'?'selected':'' }}>Transfer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('pos.history') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th>Kasir</th>
                    <th>Total</th><th>Dibayar</th><th>Sisa</th>
                    <th>Metode</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td><strong style="font-size:12px;color:var(--primary-light);">{{ $trx->invoice_number }}</strong></td>
                    <td>{{ $trx->customer?->name ?: '-' }}</td>
                    <td style="font-size:12px;">{{ $trx->user->name }}</td>
                    <td style="font-weight:700;">Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                    <td>Rp {{ number_format($trx->paid_amount,0,',','.') }}</td>
                    <td style="{{ $trx->remaining_debt > 0 ? 'color:var(--danger);font-weight:700;' : 'color:var(--text-muted);' }}">
                        Rp {{ number_format($trx->remaining_debt,0,',','.') }}
                    </td>
                    <td>
                        @if($trx->payment_method === 'cash')
                            <span class="badge badge-info">Cash</span>
                        @else
                            <span class="badge badge-gray">Transfer</span>
                        @endif
                    </td>
                    <td>
                        @if($trx->payment_status === 'lunas')
                            <span class="badge badge-success">✓ Lunas</span>
                        @else
                            <span class="badge badge-danger">Kasbon</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $trx->transaction_date->format('d/m/y H:i') }}</td>
                    <td>
                        <a href="{{ route('pos.receipt', $trx) }}" class="btn btn-outline btn-xs" target="_blank">
                            <i class="fa-solid fa-receipt"></i> Nota
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">Tidak ada transaksi ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:0 16px;">{{ $transactions->links('vendor.pagination.simple') }}</div>
</div>
@endsection
