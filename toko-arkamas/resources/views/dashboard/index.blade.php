@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', today()->translatedFormat('l, j F Y'))

@section('topbar-actions')
    <a href="{{ route('pos.index') }}" class="btn btn-primary">
        <x-icon name="cart" /> Buka Kasir
    </a>
@endsection

@section('content')

@if($lowStock->count() > 0)
<div class="alert alert-warning" role="status">
    <x-icon name="alert" />
    <div class="alert-body">
        <strong>Stok tabung isi menipis:</strong>
        @foreach($lowStock as $p)
            {{ $p->name }} ({{ $p->stock_filled }}){{ $loop->last ? '' : ', ' }}
        @endforeach
    </div>
</div>
@endif

{{-- Ringkasan hari ini --}}
<div class="summary">
    <div class="summary-item">
        <div class="summary-label"><x-icon name="trending-up" size="16" /> Omzet hari ini</div>
        <div class="summary-value">@rupiah($todayRevenue)</div>
        <div class="summary-note">{{ $todayCount }} transaksi</div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><x-icon name="banknote" size="16" /> Uang masuk hari ini</div>
        <div class="summary-value">@rupiah($todayCash + $todayTransfer)</div>
        <div class="summary-note"><span class="nowrap">Tunai @rupiah($todayCash)</span> · <span class="nowrap">Transfer @rupiah($todayTransfer)</span></div>
    </div>
    <a href="{{ route('debts.index') }}" class="summary-item">
        <div class="summary-label"><x-icon name="wallet" size="16" /> Piutang belum lunas</div>
        <div class="summary-value {{ $totalPiutang > 0 ? 'text-danger' : '' }}">@rupiah($totalPiutang)</div>
        <div class="summary-note">{{ $countPiutang }} nota</div>
    </a>
    <a href="{{ route('loans.index') }}" class="summary-item">
        <div class="summary-label"><x-icon name="package" size="16" /> Tabung dipinjam</div>
        <div class="summary-value">{{ number_format($loanTubes) }} tabung</div>
        <div class="summary-note">{{ $loanCustomers }} pelanggan belum mengembalikan</div>
    </a>
    @if($monthRevenue !== null)
    <div class="summary-item">
        <div class="summary-label"><x-icon name="bar-chart" size="16" /> Omzet bulan ini</div>
        <div class="summary-value">@rupiah($monthRevenue)</div>
        <div class="summary-note">{{ now()->translatedFormat('F Y') }}</div>
    </div>
    @endif
</div>

<div class="layout-side mt-2">

    <div class="stack" style="gap:16px;">

        {{-- Grafik 7 hari --}}
        <div class="card">
            <div class="card-head">
                <span class="card-title"><x-icon name="bar-chart" /> Omzet 7 hari terakhir</span>
            </div>
            <div class="card-body">
                <div class="bars">
                    @foreach($chartData as $bar)
                    <div class="bar-col {{ $bar['isToday'] ? 'today' : '' }}" title="{{ $bar['date'] }}: @rupiah($bar['revenue']) ({{ $bar['count'] }} transaksi)">
                        <div class="bar-val">{{ $bar['short'] }}</div>
                        <div class="bar-track"><div class="bar {{ $bar['isToday'] ? 'today' : '' }}" style="height: {{ $bar['height'] }}%;"></div></div>
                        <div class="bar-day">{{ $bar['day'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Transaksi terbaru --}}
        <div class="card">
            <div class="card-head">
                <span class="card-title"><x-icon name="receipt" /> {{ auth()->user()->isOwner() ? 'Transaksi terbaru' : 'Transaksi hari ini' }}</span>
                <a href="{{ route('pos.history') }}" class="text-sm fw">Lihat semua</a>
            </div>
            @if($recentTransactions->count() > 0)
            <ul class="trx-list">
                @foreach($recentTransactions as $trx)
                <li>
                    <a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="trx-item" title="Buka nota">
                        <div class="trx-main">
                            <div class="cell-title truncate">{{ $trx->customer?->name ?? 'Pelanggan umum' }}</div>
                            <div class="cell-sub">{{ $trx->transaction_date->format('d M, H:i') }} · {{ $trx->invoice_number }}</div>
                        </div>
                        <div>
                            <div class="trx-amount">@rupiah($trx->total_amount)</div>
                            <div class="right mt-1" style="margin-top:2px;">
                                @if($trx->payment_status === 'lunas')
                                    <span class="badge badge-success badge-dot">Lunas</span>
                                @else
                                    <span class="badge badge-danger badge-dot">Kasbon</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
            @else
            <div class="empty">
                <div class="empty-icon"><x-icon name="inbox" size="24" /></div>
                <div class="empty-title">Belum ada transaksi</div>
                <div>Transaksi dari kasir akan tampil di sini.</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Stok tabung --}}
    <div class="card">
        <div class="card-head">
            <span class="card-title"><x-icon name="flame" /> Stok tabung</span>
            <a href="{{ route('inventory.index') }}" class="text-sm fw">Lihat semua</a>
        </div>
        <div class="card-body" style="border-bottom:1px solid var(--border);padding:14px 18px;">
            <div class="legend">
                <span><i class="dot dot-filled"></i> Isi <strong class="num">{{ number_format($totalFilled) }}</strong></span>
                <span><i class="dot dot-empty"></i> Kosong <strong class="num">{{ number_format($totalEmpty) }}</strong></span>
                <span><i class="dot dot-borrowed"></i> Dipinjam <strong class="num">{{ number_format($totalBorrowed) }}</strong></span>
            </div>
        </div>
        @forelse($stocks as $product)
        <div class="stock-item">
            <div class="row between" style="margin-bottom:8px;">
                <div class="grow">
                    <div class="cell-title truncate">{{ $product->name }}</div>
                    <div class="cell-sub">@rupiah($product->price_sell) / tabung</div>
                </div>
                @if($product->stock_filled <= 0)
                    <span class="badge badge-danger">Habis</span>
                @elseif($product->stock_filled <= $lowStockLimit)
                    <span class="badge badge-warning">Menipis</span>
                @endif
            </div>
            <div class="stockbar" title="Isi {{ $product->stock_filled }}, kosong {{ $product->stock_empty }}, dipinjam {{ $product->stock_borrowed }}">
                <span class="s-filled" style="width: {{ $product->total_stock > 0 ? round($product->stock_filled / $product->total_stock * 100) : 0 }}%;"></span>
                <span class="s-empty" style="width: {{ $product->total_stock > 0 ? round($product->stock_empty / $product->total_stock * 100) : 0 }}%;"></span>
                <span class="s-borrowed" style="width: {{ $product->total_stock > 0 ? round($product->stock_borrowed / $product->total_stock * 100) : 0 }}%;"></span>
            </div>
            <div class="legend" style="margin-top:8px;">
                <span><i class="dot dot-filled"></i> Isi {{ $product->stock_filled }}</span>
                <span><i class="dot dot-empty"></i> Kosong {{ $product->stock_empty }}</span>
                <span><i class="dot dot-borrowed"></i> Dipinjam {{ $product->stock_borrowed }}</span>
            </div>
        </div>
        @empty
        <div class="empty">
            <div class="empty-title">Belum ada produk aktif</div>
        </div>
        @endforelse
    </div>
</div>

@endsection
