<?php

namespace App\Http\Controllers;

use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
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

        // Jumlah tabung isi yang terjual hari ini (semua jenis penjualan gas)
        $todaySoldQty = (int) TransactionDetail::whereHas(
            'transaction',
            fn ($q) => $q->whereDate('transaction_date', $today)
        )->sum('qty');

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

        return view('dashboard.index', compact(
            'stocks', 'totalFilled', 'totalEmpty', 'totalBorrowed', 'lowStock', 'lowStockLimit',
            'todayRevenue', 'todayCash', 'todayTransfer', 'todayCount', 'todaySoldQty',
            'totalPiutang', 'countPiutang', 'loanTubes', 'loanCustomers',
            'monthRevenue', 'recentTransactions'
        ));
    }
}
