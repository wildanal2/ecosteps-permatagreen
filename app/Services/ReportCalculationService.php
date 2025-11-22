<?php

namespace App\Services;

use App\Models\{DailyReport, UserStatistic};
use App\Enums\{StatusVerifikasi, EmissionFactor, TreeCo2Absorption};

class ReportCalculationService
{
    public function recalculate(int $reportId, int $steps): void
    {
        $report = DailyReport::findOrFail($reportId);

        // 1. Konversi langkah ke jarak (km)
        $jarak = ($steps * 0.75) / 1000;
        
        // 2. Perhitungan Emisi (kg CO2e)
        $co2e = round($jarak * EmissionFactor::default()->getValue(), 2);
        
        // 3. Konversi Pohon
        $pohon = round($co2e / TreeCo2Absorption::default()->getValue(), 2);

        $report->update([
            'langkah' => $steps,
            'co2e_reduction_kg' => $co2e,
            'pohon' => $pohon,
            'poin' => $steps,
        ]);

        $this->recalculateUserStatistics($report->user_id);
    }

    private function recalculateUserStatistics(int $userId): void
    {
        $reports = DailyReport::where('user_id', $userId)
            ->where('status_verifikasi', StatusVerifikasi::DIVERIFIKASI)
            ->get();

        $totalLangkah = $reports->sum('langkah');
        $totalCo2e = $reports->sum('co2e_reduction_kg');
        $totalPohon = $reports->sum('pohon');

        $currentStreak = $this->calculateStreak($userId);

        $stats = UserStatistic::firstOrCreate(['user_id' => $userId]);
        $stats->update([
            'total_langkah' => $totalLangkah,
            'total_co2e_kg' => $totalCo2e,
            'total_pohon' => $totalPohon,
            'current_streak' => $currentStreak,
            'last_update' => now(),
        ]);
    }

    private function calculateStreak(int $userId): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();

        while (true) {
            $report = DailyReport::where('user_id', $userId)
                ->where('tanggal_laporan', $currentDate->toDateString())
                ->whereRaw('DATE(created_at) = tanggal_laporan')
                ->where('status_verifikasi', StatusVerifikasi::DIVERIFIKASI)
                ->where('langkah', '>', 0)
                ->first();

            if (!$report) {
                break;
            }

            $streak++;
            $currentDate->subDay();
        }

        return $streak;
    }
}
