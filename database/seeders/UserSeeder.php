<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\Directorate;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 1; $i <= 140; $i++) {
            User::factory()->withoutTwoFactor()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->userName() . $i . '@permatagreen.com',
                'password' => 'admin',
                'user_level' => 1,
                'directorate' => $faker->randomElement(Directorate::cases())->value,
            ]);
        }
    }
}
