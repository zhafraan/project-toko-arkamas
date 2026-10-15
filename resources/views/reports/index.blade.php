@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Keuangan & Penjualan')

@section('content')

{{-- Filter Panel --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end;">
            <div>
                <label class="form-label">Tipe Laporan</label>
                <select name="type" class="form-control" onchange="toggleFilters(this.value)">
                    <option value="daily"   {{ $type=='daily'   ?'selected':'' }}>📅 Harian</option>
                    <option value="weekly"  {{ $type=='weekly'  ?'selected':'' }}>📆 Mingguan</option>
                    <option value="monthly" {{ $type=='monthly' ?'selected':'' }}>🗓️ Bulanan</option>
                    <option value="yearly"  {{ $type=='yearly'  ?'selected':'' }}>📊 Tahunan</option>
                </select>
            </div>
            <div id="filterDaily" class="{{ $type !== 'daily' ? 'hidden' : '' }}">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div id="filterWeekly" style="{{ $type !== 'weekly' ? 'display:none;' : '' }}">
                <label class="form-label">Dari</label>
                <input type="date" name="week_start" class="form-control" value="{{ $weekStart }}">
            </div>
            <div id="filterWeekly2" style="{{ $type !== 'weekly' ? 'display:none;' : '' }}">
                <label class="form-label">Sampai</label>
                <input type="date" name="week_end" class="form-control" value="{{ $weekEnd }}">
            </div>
            <div id="filterMonthly" style="{{ !in_array($type,['monthly','yearly']) ? 'display:none;' : '' }}">
                <label class="form-label">Bulan</label>
                <select name="month" class="form-control">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $month==$m?'selected':''}}>{{ \Carbon\Carbon::create(null,$m)->isoFormat('MMMM') }}</option>
                    @endforeach
                </select>
            </div>
            <div id="filterYear" style="{{ $type === 'daily' || $type === 'weekly' ? 'display:none;' : '' }}">
                <label class="form-label">Tahun</label>
                <select name="year" class="form-control">
                    @foreach(range(date('Y')-2, date('Y')) as $y)
                    <option value="{{ $y }}" {{ $year==$y?'selected':''}}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Tampilkan
            </button>
            <a href="{{ route('reports.exportPdf', request()->query()) }}" class="btn btn-danger" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('reports.exportExcel', request()->query()) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
        </form>
    </div>
</div>

{{-- Period Label --}}
<div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:12px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-chart-bar" style="font-size:20px;"></i>
    <span style="font-size:16px;font-weight:700;">{{ $periodLabel }}</span>
</div>

{{-- Metrics --}}
<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($metrics['totalRevenue'],0,',','.') }}</div>
            <div class="stat-label">Total Omset ({{ $metrics['transactionCount'] }} transaksi)</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fa-solid fa-piggy-bank"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($metrics['totalProfit'],0,',','.') }}</div>
            <div class="stat-label">Estimasi Laba Bersih</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#34d399,#059669);"><i class="fa-solid fa-money-bill"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($metrics['totalCash'],0,',','.') }}</div>
            <div class="stat-label">Cash Masuk</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#60a5fa,#2563eb);"><i class="fa-solid fa-mobile-screen"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($metrics['totalTransfer'],0,',','.') }}</div>
            <div class="stat-label">Transfer Masuk</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="stat-info">
            <div class="stat-value">Rp {{ number_format($metrics['totalPiutang'],0,',','.') }}</div>
            <div class="stat-label">Piutang Periode Ini</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">

    {{-- Transaction Table --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-receipt" style="color:var(--primary-light);margin-right:6px;"></i> Detail Transaksi</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th><th>Pelanggan</th><th>Total</th>
                        <th>Metode</th><th>Status</th><th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td style="font-size:12px;font-weight:700;color:var(--primary-light);">{{ $trx->invoice_number }}</td>
                        <td>{{ $trx->customer?->name ?: '-' }}</td>
                        <td style="font-weight:700;">Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                        <td>
                            @if($trx->payment_method === 'cash')
                                <span class="badge badge-info">Cash</span>
                            @else
                                <span class="badge badge-gray">Transfer</span>
                            @endif
                        </td>
                        <td>
                            @if($trx->payment_status === 'lunas')
                                <span class="badge badge-success">Lunas</span>
                            @else
                                <span class="badge badge-danger">Kasbon</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $trx->transaction_date->isoFormat('D MMM Y, HH:mm') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">Tidak ada data periode ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Product Summary --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-fire" style="color:var(--accent);margin-right:6px;"></i> Penjualan per Produk</span>
        </div>
        <div class="card-body" style="padding:14px;">
            @foreach($metrics['productSummary'] as $item)
            <div style="border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px;">
                <div style="font-weight:700;font-size:14px;margin-bottom:8px;">{{ $item['product_name'] }}</div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
                    <span>Qty Terjual</span><strong style="color:var(--text);">{{ $item['qty_sold'] }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
                    <span>Omset</span><strong style="color:var(--primary);">Rp {{ number_format($item['revenue'],0,',','.') }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);">
                    <span>Laba</span><strong style="color:var(--success);">Rp {{ number_format($item['profit'],0,',','.') }}</strong>
                </div>
            </div>
            @endforeach
            @if($metrics['productSummary']->isEmpty())
            <div style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">Tidak ada data</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleFilters(type) {
    document.getElementById('filterDaily').style.display   = type==='daily'   ? '' : 'none';
    document.getElementById('filterWeekly').style.display  = type==='weekly'  ? '' : 'none';
    document.getElementById('filterWeekly2').style.display = type==='weekly'  ? '' : 'none';
    document.getElementById('filterMonthly').style.display = ['monthly','yearly'].includes(type) ? '' : 'none';
    document.getElementById('filterYear').style.display    = ['monthly','yearly'].includes(type) ? '' : 'none';
}
</script>
@endpush
@endsection
