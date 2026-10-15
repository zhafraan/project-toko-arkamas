<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            var theme = document.cookie.match(/(^| )simlpg_theme=([^;]+)/);
            if (theme && theme[2] === 'dark') document.documentElement.classList.add('dark-theme');
            if (localStorage.getItem('simlpg_sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <title>@yield('title', 'SIM-LPG') | Arkamas Gas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        :root {
            /* Palette Baru dari Gambar User */
            --primary: #5b5f97;         /* Slate Indigo Utama */
            --primary-dark: #1c1c1c;    /* Charcoal Gelap */
            --primary-mid: #4a4e82;     /* Medium Slate */
            --primary-light: #b8b8d1;   /* Soft Lavender Slate */
            --accent: #5b5f97;
            --accent-warm: #ffc145;     /* Warm Amber Yellow */
            --warning: #ffc145;         /* Warning Yellow */
            --danger: #ff6b6c;          /* Coral Red */
            --success: #10B981;         /* Emerald Green */
            --info: #5b5f97;            /* Slate Blue */
            --bg: #eef0f2;              /* Outer Background Netral */
            --card-bg: #fffffb;         /* Kartu Putih Bersih */
            --card-soft: #fafaff;        /* Soft White / Light Grey */
            --card-soft-hover: #ecebe4; /* Hover Light */
            --border: #daddd8;          /* Border Netral */
            --border-light: #ecebe4;    /* Border Halus */
            --text: #1c1c1c;            /* Teks Utama Charcoal */
            --text-muted: #5b5f97;      /* Teks Muted Indigo */
            --text-light: #8e93aa;      /* Teks Sekunder */
            --sidebar-w: 240px;
            --card-radius: 20px;
            --shadow: 0 4px 20px rgba(91, 95, 151, 0.06);
            --shadow-lg: 0 12px 36px rgba(28, 28, 28, 0.08);

            /* Gradients Berdasarkan Palette */
            --grad-primary: linear-gradient(135deg, #5b5f97 0%, #3e4270 100%);
            --grad-card: linear-gradient(135deg, #1c1c1c 0%, #2f324e 50%, #5b5f97 100%);
            --grad-accent: linear-gradient(135deg, #ffc145 0%, #f59e0b 100%);
            --grad-coral: linear-gradient(135deg, #ff6b6c 0%, #e04b4c 100%);
            --grad-soft: linear-gradient(135deg, #b8b8d1 0%, #9fa4c7 100%);
            --grad-sidebar: #fffffb;
            --grad-card-header: transparent;
        }

        /* Dark Theme Variant */
        body.dark-theme {
            --bg: #12131a;
            --card-bg: #1b1c26;
            --card-soft: #232533;
            --card-soft-hover: #2b2e40;
            --text: #fffffb;
            --text-muted: #b8b8d1;
            --text-light: #7a7e99;
            --border: #323547;
            --border-light: #282a3a;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.45);
            --grad-sidebar: #171822;
        }
        body.dark-theme .card,
        body.dark-theme .topbar,
        body.dark-theme .stat-card,
        body.dark-theme .modal,
        body.dark-theme .cart-section,
        body.dark-theme .product-card {
            background: #181A2A !important;
            color: #FFFFFF;
            border-color: #2A2D42;
        }
        body.dark-theme .product-name { color: #FFFFFF !important; }
        body.dark-theme .form-control,
        body.dark-theme .type-select {
            background: #121422 !important;
            color: #FFFFFF !important;
            border-color: #2A2D42 !important;
        }
        body.dark-theme thead th {
            background: #141626 !important;
            color: #8E94AA !important;
            border-color: #2A2D42 !important;
        }
        body.dark-theme tbody td {
            border-color: #2A2D42 !important;
            color: #E2E4E8;
        }
        body.dark-theme tbody tr:hover { background: #202336 !important; }
        body.dark-theme .cart-footer { background: #141626 !important; border-color: #2A2D42 !important; }
        body.dark-theme .qty-btn { background: #121422 !important; border-color: #2A2D42 !important; color: #fff; }
        body.dark-theme .btn-outline { border-color: #2A2D42; color: #8E94AA; }
        body.dark-theme .badge-gray { background: #202336; color: #E2E4E8; }
        body.dark-theme .nav-link.active {
            border-color: #FFFFFF !important;
            color: #FFFFFF !important;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
            transition: background .2s, color .2s;
            letter-spacing: -0.015em;
        }

        /* ===================== SIDEBAR ===================== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--grad-sidebar);
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid var(--border);
            box-shadow: none;
            transition: width .25s cubic-bezier(0.4, 0, 0.2, 1), transform .3s ease;
        }
        .sidebar-brand {
            padding: 16px 18px 14px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
        }
        .sidebar-brand .logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; overflow: hidden;
        }
        .sidebar-brand .logo-icon {
            width: 32px; height: 32px;
            background: transparent;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(91, 95, 151, 0.25);
        }
        .sidebar-brand .logo-icon svg {
            width: 100%; height: 100%;
            display: block;
        }
        .sidebar-brand .logo-text { color: var(--text); white-space: nowrap; overflow: hidden; }
        .sidebar-brand .logo-text h2 { font-size: 15px; font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; }
        .sidebar-brand .logo-text span { font-size: 10px; color: var(--text-muted); font-weight: 500; }

        .btn-sidebar-minimize {
            width: 24px; height: 24px;
            border-radius: 7px;
            border: 1px solid var(--border);
            background: var(--card-soft);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 10px;
            transition: all .2s;
            flex-shrink: 0;
        }
        .btn-sidebar-minimize:hover {
            background: var(--border);
            color: var(--text);
        }

        .sidebar-user {
            padding: 10px 18px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
            overflow: hidden;
        }
        .sidebar-user .avatar {
            width: 30px; height: 30px;
            background: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 12px;
            flex-shrink: 0;
        }
        .sidebar-user .user-info { flex: 1; overflow: hidden; white-space: nowrap; }
        .sidebar-user .user-name {
            color: var(--text); font-size: 12px; font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user .user-role {
            font-size: 10px;
            background: rgba(34,197,94,0.12);
            color: #16a34a;
            padding: 2px 8px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; font-weight: 600;
        }
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 2px rgba(34,197,94,0.3); }
            50% { box-shadow: 0 0 0 4px rgba(34,197,94,0.15); }
        }

        .sidebar-nav { flex: 1; padding: 10px 0; overflow-y: auto; overflow-x: hidden; }
        .nav-section {
            padding: 8px 20px 4px;
            font-size: 9.5px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.8px;
            white-space: nowrap; overflow: hidden;
            transition: opacity .2s;
        }
        .nav-item { display: block; position: relative; }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 7px 14px;
            margin: 2px 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12.5px; font-weight: 500;
            border-radius: 9999px;
            transition: all .2s;
            position: relative;
            white-space: nowrap;
            border: 1.5px solid transparent;
        }
        .nav-link:hover {
            background: var(--card-soft);
            color: var(--text);
        }
        .nav-link.active {
            background: #FFFFFF;
            border-color: var(--primary);
            color: var(--primary);
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(22, 25, 44, 0.04);
        }
        .nav-link.active::before { display: none; }
        .nav-link .nav-icon {
            width: 22px; height: 22px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            color: inherit;
            background: transparent;
            transition: color .2s;
            flex-shrink: 0;
        }
        .nav-link .nav-icon svg {
            width: 17px;
            height: 17px;
            stroke-width: 2px;
            display: block;
            stroke: currentColor;
        }
        .nav-link .nav-text {
            flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: 9px; font-weight: 800;
            padding: 1px 6px;
            border-radius: 9999px;
            line-height: 1.3;
        }

        .sidebar-footer {
            padding: 10px 14px;
            border-top: 1px solid var(--border);
        }
        .btn-logout {
            display: flex; align-items: center; gap: 8px;
            width: 100%; padding: 6px 12px;
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 9999px;
            font-size: 12px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: all .2s;
            white-space: nowrap;
        }
        .btn-logout:hover { background: #FCA5A5; color: #991B1B; }

        /* ===================== SIDEBAR COLLAPSED / MINIMIZED ===================== */
        html.sidebar-collapsed body,
        body.sidebar-collapsed {
            --sidebar-w: 64px;
        }
        html.sidebar-collapsed .sidebar,
        body.sidebar-collapsed .sidebar {
            width: 64px !important;
        }
        html.sidebar-collapsed .main-content,
        body.sidebar-collapsed .main-content {
            margin-left: 64px !important;
        }
        html.sidebar-collapsed .sidebar-brand,
        body.sidebar-collapsed .sidebar-brand {
            padding: 14px 0 12px;
            justify-content: center;
        }
        html.sidebar-collapsed .sidebar-brand .logo-text,
        body.sidebar-collapsed .sidebar-brand .logo-text,
        html.sidebar-collapsed .btn-sidebar-minimize,
        body.sidebar-collapsed .btn-sidebar-minimize {
            display: none !important;
        }
        html.sidebar-collapsed .sidebar-user,
        body.sidebar-collapsed .sidebar-user {
            padding: 9px 0;
            justify-content: center;
        }
        html.sidebar-collapsed .sidebar-user .user-info,
        body.sidebar-collapsed .sidebar-user .user-info {
            display: none !important;
        }
        html.sidebar-collapsed .nav-section,
        body.sidebar-collapsed .nav-section {
            font-size: 0;
            padding: 0;
            margin: 6px 14px;
            border-bottom: 1px solid var(--border);
            height: 1px;
        }
        html.sidebar-collapsed .nav-link,
        body.sidebar-collapsed .nav-link {
            padding: 7px 0;
            margin: 2px 8px;
            justify-content: center;
            gap: 0;
        }
        html.sidebar-collapsed .nav-link .nav-text,
        body.sidebar-collapsed .nav-link .nav-text,
        html.sidebar-collapsed .nav-badge,
        body.sidebar-collapsed .nav-badge {
            display: none !important;
        }
        html.sidebar-collapsed .sidebar-footer,
        body.sidebar-collapsed .sidebar-footer {
            padding: 8px 0;
            display: flex;
            justify-content: center;
        }
        html.sidebar-collapsed .btn-logout,
        body.sidebar-collapsed .btn-logout {
            width: 34px;
            height: 34px;
            padding: 0;
            justify-content: center;
            border-radius: 50%;
        }
        html.sidebar-collapsed .btn-logout span,
        body.sidebar-collapsed .btn-logout span {
            display: none !important;
        }

        /* Tooltip when sidebar collapsed */
        html.sidebar-collapsed .nav-link:hover::after,
        body.sidebar-collapsed .nav-link:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 68px;
            top: 50%;
            transform: translateY(-50%);
            background: #16192C;
            color: #fff;
            padding: 5px 11px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            pointer-events: none;
            animation: tipFade .15s ease;
        }
        @keyframes tipFade { from { opacity: 0; transform: translateY(-50%) translateX(-4px); } to { opacity: 1; transform: translateY(-50%) translateX(0); } }
        @keyframes tipFade { from { opacity: 0; transform: translateY(-50%) translateX(-4px); } to { opacity: 1; transform: translateY(-50%) translateX(0); } }

        /* Sidebar Toggle Button in Topbar */
        .btn-sidebar-toggle {
            width: 32px; height: 32px;
            border-radius: 7px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all .2s;
            flex-shrink: 0;
        }
        .btn-sidebar-toggle:hover {
            background: var(--bg);
            color: var(--holst-2);
            border-color: var(--holst-3);
            transform: translateY(-1px);
        }
        body.dark-theme .btn-sidebar-toggle {
            background: #18283f;
            color: #D0E1F2;
            border-color: #243956;
        }

        /* ===================== MAIN CONTENT ===================== */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--border);
            padding: 10px 24px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-title {
            font-size: 16px; font-weight: 800; color: var(--primary);
            letter-spacing: -0.02em;
        }
        .topbar-subtitle { font-size: 11px; color: var(--text-muted); margin-top: 1px; font-weight: 500; }

        .topbar-search {
            position: relative;
            display: flex;
            align-items: center;
        }
        .topbar-search i {
            position: absolute;
            left: 14px;
            font-size: 12px;
            color: var(--text-muted);
            pointer-events: none;
        }
        .topbar-search input {
            background: #F4F5F7;
            border: 1px solid transparent;
            border-radius: 9999px;
            padding: 7px 16px 7px 34px;
            font-size: 12px;
            font-family: inherit;
            color: var(--text);
            width: 200px;
            transition: all .2s;
            outline: none;
        }
        .topbar-search input:focus {
            background: #FFFFFF;
            border-color: var(--border);
            width: 250px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        body.dark-theme .topbar-search input {
            background: #141626;
            color: #FFFFFF;
        }

        .topbar-actions { display: flex; align-items: center; gap: 8px; }
        .topbar-notification {
            position: relative;
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: #FFFFFF;
            display: flex; align-items: center; justify-content: center;
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            transition: all .2s;
            text-decoration: none;
        }
        .topbar-notification:hover { background: var(--card-soft); }
        .topbar-notification .dot {
            position: absolute; top: 6px; right: 7px;
            width: 6px; height: 6px;
            background: var(--danger);
            border-radius: 50%;
            border: 1.5px solid #fff;
        }
        body.dark-theme .topbar-notification {
            background: #181A2A;
            border-color: #2A2D42;
            color: #FFFFFF;
        }

        .btn-topbar-action {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 16px;
            background: var(--primary);
            color: #fff;
            border: none; border-radius: 9999px;
            font-size: 12px; font-weight: 700;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 4px 12px rgba(22, 25, 44, 0.15);
            transition: all .2s;
        }
        .btn-topbar-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(22, 25, 44, 0.25);
            background: #25283D;
            color: #fff;
        }
        .btn-topbar-action.accent {
            background: var(--primary);
            color: #fff;
        }

        .page-content { padding: 20px 24px; flex: 1; }

        /* ===================== ALERTS / FLASH ===================== */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px; font-weight: 500;
            animation: slideDown .3s ease;
        }
        @keyframes slideDown { from { opacity:0; transform: translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
        .alert-error   { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid var(--warning); }
        .alert-info    { background: #eef5fc; color: #1e3a5f; border-left: 4px solid var(--holst-3); }

        /* ===================== CARDS ===================== */
        .card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: box-shadow .2s;
        }
        .card:hover { box-shadow: var(--shadow-lg); }
        .card-header {
            padding: 16px 22px;
            background: var(--grad-card-header);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size: 15px; font-weight: 700; color: var(--primary); letter-spacing: .2px; }
        .card-body { padding: 22px; }

        /* ===================== STAT CARDS ===================== */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px; }
        .stat-card {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 16px;
            position: relative; overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .stat-card::after {
            content: '';
            position: absolute; right: -20px; top: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.07;
        }
        .stat-card.blue::after   { background: var(--holst-1); }
        .stat-card.orange::after { background: var(--holst-2); }
        .stat-card.green::after  { background: var(--holst-3); }
        .stat-card.red::after    { background: var(--danger); }

        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(34, 58, 94, 0.2);
        }
        .stat-icon.blue   { background: var(--grad-primary); }
        .stat-icon.orange { background: var(--grad-accent); }
        .stat-icon.green  { background: var(--grad-sky); }
        .stat-icon.purple { background: var(--grad-ice); color: var(--holst-1); }
        .stat-icon.red    { background: linear-gradient(135deg, #f87171, var(--danger)); }

        .stat-info { flex: 1; }
        .stat-value { font-size: 22px; font-weight: 800; color: var(--primary); line-height: 1.2; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 3px; font-weight: 500; }
        .stat-change { font-size: 11px; margin-top: 4px; }
        .stat-change.up   { color: var(--success); }
        .stat-change.down { color: var(--danger); }

        /* ===================== TABLE ===================== */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        thead th {
            background: linear-gradient(180deg, #F3F8FD 0%, #e5eef7 100%);
            padding: 12px 14px;
            text-align: left;
            font-weight: 700;
            color: var(--primary);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 2px solid var(--border);
        }
        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tbody tr:hover { background: #f0f6fc; }
        tbody tr:last-child td { border-bottom: none; }

        /* ===================== BADGES ===================== */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger  { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fff3d6; color: #92400e; }
        .badge-info    { background: #eef0f8; color: var(--primary); border: 1px solid var(--border); }
        .badge-gray    { background: #ecebe4; color: var(--text); }
        .badge-orange  { background: #fff3d6; color: #b45309; border: 1px solid #fed7aa; }

        /* ===================== BUTTONS (Tactile "STRONG" Pill Style) ===================== */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 8px 18px; border-radius: 9999px;
            font-size: 12.5px; font-weight: 700;
            cursor: pointer; text-decoration: none;
            border: none; transition: transform .12s, box-shadow .12s, background .15s;
            font-family: inherit;
        }
        .btn:active {
            transform: translateY(2px) !important;
        }
        .btn-primary {
            background: #5b5f97; color: #ffffff;
            box-shadow: 0 3px 0 #3e4270, 0 6px 14px rgba(91, 95, 151, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            background: #4f5388;
            box-shadow: 0 4px 0 #3e4270, 0 8px 18px rgba(91, 95, 151, 0.32);
        }
        .btn-accent {
            background: #ffc145; color: #1c1c1c;
            box-shadow: 0 3px 0 #d99a1b, 0 6px 14px rgba(255, 193, 69, 0.28);
        }
        .btn-accent:hover {
            transform: translateY(-1px);
            background: #f7b736;
            box-shadow: 0 4px 0 #d99a1b, 0 8px 18px rgba(255, 193, 69, 0.36);
        }
        .btn-success {
            background: #10B981; color: #ffffff;
            box-shadow: 0 3px 0 #059669, 0 6px 14px rgba(16, 185, 129, 0.25);
        }
        .btn-success:hover {
            transform: translateY(-1px);
            background: #059669;
            box-shadow: 0 4px 0 #047857, 0 8px 18px rgba(16, 185, 129, 0.32);
        }
        .btn-danger {
            background: #ff6b6c; color: #ffffff;
            box-shadow: 0 3px 0 #d94b4c, 0 6px 14px rgba(255, 107, 108, 0.25);
        }
        .btn-danger:hover {
            transform: translateY(-1px);
            background: #f05a5b;
            box-shadow: 0 4px 0 #d94b4c, 0 8px 18px rgba(255, 107, 108, 0.32);
        }
        .btn-outline {
            background: #fafaff; border: 1.5px solid var(--border);
            color: var(--text);
            box-shadow: 0 2px 0 var(--border-light);
        }
        .btn-outline:hover {
            background: #ecebe4;
            color: var(--primary);
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }
        .btn-sm { padding: 5px 12px; font-size: 11.5px; }
        .btn-xs { padding: 3px 9px; font-size: 10.5px; }
        .btn-icon { padding: 7px 10px; }

        /* ===================== FORMS ===================== */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 600;
            color: var(--text-muted); margin-bottom: 5px;
            text-transform: uppercase; letter-spacing: .4px;
        }
        .form-control {
            width: 100%; padding: 9px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px; font-size: 13.5px;
            color: var(--text);
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(37,99,168,0.12);
        }
        .form-control.is-invalid { border-color: var(--danger); }
        .invalid-feedback { color: var(--danger); font-size: 12px; margin-top: 4px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* ===================== MODAL ===================== */
        .modal-overlay {
            display: none; position: fixed;
            inset: 0; background: rgba(0,0,0,0.5);
            z-index: 999; align-items: center; justify-content: center;
            backdrop-filter: blur(3px);
        }
        .modal-overlay.active { display: flex; animation: fadeIn .2s; }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        .modal {
            background: #fff; border-radius: 16px;
            padding: 28px; width: 90%; max-width: 540px;
            box-shadow: var(--shadow-lg);
            animation: slideUp .25s ease;
            max-height: 90vh; overflow-y: auto;
        }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .modal-title { font-size: 17px; font-weight: 700; color: var(--primary); }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--bg); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 16px;
            transition: background .2s;
        }
        .modal-close:hover { background: #fee2e2; color: var(--danger); }

        /* ===================== PAGINATION ===================== */
        .pagination { display: flex; gap: 4px; align-items: center; justify-content: center; padding: 16px 0; }
        .pagination a, .pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            border: 1.5px solid var(--border);
            color: var(--text); text-decoration: none;
            transition: all .2s;
        }
        .pagination a:hover { border-color: var(--primary); color: var(--primary); }
        .pagination .active-page { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ===================== RESPONSIVE ===================== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
        @media print { .sidebar, .topbar, .no-print { display: none !important; } .main-content { margin-left: 0; } }
    </style>
    @stack('styles')
</head>
<body>

{{-- SIDEBAR --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo">
            <div class="logo-icon" title="Arkamas Gas">
                <svg width="32" height="32" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="agBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#5b5f97"/>
                            <stop offset="100%" stop-color="#3c4071"/>
                        </linearGradient>
                        <linearGradient id="agGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffe394"/>
                            <stop offset="100%" stop-color="#ffc145"/>
                        </linearGradient>
                    </defs>
                    <rect width="36" height="36" rx="9" fill="url(#agBrandGrad)"/>
                    <rect x="0.75" y="0.75" width="34.5" height="34.5" rx="8.25" stroke="rgba(255,255,255,0.22)" stroke-width="1.5"/>
                    <text x="17" y="24" text-anchor="middle" font-family="'Plus Jakarta Sans', system-ui, -apple-system, sans-serif" font-weight="900" font-size="16" letter-spacing="-0.5px">
                        <tspan fill="#FFFFFB">A</tspan><tspan fill="url(#agGoldGrad)" dx="0.5">G</tspan>
                    </text>
                    <circle cx="28" cy="11.5" r="2" fill="url(#agGoldGrad)"/>
                </svg>
            </div>
            <div class="logo-text">
                <h2>Arkamas Gas</h2>
                <span>Sistem Informasi Kasir</span>
            </div>
        </div>
        <button type="button" id="sidebarMinimizeBtn" onclick="toggleSidebar()" class="btn-sidebar-minimize" title="Minimalkan Sidebar">
            <i class="fa-solid fa-angles-left"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <div class="user-info">
            <div class="user-name">{{ auth()->user()->name }}</div>
            <span class="user-role" style="display:flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow:0 0 0 2px rgba(34,197,94,0.3);animation:pulse-dot 2s infinite;"></span>
                Aktif
            </span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu Utama</div>

        <div class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1.5"/><rect width="7" height="5" x="14" y="3" rx="1.5"/><rect width="7" height="9" x="14" y="12" rx="1.5"/><rect width="7" height="5" x="3" y="16" rx="1.5"/></svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}" data-tooltip="Kasir / POS">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                </div>
                <span class="nav-text">Kasir / POS</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pos.history') }}" class="nav-link {{ request()->routeIs('pos.history') ? 'active' : '' }}" data-tooltip="Riwayat Transaksi">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </div>
                <span class="nav-text">Riwayat Transaksi</span>
            </a>
        </div>

        <div class="nav-section">Operasional</div>

        <div class="nav-item">
            <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" data-tooltip="Pelanggan">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="nav-text">Pelanggan</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('loans.index') }}" class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}" data-tooltip="Pinjam Tabung">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                </div>
                <span class="nav-text">Pinjam Tabung</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('debts.index') }}" class="nav-link {{ request()->routeIs('debts.*') ? 'active' : '' }}" data-tooltip="Piutang / Kasbon">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="3"/><line x1="2" x2="22" y1="10" y2="10"/><path d="M6 15h2"/></svg>
                </div>
                <span class="nav-text">Piutang / Kasbon</span>
            </a>
        </div>

        @if(auth()->user()->isOwner())
        <div class="nav-section">Manajemen</div>

        <div class="nav-item">
            <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}" data-tooltip="Produk & Stok">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                </div>
                <span class="nav-text">Produk & Stok</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('inventory.deliveries') }}" class="nav-link {{ request()->routeIs('inventory.deliveries') ? 'active' : '' }}" data-tooltip="Kiriman Agen">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-5l-3-4h-5v10Z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                </div>
                <span class="nav-text">Kiriman Agen</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-tooltip="Laporan">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 18v-4"/><path d="M14 18v-2"/><path d="M6 18v-6"/></svg>
                </div>
                <span class="nav-text">Laporan</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" data-tooltip="Manajemen Akun">
                <div class="nav-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </div>
                <span class="nav-text">Manajemen Akun</span>
            </a>
        </div>
        @endif
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout" title="Keluar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

{{-- MAIN CONTENT --}}
<div class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <button type="button" id="sidebarToggleBtn" onclick="toggleSidebar()" class="btn-sidebar-toggle" title="Tutup / Minimalkan Sidebar">
                <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
            </button>
            <div class="topbar-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-light);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                <input type="text" placeholder="Search..." onclick="window.location.href='{{ route('pos.history') }}'" style="cursor:pointer;" title="Cari Transaksi">
            </div>
        </div>
        <div class="topbar-actions">
            {{-- Tombol Toggle Tema --}}
            <button type="button" id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-outline btn-sm" style="border-radius:9999px;padding:6px 14px;font-size:12px;font-weight:600;" title="Mode Gelap / Terang">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg> <span id="themeText">Tema</span>
            </button>

            <a href="{{ route('pos.history') }}" class="topbar-notification" title="Riwayat & Notifikasi">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span class="dot"></span>
            </a>

            <div style="display:flex;align-items:center;gap:8px;padding-left:4px;">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>

            @yield('topbar-actions')
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info" style="background:#e0f2fe;color:#0369a1;border-left:4px solid #0284c7;"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script>
// ==================== COOKIE HELPER FUNCTIONS ====================
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/; SameSite=Lax";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// ==================== THEME TOGGLE ====================
function applySavedTheme() {
    const savedTheme = getCookie('simlpg_theme') || 'light';
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        if (icon) icon.className = 'fa-solid fa-sun';
        if (text) text.textContent = 'Terang';
    } else {
        document.body.classList.remove('dark-theme');
        if (icon) icon.className = 'fa-solid fa-moon';
        if (text) text.textContent = 'Gelap';
    }
}

function toggleTheme() {
    const isDark = document.body.classList.contains('dark-theme');
    const newTheme = isDark ? 'light' : 'dark';
    setCookie('simlpg_theme', newTheme, 30);
    applySavedTheme();
}

// Jalankan tema segera
applySavedTheme();

// ==================== SIDEBAR TOGGLE ====================
function updateSidebarToggleIcon(isCollapsed) {
    const icon = document.getElementById('sidebarToggleIcon');
    if (icon) {
        icon.className = isCollapsed ? 'fa-solid fa-bars-staggered' : 'fa-solid fa-bars';
    }
}

function toggleSidebar() {
    const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
    document.documentElement.classList.toggle('sidebar-collapsed', isCollapsed);
    localStorage.setItem('simlpg_sidebar_collapsed', isCollapsed ? 'true' : 'false');
    updateSidebarToggleIcon(isCollapsed);

    // On mobile screens (width <= 768px), also toggle .open
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Initial state sync
(function initSidebar() {
    const isCollapsed = localStorage.getItem('simlpg_sidebar_collapsed') === 'true';
    if (isCollapsed) {
        document.body.classList.add('sidebar-collapsed');
        document.documentElement.classList.add('sidebar-collapsed');
    }
    updateSidebarToggleIcon(isCollapsed);
})();

// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>

@stack('scripts')
</body>
</html>
