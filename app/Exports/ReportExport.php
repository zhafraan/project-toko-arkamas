<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Laporan ' . ucfirst($this->data['type']);
    }

    /** Header laporan (rata kiri) di atas judul kolom. */
    public function headings(): array
    {
        return [
            ['Toko Arkamas - Pangkalan Gas LPG'],
            [$this->data['periodTitle'] ?? ucfirst($this->data['type'])],
            ['Periode: ' . ($this->data['periodRange'] ?? '')],
            ['Dicetak: ' . now()->isoFormat('D MMMM Y, HH:mm')],
            [],
            [
                'No. Invoice', 'Tanggal', 'Pelanggan',
                'Total (Rp)', 'Dibayar (Rp)', 'Sisa (Rp)',
                'Status', 'Metode',
            ],
        ];
    }

    /** Angka dikirim sebagai angka (bukan teks) agar bisa dijumlah di Excel. */
    public function array(): array
    {
        return $this->data['transactions']->map(fn ($trx) => [
            $trx->invoice_number,
            $trx->transaction_date->format('d/m/Y H:i'),
            $trx->customer?->name ?? 'Umum',
            (float) $trx->total_amount,
            (float) $trx->paid_amount - (float) $trx->change_amount,
            (float) $trx->remaining_debt,
            $trx->payment_status === 'lunas' ? 'LUNAS' : 'PIUTANG',
            strtoupper($trx->payment_method),
        ])->toArray();
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /** Hitam putih: judul kolom latar hitam teks putih, baris header tebal. */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            6 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF000000']],
            ],
        ];
    }
}
