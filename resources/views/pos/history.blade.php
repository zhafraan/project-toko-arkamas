@extends('layouts.app')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')
@section('page-sub', $onlyToday ? 'Transaksi hari ini' : 'Semua transaksi penjualan')

@section('content')
@if($onlyToday)
<div class="alert alert-info" role="status">
    <x-icon name="info" />
    <div class="alert-body">Akun kasir hanya dapat melihat transaksi hari ini. Riwayat lengkap dapat diakses oleh pemilik.</div>
</div>
@endif

<div class="card">
    <form method="GET" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari no. invoice atau nama pelanggan…">
        </div>
        <select name="payment_status" class="select" onchange="this.form.submit()" aria-label="Status pembayaran">
            <option value="">Semua status</option>
            <option value="lunas" {{ request('payment_status') === 'lunas' ? 'selected' : '' }}>Lunas</option>
            <option value="belum_lunas" {{ request('payment_status') === 'belum_lunas' ? 'selected' : '' }}>Piutang</option>
        </select>
        <select name="payment_method" class="select" onchange="this.form.submit()" aria-label="Metode pembayaran">
            <option value="">Semua metode</option>
            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Tunai</option>
            <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
        </select>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search') || request('payment_status') || request('payment_method'))
            <a href="{{ route('pos.history') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th>Tanggal</th>
                    <th class="num">Total</th><th class="num">Sisa</th><th>Metode</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td data-label="Invoice">
                        <div class="cell-title nowrap">{{ $trx->invoice_number }}</div>
                        <div class="cell-sub">Kasir: {{ $trx->user->name }}</div>
                    </td>
                    <td data-label="Pelanggan">{{ $trx->customer?->name ?? 'Umum' }}</td>
                    <td class="muted nowrap" data-label="Tanggal">{{ $trx->transaction_date->format('d/m/y H:i') }}</td>
                    <td class="num fw" data-label="Total">@rupiah($trx->total_amount)</td>
                    <td class="num {{ $trx->remaining_debt > 0 ? 'text-danger fw' : 'muted' }}" data-label="Sisa">@rupiah($trx->remaining_debt)</td>
                    <td data-label="Metode">
                        @if($trx->payment_method === 'cash')
                            <span class="badge">Tunai</span>
                        @else
                            <span class="badge badge-info">Transfer</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        @if($trx->payment_status === 'lunas')
                            <span class="badge badge-success badge-dot">Lunas</span>
                        @else
                            <span class="badge badge-danger badge-dot">Piutang</span>
                        @endif
                    </td>
                    <td data-label="Aksi"><div class="actions">
                        <a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="btn btn-outline btn-xs"><x-icon name="receipt" size="14" /> Nota</a>
                        @if(auth()->user()->isOwner())
                        <form method="POST" action="{{ route('pos.destroy', $trx) }}" data-confirm="Hapus nota {{ $trx->invoice_number }}? Stok tabung akan dikembalikan dan tindakan ini tercatat di Riwayat Audit.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus nota" aria-label="Hapus nota"><x-icon name="trash" size="15" /></button>
                        </form>
                        @endif
                    </div></td>
                </tr>
                @empty
                <tr><td colspan="8">
                    <div class="empty">
                        <div class="empty-icon"><x-icon name="receipt" size="24" /></div>
                        <div class="empty-title">Tidak ada transaksi</div>
                        <div>Coba ubah kata kunci atau filter.</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links('vendor.pagination.simple') }}
</div>
@endsection
