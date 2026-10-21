@extends('layouts.app')
@section('title', 'Riwayat Audit')
@section('page-title', 'Riwayat Audit')
@section('page-sub', 'Catatan penghapusan data dan penyesuaian stok oleh pemilik (jejak audit)')

@section('content')
@php $tab = $tab ?? 'deletion'; @endphp

<div class="row mb-2" style="gap:8px; flex-wrap:wrap;">
    <a href="{{ route('deletion-logs.index') }}" class="btn btn-sm {{ $tab === 'deletion' ? 'btn-primary' : 'btn-outline' }}">
        <x-icon name="trash" size="15" /> Penghapusan
    </a>
    <a href="{{ route('deletion-logs.adjustments') }}" class="btn btn-sm {{ $tab === 'adjustment' ? 'btn-primary' : 'btn-outline' }}">
        <x-icon name="sliders" size="15" /> Penyesuaian Stok
    </a>
</div>

@if($tab === 'adjustment')
<div class="card">
    <form method="GET" action="{{ route('deletion-logs.adjustments') }}" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari alasan, produk, atau petugas…">
        </div>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search'))
            <a href="{{ route('deletion-logs.adjustments') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Waktu</th><th>Produk</th><th>Perubahan</th><th>Alasan</th><th>Petugas</th></tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                <tr>
                    <td class="muted nowrap">{{ $adj->created_at->format('d/m/y H:i') }}</td>
                    <td>
                        <div class="cell-title">{{ $adj->product?->name ?? '-' }}</div>
                        <div class="cell-sub">SKU {{ $adj->product?->sku ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="cell-title">{{ $adj->diff_label }}</div>
                        <div class="cell-sub">
                            isi {{ $adj->filled_before }}&#8594;{{ $adj->filled_after }} ·
                            kosong {{ $adj->empty_before }}&#8594;{{ $adj->empty_after }} ·
                            dipinjam {{ $adj->borrowed_before }}&#8594;{{ $adj->borrowed_after }}
                        </div>
                    </td>
                    <td>{{ $adj->reason }}</td>
                    <td>{{ $adj->user?->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <div class="empty">
                        <div class="empty-icon"><x-icon name="sliders" size="24" /></div>
                        <div class="empty-title">Belum ada penyesuaian stok</div>
                        <div>Setiap penyesuaian stok dari pemilik akan tercatat di sini beserta alasannya.</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $adjustments->links('vendor.pagination.simple') }}
</div>
@else
<div class="card">
    <form method="GET" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari ringkasan atau nama penghapus…">
        </div>
        <select name="type" class="select" onchange="this.form.submit()" aria-label="Jenis data">
            <option value="">Semua jenis</option>
            <option value="Transaction" {{ request('type') === 'Transaction' ? 'selected' : '' }}>Nota</option>
            <option value="AgentDelivery" {{ request('type') === 'AgentDelivery' ? 'selected' : '' }}>Penerimaan Agen</option>
            <option value="Customer" {{ request('type') === 'Customer' ? 'selected' : '' }}>Pelanggan</option>
            <option value="Product" {{ request('type') === 'Product' ? 'selected' : '' }}>Produk</option>
            <option value="User" {{ request('type') === 'User' ? 'selected' : '' }}>Pengguna</option>
        </select>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search') || request('type'))
            <a href="{{ route('deletion-logs.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Waktu</th><th>Jenis</th><th>Data</th><th>Dihapus oleh</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="muted nowrap">{{ $log->created_at->format('d/m/y H:i') }}</td>
                    <td>
                        @if($log->deletable_type === 'Transaction')
                            <span class="badge badge-danger">Nota</span>
                        @elseif($log->deletable_type === 'AgentDelivery')
                            <span class="badge badge-warning">Penerimaan</span>
                        @elseif($log->deletable_type === 'Product')
                            <span class="badge badge-info">Produk</span>
                        @elseif($log->deletable_type === 'User')
                            <span class="badge badge-info">Pengguna</span>
                        @else
                            <span class="badge badge-info">Pelanggan</span>
                        @endif
                    </td>
                    <td>
                        <div class="cell-title">{{ $log->summary }}</div>
                        @if(!empty($log->data['items']))
                        <div class="cell-sub">
                            @foreach($log->data['items'] as $item)
                                {{ $item['qty'] }}× {{ $item['product'] }}{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </div>
                        @elseif($log->deletable_type === 'Product' && !empty($log->data['sku']))
                        <div class="cell-sub">SKU {{ $log->data['sku'] }} · stok isi {{ $log->data['stock_filled'] ?? 0 }} · stok kosong {{ $log->data['stock_empty'] ?? 0 }}</div>
                        @elseif($log->deletable_type === 'User' && !empty($log->data['email']))
                        <div class="cell-sub">{{ $log->data['email'] }} · peran {{ $log->data['role'] ?? '-' }}</div>
                        @elseif(!empty($log->data['product']))
                        <div class="cell-sub">{{ $log->data['product'] }} · {{ $log->data['delivery_date'] ?? '' }}</div>
                        @elseif(!empty($log->data['phone']) || !empty($log->data['address']))
                        <div class="cell-sub">{{ $log->data['phone'] ?? '-' }}{{ !empty($log->data['address']) ? ' · ' . $log->data['address'] : '' }}</div>
                        @endif
                    </td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="4">
                    <div class="empty">
                        <div class="empty-icon"><x-icon name="trash" size="24" /></div>
                        <div class="empty-title">Belum ada data yang dihapus</div>
                        <div>Riwayat penghapusan nota, penerimaan agen, produk, dan pelanggan akan muncul di sini.</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links('vendor.pagination.simple') }}
</div>
@endif
@endsection
