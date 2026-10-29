<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $transaction->invoice_number }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('arkamas_theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { display: grid; place-items: start center; padding: 28px 16px 40px; }
        .receipt-wrap { width: 100%; max-width: 360px; }
        /* Kertas nota selalu putih agar tercetak benar, apa pun tema layar */
        .receipt { background: #fff; color: #1d1f2e; border-radius: 14px; padding: 22px 22px 18px; box-shadow: var(--shadow-md); font-size: 13px; }
        .r-head { text-align: center; }
        .r-name { font-family: 'Lexend', sans-serif; font-size: 18px; font-weight: 600; letter-spacing: 0; }
        .r-sub { font-size: 12px; color: #6b6f8c; }
        .r-line { border: 0; border-top: 1px dashed #b9bdd2; margin: 14px 0; }
        .r-row { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        .r-row span:first-child { color: #6b6f8c; }
        .r-row span:last-child { text-align: right; font-weight: 600; }
        .r-item { margin-bottom: 10px; }
        .r-item-name { font-weight: 700; }
        .r-item-meta { display: flex; justify-content: space-between; color: #4b4f6e; font-variant-numeric: tabular-nums; }
        .r-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 99px; background: #E3EBDD; color: #2E5239; margin: 2px 0; }
        .r-total { font-family: 'Lexend', sans-serif; display: flex; justify-content: space-between; align-items: baseline; font-size: 17px; font-weight: 600; margin: 8px 0; }
        .r-status { text-align: center; font-weight: 800; letter-spacing: .04em; padding: 8px; border-radius: 8px; margin-top: 12px; }
        .r-status.lunas { background: #e0f6ec; color: #05694a; }
        .r-status.belum { background: #fdeaea; color: #a8262b; }
        .r-loan { margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: #fff3d1; color: #835400; font-size: 12px; }
        .r-foot { text-align: center; color: #6b6f8c; font-size: 12px; margin-top: 14px; }
        .num { font-variant-numeric: tabular-nums; }
        .receipt-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 18px; }
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .receipt-wrap { max-width: none; }
            .receipt { box-shadow: none; border-radius: 0; padding: 0; }
            .receipt-actions { display: none !important; }
        }
    </style>
</head>
<body>
<div class="receipt-wrap">
    <div class="receipt">
        <div class="r-head">
            <div class="r-name">ARKAMAS GAS</div>
            <div class="r-sub">Pangkalan Gas Elpiji</div>
        </div>

        <hr class="r-line">

        <div class="r-row"><span>No. nota</span><span>{{ $transaction->invoice_number }}</span></div>
        <div class="r-row"><span>Tanggal</span><span>{{ $transaction->transaction_date->isoFormat('D MMM Y, HH:mm') }}</span></div>
        <div class="r-row"><span>Kasir</span><span>{{ $transaction->user->name }}</span></div>
        <div class="r-row"><span>Pelanggan</span><span>{{ $transaction->customer?->name ?? 'Umum' }}</span></div>

        <hr class="r-line">

        @foreach($transaction->details as $detail)
        <div class="r-item">
            <div class="r-item-name">{{ $detail->product->name }}</div>
            <span class="r-tag">
                @if($detail->transaction_type === 'jual')
                    Penjualan
                @elseif($detail->transaction_type === 'refill')
                    Isi ulang (tukar tabung)
                @elseif($detail->transaction_type === 'pinjam_tabung')
                    Pinjam tabung
                @elseif($detail->transaction_type === 'plus_tabung')
                    Plus tabung
                @else
                    Penjualan
                @endif
            </span>
            <div class="r-item-meta">
                <span>{{ $detail->qty }} {{ $detail->unit ?? '' }} × @rupiah($detail->price)</span>
                <strong>@rupiah($detail->subtotal)</strong>
            </div>
        </div>
        @endforeach

        <hr class="r-line">

        <div class="r-total"><span>TOTAL</span><span class="num">@rupiah($transaction->total_amount)</span></div>
        <div class="r-row"><span>Metode</span><span>{{ $transaction->payment_method === 'cash' ? 'Tunai' : 'Transfer' }}</span></div>
        <div class="r-row"><span>Dibayar</span><span class="num">@rupiah($transaction->paid_amount)</span></div>
        @if($transaction->change_amount > 0)
        <div class="r-row"><span>Kembalian</span><span class="num">@rupiah($transaction->change_amount)</span></div>
        @endif
        @if($transaction->remaining_debt > 0)
        <div class="r-row"><span>Sisa tagihan</span><span class="num" style="color:#a8262b;">@rupiah($transaction->remaining_debt)</span></div>
        @endif

        <div class="r-status {{ $transaction->payment_status === 'lunas' ? 'lunas' : 'belum' }}">
            {{ $transaction->payment_status === 'lunas' ? 'LUNAS' : 'BELUM LUNAS' }}
        </div>

        @if($transaction->tubeLoans->count() > 0)
        <div class="r-loan">
            <strong>Pinjaman tabung</strong>
            @foreach($transaction->tubeLoans as $loan)
            <div>
                {{ $loan->product->name }}: {{ $loan->qty_borrowed }} tabung
                @if($loan->deposit_amount > 0) · deposit @rupiah($loan->deposit_amount) @endif
            </div>
            @endforeach
            <div style="margin-top:4px;">Mohon kembalikan tabung dalam kondisi bersih.</div>
        </div>
        @endif

        <div class="r-foot">
            Terima kasih telah berbelanja di Arkamas Gas.<br>Simpan nota ini sebagai bukti pembelian.
        </div>
    </div>

    <div class="receipt-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()"><x-icon name="printer" /> Cetak nota</button>
        <a href="{{ route('pos.index') }}" class="btn btn-outline"><x-icon name="plus" /> Transaksi baru</a>
        <a href="{{ route('dashboard') }}" class="btn btn-ghost">Ke dashboard</a>
    </div>
</div>
</body>
</html>
