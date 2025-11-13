<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $branches = ['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Semarang', 'Makassar', 'Palembang', 'Tangerang', 'Depok', 'Bekasi'];
        $directorates = ['Keuangan', 'Operasional', 'SDM', 'Pemasaran', 'IT', 'Produksi', 'Legal', 'Umum'];
        $transports = ['Motor', 'Mobil', 'Angkutan Umum', 'Sepeda', 'Jalan Kaki'];
        $workModes = ['WFO', 'WFH'];

        for ($i = 1; $i <= 140; $i++) {
            User::factory()->withoutTwoFactor()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->userName() . $i . '@permatagreen.com',
                'password' => 'admin',
                'user_level' => 1,
                'branch' => $faker->randomElement($branches),
                'directorate' => $faker->randomElement($directorates),
                'transport' => $faker->randomElement($transports),
                'distance' => $faker->numberBetween(1, 50),
                'work_mode' => $faker->randomElement($workModes),
            ]);
        }
    }
}
