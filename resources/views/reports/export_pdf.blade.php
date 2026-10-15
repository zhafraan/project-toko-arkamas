<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $label }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
        .header { background: #1a3a5c; color: #fff; padding: 18px 24px; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header p  { font-size: 11px; opacity: .8; margin: 0; }
        .content   { padding: 20px 24px; }
        .metrics   { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 20px; }
        .metric-box { background: #f0f4f8; border-radius: 8px; padding: 12px; }
        .metric-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
        .metric-value { font-size: 15px; font-weight: 800; color: #1a3a5c; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        thead th { background: #1a3a5c; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .badge-l { padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .badge-lunas { background: #d1fae5; color: #065f46; }
        .badge-belum { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; padding: 12px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔥 Arkamas Gas — Laporan</h1>
        <p>{{ $label }}</p>
        <p>Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>
    </div>
    <div class="content">
        <div class="metrics">
            <div class="metric-box">
                <div class="metric-label">Total Omset</div>
                <div class="metric-value">Rp {{ number_format($metrics['totalRevenue'],0,',','.') }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Cash Masuk</div>
                <div class="metric-value">Rp {{ number_format($metrics['totalCash'],0,',','.') }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Transfer Masuk</div>
                <div class="metric-value">Rp {{ number_format($metrics['totalTransfer'],0,',','.') }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Laba Bersih</div>
                <div class="metric-value">Rp {{ number_format($metrics['totalProfit'],0,',','.') }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Piutang</div>
                <div class="metric-value">Rp {{ number_format($metrics['totalPiutang'],0,',','.') }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Total Transaksi</div>
                <div class="metric-value">{{ $metrics['transactionCount'] }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th>Total</th>
                    <th>Dibayar</th><th>Sisa</th><th>Metode</th><th>Status</th><th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td>{{ $trx->invoice_number }}</td>
                    <td>{{ $trx->customer?->name ?? 'Umum' }}</td>
                    <td>Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                    <td>Rp {{ number_format($trx->paid_amount,0,',','.') }}</td>
                    <td>Rp {{ number_format($trx->remaining_debt,0,',','.') }}</td>
                    <td>{{ strtoupper($trx->payment_method) }}</td>
                    <td>
                        <span class="badge-l {{ $trx->payment_status === 'lunas' ? 'badge-lunas' : 'badge-belum' }}">
                            {{ strtoupper($trx->payment_status) }}
                        </span>
                    </td>
                    <td>{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:20px;color:#94a3b8;">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            SIM-LPG Arkamas Gas — Laporan ini dibuat secara otomatis oleh sistem
        </div>
    </div>
</body>
</html>
