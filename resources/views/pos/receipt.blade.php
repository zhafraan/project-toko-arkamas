<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota {{ $transaction->invoice_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', monospace; background: linear-gradient(180deg, #D0E1F2 0%, #9FC0E3 45%, #4D79A8 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }

        .receipt-wrapper {
            background: #fff; width: 340px;
            border-radius: 16px;
            box-shadow: 0 16px 50px rgba(34, 58, 94, 0.25);
            overflow: hidden;
            border: 1px solid #D0E1F2;
        }
        .receipt-header {
            background: linear-gradient(135deg, #223A5E 0%, #355982 50%, #4D79A8 100%);
            color: #fff; text-align: center; padding: 22px 16px;
        }
        .receipt-header .store-name { font-size: 19px; font-weight: 800; letter-spacing: .5px; }
        .receipt-header .store-sub { font-size: 11px; color: #D0E1F2; margin-top: 3px; }
        .receipt-header .invoice-num {
            display: inline-block; margin-top: 10px;
            background: rgba(208, 225, 242, 0.25); color: #fff;
            padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
            border: 1px solid rgba(255,255,255,0.4);
        }

        .receipt-body { padding: 16px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
        .info-label { color: #64748b; }
        .info-value { font-weight: 600; color: #1e293b; text-align: right; }

        .divider { border: none; border-top: 1.5px dashed #e2e8f0; margin: 12px 0; }

        .items-header {
            display: flex; justify-content: space-between;
            font-size: 10px; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px;
        }
        .item-row { margin-bottom: 10px; }
        .item-name { font-size: 13px; font-weight: 600; }
        .item-type {
            display: inline-block; font-size: 10px; padding: 1px 7px;
            border-radius: 10px; margin: 3px 0;
        }
        .type-refill        { background: #dbeafe; color: #1e40af; }
        .type-pinjam_tabung { background: #ffedd5; color: #9a3412; }
        .type-plus_tabung   { background: #d1fae5; color: #065f46; }
        .item-detail { display: flex; justify-content: space-between; font-size: 12px; color: #475569; }
        .item-subtotal { font-weight: 700; color: #1e293b; }

        .total-section { background: #f8fafc; border-radius: 8px; padding: 12px; margin-top: 10px; }
        .total-line { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; }
        .total-line.grand { font-size: 15px; font-weight: 800; border-top: 2px solid #1a3a5c; padding-top: 8px; margin-top: 4px; color: #1a3a5c; }

        .status-badge {
            text-align: center; padding: 8px; margin-top: 10px;
            border-radius: 8px; font-size: 13px; font-weight: 800;
            letter-spacing: 1px; text-transform: uppercase;
        }
        .status-lunas     { background: #d1fae5; color: #065f46; }
        .status-belum     { background: #fee2e2; color: #991b1b; }

        .receipt-footer { text-align: center; padding: 14px 16px; border-top: 1px solid #e2e8f0; }
        .receipt-footer p { font-size: 11px; color: #94a3b8; }

        /* Loan info */
        .loan-box { background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 8px; padding: 10px 12px; margin-top: 10px; }
        .loan-box-title { font-size: 11px; font-weight: 700; color: #9a3412; margin-bottom: 6px; text-transform: uppercase; }

        /* Print Actions */
        .print-actions {
            display: flex; gap: 10px; justify-content: center;
            padding: 16px;
        }
        .btn-print {
            padding: 9px 20px; border-radius: 8px; border: none;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
        }
        .btn-print.primary { background: #1a3a5c; color: #fff; }
        .btn-print.success { background: #10b981; color: #fff; }
        .btn-print.outline { background: #fff; color: #1a3a5c; border: 1.5px solid #1a3a5c; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-wrapper { box-shadow: none; border-radius: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
<div>
    <div class="receipt-wrapper">
        {{-- Header --}}
        <div class="receipt-header">
            <div class="store-name">🔥 ARKAMAS GAS</div>
            <div class="store-sub">Pangkalan Gas Elpiji Resmi</div>
            <div class="invoice-num">{{ $transaction->invoice_number }}</div>
        </div>

        {{-- Body --}}
        <div class="receipt-body">
            {{-- Transaction Info --}}
            <div class="info-row"><span class="info-label">Tanggal</span><span class="info-value">{{ $transaction->transaction_date->isoFormat('D MMM Y, HH:mm') }}</span></div>
            <div class="info-row"><span class="info-label">Kasir</span><span class="info-value">{{ $transaction->user->name }}</span></div>
            <div class="info-row"><span class="info-label">Pelanggan</span><span class="info-value">{{ $transaction->customer?->name ?? 'Umum' }}</span></div>

            <hr class="divider">

            {{-- Items --}}
            <div class="items-header">
                <span>Produk / Layanan</span>
                <span>Subtotal</span>
            </div>

            @foreach($transaction->details as $detail)
            <div class="item-row">
                <div class="item-name">{{ $detail->product->name }}</div>
                <span class="item-type type-{{ $detail->transaction_type }}">
                    @if($detail->transaction_type === 'refill') 🔄 Refill
                    @elseif($detail->transaction_type === 'pinjam_tabung') 📦 Pinjam Tabung
                    @else 🆕 Beli + Tabung
                    @endif
                </span>
                <div class="item-detail">
                    <span>{{ $detail->qty }} x Rp {{ number_format($detail->price, 0, ',', '.') }}</span>
                    <span class="item-subtotal">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach

            {{-- Total --}}
            <div class="total-section">
                <div class="total-line">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="total-line">
                    <span>Dibayar</span>
                    <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                </div>
                @if($transaction->change_amount > 0)
                <div class="total-line">
                    <span>Kembalian</span>
                    <span style="color:var(--success);">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->remaining_debt > 0)
                <div class="total-line" style="color:#ef4444;">
                    <span>Sisa Tagihan</span>
                    <span>Rp {{ number_format($transaction->remaining_debt, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="total-line grand">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Metode --}}
            <div style="display:flex;gap:8px;margin-top:10px;">
                <div style="flex:1;background:#f1f5f9;border-radius:7px;padding:7px;text-align:center;font-size:11px;color:#475569;">
                    <div style="font-weight:700;">{{ strtoupper($transaction->payment_method) }}</div>
                    <div>Metode Bayar</div>
                </div>
                <div class="status-badge flex-1 {{ $transaction->payment_status === 'lunas' ? 'status-lunas' : 'status-belum' }}" style="flex:1;">
                    {{ $transaction->payment_status === 'lunas' ? '✅ LUNAS' : '🔴 BELUM LUNAS' }}
                </div>
            </div>

            {{-- Tube Loans --}}
            @if($transaction->tubeLoans->count() > 0)
            <div class="loan-box">
                <div class="loan-box-title">📦 Info Pinjaman Tabung</div>
                @foreach($transaction->tubeLoans as $loan)
                <div style="font-size:12px;margin-bottom:4px;">
                    {{ $loan->product->name }}: <strong>{{ $loan->qty_borrowed }} tabung</strong>
                    @if($loan->deposit_amount > 0)
                    — Deposit: <strong>Rp {{ number_format($loan->deposit_amount, 0, ',', '.') }}</strong>
                    @endif
                </div>
                @endforeach
                <div style="font-size:11px;color:#b45309;margin-top:4px;">*Tabung harap dikembalikan dalam keadaan bersih</div>
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="receipt-footer">
            <p>Terima kasih telah berbelanja di Arkamas Gas!</p>
            <p style="margin-top:3px;">Simpan nota ini sebagai bukti pembelian.</p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="print-actions">
        <button onclick="window.print()" class="btn-print primary">🖨️ Cetak Nota</button>
        <a href="{{ route('pos.index') }}" class="btn-print outline">+ Transaksi Baru</a>
        <a href="{{ route('dashboard') }}" class="btn-print outline">Dashboard</a>
    </div>
</div>
</body>
</html>
