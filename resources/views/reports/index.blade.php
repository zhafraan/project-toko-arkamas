@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-sub', $periodLabel)

@section('content')

<div class="card mb-2">
    <form method="GET" action="{{ route('reports.index') }}" class="toolbar" id="reportForm" style="border-bottom:0;align-items:flex-end;">
        <div class="range-tabs">
            @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $k => $l)
            <button type="button" class="{{ $type === $k ? 'active' : '' }}" {{ $type === $k ? 'aria-pressed="true"' : '' }} onclick="var s = document.getElementById('repType'); s.value='{{ $k }}'; s.dispatchEvent(new Event('change'));">
                {{ $l }}
            </button>
            @endforeach
        </div>
        <select name="type" id="repType" class="select" style="display:none;">
            <option value="daily" {{ $type === 'daily' ? 'selected' : '' }}>Harian</option>
            <option value="weekly" {{ $type === 'weekly' ? 'selected' : '' }}>Mingguan</option>
            <option value="monthly" {{ $type === 'monthly' ? 'selected' : '' }}>Bulanan</option>
            <option value="yearly" {{ $type === 'yearly' ? 'selected' : '' }}>Tahunan</option>
        </select>
        <div data-for="daily" {{ $type === 'daily' ? '' : 'hidden' }}>
            <label class="label">Tanggal</label>
            <input type="date" name="date" class="input" value="{{ $date }}">
        </div>
        <div data-for="weekly" {{ $type === 'weekly' ? '' : 'hidden' }}>
            <label class="label">Dari</label>
            <input type="date" name="week_start" class="input" value="{{ $weekStart }}">
        </div>
        <div data-for="weekly" {{ $type === 'weekly' ? '' : 'hidden' }}>
            <label class="label">Sampai</label>
            <input type="date" name="week_end" class="input" value="{{ $weekEnd }}">
        </div>
        <div data-for="monthly" {{ $type === 'monthly' ? '' : 'hidden' }}>
            <label class="label">Bulan</label>
            <select name="month" class="select">
                @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(2000, $m, 1)->isoFormat('MMMM') }}</option>
                @endforeach
            </select>
        </div>
        <div data-for="monthly yearly" {{ ($type === 'monthly' || $type === 'yearly') ? '' : 'hidden' }}>
            <label class="label">Tahun</label>
            <select name="year" class="select">
                @foreach(range((int) date('Y') - 3, (int) date('Y')) as $y)
                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><x-icon name="filter" size="16" /> Tampilkan</button>
        <div class="row" style="margin-left:auto;gap:8px;">
            <button type="submit" class="btn btn-outline" formaction="{{ route('reports.exportPdf') }}" formtarget="_blank"><x-icon name="file-text" size="16" /> PDF</button>
            <button type="submit" class="btn btn-outline" formaction="{{ route('reports.exportExcel') }}"><x-icon name="sheet" size="16" /> Excel</button>
        </div>
    </form>
</div>

<div class="summary mb-2">
    <div class="summary-item">
        <div class="summary-label">Omzet</div>
        <div class="summary-value">@rupiah($metrics['totalRevenue'])</div>
        <div class="summary-note">{{ $metrics['transactionCount'] }} transaksi</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Estimasi Laba</div>
        <div class="summary-value text-success">@rupiah($metrics['totalProfit'])</div>
        <div class="summary-note">estimasi keuntungan kotor</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Penerimaan Tunai</div>
        <div class="summary-value">@rupiah($metrics['totalCash'])</div>
        <div class="summary-note">tunai yang diterima pada periode ini</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Penerimaan Transfer</div>
        <div class="summary-value">@rupiah($metrics['totalTransfer'])</div>
        <div class="summary-note">transfer yang diterima pada periode ini</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Piutang Periode Ini</div>
        <div class="summary-value {{ $metrics['totalPiutang'] > 0 ? 'text-danger' : '' }}">@rupiah($metrics['totalPiutang'])</div>
        <div class="summary-note">belum terbayar oleh customer</div>
    </div>
</div>

<div class="layout-side">
    <div class="card">
        <div class="card-head"><span class="card-title"> Detail transaksi</span></div>
        <div class="table-wrap">
            <table class="table table-stack">
                <thead>
                    <tr><th>Invoice</th><th>Pelanggan</th><th>Tanggal</th><th class="num">Total</th><th>Metode</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td class="cell-title nowrap" data-label="Invoice">{{ $trx->invoice_number }}</td>
                        <td data-label="Pelanggan">{{ $trx->customer?->name ?? 'Umum' }}</td>
                        <td class="muted nowrap" data-label="Tanggal">{{ $trx->transaction_date->isoFormat('D MMM Y, HH:mm') }}</td>
                        <td class="num fw" data-label="Total">@rupiah($trx->total_amount)</td>
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
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="inbox" size="24" /></div><div class="empty-title">Tidak ada transaksi pada periode ini</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><span class="card-title">Penjualan per produk</span></div>
        @forelse($metrics['productSummary'] as $item)
        <div class="stock-item">
            <div class="cell-title" style="margin-bottom:6px;">{{ $item['product_name'] }}</div>
            <div class="pay-line"><span>Terjual</span><strong class="num">{{ $item['qty_sold'] }} tabung</strong></div>
            <div class="pay-line"><span>Omzet</span><strong class="num">@rupiah($item['revenue'])</strong></div>
            <div class="pay-line" style="margin-bottom:0;"><span>Laba kotor</span><strong class="num text-success">@rupiah($item['profit'])</strong></div>
        </div>
        @empty
        <div class="empty"><div class="empty-title">Belum ada data</div></div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('reportForm');
    function sync() {
        var type = document.getElementById('repType').value;
        form.querySelectorAll('[data-for]').forEach(function (el) {
            el.hidden = el.dataset.for.split(' ').indexOf(type) === -1;
        });
    }
    document.getElementById('repType').addEventListener('change', sync);
})();
</script>
@endpush
