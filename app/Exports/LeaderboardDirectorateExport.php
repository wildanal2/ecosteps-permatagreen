<?php

namespace App\Exports;

use App\Models\UserStatistic;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaderboardDirectorateExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $sortBy;
    protected $sortDirection;

    public function __construct($sortBy = 'total_langkah', $sortDirection = 'desc')
    {
        $this->sortBy = $sortBy;
        $this->sortDirection = $sortDirection;
    }

    public function collection()
    {
        $directorates = UserStatistic::join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->select(
                'users.directorate',
                DB::raw('SUM(user_statistics.total_langkah) as total_langkah'),
                DB::raw('SUM(user_statistics.total_co2e_kg) as total_co2e_kg'),
                DB::raw('COUNT(DISTINCT users.id) as jumlah_peserta')
            )
            ->groupBy('users.directorate')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();

        return $directorates->map(function ($item, $index) {
            $directorate = \App\Enums\Directorate::tryFrom($item->directorate);
            
            return [
                $index + 1,
                $directorate ? $directorate->label() : '-',
                $item->jumlah_peserta,
                $item->total_langkah,
                number_format($item->total_co2e_kg, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'Direktorat',
            'Jumlah Peserta',
            'Total Langkah',
            'CO₂e Dihindari (kg)',
        ];
    }

    public function title(): string
    {
        return 'Leaderboard Direktorat';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB']
            ]
        ]);
        
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
