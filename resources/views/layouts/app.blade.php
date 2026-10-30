<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Toko Arkamas') · Toko Arkamas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Jost:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

<div class="sidebar-backdrop" data-sidebar-backdrop></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <svg viewBox="0 0 72 72" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="agBrand" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#4F7F61"/><stop offset="100%" stop-color="#2E5239"/></linearGradient>
                <linearGradient id="agGold" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#F2B66B"/><stop offset="100%" stop-color="#D9822B"/></linearGradient>
            </defs>
            <rect width="72" height="72" rx="18" fill="url(#agBrand)"/>
            <text x="34" y="48" text-anchor="middle" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-weight="900" font-size="32" letter-spacing="-1">
                <tspan fill="#FFFFFB">A</tspan><tspan fill="url(#agGold)" dx="1">G</tspan>
            </text>
            <circle cx="56" cy="23" r="4" fill="url(#agGold)"/>
        </svg>
        <div class="brand-text">
            <div class="brand-name">Toko Arkamas</div>
            <div class="brand-sub">Penjualan Gas & Telur</div>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Menu utama">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <x-icon name="dashboard" /><span class="label">Dashboard</span>
        </a>
        <a href="{{ route('statistics.index') }}" class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" title="Statistik penjualan">
            <x-icon name="bar-chart" /><span class="label">Statistik Penjualan</span>
        </a>

        <div class="nav-group">Penjualan</div>
        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}" title="Kasir">
            <x-icon name="cart" /><span class="label">Kasir</span>
        </a>
        <a href="{{ route('pos.history') }}" class="nav-link {{ request()->routeIs('pos.history') ? 'active' : '' }}" title="Riwayat transaksi">
            <x-icon name="history" /><span class="label">Riwayat Transaksi</span>
        </a>

        <div class="nav-group">Operasional</div>
        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Pelanggan">
            <x-icon name="users" /><span class="label">Pelanggan</span>
        </a>
        <a href="{{ route('loans.index') }}" class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}" title="Pinjaman tabung gas">
            <x-icon name="package" /><span class="label">Pinjaman Tabung</span>
        </a>
        <a href="{{ route('debts.index') }}" class="nav-link {{ request()->routeIs('debts.*') ? 'active' : '' }}" title="Piutang">
            <x-icon name="wallet" /><span class="label">Piutang</span>
        </a>
        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}" title="Stok gas dan telur">
            <x-icon name="stock" /><span class="label">{{ auth()->user()->isOwner() ? 'Produk & Stok' : 'Stok' }}</span>
        </a>
        @if(auth()->user()->isOwner())
        <a href="{{ route('inventory.goodsReceipts') }}" class="nav-link {{ request()->routeIs('inventory.goodsReceipts') ? 'active' : '' }}" title="Stok masuk telur">
            <x-icon name="truck" /><span class="label">Stok Masuk Telur</span>
        </a>
        @endif
        <a href="{{ route('inventory.deliveries') }}" class="nav-link {{ request()->routeIs('inventory.deliveries') ? 'active' : '' }}" title="Penerimaan dari agen gas">
            <x-icon name="truck" /><span class="label">Penerimaan dari Agen</span>
        </a>

        @if(auth()->user()->isOwner())
        <div class="nav-group">Manajemen</div>
        <a href="{{ route('deletion-logs.index') }}" class="nav-link {{ request()->routeIs('deletion-logs.*') ? 'active' : '' }}" title="Riwayat audit: penghapusan & penyesuaian stok">
            <x-icon name="trash" /><span class="label">Riwayat Audit</span>
        </a>
        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Laporan">
            <x-icon name="report" /><span class="label">Laporan</span>
        </a>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Akun pengguna">
            <x-icon name="settings" /><span class="label">Akun Pengguna</span>
        </a>
        @endif
    </nav>

    <div class="sidebar-user">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <div class="user-meta">
            <div class="user-name truncate">{{ auth()->user()->name }}</div>
            <div class="user-role">{{ auth()->user()->isOwner() ? 'Pemilik' : 'Kasir' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="Keluar" aria-label="Keluar"><x-icon name="log-out" /></button>
        </form>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <button type="button" class="btn btn-ghost btn-icon" onclick="toggleSidebar()" title="Ciutkan atau perluas menu" aria-label="Menu">
            <x-icon name="panel-left" size="20" />
        </button>
        <div class="topbar-title">
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            @hasSection('page-sub')
            <div class="page-sub">@yield('page-sub')</div>
            @endif
        </div>
        <div class="topbar-actions">
            @yield('topbar-actions')
        </div>
    </header>

    <main class="page">
        @if(session('success'))
        <div class="alert alert-success" role="status">
            <x-icon name="check-circle" />
            <div class="alert-body">{{ session('success') }}</div>
            <button type="button" class="alert-close" data-dismiss aria-label="Tutup"><x-icon name="x" size="16" /></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-error" role="alert">
            <x-icon name="x-circle" />
            <div class="alert-body">{{ session('error') }}</div>
            <button type="button" class="alert-close" data-dismiss aria-label="Tutup"><x-icon name="x" size="16" /></button>
        </div>
        @endif

        @if(session('info'))
        <div class="alert alert-info" role="status">
            <x-icon name="info" />
            <div class="alert-body">{{ session('info') }}</div>
            <button type="button" class="alert-close" data-dismiss aria-label="Tutup"><x-icon name="x" size="16" /></button>
        </div>
        @endif

        @if(isset($errors) && $errors->any())
        <div class="alert alert-error" role="alert">
            <x-icon name="alert" />
            <div class="alert-body">
                <strong>Mohon periksa kembali data yang Anda isi:</strong>
                <ul style="margin:4px 0 0 18px;">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="alert-close" data-dismiss aria-label="Tutup"><x-icon name="x" size="16" /></button>
        </div>
        @endif

        @yield('content')
    </main>
</div>

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
