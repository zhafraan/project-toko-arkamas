<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Perhitungan statistik penjualan (murni PHP, tanpa query) agar angkanya
 * konsisten di semua database dan mudah diuji.
 *
 * Masukan:
 *  $trx   : [['id','customer_id','customer_name','date'(Y-m-d H:i:s),'total','method','status'], ...]
 *  $lines : [['transaction_id','product_id','product_name','type','qty','cost','subtotal'], ...]
 */
class SalesStats
{
    public const TYPE_LABELS = [
        'refill'        => 'Isi ulang (tukar tabung)',
        'pinjam_tabung' => 'Isi + pinjam tabung',
        'plus_tabung'   => 'Beli tabung + isi',
    ];

    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public static function compute(array $trx, array $lines, CarbonInterface $from, CarbonInterface $to): array
    {
        $revenue = 0.0;
        foreach ($trx as $t) {
            $revenue += (float) $t['total'];
        }
        $count = count($trx);

        // ---- Per baris item ----
        $units = 0;
        $cogs  = 0.0;
        $byProduct = [];
        $byType    = [];
        $unitsByTrx = [];
        foreach ($lines as $l) {
            $qty = (int) $l['qty'];
            $sub = (float) $l['subtotal'];
            $cost = (float) $l['cost'] * $qty;
            $units += $qty;
            $cogs  += $cost;
            $unitsByTrx[$l['transaction_id']] = ($unitsByTrx[$l['transaction_id']] ?? 0) + $qty;

            $pid = $l['product_id'];
            $byProduct[$pid] ??= ['name' => $l['product_name'] ?? '-', 'units' => 0, 'revenue' => 0.0, 'profit' => 0.0];
            $byProduct[$pid]['units']   += $qty;
            $byProduct[$pid]['revenue'] += $sub;
            $byProduct[$pid]['profit']  += $sub - $cost;

            $type = $l['type'];
            $byType[$type] ??= ['key' => $type, 'label' => self::TYPE_LABELS[$type] ?? $type, 'units' => 0, 'revenue' => 0.0];
            $byType[$type]['units']   += $qty;
            $byType[$type]['revenue'] += $sub;
        }
        $profit = $revenue - $cogs;

        usort($byProduct, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        foreach ($byProduct as &$p) {
            $p['share'] = $revenue > 0 ? round($p['revenue'] / $revenue * 100, 1) : 0.0;
        }
        unset($p);
        $byType = array_values($byType);
        usort($byType, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // ---- Deret waktu ----
        $days    = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $monthly = $days > 62;
        $buckets = [];
        $cursor  = $from->copy()->startOfDay();
        $end     = $to->copy()->startOfDay();
        if ($monthly) {
            $cursor = $cursor->startOfMonth();
        }
        while ($cursor <= $end) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $buckets[$key] = [
                'key'     => $key,
                'label'   => $monthly ? $cursor->translatedFormat('M Y') : $cursor->translatedFormat('j M'),
                'revenue' => 0.0, 'count' => 0, 'units' => 0,
            ];
            $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        // ---- Per transaksi ----
        $weekday = array_map(fn ($n) => ['label' => $n, 'count' => 0, 'revenue' => 0.0], self::DAYS);
        $hours   = array_fill(0, 24, 0);
        $methods = ['cash' => ['label' => 'Tunai', 'count' => 0, 'amount' => 0.0], 'transfer' => ['label' => 'Transfer', 'count' => 0, 'amount' => 0.0]];
        $status  = ['lunas' => ['label' => 'Lunas', 'count' => 0, 'amount' => 0.0], 'belum_lunas' => ['label' => 'Piutang', 'count' => 0, 'amount' => 0.0]];
        $customers = [];
        $perDay = [];

        foreach ($trx as $t) {
            $ts    = strtotime($t['date']);
            $total = (float) $t['total'];
            $day   = date('Y-m-d', $ts);
            $key   = $monthly ? date('Y-m', $ts) : $day;

            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += $total;
                $buckets[$key]['count']++;
                $buckets[$key]['units']   += $unitsByTrx[$t['id']] ?? 0;
            }
            $perDay[$day] = ($perDay[$day] ?? 0) + $total;

            $w = (int) date('N', $ts) - 1;
            $weekday[$w]['count']++;
            $weekday[$w]['revenue'] += $total;
            $hours[(int) date('G', $ts)]++;

            if (isset($methods[$t['method']])) {
                $methods[$t['method']]['count']++;
                $methods[$t['method']]['amount'] += $total;
            }
            if (isset($status[$t['status']])) {
                $status[$t['status']]['count']++;
                $status[$t['status']]['amount'] += $total;
            }

            if ($t['customer_id']) {
                $cid = $t['customer_id'];
                $customers[$cid] ??= ['name' => $t['customer_name'] ?? '-', 'count' => 0, 'revenue' => 0.0];
                $customers[$cid]['count']++;
                $customers[$cid]['revenue'] += $total;
            }
        }
        usort($customers, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $bestDay = null;
        if ($perDay) {
            arsort($perDay);
            $d = array_key_first($perDay);
            $bestDay = ['date' => $d, 'revenue' => $perDay[$d]];
        }

        return [
            'totals' => [
                'revenue' => $revenue,
                'count'   => $count,
                'units'   => $units,
                'avg'     => $count > 0 ? $revenue / $count : 0.0,
                'profit'  => $profit,
                'margin'  => $revenue > 0 ? $profit / $revenue * 100 : 0.0,
            ],
            'granularity' => $monthly ? 'month' : 'day',
            'series'      => array_values($buckets),
            'products'    => array_values($byProduct),
            'types'       => $byType,
            'methods'     => array_values($methods),
            'status'      => array_values($status),
            'weekday'     => $weekday,
            'hours'       => $hours,
            'customers'   => array_slice($customers, 0, 5),
            'bestDay'     => $bestDay,
            'activeDays'  => count($perDay),
            'days'        => $days,
        ];
    }

    /** Selisih persen terhadap periode pembanding; null bila pembanding 0. */
    public static function change(float $now, float $before): ?float
    {
        return $before > 0 ? ($now - $before) / $before * 100 : null;
    }
}
