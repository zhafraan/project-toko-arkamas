<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TubeLoan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Stok ringkasan
        $stocks = Product::where('is_active', true)->get();
        $totalFilled   = $stocks->sum('stock_filled');
        $totalEmpty    = $stocks->sum('stock_empty');
        $totalBorrowed = $stocks->sum('stock_borrowed');

        // Transaksi hari ini
        $todayTransactions = Transaction::whereDate('transaction_date', $today);
        $todayRevenue  = (clone $todayTransactions)->sum('total_amount');
        $todayCash     = (clone $todayTransactions)->where('payment_method', 'cash')
                            ->where('payment_status', 'lunas')->sum('paid_amount');
        $todayTransfer = (clone $todayTransactions)->where('payment_method', 'transfer')
                            ->where('payment_status', 'lunas')->sum('paid_amount');
        $todayCount    = (clone $todayTransactions)->count();

        // Piutang aktif
        $totalPiutang = Transaction::where('payment_status', 'belum_lunas')->sum('remaining_debt');
        $countPiutang = Transaction::where('payment_status', 'belum_lunas')->count();

        // Pinjaman aktif
        $activeLoanCount = TubeLoan::whereIn('status', ['dipinjam', 'sebagian_kembali'])->count();

        // Data khusus owner
        $monthRevenue = null;
        $recentTransactions = collect();

        if ($user->isOwner()) {
            $monthRevenue = Transaction::whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->sum('total_amount');

            $recentTransactions = Transaction::with(['customer', 'user'])
                ->latest('transaction_date')
                ->limit(10)
                ->get();
        } else {
            $recentTransactions = Transaction::with(['customer', 'user'])
                ->whereDate('transaction_date', $today)
                ->latest('transaction_date')
                ->limit(10)
                ->get();
        }

        // Grafik 7 hari terakhir
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dt = now()->subDays($i);
            $date = $dt->toDateString();
            $chartData[] = [
                'day'     => $dt->format('D'), // Sun, Mon, Tue...
                'date'    => $dt->format('d M'),
                'isToday' => $i === 0,
                'revenue' => (float) Transaction::whereDate('transaction_date', $date)->sum('total_amount'),
                'count'   => Transaction::whereDate('transaction_date', $date)->count(),
            ];
        }

        return view('dashboard.index', compact(
            'stocks', 'totalFilled', 'totalEmpty', 'totalBorrowed',
            'todayRevenue', 'todayCash', 'todayTransfer', 'todayCount',
            'totalPiutang', 'countPiutang', 'activeLoanCount',
            'monthRevenue', 'recentTransactions', 'chartData'
        ));
    }
}
