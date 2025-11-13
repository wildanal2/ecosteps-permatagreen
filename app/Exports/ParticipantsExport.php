<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class ParticipantsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return User::query()
            ->where('user_level', 1)
            ->with('statistics');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Email',
            'Direktorat',
            'Cabang',
            'Transportasi',
            'Jarak (km)',
            'Mode Kerja',
            'Total Langkah',
            'Total Jarak (km)',
            'CO₂e Dihindari (kg)',
            'Est. Pohon',
            'Jumlah Streak (hari)',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->directorate ?? '-',
            $user->branch ?? '-',
            $user->transport ?? '-',
            $user->distance ?? 0,
            $user->work_mode ?? '-',
            $user->statistics->total_langkah ?? 0,
            number_format(($user->statistics->total_langkah ?? 0) * 0.0008, 2),
            number_format($user->statistics->total_co2e_kg ?? 0, 2),
            number_format($user->statistics->total_pohon ?? 0, 0),
            $user->statistics->current_streak ?? 0,
        ];
    }
}
