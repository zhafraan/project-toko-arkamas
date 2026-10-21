<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\DebtPayment;
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
        // Semua parameter diverifikasi dulu — tidak ada nilai mentah dari user
        // yang masuk ke query/filter.
        $request->validate([
            'type'       => 'nullable|in:daily,weekly,monthly,yearly',
            'date'       => 'nullable|date',
            'month'      => 'nullable|integer|min:1|max:12',
            'year'       => 'nullable|integer|min:2000|max:2100',
            'week_start' => 'nullable|date',
            'week_end'   => 'nullable|date|after_or_equal:week_start',
        ], [
            'week_end.after_or_equal' => 'Rentang tanggal minggu tidak valid (akhir sebelum awal).',
        ]);

        $type  = in_array($request->input('type'), ['daily', 'weekly', 'monthly', 'yearly'], true)
            ? $request->input('type') : 'daily';
        $date  = $request->input('date') ?: today()->toDateString();
        $month = (int) ($request->input('month') ?: now()->month);
        $year  = (int) ($request->input('year') ?: now()->year);
        $weekStart = $request->input('week_start') ?: now()->startOfWeek()->toDateString();
        $weekEnd   = $request->input('week_end') ?: now()->endOfWeek()->toDateString();

        $query = Transaction::with(['details.product', 'customer', 'debtPayments']);

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

        // Batas periode dibutuhkan untuk kas cash-basis: uang dihitung pada
        // tanggal diterimanya pembayaran (nota atau cicilan), bukan tanggal nota.
        $periodStart = match ($type) {
            'weekly'  => Carbon::parse($weekStart)->startOfDay(),
            'monthly' => Carbon::create($year, $month, 1)->startOfDay(),
            'yearly'  => Carbon::create($year, 1, 1)->startOfDay(),
            default   => Carbon::parse($date)->startOfDay(),
        };
        $periodEnd = match ($type) {
            'weekly'  => Carbon::parse($weekEnd)->endOfDay(),
            'monthly' => Carbon::create($year, $month, 1)->endOfMonth()->endOfDay(),
            'yearly'  => Carbon::create($year, 12, 31)->endOfDay(),
            default   => Carbon::parse($date)->endOfDay(),
        };

        $transactions = $query->latest('transaction_date')->get();
        $metrics      = $this->calculateMetrics($transactions, $periodStart, $periodEnd);

        $periodTitle = match ($type) {
            'weekly'  => 'Laporan Mingguan',
            'monthly' => 'Laporan Bulanan',
            'yearly'  => 'Laporan Tahunan',
            default   => 'Laporan Harian',
        };
        $periodRange = match ($type) {
            'weekly'  => Carbon::parse($weekStart)->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($weekEnd)->isoFormat('D MMM Y'),
            'monthly' => Carbon::create($year, $month, 1)->isoFormat('MMMM Y'),
            'yearly'  => (string) $year,
            default   => Carbon::parse($date)->isoFormat('D MMMM Y'),
        };

        return compact('transactions', 'metrics', 'type', 'label', 'date', 'month', 'year', 'weekStart', 'weekEnd', 'periodTitle', 'periodRange');
    }

    /**
     * Uang masuk (cash-basis): pembayaran awal nota yang tanggal jualnya ada di
     * periode + cicilan piutang yang benar-benar dibayar pada periode.
     *
     * Sebelumnya dihitung dari paid_amount akhir seluruh nota periode, sehingga
     * cicilan yang menyusul (setelah periode berakhir) ikut terhitung — angka
     * "penerimaan" meleset dari uang yang diterima pada periode tersebut.
     */
    private function calculateMetrics($transactions, Carbon $periodStart, Carbon $periodEnd): array
    {
        $details = $transactions->flatMap->details;

        // Pembayaran awal = dibayar akhir - kembalian - seluruh cicilan piutang
        // (dicatat ulang lewat $installments pada tanggal pembayarannya).
        $saleReceived = fn ($method) => (float) $transactions->where('payment_method', $method)
            ->sum(fn ($t) => (float) $t->paid_amount - (float) $t->change_amount - (float) $t->debtPayments->sum('amount_paid'));

        // Cicilan piutang yang jatuh dalam periode (kas benar-benar bergerak)
        $installments = fn ($method) => (float) DebtPayment::where('payment_method', $method)
            ->whereBetween('payment_date', [$periodStart, $periodEnd])
            ->sum('amount_paid');

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
            'totalCash'        => $saleReceived('cash') + $installments('cash'),
            'totalTransfer'    => $saleReceived('transfer') + $installments('transfer'),
            'totalPiutang'     => (float) $transactions->where('payment_status', 'belum_lunas')->sum('remaining_debt'),
            'totalCOGS'        => (float) $details->sum(fn ($d) => $d->cost_price * $d->qty),
            'totalProfit'      => (float) $details->sum(fn ($d) => ($d->price - $d->cost_price) * $d->qty),
            'transactionCount' => $transactions->count(),
            'productSummary'   => $productSummary,
        ];
    }
}
