@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('topbar-actions')
    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-cash-register"></i> Buka Kasir
    </a>
@endsection

@push('styles')
<style>

    .page-content {
        padding: 14px 20px !important;
        display: flex;
        flex-direction: column;
        flex: 1;
        height: calc(100vh - 54px);
        box-sizing: border-box;
        overflow: hidden;
        background: var(--bg);
    }
    @media (max-width: 1024px) {
        .page-content {
            height: auto;
            overflow: visible;
        }
    }

    /* Main rounded canvas container */
    .onpay-canvas {
        background: var(--card-bg);
        border-radius: 26px;
        border: 1px solid var(--border);
        box-shadow: 0 4px 20px rgba(91, 95, 151, 0.05);
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        flex: 1;
        min-height: 0;
        box-sizing: border-box;
    }
    body.dark-theme .onpay-canvas {
        background: var(--card-bg);
        border-color: var(--border);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
    }

    /* Section Headings */
    .onpay-sec-heading {
        font-size: 15px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -0.02em;
        margin-bottom: 10px;
    }

    /* Tactile Dropdown / Filter Pill (Image 3 "STRONG" Button Influence) */
    .onpay-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fafaff;
        border: 1.5px solid var(--border);
        color: var(--text);
        font-size: 11.5px;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 9999px;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 2px 0 var(--border-light);
        transition: transform .12s, box-shadow .12s, background .15s;
        position: relative;
    }
    .onpay-pill-btn:hover {
        background: #ecebe4;
        color: var(--primary);
        border-color: var(--primary-light);
        transform: translateY(-1px);
    }
    .onpay-pill-btn:active {
        transform: translateY(1px);
        box-shadow: 0 1px 0 var(--border-light);
    }
    body.dark-theme .onpay-pill-btn {
        background: #232533;
        border-color: #323547;
        color: #fffffb;
        box-shadow: 0 2px 0 #1b1c26;
    }

    /* ===================== TOP ROW: 3 SECTIONS ===================== */
    .onpay-top-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr 1fr;
        gap: 16px;
        align-items: stretch;
        flex-shrink: 0;
    }
    @media (max-width: 1200px) {
        .onpay-top-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 860px) {
        .onpay-top-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Squircle Cards Grid (Model Aksi Cepat yang Seragam & Taktil) */
    .squircle-cards-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        height: 165px;
        box-sizing: border-box;
    }
    .squircle-card {
        background: #fafaff;
        border-radius: 18px;
        padding: 14px 14px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-decoration: none;
        border: 1.5px solid var(--border);
        box-shadow: 0 3px 0 var(--border-light);
        transition: transform .12s, box-shadow .12s, background .15s;
        box-sizing: border-box;
    }
    .squircle-card:hover {
        transform: translateY(-2px);
        background: #ecebe4;
        border-color: var(--primary-light);
        box-shadow: 0 4px 0 var(--border-light), 0 8px 16px rgba(91, 95, 151, 0.08);
    }
    .squircle-card:active {
        transform: translateY(2px);
        box-shadow: 0 1px 0 var(--border-light);
    }
    body.dark-theme .squircle-card {
        background: #232533;
        border-color: #323547;
        box-shadow: 0 3px 0 #1b1c26;
    }
    body.dark-theme .squircle-card:hover {
        background: #2b2e40;
    }
    .squircle-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(91, 95, 151, 0.12);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1.5px solid rgba(91, 95, 151, 0.2);
        box-shadow: 0 2px 6px rgba(91, 95, 151, 0.08);
        transition: transform .15s, box-shadow .15s;
    }
    .squircle-icon.amber {
        background: rgba(255, 193, 69, 0.16);
        color: #d97706;
        border-color: rgba(255, 193, 69, 0.35);
        box-shadow: 0 2px 6px rgba(255, 193, 69, 0.12);
    }
    .squircle-icon.green {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        border-color: rgba(16, 185, 129, 0.3);
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1);
    }
    .squircle-icon.coral {
        background: rgba(255, 107, 108, 0.15);
        color: var(--danger);
        border-color: rgba(255, 107, 108, 0.3);
        box-shadow: 0 2px 6px rgba(255, 107, 108, 0.1);
    }
    .squircle-icon svg {
        width: 21px;
        height: 21px;
        stroke-width: 2.2px;
        display: block;
        stroke: currentColor;
    }
    .squircle-title {
        font-size: 12.5px;
        font-weight: 800;
        color: var(--text);
        margin-top: 4px;
        line-height: 1.2;
    }
    .squircle-sub {
        font-size: 10px;
        color: var(--text-light);
        margin-top: 1px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .squircle-val {
        font-size: 14.5px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -0.01em;
        margin-top: 4px;
    }

    /* Alpine.js Dropdown Menu */
    .dropdown-menu-alpine {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 6px;
        background: var(--card-bg);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
        padding: 4px;
        z-index: 50;
        min-width: 120px;
    }
    .dropdown-item-alpine {
        padding: 6px 12px;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text);
        border-radius: 8px;
        cursor: pointer;
        transition: background .15s;
    }
    .dropdown-item-alpine:hover {
        background: var(--card-soft-hover);
        color: var(--primary);
    }
    .dropdown-item-alpine.active {
        background: var(--primary);
        color: #fffffb;
    }

    /* ===================== BOTTOM ROW: 2 COLUMNS ===================== */
    .onpay-bottom-grid {
        display: grid;
        grid-template-columns: 1.45fr 1fr;
        gap: 22px;
        flex: 1;
        min-height: 0;
        align-items: stretch;
    }
    @media (max-width: 1024px) {
        .onpay-bottom-grid {
            grid-template-columns: 1fr;
            flex: unset;
        }
    }

    /* Left: Recent Transactions */
    .trx-column {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .trx-list-wrapper {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding-right: 6px;
        display: flex;
        flex-direction: column;
    }
    .trx-item-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px solid var(--border-light);
        transition: background .15s;
    }
    .trx-item-row:last-child {
        border-bottom: none;
    }
    .trx-item-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: #eef0f2;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13.5px;
        flex-shrink: 0;
    }
    body.dark-theme .trx-item-icon {
        background: #232533;
        color: var(--primary-light);
    }
    .trx-item-info {
        flex: 1;
        overflow: hidden;
        white-space: nowrap;
    }
    .trx-item-name {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text);
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .trx-item-sub {
        font-size: 10.5px;
        color: var(--text-light);
        margin-top: 1px;
    }
    .trx-item-amount {
        font-size: 13px;
        font-weight: 800;
        color: var(--text);
        white-space: nowrap;
        text-align: right;
    }
    .trx-status-pill {
        font-size: 9.5px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 9999px;
        display: inline-block;
        margin-top: 2px;
    }
    .trx-status-pill.lunas {
        background: #DCFCE7;
        color: #15803D;
    }
    .trx-status-pill.kasbon {
        background: #FEE2E2;
        color: var(--danger);
    }
    .btn-trx-more {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        text-decoration: none;
        transition: all .15s;
        flex-shrink: 0;
    }
    .btn-trx-more:hover {
        background: var(--card-soft);
        color: var(--primary);
    }

    /* Right: Available Cards (Stok Tabung Gas - Tanpa Toggle Switch) */
    .stocks-column {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .stock-cards-wrapper {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-right: 4px;
    }
    .stock-card-row {
        background: #ecebe4;
        border-radius: 17px;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform .15s, background .15s;
        border: 1px solid rgba(0, 0, 0, 0.04);
    }
    .stock-card-row:hover {
        background: #e2e1da;
        transform: translateY(-1px);
    }
    body.dark-theme .stock-card-row {
        background: #232533;
        border-color: #323547;
    }
    body.dark-theme .stock-card-row:hover {
        background: #2b2e40;
    }
    .stock-val-big {
        font-size: 16px;
        font-weight: 800;
        color: var(--text);
        line-height: 1.15;
        letter-spacing: -0.01em;
    }
    .stock-unit-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-light);
        margin-left: 2px;
    }
    .stock-name-text {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--primary);
        margin-top: 2px;
    }
    body.dark-theme .stock-name-text { color: var(--primary-light); }
    .stock-pills-row {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
    }
    .stock-pill-tag {
        font-size: 9px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 5px;
        background: #fffffb;
        color: var(--text);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    body.dark-theme .stock-pill-tag {
        background: #1b1c26;
        color: #fffffb;
    }
    .stock-pill-tag.ksg {
        background: #ffe4e4;
        color: var(--danger);
    }
    .stock-pill-tag.pjm {
        background: #fff3d6;
        color: #b45309;
    }
    .stock-price-badge {
        display: inline-flex;
        align-items: center;
        background: #fafaff;
        border: 1.5px solid var(--border);
        color: var(--text);
        font-size: 12px;
        font-weight: 800;
        padding: 5px 12px;
        border-radius: 9999px;
        box-shadow: 0 2px 0 var(--border-light);
        letter-spacing: -0.01em;
    }
    body.dark-theme .stock-price-badge {
        background: #1b1c26;
        border-color: #323547;
        color: #fffffb;
        box-shadow: 0 2px 0 #12131a;
    }
</style>
@endpush

@section('content')

<div class="onpay-canvas" x-data="{ currentFilter: 'all' }">

    {{-- ===================== 1. TOP ROW: 3 SECTIONS ===================== --}}
    <div class="onpay-top-grid">

        {{-- Col 1: Pangkalan Gas (Lebih Lebar, Model Card Aksi Cepat Berwarna) --}}
        <div>
            <div class="onpay-sec-heading">Pangkalan Gas</div>
            <div class="squircle-cards-grid">
                {{-- Squircle 1: Tabung Siap Jual --}}
                <div class="squircle-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div class="squircle-icon amber">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                            </svg>
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:9999px;background:rgba(16,185,129,0.15);color:#059669;">
                            <span style="width:5px;height:5px;border-radius:50%;background:#10B981;"></span> Toko Buka
                        </span>
                    </div>
                    <div>
                        <div class="squircle-title">Tabung Siap Jual</div>
                        <div class="squircle-sub">Stok terisi siap distribusi</div>
                    </div>
                    <div>
                        <div class="squircle-val" style="color:#d99a1b;font-size:17px;">{{ number_format($totalFilled) }} Tabung</div>
                        <div class="squircle-sub" style="font-size:9.5px;">Arkamas Gas</div>
                    </div>
                </div>

                {{-- Squircle 2: Kosong & Kasbon --}}
                <div class="squircle-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div class="squircle-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                <path d="M3 3v5h5"/>
                                <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                <path d="M16 21h5v-5"/>
                            </svg>
                        </div>
                        <span style="font-size:9.5px;font-weight:700;color:var(--text-light);background:#eef0f2;padding:2px 7px;border-radius:9999px;">
                            {{ number_format($totalBorrowed) }} Dipinjam
                        </span>
                    </div>
                    <div>
                        <div class="squircle-title">Tabung Kosong</div>
                        <div class="squircle-sub">Perputaran tabung toko</div>
                    </div>
                    <div>
                        <div class="squircle-val" style="color:var(--primary);font-size:17px;">{{ number_format($totalEmpty) }} Tabung</div>
                        <div class="squircle-sub" style="font-size:9.5px;color:var(--danger);font-weight:600;">Kasbon: Rp {{ $totalPiutang }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Col 2: Aksi Cepat (Kasir POS & Pinjam Tabung) --}}
        <div>
            <div class="onpay-sec-heading">Aksi Cepat</div>
            <div class="squircle-cards-grid">
                {{-- Squircle 1: Kasir POS --}}
                <a href="{{ route('pos.index') }}" class="squircle-card" title="Buka Kasir">
                    <div class="squircle-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                            <path d="M3 6h18"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <div>
                        <div class="squircle-title">Kasir</div>
                        <div class="squircle-sub">Penjualan langsung</div>
                    </div>
                    <div>
                        <div class="squircle-val">Rp {{ number_format($todayCash, 0, ',', '.') }}</div>
                        <div class="squircle-sub" style="font-size:9.5px;">Tunai hari ini</div>
                    </div>
                </a>

                {{-- Squircle 2: Pinjam Tabung --}}
                <a href="{{ route('loans.index') }}" class="squircle-card" title="Kelola Peminjaman">
                    <div class="squircle-icon amber">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m7.5 4.27 9 5.15"/>
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="squircle-title">Pinjam Tabung</div>
                        <div class="squircle-sub">Peminjaman aktif</div>
                    </div>
                    <div>
                        <div class="squircle-val" style="color:#d99a1b;">{{ $activeLoanCount }} Tabung</div>
                        <div class="squircle-sub" style="font-size:9.5px;">Belum kembali</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Col 3: Omset Penjualan (Dalam Bentuk Cards Saja, Tanpa Kurva) --}}
        <div>
            <div class="onpay-sec-heading">Omset Penjualan</div>
            <div class="squircle-cards-grid">
                {{-- Card 1: Omset Hari Ini --}}
                <div class="squircle-card">
                    <div class="squircle-icon green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M3 5v14a9 3 0 0 0 18 0V5"/>
                            <path d="M3 12a9 3 0 0 0 18 0"/>
                        </svg>
                    </div>
                    <div>
                        <div class="squircle-title">Omset Hari Ini</div>
                        <div class="squircle-sub">Total penerimaan penjualan</div>
                    </div>
                    <div>
                        <div class="squircle-val" style="color:#059669;font-size:16px;">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</div>
                        <div class="squircle-sub" style="font-size:9.5px;">Transaksi: {{ $todayCount ?? 0 }} sesi</div>
                    </div>
                </div>

                {{-- Card 2: Bulan Ini / Transfer --}}
                <div class="squircle-card">
                    <div class="squircle-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                            <polyline points="16 7 22 7 22 13"/>
                        </svg>
                    </div>
                    <div>
                        <div class="squircle-title">Bulan Ini</div>
                        <div class="squircle-sub">Akumulasi periode berjalan</div>
                    </div>
                    <div>
                        <div class="squircle-val" style="color:var(--primary);font-size:16px;">Rp {{ number_format($monthRevenue ?? $todayRevenue, 0, ',', '.') }}</div>
                        <div class="squircle-sub" style="font-size:9.5px;">QRIS: Rp {{ $todayTransfer }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ===================== 2. BOTTOM ROW: 2 COLUMNS ===================== --}}
    <div class="onpay-bottom-grid">

        {{-- KIRI: Transaksi Terbaru --}}
        <div class="trx-column" x-data="{ sortOpen: false, filterStatus: 'all' }">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="onpay-sec-heading" style="margin-bottom:0;">Transaksi Terbaru</div>
                <div style="position:relative;">
                    <button type="button" @click="sortOpen = !sortOpen" class="onpay-pill-btn">
                        <span>Filter: </span>
                        <strong x-text="filterStatus === 'all' ? 'Semua' : (filterStatus === 'lunas' ? 'Lunas' : 'Kasbon')">Semua</strong>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:2px;"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-menu-alpine" x-show="sortOpen" @click.away="sortOpen = false" style="display:none;" x-transition>
                        <div class="dropdown-item-alpine" :class="{ 'active': filterStatus === 'all' }" @click="filterStatus = 'all'; sortOpen = false;">Semua Transaksi</div>
                        <div class="dropdown-item-alpine" :class="{ 'active': filterStatus === 'lunas' }" @click="filterStatus = 'lunas'; sortOpen = false;">Hanya Lunas</div>
                        <div class="dropdown-item-alpine" :class="{ 'active': filterStatus === 'belum_lunas' }" @click="filterStatus = 'belum_lunas'; sortOpen = false;">Hanya Kasbon</div>
                    </div>
                </div>
            </div>

            <div class="trx-list-wrapper">
                @forelse($recentTransactions as $index => $trx)
                    <div class="trx-item-row" x-show="filterStatus === 'all' || filterStatus === '{{ $trx->payment_status }}'">
                        <div class="trx-item-icon">
                            @if($index % 3 === 0)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            @elseif($index % 3 === 1)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                            @endif
                        </div>
                        <div class="trx-item-info">
                            <div class="trx-item-name">{{ $trx->customer?->name ?: '-' }}</div>
                            <div class="trx-item-sub">{{ $trx->transaction_date->format('d M Y, H:i') }} • {{ $trx->invoice_number }}</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="trx-item-amount">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</div>
                            @if($trx->payment_status === 'lunas')
                                <span class="trx-status-pill lunas">Lunas</span>
                            @else
                                <span class="trx-status-pill kasbon">Kasbon</span>
                            @endif
                        </div>
                        <a href="{{ route('pos.receipt', $trx) }}" class="btn-trx-more" title="Lihat Struk">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                        </a>
                    </div>
                @empty
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px 10px;color:var(--text-light);">
                        <div style="width:54px;height:54px;border-radius:16px;background:#fafaff;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--primary);margin-bottom:12px;box-shadow:0 3px 0 var(--border-light);">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </div>
                        <div style="font-size:13.5px;font-weight:700;color:var(--text);">Belum Ada Riwayat Transaksi</div>
                        <div style="font-size:11px;margin-top:2px;max-width:260px;">Transaksi kasir yang diproses akan otomatis tercatat dan tersusun rapi di sini</div>
                        <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm" style="margin-top:14px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Buka Kasir Sekarang
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- KANAN: Stok Tabung Gas (Tanpa Toggle Switch Sesuai Permintaan) --}}
        <div class="stocks-column">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="onpay-sec-heading" style="margin-bottom:0;">Stok Tabung Gas</div>
                <a href="{{ route('inventory.index') }}" style="font-size:11.5px;color:var(--primary);font-weight:700;text-decoration:none;" title="Kelola Inventaris">
                    Lihat Semua
                </a>
            </div>

            <div class="stock-cards-wrapper">
                @foreach($stocks as $product)
                    <div class="stock-card-row">
                        <div>
                            <div class="stock-val-big">
                                {{ number_format($product->stock_filled) }} <span class="stock-unit-label">TABUNG</span>
                            </div>
                            <div class="stock-name-text">{{ $product->name }}</div>
                            <div class="stock-pills-row">
                                <span class="stock-pill-tag">Isi: {{ $product->stock_filled }}</span>
                                <span class="stock-pill-tag ksg">Kosong: {{ $product->stock_empty }}</span>
                                <span class="stock-pill-tag pjm">Pinjam: {{ $product->stock_borrowed }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="stock-price-badge">
                                Rp {{ number_format($product->price_refill, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

@endsection
