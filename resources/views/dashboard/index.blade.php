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


{{-- Ringkasan statistik (KPI cards) --}}
@php
    $isOwner = auth()->user()->isOwner();
    $statCount = 5 + ($isOwner ? 1 : 0);
    $statClass = in_array($statCount, [3, 4, 5, 6]) ? 'stat-grid-' . $statCount : '';
@endphp
<div class="stat-grid {{ $statClass }}">
    @if($isOwner)
    <div class="stat-card stat-card-dark">
        <div class="stat-card-head">
            <span class="stat-card-title">Total Omzet Hari Ini</span>
        </div>
        <div class="stat-card-value">@rupiah($todayRevenue)</div>
        <div class="stat-card-note"><span class="stat-trend up">↗</span> {{ $todayCount }} transaksi hari ini</div>
    </div>
    @endif

    <div class="stat-card">
        <div class="stat-card-head">
            <span class="stat-card-title">Gas Terjual Hari Ini</span>
        </div>
        <div class="stat-card-value">{{ number_format($todayGasQty) }} tabung</div>
        <div class="stat-card-note"><span class="stat-trend up">↗</span> Terjual {{ number_format($todayGasQty) }} tabung isi</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-head">
            <span class="stat-card-title">Telur Terjual Hari Ini</span>
        </div>
        <div class="stat-card-value">{{ number_format($todayTelurQty) }} {{ $telurUnitLabel }}</div>
        <div class="stat-card-note"><span class="stat-trend up">↗</span> Terjual {{ number_format($todayTelurQty) }} {{ $telurUnitLabel }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-head">
            <span class="stat-card-title">Penerimaan Hari Ini</span>
        </div>
        <div class="stat-card-value">@rupiah($todayTotalRevenue)</div>
        <div class="stat-card-note"><span class="nowrap">Gas @rupiah($todayGasRevenue)</span> · <span class="nowrap">Telur @rupiah($todayTelurRevenue)</span></div>
    </div>

    <a href="{{ route('debts.index') }}" class="stat-card">
        <div class="stat-card-head">
            <span class="stat-card-title">Piutang Belum Lunas</span>
        </div>
        <div class="stat-card-value {{ $totalPiutang > 0 ? 'danger' : '' }}">@rupiah($totalPiutang)</div>
        <div class="stat-card-note"><span class="stat-trend {{ $totalPiutang > 0 ? 'down' : 'up' }}">{{ $totalPiutang > 0 ? '↘' : '✓' }}</span> {{ $countPiutang }} nota belum lunas</div>
    </a>

    <a href="{{ route('loans.index') }}" class="stat-card">
        <div class="stat-card-head">
            <span class="stat-card-title">Tabung Dalam Pinjaman</span>
        </div>
        <div class="stat-card-value">{{ number_format($loanTubes) }} tabung</div>
        <div class="stat-card-note"><span class="stat-trend up">↗</span> Dipinjam {{ $loanCustomers }} pelanggan</div>
    </a>
</div>

<div class="layout-side mt-2">

    <div class="stack" style="gap:16px;">

        {{-- Transaksi terbaru (Gaya Tabel Skillset) --}}
        <div class="card">
            <div class="card-head">
                <span class="card-title">{{ auth()->user()->isOwner() ? 'Transaksi Terbaru' : 'Transaksi Hari Ini' }}</span>
                <div class="row" style="gap:8px;">
                    <a href="{{ route('pos.history') }}" class="card-action-btn" title="Lihat semua transaksi" aria-label="Lihat semua">
                        <x-icon name="arrow-up-right" size="14" />
                    </a>
                </div>
            </div>
            @if($recentTransactions->count() > 0)
            <ul class="trx-list">
                @foreach($recentTransactions as $trx)
                <li>
                    <a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="trx-item" title="Buka nota">
                        <div class="avatar" style="width:38px;height:38px;border-radius:12px;background:var(--surface-2);color:var(--text);font-size:12px;border:1px solid var(--border);flex-shrink:0;">
                            <x-icon name="cart" size="16" />
                        </div>
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
                                    <span class="badge badge-danger badge-dot">Piutang</span>
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
                <div class="empty-title">Belum ada transaksi.</div>
                <div>Transaksi yang dicatat kasir akan muncul di sini.</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Kolom kanan: Peringatan & Stok tabung --}}
    <div class="stack" style="gap:16px;">
        {{-- Peringatan stok menipis (khusus owner) --}}
        @if(auth()->user()->isOwner() && $lowStock->count() > 0)
        <div class="card">
            <div class="card-head">
                <span class="card-title text-warning">
                    <x-icon name="alert" size="17" />
                    Peringatan stok
                </span>
                <a href="{{ route('inventory.index') }}" class="text-sm fw">Kelola stok</a>
            </div>
            <ul style="margin:0;padding:0;list-style:none;">
                @foreach($lowStock as $lp)
                <li style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 18px;border-bottom:1px solid var(--border);">
                    <div>
                        <div class="cell-title">{{ $lp->name }}</div>
                        <div class="cell-sub">Sisa stok: <strong>{{ $lp->stock_filled }} {{ $lp->getUnitLabel() }}</strong></div>
                    </div>
                    @if($lp->stock_filled <= 0)
                        <span class="badge badge-danger">Habis</span>
                    @else
                        <span class="badge badge-warning">Menipis</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-head">
                <span class="card-title"><x-icon name="stock" /> Stok</span>
                <a href="{{ route('inventory.index') }}" class="text-sm fw">{{ auth()->user()->isOwner() ? 'Kelola stok' : 'Stok Gas' }}</a>
            </div>
            <div class="card-body" style="border-bottom:1px solid var(--border);padding:14px 18px;">
                <div class="legend">
                    <span><i class="dot dot-filled"></i> Isi <strong class="num">{{ number_format($totalGasFilled) }}</strong></span>
                    <span><i class="dot dot-empty"></i> Kosong <strong class="num">{{ number_format($totalGasEmpty) }}</strong></span>
                    <span><i class="dot dot-borrowed"></i> Dipinjam <strong class="num">{{ number_format($totalGasBorrowed) }}</strong></span>
                </div>
            </div>
            @forelse($gasStocks as $product)
            <div class="stock-item">
                <div class="row between" style="margin-bottom:8px;">
                    <div class="grow">
                        <div class="cell-title truncate">{{ $product->name }}</div>
                        <div class="cell-sub">@rupiah($product->price_sell) / tabung</div>
                    </div>
                    @if($product->stock_filled <= 0)
                        <span class="badge badge-danger">Habis</span>
                    @elseif($product->stock_filled <= $product->min_stock)
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

        @if($telurStocks->count() > 0)
        <div class="card">
            <div class="card-head">
                <span class="card-title"><x-icon name="stock" /> Stok telur</span>
                <a href="{{ route('inventory.index') }}" class="text-sm fw">{{ auth()->user()->isOwner() ? 'Kelola stok' : 'Stok Telur' }}</a>
            </div>
            <div class="card-body" style="border-bottom:1px solid var(--border);padding:14px 18px;">
                <div class="legend">
                    <span><i class="dot dot-filled"></i> Isi <strong class="num">{{ number_format($totalTelurFilled) }}</strong> {{ $telurUnitLabel }}</span>
                </div>
            </div>
            @forelse($telurStocks as $product)
            <div class="stock-item">
                <div class="row between" style="margin-bottom:8px;">
                    <div class="grow">
                        <div class="cell-title truncate">{{ $product->name }}</div>
                        <div class="cell-sub">@rupiah($product->price_sell) / {{ $product->getUnitLabel() }}</div>
                    </div>
                    @if($product->stock_filled <= 0)
                        <span class="badge badge-danger">Habis</span>
                    @elseif($product->stock_filled <= $product->min_stock)
                        <span class="badge badge-warning">Menipis</span>
                    @endif
                </div>
                <div class="legend" style="margin-top:8px;">
                    <span><i class="dot dot-filled"></i> Isi {{ $product->stock_filled }} {{ $product->getUnitLabel() }}</span>
                </div>
            </div>
            @empty
            <div class="empty">
                <div class="empty-title">Belum ada produk aktif</div>
            </div>
            @endforelse
        </div>
        @endif
    </div>
</div>

@endsection
