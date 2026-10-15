<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Laporan ' . ucfirst($this->data['type']);
    }

    public function headings(): array
    {
        return [
            'No. Invoice', 'Tanggal', 'Pelanggan',
            'Total (Rp)', 'Dibayar (Rp)', 'Sisa (Rp)',
            'Status', 'Metode',
        ];
    }

    public function array(): array
    {
        return $this->data['transactions']->map(function ($trx, $i) {
            return [
                $trx->invoice_number,
                $trx->transaction_date->format('d/m/Y H:i'),
                $trx->customer?->name ?? 'Umum',
                number_format($trx->total_amount, 0, ',', '.'),
                number_format($trx->paid_amount, 0, ',', '.'),
                number_format($trx->remaining_debt, 0, ',', '.'),
                strtoupper($trx->payment_status),
                strtoupper($trx->payment_method),
            ];
        })->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1a3a5c']]],
        ];
    }
}
