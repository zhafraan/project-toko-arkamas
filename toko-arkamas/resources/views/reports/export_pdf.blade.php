<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $label }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1d1f2e; margin: 0; }
        .header { background: #5b5f97; color: #fff; padding: 18px 24px; }
        .header h1 { font-size: 17px; margin: 0 0 4px; }
        .header p { font-size: 10.5px; margin: 0; opacity: .85; }
        .content { padding: 18px 24px; }
        table { width: 100%; border-collapse: collapse; }
        .metrics td { width: 33.33%; padding: 0 6px 10px 0; vertical-align: top; }
        .metric-box { background: #f3f4f8; border: 1px solid #e0e3ed; padding: 9px 11px; }
        .metric-label { font-size: 9.5px; color: #6b6f8c; }
        .metric-value { font-size: 14px; font-weight: bold; color: #3e4270; margin-top: 3px; }
        .data { margin-top: 8px; }
        .data th { background: #5b5f97; color: #fff; padding: 7px 8px; text-align: left; font-size: 9.5px; }
        .data td { padding: 7px 8px; border-bottom: 1px solid #e0e3ed; font-size: 10px; }
        .data tr.even td { background: #f7f8fb; }
        .right { text-align: right; }
        .ok { color: #05694a; font-weight: bold; }
        .bad { color: #a8262b; font-weight: bold; }
        .footer { text-align: center; padding: 10px; font-size: 9.5px; color: #8f94b3; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Arkamas Gas — Laporan Penjualan</h1>
        <p>{{ $label }}</p>
        <p>Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>
    </div>

    <div class="content">
        <table class="metrics">
            <tr>
                <td><div class="metric-box"><div class="metric-label">Omzet</div><div class="metric-value">@rupiah($metrics['totalRevenue'])</div></div></td>
                <td><div class="metric-box"><div class="metric-label">Estimasi laba kotor</div><div class="metric-value">@rupiah($metrics['totalProfit'])</div></div></td>
                <td><div class="metric-box"><div class="metric-label">Total transaksi</div><div class="metric-value">{{ $metrics['transactionCount'] }}</div></div></td>
            </tr>
            <tr>
                <td><div class="metric-box"><div class="metric-label">Tunai masuk</div><div class="metric-value">@rupiah($metrics['totalCash'])</div></div></td>
                <td><div class="metric-box"><div class="metric-label">Transfer masuk</div><div class="metric-value">@rupiah($metrics['totalTransfer'])</div></div></td>
                <td><div class="metric-box"><div class="metric-label">Piutang periode ini</div><div class="metric-value">@rupiah($metrics['totalPiutang'])</div></div></td>
            </tr>
        </table>

        <table class="data">
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th class="right">Total</th>
                    <th class="right">Dibayar</th><th class="right">Sisa</th><th>Metode</th><th>Status</th><th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td>{{ $trx->invoice_number }}</td>
                    <td>{{ $trx->customer?->name ?? 'Umum' }}</td>
                    <td class="right">@rupiah($trx->total_amount)</td>
                    <td class="right">@rupiah($trx->paid_amount - $trx->change_amount)</td>
                    <td class="right">@rupiah($trx->remaining_debt)</td>
                    <td>{{ $trx->payment_method === 'cash' ? 'Tunai' : 'Transfer' }}</td>
                    <td class="{{ $trx->payment_status === 'lunas' ? 'ok' : 'bad' }}">{{ $trx->payment_status === 'lunas' ? 'Lunas' : 'Kasbon' }}</td>
                    <td>{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:18px;color:#8f94b3;">Tidak ada data pada periode ini</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">Arkamas Gas — laporan ini dibuat otomatis oleh sistem</div>
    </div>
</body>
</html>
