<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getReportData($request);
        $data['periodLabel'] = $data['label'];

        return view('reports.index', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getReportData($request);
        $pdf  = Pdf::loadView('reports.export_pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download('laporan-' . $data['type'] . '-' . now()->format('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);

        return Excel::download(new ReportExport($data), 'laporan-' . $data['type'] . '-' . now()->format('Ymd') . '.xlsx');
    }

    /** Satu sumber data untuk tampilan, PDF, dan Excel agar angkanya selalu sama. */
    private function getReportData(Request $request): array
    {
        $type  = in_array($request->input('type'), ['daily', 'weekly', 'monthly', 'yearly'], true)
            ? $request->input('type') : 'daily';
        $date  = $request->input('date') ?: today()->toDateString();
        $month = (int) ($request->input('month') ?: now()->month);
        $year  = (int) ($request->input('year') ?: now()->year);
        $weekStart = $request->input('week_start') ?: now()->startOfWeek()->toDateString();
        $weekEnd   = $request->input('week_end') ?: now()->endOfWeek()->toDateString();

        $query = Transaction::with(['details.product', 'customer']);

        switch ($type) {
            case 'weekly':
                $query->whereBetween(DB::raw('DATE(transaction_date)'), [$weekStart, $weekEnd]);
                $label = 'Laporan Mingguan: ' . Carbon::parse($weekStart)->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($weekEnd)->isoFormat('D MMM Y');
                break;
            case 'monthly':
                $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month);
                $label = 'Laporan Bulanan: ' . Carbon::create($year, $month, 1)->isoFormat('MMMM Y');
                break;
            case 'yearly':
                $query->whereYear('transaction_date', $year);
                $label = "Laporan Tahunan: {$year}";
                break;
            default:
                $query->whereDate('transaction_date', $date);
                $label = 'Laporan Harian: ' . Carbon::parse($date)->isoFormat('D MMMM Y');
        }

        $transactions = $query->latest('transaction_date')->get();
        $metrics      = $this->calculateMetrics($transactions);

        return compact('transactions', 'metrics', 'type', 'label', 'date', 'month', 'year', 'weekStart', 'weekEnd');
    }

    private function calculateMetrics($transactions): array
    {
        $details = $transactions->flatMap->details;

        // Uang masuk = dibayar - kembalian (termasuk DP kasbon & cicilan yang sudah tercatat)
        $received = fn ($method) => (float) $transactions->where('payment_method', $method)
            ->sum(fn ($t) => $t->paid_amount - $t->change_amount);

        $productSummary = $details->groupBy('product_id')->map(function ($items) {
            return [
                'product_name' => $items->first()->product?->name ?? '-',
                'qty_sold'     => $items->sum('qty'),
                'revenue'      => $items->sum('subtotal'),
                'profit'       => $items->sum(fn ($d) => ($d->price - $d->cost_price) * $d->qty),
            ];
        })->sortByDesc('revenue')->values();

        return [
            'totalRevenue'     => (float) $transactions->sum('total_amount'),
            'totalCash'        => $received('cash'),
            'totalTransfer'    => $received('transfer'),
            'totalPiutang'     => (float) $transactions->where('payment_status', 'belum_lunas')->sum('remaining_debt'),
            'totalCOGS'        => (float) $details->sum(fn ($d) => $d->cost_price * $d->qty),
            'totalProfit'      => (float) $details->sum(fn ($d) => ($d->price - $d->cost_price) * $d->qty),
            'transactionCount' => $transactions->count(),
            'productSummary'   => $productSummary,
        ];
    }
}
