<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type      = $request->get('type', 'daily');
        $date      = $request->get('date', today()->toDateString());
        $month     = $request->get('month', now()->month);
        $year      = $request->get('year', now()->year);
        $weekStart = $request->get('week_start');
        $weekEnd   = $request->get('week_end');

        $query = Transaction::with(['details.product', 'customer']);

        switch ($type) {
            case 'daily':
                $query->whereDate('transaction_date', $date);
                $periodLabel = 'Laporan Harian: ' . \Carbon\Carbon::parse($date)->isoFormat('D MMMM Y');
                break;
            case 'weekly':
                $start = $weekStart ?? now()->startOfWeek()->toDateString();
                $end   = $weekEnd ?? now()->endOfWeek()->toDateString();
                $query->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(transaction_date)'), [$start, $end]);
                $periodLabel = "Laporan Mingguan: {$start} s/d {$end}";
                break;
            case 'monthly':
                $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month);
                $periodLabel = 'Laporan Bulanan: ' . \Carbon\Carbon::create($year, $month)->isoFormat('MMMM Y');
                break;
            case 'yearly':
                $query->whereYear('transaction_date', $year);
                $periodLabel = "Laporan Tahunan: {$year}";
                break;
        }

        $transactions = $query->latest('transaction_date')->get();

        $metrics = $this->calculateMetrics($transactions);

        return view('reports.index', compact(
            'transactions', 'metrics', 'type', 'date', 'month', 'year',
            'weekStart', 'weekEnd', 'periodLabel'
        ));
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getReportData($request);
        $pdf  = Pdf::loadView('reports.export_pdf', $data)->setPaper('a4', 'portrait');
        $filename = 'laporan-' . $data['type'] . '-' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $data     = $this->getReportData($request);
        $filename = 'laporan-' . $data['type'] . '-' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new ReportExport($data), $filename);
    }

    private function getReportData(Request $request): array
    {
        $type  = $request->get('type', 'daily');
        $date  = $request->get('date', today()->toDateString());
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);
        $start = $request->get('week_start', now()->startOfWeek()->toDateString());
        $end   = $request->get('week_end', now()->endOfWeek()->toDateString());

        $query = Transaction::with(['details.product', 'customer']);

        switch ($type) {
            case 'daily':
                $query->whereDate('transaction_date', $date);
                $label = 'Laporan Harian: ' . \Carbon\Carbon::parse($date)->isoFormat('D MMMM Y');
                break;
            case 'weekly':
                $query->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(transaction_date)'), [$start, $end]);
                $label = "Laporan Mingguan: {$start} s/d {$end}";
                break;
            case 'monthly':
                $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month);
                $label = 'Laporan Bulanan: ' . \Carbon\Carbon::create($year, $month)->isoFormat('MMMM Y');
                break;
            case 'yearly':
                $query->whereYear('transaction_date', $year);
                $label = "Laporan Tahunan: {$year}";
                break;
        }

        $transactions = $query->latest('transaction_date')->get();
        $metrics      = $this->calculateMetrics($transactions);

        return compact('transactions', 'metrics', 'type', 'label');
    }

    private function calculateMetrics($transactions): array
    {
        $totalRevenue    = $transactions->sum('total_amount');
        $totalCash       = $transactions->where('payment_method', 'cash')->where('payment_status', 'lunas')->sum('paid_amount');
        $totalTransfer   = $transactions->where('payment_method', 'transfer')->where('payment_status', 'lunas')->sum('paid_amount');
        $totalPiutang    = $transactions->where('payment_status', 'belum_lunas')->sum('remaining_debt');
        $totalCOGS       = $transactions->flatMap->details->sum(fn($d) => $d->cost_price * $d->qty);
        $totalProfit     = $transactions->flatMap->details->sum(fn($d) => ($d->price - $d->cost_price) * $d->qty);
        $transactionCount = $transactions->count();

        // Per produk
        $productSummary = $transactions->flatMap->details
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                return [
                    'product_name' => $product?->name ?? '-',
                    'qty_sold'     => $items->sum('qty'),
                    'revenue'      => $items->sum('subtotal'),
                    'profit'       => $items->sum(fn($d) => ($d->price - $d->cost_price) * $d->qty),
                ];
            })->values();

        return compact(
            'totalRevenue', 'totalCash', 'totalTransfer', 'totalPiutang',
            'totalCOGS', 'totalProfit', 'transactionCount', 'productSummary'
        );
    }
}
