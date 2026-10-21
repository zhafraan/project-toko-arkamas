@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-sub', 'Detail pelanggan')

@section('topbar-actions')
    <a href="{{ route('customers.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Semua pelanggan</a>
    @if(auth()->user()->isOwner())
    <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Hapus pelanggan {{ $customer->name }}? Riwayat pembeliannya tetap tersimpan. Penghapusan tercatat di riwayat penghapusan.">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger-soft"><x-icon name="trash" /> Hapus</button>
    </form>
    @endif
@endsection

@section('content')
<div class="layout-side side-left">

    <div class="stack" style="gap:16px;">
        <div class="card">
            <div class="card-body">
                <div class="row" style="margin-bottom:16px;">
                    <div class="avatar" style="width:52px;height:52px;font-size:20px;">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                    <div>
                        <div class="cell-title" style="font-size:16px;">{{ $customer->name }}</div>
                        @if($customer->customer_type === 'umkm')
                            <span class="badge badge-warning">UMKM</span>
                        @elseif($customer->customer_type === 'pengecer')
                            <span class="badge badge-info">Pengecer</span>
                        @else
                            <span class="badge">Rumah tangga</span>
                        @endif
                    </div>
                </div>
                <div class="stack" style="gap:10px;">
                    <div class="row" style="align-items:flex-start;"><x-icon name="phone" size="16" class="muted" /><span>{{ $customer->phone ?? 'Belum diisi' }}</span></div>
                    <div class="row" style="align-items:flex-start;"><x-icon name="id-card" size="16" class="muted" /><span class="mono">{{ $customer->nik ?? 'Belum diisi' }}</span></div>
                    <div class="row" style="align-items:flex-start;"><x-icon name="map-pin" size="16" class="muted" /><span>{{ $customer->address ?? 'Belum diisi' }}</span></div>
                </div>
            </div>
        </div>

        <div class="summary" style="grid-template-columns:1fr;">
            <div class="summary-item">
                <div class="summary-label">Total transaksi</div>
                <div class="summary-value" style="font-size:20px;">{{ $customer->transactions->count() }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Piutang belum lunas</div>
                <div class="summary-value {{ $totalDebt > 0 ? 'text-danger' : 'text-success' }}" style="font-size:20px;">@rupiah($totalDebt)</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Tabung dalam pinjaman</div>
                <div class="summary-value" style="font-size:20px;">{{ $activeLoanCount }} pinjaman</div>
            </div>
        </div>
    </div>

    <div class="stack" style="gap:16px;">
        @if($customer->tubeLoans->count() > 0)
        <div class="card">
            <div class="card-head"><span class="card-title"><x-icon name="package" /> Riwayat pinjaman tabung</span></div>
            <div class="table-wrap">
                <table class="table table-stack">
                    <thead><tr><th>Produk</th><th class="num">Pinjam</th><th class="num">Kembali</th><th>Status</th><th>Tgl pinjam</th></tr></thead>
                    <tbody>
                        @foreach($customer->tubeLoans as $loan)
                        <tr>
                            <td class="cell-title" data-label="Produk">{{ $loan->product->name }}</td>
                            <td class="num" data-label="Pinjam">{{ $loan->qty_borrowed }}</td>
                            <td class="num" data-label="Kembali">{{ $loan->qty_returned }}</td>
                            <td data-label="Status">
                                @if($loan->status === 'dikembalikan')
                                    <span class="badge badge-success">Dikembalikan</span>
                                @elseif($loan->status === 'sebagian_kembali')
                                    <span class="badge badge-warning">Sebagian</span>
                                @else
                                    <span class="badge badge-danger">Dipinjam</span>
                                @endif
                            </td>
                            <td class="muted" data-label="Tgl pinjam">{{ $loan->borrow_date->isoFormat('D MMM Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-head"><span class="card-title"><x-icon name="receipt" /> Riwayat transaksi</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Invoice</th><th>Tanggal</th><th class="num">Total</th><th class="num">Sisa</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($customer->transactions as $trx)
                        <tr>
                            <td class="cell-title nowrap">{{ $trx->invoice_number }}</td>
                            <td class="muted">{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                            <td class="num fw">@rupiah($trx->total_amount)</td>
                            <td class="num {{ $trx->remaining_debt > 0 ? 'text-danger fw' : 'muted' }}">@rupiah($trx->remaining_debt)</td>
                            <td>
                                @if($trx->payment_status === 'lunas')
                                    <span class="badge badge-success">Lunas</span>
                                @else
                                    <span class="badge badge-danger">Piutang</span>
                                @endif
                            </td>
                            <td><a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="btn btn-outline btn-xs"><x-icon name="receipt" size="14" /> Nota</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6"><div class="empty"><div class="empty-title">Belum ada transaksi</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
