<?php

namespace App\Http\Controllers;

use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TubeLoan;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $today = today();

        // ---- Stok ----
        $stocks        = Product::where('is_active', true)->orderBy('name')->get();
        $totalFilled   = $stocks->sum('stock_filled');
        $totalEmpty    = $stocks->sum('stock_empty');
        $totalBorrowed = $stocks->sum('stock_borrowed');
        $lowStockLimit = Product::LOW_STOCK;
        $lowStock      = $stocks->filter(fn ($p) => $p->stock_filled <= $lowStockLimit);

        // ---- Hari ini ----
        $todayQuery   = fn () => Transaction::whereDate('transaction_date', $today);
        $todayRevenue = (float) $todayQuery()->sum('total_amount');
        $todayCount   = $todayQuery()->count();

        // Uang yang benar-benar masuk hari ini (cash-basis): pembayaran awal nota
        // yang terjual hari ini (dikurangi kembalian dan cicilan) ditambah cicilan
        // piutang yang jatuh tempo dibayar hari ini — dipisah per metode.
        $saleReceived = $todayQuery()->with('debtPayments')->get()
            ->groupBy('payment_method')
            ->map(fn ($ts) => $ts->sum(
                fn ($t) => (float) $t->paid_amount - (float) $t->change_amount - (float) $t->debtPayments->sum('amount_paid')
            ));
        $installmentsToday = DebtPayment::whereDate('payment_date', $today)->get()
            ->groupBy('payment_method')
            ->map->sum('amount_paid');

        $todayCash     = (float) (($saleReceived['cash'] ?? 0) + ($installmentsToday['cash'] ?? 0));
        $todayTransfer = (float) (($saleReceived['transfer'] ?? 0) + ($installmentsToday['transfer'] ?? 0));

        // ---- Piutang & pinjaman ----
        $totalPiutang = (float) Transaction::where('payment_status', 'belum_lunas')->sum('remaining_debt');
        $countPiutang = Transaction::where('payment_status', 'belum_lunas')->count();

        $activeLoans   = TubeLoan::whereIn('status', ['dipinjam', 'sebagian_kembali']);
        $loanTubes     = (int) (clone $activeLoans)->selectRaw('COALESCE(SUM(qty_borrowed - qty_returned), 0) as n')->value('n');
        $loanCustomers = (clone $activeLoans)->distinct()->count('customer_id');

        // ---- Khusus pemilik ----
        $monthRevenue = null;
        if ($user->isOwner()) {
            $monthRevenue = (float) Transaction::whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->sum('total_amount');
        }

        $recent = Transaction::with('customer')->latest('transaction_date')->limit(8);
        if ($user->isKasir()) {
            $recent->whereDate('transaction_date', $today);
        }
        $recentTransactions = $recent->get();

        // ---- Grafik 7 hari (satu query) ----
        $from = $today->copy()->subDays(6)->startOfDay();
        $rows = Transaction::where('transaction_date', '>=', $from)
            ->selectRaw('DATE(transaction_date) as d, SUM(total_amount) as revenue, COUNT(*) as cnt')
            ->groupBy('d')->get()->keyBy('d');

        $chartData = [];
        $maxRevenue = 0;
        for ($i = 6; $i >= 0; $i--) {
            $dt      = $today->copy()->subDays($i);
            $revenue = (float) ($rows[$dt->toDateString()]->revenue ?? 0);
            $maxRevenue = max($maxRevenue, $revenue);
            $chartData[] = [
                'day'     => $dt->translatedFormat('D'),
                'date'    => $dt->translatedFormat('d M'),
                'isToday' => $i === 0,
                'revenue' => $revenue,
                'count'   => (int) ($rows[$dt->toDateString()]->cnt ?? 0),
                'short'   => $this->shortMoney($revenue),
            ];
        }
        // tinggi batang dalam persen (min 3% agar hari kosong tetap terlihat)
        foreach ($chartData as &$bar) {
            $bar['height'] = $maxRevenue > 0 ? max(3, round($bar['revenue'] / $maxRevenue * 100)) : 3;
        }
        unset($bar);

        return view('dashboard.index', compact(
            'stocks', 'totalFilled', 'totalEmpty', 'totalBorrowed', 'lowStock', 'lowStockLimit',
            'todayRevenue', 'todayCash', 'todayTransfer', 'todayCount',
            'totalPiutang', 'countPiutang', 'loanTubes', 'loanCustomers',
            'monthRevenue', 'recentTransactions', 'chartData'
        ));
    }

    private function shortMoney(float $n): string
    {
        if ($n >= 1_000_000) {
            return number_format($n / 1_000_000, 1, ',', '.') . ' jt';
        }
        if ($n >= 1_000) {
            return number_format($n / 1_000, 0, ',', '.') . ' rb';
        }
        return $n > 0 ? number_format($n, 0, ',', '.') : '0';
    }
}
