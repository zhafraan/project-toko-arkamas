@extends('layouts.app')
@section('title', 'Statistik Penjualan')
@section('page-title', 'Statistik Penjualan')
@section('page-sub', $periodLabel)

@push('styles')
<style>
    .st-delta { display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; }
    .st-delta.up { color: var(--success-ink); }
    .st-delta.down { color: var(--danger-ink); }
    .st-delta.flat { color: var(--text-3); }
    .st-chart-wrap { position:relative; padding: 8px 12px 14px; }
    .st-chart { width:100%; display:block; }
    .st-chart text { font-family: var(--font); font-size: 11px; fill: var(--text-3); }
    .st-chart .grid { stroke: var(--border); stroke-width: 1; }
    .st-chart .bar { fill: var(--primary); }
    .st-chart .bar.is-hover { fill: #4A4A4F; }
    :root[data-theme="dark"] .st-chart .bar.is-hover { fill: #A1A1AA; }
    .st-chart .hit { fill: transparent; cursor: default; }
    .st-tip { position:absolute; z-index:5; pointer-events:none; background: var(--surface); color: var(--text); border:1px solid var(--border-strong); border-radius: var(--r-sm); box-shadow: var(--shadow-md); padding: 8px 10px; font-size: 12.5px; min-width: 150px; opacity:0; transition: opacity .08s; }
    .st-tip.show { opacity:1; }
    .st-tip b { display:block; margin-bottom:4px; }
    .st-tip div { display:flex; justify-content:space-between; gap:14px; }
    .st-tip span:last-child { font-variant-numeric: tabular-nums; font-weight:600; }
    .st-rows { padding: 6px 18px 14px; }
    .st-row { padding: 9px 0; border-bottom: 1px solid var(--border); }
    .st-row:last-child { border-bottom: 0; }
    .st-row-head { display:flex; justify-content:space-between; gap:12px; align-items:baseline; font-size: 13.5px; }
    .st-row-head .name { font-weight:600; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .st-row-head .val { font-variant-numeric: tabular-nums; font-weight:700; white-space:nowrap; }
    .st-track { height:6px; border-radius:99px; background: var(--surface-3); margin:7px 0 5px; overflow:hidden; }
    .st-track > i { display:block; height:100%; border-radius:99px; background: var(--primary); }
    .st-sub { font-size:12.5px; color: var(--text-3); display:flex; gap:12px; flex-wrap:wrap; }
    .st-cols { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px; align-items:start; }
    .st-mini { display:grid; gap:6px; padding: 12px 18px 16px; }
    .st-mini-row { display:grid; grid-template-columns: 62px 1fr 44px; align-items:center; gap:10px; font-size:12.5px; }
    .st-mini-row .t { height:8px; border-radius:99px; background: var(--surface-3); overflow:hidden; }
    .st-mini-row .t i { display:block; height:100%; background: var(--primary); border-radius:99px; }
    .st-mini-row .n { text-align:right; font-variant-numeric: tabular-nums; color: var(--text-2); }
    .st-hours { display:flex; align-items:flex-end; gap:3px; height:96px; padding: 14px 18px 0; }
    .st-hours .h { flex:1; background: var(--surface-3); border-radius: 4px 4px 0 0; min-height:3px; position:relative; }
    .st-hours .h.on { background: var(--primary); }
    .st-hours-axis { display:flex; justify-content:space-between; padding: 4px 18px 14px; font-size:11px; color: var(--text-3); }
    .st-insight { padding: 12px 18px; font-size: 13.5px; color: var(--text-2); border-top:1px solid var(--border); }
    .st-seg { display:inline-flex; background: var(--surface-3); border-radius: var(--r-sm); padding:3px; gap:2px; }
    .st-seg button { border:0; background:transparent; font: inherit; font-size:12.5px; font-weight:600; color: var(--text-2); padding:5px 12px; border-radius:6px; cursor:pointer; }
    .st-seg button[aria-pressed="true"] { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }
    details.st-table summary { cursor:pointer; padding: 10px 18px; font-size:12.5px; font-weight:600; color: var(--primary-text); border-top:1px solid var(--border); }
</style>
@endpush

@section('content')
@php
    $t = $stats['totals'];
    $fmtDelta = function ($v) {
        if ($v === null) return ['flat', 'baru', null];
        if (abs($v) < 0.05) return ['flat', '0%', null];
        return [$v > 0 ? 'up' : 'down', ($v > 0 ? '▲ ' : '▼ ') . number_format(abs($v), 1, ',', '.') . '%', null];
    };
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $maxProduct = collect($stats['products'])->max('revenue') ?: 1;
    $maxWeekday = collect($stats['weekday'])->max('count') ?: 1;
    $maxHour = max($stats['hours']) ?: 1;
@endphp

{{-- Filter periode --}}
<div class="card mb-2">
    <form method="GET" action="{{ route('statistics.index') }}" class="toolbar" id="stForm" style="border-bottom:0;align-items:center;">
        <div class="range-tabs" id="rangeTabs">
            @foreach($presets as $key => $label)
            <button type="button" class="{{ $range === $key ? 'active' : '' }}" {{ $range === $key ? 'aria-pressed="true"' : '' }} onclick="var s = document.getElementById('stRange'); s.value='{{ $key }}'; s.dispatchEvent(new Event('change'));">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <select name="range" id="stRange" class="select" style="display:none;">
            @foreach($presets as $key => $label)
            <option value="{{ $key }}" {{ $range === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div data-custom {{ $range === 'custom' ? '' : 'hidden' }} class="row" style="gap:8px;margin-left:8px;">
            <label class="label" for="stFrom" style="margin-bottom:0;">Dari</label>
            <input type="date" id="stFrom" name="from" class="input input-sm" value="{{ $from }}">
            <label class="label" for="stTo" style="margin-bottom:0;">Sampai</label>
            <input type="date" id="stTo" name="to" class="input input-sm" value="{{ $to }}">
            <button type="submit" class="btn btn-primary btn-sm"><x-icon name="filter" size="14" /> Tampilkan</button>
        </div>
        <div class="muted text-sm" style="margin-left:auto;">Pembanding: {{ $prevLabel }}</div>
    </form>
</div>

@if($clamped)
<div class="alert alert-info" role="status"><x-icon name="info" /><div class="alert-body">Rentang dibatasi maksimal {{ $maxDays }} hari. Yang ditampilkan adalah {{ $maxDays }} hari yang berakhir pada tanggal akhir.</div></div>
@endif

{{-- Ringkasan --}}
<div class="summary mb-2">
    <div class="summary-item">
        <div class="summary-label">Omzet</div>
        <div class="summary-value">{{ $rp($t['revenue']) }}</div>
        @php [$c, $txt] = $fmtDelta($compare['revenue']); @endphp
        <div class="summary-note"><span class="st-delta {{ $c }}">{{ $txt }}</span> dari periode sebelumnya</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Transaksi</div>
        <div class="summary-value">{{ number_format($t['count'], 0, ',', '.') }}</div>
        @php [$c, $txt] = $fmtDelta($compare['count']); @endphp
        <div class="summary-note"><span class="st-delta {{ $c }}">{{ $txt }}</span> dari periode sebelumnya</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Tabung Terjual</div>
        <div class="summary-value">{{ number_format($t['units'], 0, ',', '.') }}</div>
        @php [$c, $txt] = $fmtDelta($compare['units']); @endphp
        <div class="summary-note"><span class="st-delta {{ $c }}">{{ $txt }}</span> dari periode sebelumnya</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Rata-rata per transaksi</div>
        <div class="summary-value">{{ $rp($t['avg']) }}</div>
        <div class="summary-note">{{ $stats['activeDays'] }} dari {{ $stats['days'] }} hari ada penjualan</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Estimasi laba kotor</div>
        <div class="summary-value text-success">{{ $rp($t['profit']) }}</div>
        @php [$c, $txt] = $fmtDelta($compare['profit']); @endphp
        <div class="summary-note">margin {{ number_format($t['margin'], 1, ',', '.') }}% · <span class="st-delta {{ $c }}">{{ $txt }}</span></div>
    </div>
</div>

@if($t['count'] === 0)
<div class="card"><div class="empty">
    <div class="empty-icon"><x-icon name="bar-chart" size="24" /></div>
    <div class="empty-title">Belum ada penjualan pada periode ini</div>
    <div>Coba pilih periode lain. Statistik akan muncul setelah kasir mencatat transaksi.</div>
</div></div>
@else

{{-- Tren Penjualan --}}
<div class="card mb-2">
    <div class="card-head">
        <span class="card-title">Tren Penjualan {{ $stats['granularity'] === 'month' ? 'per Bulan' : 'per Hari' }}</span>
        <div class="st-seg" id="stMetric" role="group" aria-label="Ukuran grafik">
            <button type="button" data-m="revenue" aria-pressed="true">Omzet</button>
            <button type="button" data-m="count" aria-pressed="false">Transaksi</button>
            <button type="button" data-m="units" aria-pressed="false">Tabung</button>
        </div>
    </div>
    <div class="st-chart-wrap" id="stChartWrap">
        <svg class="st-chart" id="stChart" role="img" aria-label="Grafik tren penjualan"></svg>
        <div class="st-tip" id="stTip"></div>
    </div>
    @if($stats['bestDay'])
    <div class="st-insight">
        Hari terbaik: <strong>{{ \Carbon\Carbon::parse($stats['bestDay']['date'])->isoFormat('dddd, D MMMM Y') }}</strong>
        dengan omzet <strong>{{ $rp($stats['bestDay']['revenue']) }}</strong>.
    </div>
    @endif
    <details class="st-table">
        <summary>Lihat sebagai tabel</summary>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ $stats['granularity'] === 'month' ? 'Bulan' : 'Tanggal' }}</th><th class="num">Transaksi</th><th class="num">Unit</th><th class="num">Omzet</th></tr></thead>
                <tbody>
                @foreach($stats['series'] as $b)
                    <tr><td>{{ $b['label'] }}</td><td class="num">{{ $b['count'] }}</td><td class="num">{{ $b['units'] }}</td><td class="num">{{ $rp($b['revenue']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>

{{-- Tabung Terjual 7 Hari Terakhir (Khusus Range 7 Hari) --}}
@if($range === '7d')
<div class="card mb-2">
    <div class="card-head">
        <span class="card-title">Tabung terjual 7 hari terakhir</span>
    </div>
    <div class="card-body">
        <div class="bars">
            @foreach($last7DaysData as $bar)
            <div class="bar-col {{ $bar['isToday'] ? 'today' : '' }}" title="{{ $bar['date'] }}: {{ number_format($bar['qty']) }} tabung ({{ $bar['count'] }} transaksi)">
                <div class="bar-val">{{ number_format($bar['qty']) }}</div>
                <div class="bar-track"><div class="bar {{ $bar['isToday'] ? 'today' : '' }}" style="height: {{ $bar['height'] }}%;"></div></div>
                <div class="bar-day">{{ $bar['day'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="st-cols mb-2">
    {{-- Per produk --}}
    <div class="card" style="grid-column: 1 / -1;">
        <div class="card-head"><span class="card-title">Penjualan per produk</span><span class="muted text-sm">diurutkan dari omzet terbesar</span></div>
        <div class="st-rows">
            @foreach($stats['products'] as $p)
            @php
                $pName = strtolower($p['name']);
                $pCatClass = 'cat-bar-gas';
            @endphp
            <div class="st-row">
                <div class="st-row-head"><span class="name">{{ $p['name'] }}</span><span class="val">{{ $rp($p['revenue']) }}</span></div>
                <div class="st-track" role="presentation"><i class="{{ $pCatClass }}" style="width: {{ round($p['revenue'] / $maxProduct * 100) }}%"></i></div>
                <div class="st-sub">
                    <span>{{ number_format($p['units'], 0, ',', '.') }} unit</span>
                    <span>{{ number_format($p['share'], 1, ',', '.') }}% dari omzet</span>
                    <span>Laba kotor {{ $rp($p['profit']) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Jenis transaksi --}}
    <div class="card">
        <div class="card-head"><span class="card-title">Jenis penjualan</span></div>
        <div class="st-rows">
            @php $maxType = collect($stats['types'])->max('revenue') ?: 1; @endphp
            @foreach($stats['types'] as $ty)
            @php
                $tyLabel = strtolower($ty['label']);
                $tyCatClass = 'cat-bar-gas';
            @endphp
            <div class="st-row">
                <div class="st-row-head"><span class="name">{{ $ty['label'] }}</span><span class="val">{{ $rp($ty['revenue']) }}</span></div>
                <div class="st-track" role="presentation"><i class="{{ $tyCatClass }}" style="width: {{ round($ty['revenue'] / $maxType * 100) }}%"></i></div>
                <div class="st-sub"><span>{{ number_format($ty['units'], 0, ',', '.') }} unit</span></div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Pembayaran --}}
    <div class="card">
        <div class="card-head"><span class="card-title">Pembayaran</span></div>
        <div class="st-rows">
            @foreach($stats['methods'] as $m)
            <div class="st-row">
                <div class="st-row-head"><span class="name">{{ $m['label'] }}</span><span class="val">{{ $rp($m['amount']) }}</span></div>
                <div class="st-track" role="presentation"><i style="width: {{ $t['revenue'] > 0 ? round($m['amount'] / $t['revenue'] * 100) : 0 }}%"></i></div>
                <div class="st-sub"><span>{{ $m['count'] }} transaksi</span></div>
            </div>
            @endforeach
            @foreach($stats['status'] as $s)
            <div class="st-row">
                <div class="st-row-head"><span class="name">{{ $s['label'] }}</span><span class="val">{{ $rp($s['amount']) }}</span></div>
                <div class="st-sub"><span>{{ $s['count'] }} transaksi</span></div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Pelanggan teratas --}}
    <div class="card">
        <div class="card-head"><span class="card-title">Pelanggan teratas</span></div>
        <div class="st-rows">
            @forelse($stats['customers'] as $c)
            <div class="st-row">
                <div class="st-row-head"><span class="name">{{ $c['name'] }}</span><span class="val">{{ $rp($c['revenue']) }}</span></div>
                <div class="st-sub"><span>{{ $c['count'] }} transaksi</span></div>
            </div>
            @empty
            <div class="empty"><div class="empty-title">Belum ada pelanggan tercatat</div></div>
            @endforelse
        </div>
    </div>
</div>

{{-- Pola waktu --}}
<div class="st-cols mb-2">
    <div class="card">
        <div class="card-head"><span class="card-title">Jumlah transaksi per hari dalam seminggu</span></div>
        <div class="st-mini">
            @foreach($stats['weekday'] as $w)
            <div class="st-mini-row" title="{{ $w['label'] }}: {{ $w['count'] }} transaksi, {{ $rp($w['revenue']) }}">
                <span>{{ $w['label'] }}</span>
                <span class="t"><i style="width: {{ round($w['count'] / $maxWeekday * 100) }}%"></i></span>
                <span class="n">{{ $w['count'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-head"><span class="card-title">Jam paling ramai</span></div>
        <div class="st-hours" role="img" aria-label="Jumlah transaksi per jam">
            @foreach($stats['hours'] as $hour => $n)
            <div class="h {{ $n > 0 ? 'on' : '' }}" style="height: {{ $n > 0 ? max(4, round($n / $maxHour * 100)) : 3 }}%" title="Pukul {{ sprintf('%02d', $hour) }}.00 — {{ $n }} transaksi"></div>
            @endforeach
        </div>
        <div class="st-hours-axis"><span>00</span><span>06</span><span>12</span><span>18</span><span>23</span></div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // Form: tampilkan input tanggal hanya untuk "Pilih tanggal"
    var sel = document.getElementById('stRange');
    sel.addEventListener('change', function () {
        document.querySelectorAll('[data-custom]').forEach(function (el) { el.hidden = sel.value !== 'custom'; });
        if (sel.value !== 'custom') document.getElementById('stForm').submit();
    });

    var svg = document.getElementById('stChart');
    if (!svg) return;
    var DATA = @json($stats['series']);
    var NS = 'http://www.w3.org/2000/svg';
    var metric = 'revenue';
    var LABEL = { revenue: 'Omzet', count: 'Transaksi', units: 'Unit' };
    var wrap = document.getElementById('stChartWrap'), tip = document.getElementById('stTip');

    function fmt(m, v) { return m === 'revenue' ? 'Rp ' + Math.round(v).toLocaleString('id-ID') : Math.round(v).toLocaleString('id-ID'); }
    function compact(v) {
        if (v >= 1e9) return (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (v >= 1e6) return (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        if (v >= 1e3) return (v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
        return String(Math.round(v));
    }
    function niceMax(v) {
        if (v <= 0) return 4;
        var p = Math.pow(10, Math.floor(Math.log10(v))), n = v / p;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 4 ? 4 : n <= 5 ? 5 : 10) * p;
    }
    function el(name, attrs, text) {
        var e = document.createElementNS(NS, name);
        Object.keys(attrs || {}).forEach(function (k) { e.setAttribute(k, attrs[k]); });
        if (text != null) e.textContent = text;
        return e;
    }

    function draw() {
        var W = Math.max(280, wrap.clientWidth - 24), H = 260;
        var m = { l: 46, r: 8, t: 12, b: 28 };
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.setAttribute('height', H);
        svg.innerHTML = '';

        var max = niceMax(Math.max.apply(null, DATA.map(function (d) { return d[metric]; })));
        var iw = W - m.l - m.r, ih = H - m.t - m.b;
        var band = iw / DATA.length, bw = Math.max(2, Math.min(28, band - 2));
        function y(v) { return m.t + ih - (v / max) * ih; }

        for (var g = 0; g <= 4; g++) {
            var gv = max / 4 * g, gy = y(gv);
            svg.appendChild(el('line', { x1: m.l, x2: W - m.r, y1: gy, y2: gy, 'class': 'grid' }));
            svg.appendChild(el('text', { x: m.l - 8, y: gy + 4, 'text-anchor': 'end' }, metric === 'revenue' ? compact(gv) : Math.round(gv).toLocaleString('id-ID')));
        }

        var every = Math.max(1, Math.ceil(DATA.length / Math.max(2, Math.floor(iw / 64))));
        DATA.forEach(function (d, i) {
            var x = m.l + band * i + (band - bw) / 2, v = d[metric], top = y(v), h = m.t + ih - top;
            if (v > 0) {
                var r = Math.min(10, bw / 2, h);
                var path = 'M' + x + ',' + (m.t + ih) + 'V' + (top + r) + 'Q' + x + ',' + top + ' ' + (x + r) + ',' + top +
                           'H' + (x + bw - r) + 'Q' + (x + bw) + ',' + top + ' ' + (x + bw) + ',' + (top + r) + 'V' + (m.t + ih) + 'Z';
                svg.appendChild(el('path', { d: path, 'class': 'bar', 'data-i': i }));
            }
            if (i % every === 0) svg.appendChild(el('text', { x: x + bw / 2, y: H - 8, 'text-anchor': 'middle' }, d.label));
            var hit = el('rect', { x: m.l + band * i, y: m.t, width: band, height: ih, 'class': 'hit' });
            hit.addEventListener('mouseenter', function () { show(i, x + bw / 2); });
            hit.addEventListener('mousemove', function () { show(i, x + bw / 2); });
            hit.addEventListener('mouseleave', hide);
            hit.addEventListener('touchstart', function () { show(i, x + bw / 2); }, { passive: true });
            svg.appendChild(hit);
        });
        svg.appendChild(el('line', { x1: m.l, x2: W - m.r, y1: m.t + ih, y2: m.t + ih, stroke: 'var(--border-strong)' }));
    }

    function show(i, cx) {
        var d = DATA[i];
        tip.innerHTML = '<b>' + d.label + '</b>' +
            '<div><span>Omzet</span><span>' + fmt('revenue', d.revenue) + '</span></div>' +
            '<div><span>Transaksi</span><span>' + fmt('count', d.count) + '</span></div>' +
            '<div><span>Unit</span><span>' + fmt('units', d.units) + '</span></div>';
        var left = Math.min(Math.max(8, cx + 12 - 75), wrap.clientWidth - tip.offsetWidth - 8);
        tip.style.left = left + 'px'; tip.style.top = '10px';
        tip.classList.add('show');
        svg.querySelectorAll('.bar').forEach(function (b) { b.classList.toggle('is-hover', b.getAttribute('data-i') == i); });
    }
    function hide() { tip.classList.remove('show'); svg.querySelectorAll('.bar.is-hover').forEach(function (b) { b.classList.remove('is-hover'); }); }

    document.getElementById('stMetric').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        metric = b.dataset.m;
        this.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        svg.setAttribute('aria-label', 'Grafik ' + LABEL[metric].toLowerCase() + ' per periode');
        draw();
    });
    var t; window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(draw, 120); });
    draw();
})();
</script>
@endpush
