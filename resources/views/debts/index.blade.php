@extends('layouts.app')
@section('title', 'Piutang')
@section('page-title', 'Piutang')
@section('page-sub', 'Nota yang belum dibayar penuh')

@section('content')
<div class="summary mb-2">
    <div class="summary-item">
        <div class="summary-label"><x-icon name="wallet" size="16" /> Total piutang</div>
        <div class="summary-value {{ $totalPiutang > 0 ? 'text-danger' : 'text-success' }}">@rupiah($totalPiutang)</div>
        <div class="summary-note">{{ $transactions->total() }} nota belum lunas</div>
    </div>
</div>

<div class="card">
    <form method="GET" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari no. invoice atau nama pelanggan…">
        </div>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search'))
            <a href="{{ route('debts.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th>Invoice</th><th>Pelanggan</th><th>Tanggal</th>
                    <th class="num">Total</th><th class="num">Sudah dibayar</th><th class="num">Sisa</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td data-label="Invoice">
                        <div class="cell-title nowrap">{{ $trx->invoice_number }}</div>
                        <div class="cell-sub">Kasir: {{ $trx->user->name }}</div>
                    </td>
                    <td data-label="Pelanggan">
                        @if($trx->customer)
                            <a href="{{ route('customers.show', $trx->customer) }}" class="cell-title">{{ $trx->customer->name }}</a>
                        @else
                            <span class="muted">Umum</span>
                        @endif
                    </td>
                    <td class="muted" data-label="Tanggal">{{ $trx->transaction_date->isoFormat('D MMM Y') }}</td>
                    <td class="num" data-label="Total">@rupiah($trx->total_amount)</td>
                    <td class="num text-success" data-label="Dibayar">@rupiah($trx->paid_amount)</td>
                    <td class="num text-danger fw" data-label="Sisa">@rupiah($trx->remaining_debt)</td>
                    <td data-label="Aksi">
                        <div class="actions">
                            <button type="button" class="btn btn-success btn-sm"
                                    data-modal-open="payModal"
                                    data-action="{{ route('debts.pay', $trx) }}"
                                    data-subject="{{ $trx->invoice_number }}"
                                    data-remaining="{{ (float) $trx->remaining_debt }}">
                                <x-icon name="banknote" size="15" /> Bayar
                            </button>
                            <a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Lihat nota" aria-label="Lihat nota"><x-icon name="receipt" size="16" /></a>
                        </div>
                        @if($trx->debtPayments->count() > 0)
                        <div class="pay-history">
                            <div class="pay-history-label"><x-icon name="history" size="13" /> Riwayat pembayaran</div>
                            @foreach($trx->debtPayments->sortByDesc('payment_date') as $pm)
                            <div class="pay-history-row">
                                <div>
                                    <span class="fw">@rupiah($pm->amount_paid)</span>
                                    <span class="cell-sub">· {{ $pm->payment_date->isoFormat('D MMM Y') }} · {{ $pm->payment_method === 'cash' ? 'Tunai' : 'Transfer' }} · {{ $pm->user?->name }}</span>
                                </div>
                                @if(auth()->user()->isOwner())
                                <form method="POST" action="{{ route('debts.pay.destroy', $pm) }}" data-confirm="Batalkan pembayaran @rupiah($pm->amount_paid) untuk nota {{ $trx->invoice_number }}? Saldo piutang akan dikembalikan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Batalkan pembayaran" aria-label="Batalkan pembayaran"><x-icon name="undo" size="14" /></button>
                                </form>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty">
                        <div class="empty-icon" style="background:var(--success-soft);color:var(--success-ink);"><x-icon name="check" size="24" /></div>
                        <div class="empty-title">{{ request('search') ? 'Tidak ditemukan' : 'Tidak ada piutang.' }}</div>
                        <div>{{ request('search') ? 'Coba kata kunci lain.' : 'Seluruh nota sudah lunas.' }}</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links('vendor.pagination.simple') }}
</div>

{{-- Modal pembayaran --}}
<div class="modal-overlay" id="payModal">
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-title">Catat pembayaran</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" id="payForm" class="modal-body">
            @csrf
            <div class="modal-note">Nota <strong data-modal-subject></strong><div class="muted" id="payInfo" style="margin-top:2px;"></div></div>
            <div class="field">
                <label class="label" for="payAmount">Jumlah dibayar <span class="req">*</span></label>
                <div class="input-affix">
                    <span class="affix">Rp</span>
                    <input type="number" name="amount_paid" id="payAmount" class="input num" min="1" step="any" required>
                </div>
                <div class="hint"><button type="button" class="btn btn-ghost btn-xs" id="btnPayFull" style="margin-left:-8px;">Bayar seluruh sisa tagihan</button></div>
            </div>
            <div class="field">
                <label class="label" for="payMethod">Metode</label>
                <select name="payment_method" id="payMethod" class="select">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>
            <div class="field">
                <label class="label" for="payNotes">Catatan (opsional)</label>
                <input type="text" name="notes" id="payNotes" class="input" maxlength="255" placeholder="No. referensi / keterangan">
            </div>
            <div class="field-error" id="payError" hidden style="margin-bottom:12px;"></div>
            <button type="submit" class="btn btn-success btn-block" id="btnPay"><x-icon name="check" /> Simpan pembayaran</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
var payRemaining = 0;
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-modal-open="payModal"]');
    if (!b) return;
    payRemaining = parseFloat(b.dataset.remaining) || 0;
    var amount = document.getElementById('payAmount');
    amount.max = payRemaining; amount.value = payRemaining;
    document.getElementById('payInfo').textContent = 'Sisa tagihan: ' + rupiah(payRemaining);
    document.getElementById('payError').hidden = true;
});
document.getElementById('btnPayFull').addEventListener('click', function () {
    document.getElementById('payAmount').value = payRemaining;
});

document.getElementById('payForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = this, btn = document.getElementById('btnPay'), err = document.getElementById('payError');
    err.hidden = true; btn.disabled = true;
    postJSON(form.action, {
        amount_paid: document.getElementById('payAmount').value,
        payment_method: document.getElementById('payMethod').value,
        notes: document.getElementById('payNotes').value
    }).then(function (res) { toastAfterReload(res.message, 'success'); location.reload(); })
      .catch(function (ex) { err.textContent = errorText(ex); err.hidden = false; btn.disabled = false; });
});
</script>
@endpush
