<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStatistic;
use App\Enums\Directorate;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 1; $i <= 140; $i++) {
            $user = User::factory()->withoutTwoFactor()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->userName() . $i . '@permatagreen.com',
                'password' => 'admin',
                'user_level' => 1,
                'directorate' => $faker->randomElement(Directorate::cases())->value,
            ]);

            $totalLangkah = $faker->numberBetween(50000, 500000);
            $totalCo2e = $totalLangkah * 0.00005;
            $totalPohon = $totalCo2e / 21.77;

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
