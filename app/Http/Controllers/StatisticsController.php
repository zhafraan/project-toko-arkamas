<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\SalesStats;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Statistik semua penjualan (khusus pemilik). Tidak menyentuh data penerimaan dari agen. */
class StatisticsController extends Controller
{
    private const PRESETS = [
        '7d'         => '7 hari terakhir',
        '30d'        => '30 hari terakhir',
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'this_year'  => 'Tahun ini',
        'custom'     => 'Pilih tanggal',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'range' => 'nullable|in:' . implode(',', array_keys(self::PRESETS)),
            'from'  => 'nullable|date',
            'to'    => 'nullable|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $range = $request->input('range', '30d');
        $today = today();

        switch ($range) {
            case '7d':
                [$from, $to] = [$today->copy()->subDays(6), $today->copy()];
                break;
            case 'this_month':
                [$from, $to] = [$today->copy()->startOfMonth(), $today->copy()];
                break;
            case 'last_month':
                $from = $today->copy()->subMonthNoOverflow()->startOfMonth();
                $to   = $from->copy()->endOfMonth()->startOfDay();
                break;
            case 'this_year':
                [$from, $to] = [$today->copy()->startOfYear(), $today->copy()];
                break;
            case 'custom':
                $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : $today->copy()->subDays(29);
                $to   = $request->filled('to') ? Carbon::parse($request->input('to'))->startOfDay() : $today->copy();
                break;
            default:
                $range = '30d';
                [$from, $to] = [$today->copy()->subDays(29), $today->copy()];
        }

        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();

        // Batasi rentang agar halaman tetap ringan
        $maxDays = 366;
        $clamped = false;
        if ((int) $from->diffInDays($to) + 1 > $maxDays) {
            $from    = $to->copy()->subDays($maxDays - 1);
            $clamped = true;
        }

        $current = SalesStats::compute(...$this->load($from, $to));

        // Periode pembanding: rentang sama panjang tepat sebelum periode ini
        $days     = (int) $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);
        $previous = SalesStats::compute(...$this->load($prevFrom, $prevTo));

        $compare = [
            'revenue' => SalesStats::change($current['totals']['revenue'], $previous['totals']['revenue']),
            'count'   => SalesStats::change($current['totals']['count'], $previous['totals']['count']),
            'units'   => SalesStats::change($current['totals']['units'], $previous['totals']['units']),
            'profit'  => SalesStats::change($current['totals']['profit'], $previous['totals']['profit']),
        ];

        $presets     = self::PRESETS;
        $periodLabel = $from->isoFormat('D MMM Y') . ' – ' . $to->isoFormat('D MMM Y');
        $prevLabel   = $prevFrom->isoFormat('D MMM Y') . ' – ' . $prevTo->isoFormat('D MMM Y');

        $last7DaysData = $this->getLast7DaysData($to);

        return view('statistics.index', [
            'stats'       => $current,
            'compare'     => $compare,
            'range'       => $range,
            'presets'     => $presets,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'periodLabel' => $periodLabel,
            'prevLabel'   => $prevLabel,
            'clamped'     => $clamped,
            'maxDays'     => $maxDays,
            'last7DaysData' => $last7DaysData,
        ]);
    }

    /** @return array{0: array, 1: array, 2: Carbon, 3: Carbon} */
    private function load(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end   = $to->copy()->endOfDay();

        $trx = Transaction::with('customer:id,name')
            ->whereBetween('transaction_date', [$start, $end])
            ->get(['id', 'customer_id', 'transaction_date', 'total_amount', 'payment_method', 'payment_status'])
            ->map(fn ($t) => [
                'id'            => $t->id,
                'customer_id'   => $t->customer_id,
                'customer_name' => $t->customer?->name,
                'date'          => $t->transaction_date->format('Y-m-d H:i:s'),
                'total'         => (float) $t->total_amount,
                'method'        => $t->payment_method,
                'status'        => $t->payment_status,
            ])->all();

        $lines = TransactionDetail::with('product:id,name')
            ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->get(['transaction_id', 'product_id', 'transaction_type', 'qty', 'cost_price', 'subtotal'])
            ->map(fn ($d) => [
                'transaction_id' => $d->transaction_id,
                'product_id'     => $d->product_id,
                'product_name'   => $d->product?->name,
                'type'           => $d->transaction_type,
                'qty'            => $d->qty,
                'cost'           => (float) $d->cost_price,
                'subtotal'       => (float) $d->subtotal,
            ])->all();

        return [$trx, $lines, $from, $to];
    }

    private function getLast7DaysData(Carbon $to)
    {
        $today = today();
        $from = $to->copy()->subDays(6)->startOfDay();

        $rows = TransactionDetail::whereHas(
            'transaction',
            fn ($q) => $q->where('transaction_date', '>=', $from)
        )
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->selectRaw('DATE(transactions.transaction_date) as d, SUM(transaction_details.qty) as qty, COUNT(DISTINCT transactions.id) as cnt')
            ->groupBy('d')->get()->keyBy('d');

        $chartData = [];
        $maxQty = 0;
        for ($i = 6; $i >= 0; $i--) {
            $dt  = $to->copy()->subDays($i);
            $qty = (int) ($rows[$dt->toDateString()]->qty ?? 0);
            $maxQty = max($maxQty, $qty);
            $chartData[] = [
                'day'     => $dt->translatedFormat('D'),
                'date'    => $dt->translatedFormat('d M'),
                'isToday' => $i === 0,
                'qty'     => $qty,
                'count'   => (int) ($rows[$dt->toDateString()]->cnt ?? 0),
            ];
        }

        foreach ($chartData as &$bar) {
            $bar['height'] = $maxQty > 0 ? max(3, round($bar['qty'] / $maxQty * 100)) : 3;
        }
        unset($bar);

        return $chartData;
    }
}
