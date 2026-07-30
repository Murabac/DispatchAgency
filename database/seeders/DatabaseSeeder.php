<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@dispatchlogistics.com'],
            [
                'name' => 'Dispatch Admin',
                'password' => Hash::make('Dispatch@2026'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            BusinessSettingsSeeder::class,
            SampleDataSeeder::class,
        ]);
    }
}
