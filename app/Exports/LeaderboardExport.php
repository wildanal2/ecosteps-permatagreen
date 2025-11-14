<?php

namespace App\Exports;

use App\Models\{User, UserStatistic};
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaderboardExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        $participants = User::where('user_level', 1)
            ->with(['statistics'])
            ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
            ->orderByDesc('user_statistics.total_langkah')
            ->select('users.*')
            ->get();

        $directorates = UserStatistic::join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->select(
                'users.directorate',
                DB::raw('SUM(user_statistics.total_langkah) as total_langkah'),
                DB::raw('SUM(user_statistics.total_co2e_kg) as total_co2e_kg'),
                DB::raw('COUNT(DISTINCT users.id) as jumlah_peserta')
            )
            ->groupBy('users.directorate')
            ->orderByDesc('total_langkah')
            ->get();

        $rows = [];
        $maxRows = max($participants->count(), $directorates->count());

        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            
            // Leaderboard Peserta (kiri)
            if (isset($participants[$i])) {
                $p = $participants[$i];
                $row[] = $i + 1;
                $row[] = $p->name;
                $row[] = $p->directorate?->label() ?? '-';
                $row[] = $p->statistics->total_langkah ?? 0;
                $row[] = number_format($p->statistics->total_co2e_kg ?? 0, 2);
                $row[] = $p->statistics->current_streak ?? 0;
            } else {
                $row = array_fill(0, 6, '');
            }

            // 5 kolom kosong
            $row = array_merge($row, ['', '', '', '', '']);

            // Leaderboard Direktorat (kanan)
            if (isset($directorates[$i])) {
                $d = $directorates[$i];
                $dirEnum = \App\Enums\Directorate::tryFrom($d->directorate);
                $row[] = $i + 1;
                $row[] = $dirEnum?->label() ?? '-';
                $row[] = $d->total_langkah ?? 0;
                $row[] = number_format($d->total_co2e_kg ?? 0, 2);
                $row[] = $d->jumlah_peserta ?? 0;
            } else {
                $row = array_merge($row, ['', '', '', '', '']);
            }

            $rows[] = $row;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'Nama',
            'Direktorat',
            'Total Langkah',
            'CO₂e Dihindari (kg)',
            'Runtutan (hari)',
            '',
            '',
            '',
            '',
            '',
            'Peringkat',
            'Direktorat',
            'Total Langkah',
            'CO₂e Dihindari (kg)',
            'Jumlah Peserta',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
