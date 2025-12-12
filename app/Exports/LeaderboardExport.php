<?php

namespace App\Exports;

use App\Enums\Directorate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaderboardExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $directorateName;

    public function __construct($data, $directorateName = 'Semua')
    {
        $this->data = $data;
        $this->directorateName = $directorateName;
    }

    public function collection()
    {
        return $this->data->map(function ($item, $index) { 
            
            return [
                $index + 1,
                $item->name ?? '-',
                $item->email ?? '-',
                $this->directorateName,
                $item->total_langkah ?? 0,
                number_format($item->total_co2e_kg ?? 0, 2),
                $item->current_streak ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'Nama',
            'Email',
            'Direktorat',
            'Total Langkah',
            'CO₂e Dihindari (kg)',
            'Runtutan (hari)',
        ];
    }

    public function title(): string
    {
        return substr($this->directorateName, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB']
            ]
        ]);
        
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
