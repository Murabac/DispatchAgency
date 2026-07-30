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
                'name' => 'User',
                'password' => Hash::make('O4447337@'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            BusinessSettingsSeeder::class,
        ]);

        // Demo clients/invoices only for local/dev — not on Coolify production.
        if (! app()->environment('production')) {
            $this->call(SampleDataSeeder::class);
        }
    }
}
