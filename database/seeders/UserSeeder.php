<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStatistic;
use App\Enums\Directorate;
use App\Models\DailyReport;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $bukti_ss = [
            'https://permata-wfe.nos.jkt-1.neo.id/reports/yanto_gmail_com/2025-11-17_142434.png',
            'https://nos.jkt-1.neo.id/permata-wfe/reports/wildanal2_gmail_com/2025-11-19_201443.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal4_gmail_com/2025-11-18_065550.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-14_134146.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-12_173059.png',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-13_193129.jpg',
            'https://nos.jkt-1.neo.id/permata-wfe/reports/wildanal3_gmail_com/2025-11-19_201445.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal3_gmail_com/2025-11-17_151814.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-17_151948.jpg',
            'https://nos.jkt-1.neo.id/permata-wfe/reports/wildanal2_gmail_com/2025-11-20_210551.jpg',
            'https://nos.jkt-1.neo.id/permata-wfe/reports/wildanal2_gmail_com/2025-11-21_144101.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-18_233650.jpg',
            'https://permata-wfe.nos.jkt-1.neo.id/reports/wildanal2_gmail_com/2025-11-15_003923.jpg',
        ];

        for ($i = 1; $i <= 140; $i++) {
            $user = User::factory()->withoutTwoFactor()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->userName() . $i . '@permatagreen.com',
                'password' => 'admin',
                'user_level' => 1,
                'directorate' => $faker->randomElement(Directorate::cases())->value,
            ]);

            $totalLangkah = $faker->numberBetween(50000, 500000);
            $totalCo2e = $totalLangkah * 0.000005;
            $totalPohon = $totalCo2e / 21.77;
            DailyReport::create([
                'user_id' => $user->id,
                'tanggal_laporan' => now(),
                'langkah' => $totalLangkah,
                'co2e_reduction_kg' => $totalCo2e,
                'poin' => 0,
                'pohon' => $totalPohon,
                'bukti_screenshot' => $faker->randomElement($bukti_ss),
                'status_verifikasi' => 2,
                'ocr_result' => json_encode(['steps' => (int) ($totalLangkah ?? 0)]),
                'count_document' => 1,
                'verified_id' => 1,
            ]);

            UserStatistic::create([
                'user_id' => $user->id,
                'total_langkah' => $totalLangkah,
                'total_co2e_kg' => $totalCo2e,
                'total_pohon' => $totalPohon,
                'current_streak' => $faker->numberBetween(0, 30),
                'last_update' => now(),
            ]);
        }
    }
}
