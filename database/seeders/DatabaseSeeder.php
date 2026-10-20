<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin user
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@terapis.id'],
            [
                'name' => 'Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        // TherapistSeeder tidak dijalankan default.
        // Jalankan manual jika butuh dummy data: php artisan db:seed --class=TherapistSeeder
    }
}
