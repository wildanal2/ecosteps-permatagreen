<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaderboardParticipantExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $orderColumn = $this->sortBy === 'name' ? 'users.name' : 'user_statistics.' . $this->sortBy;
        
        $participants = User::where('user_level', 1)
            ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
            ->select(
                'users.name',
                'users.email',
                'users.directorate',
                'user_statistics.total_langkah',
                'user_statistics.total_co2e_kg',
                'user_statistics.current_streak'
            )
            ->orderBy($orderColumn, $this->sortDirection)
            ->get();

        return $participants->map(function ($item, $index) {
            $directorate = $item->directorate instanceof \App\Enums\Directorate 
                ? $item->directorate 
                : \App\Enums\Directorate::tryFrom($item->directorate);
            
            return [
                $index + 1,
                $item->name,
                $item->email,
                $directorate ? $directorate->label() : '-',
                $item->total_langkah,
                number_format($item->total_co2e_kg, 2),
                $item->current_streak,
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
        return 'Leaderboard Peserta';
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
