<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BusinessSettingsSeeder extends Seeder
{
    /**
     * Dispatch Logistics real business defaults (Step 11).
     * Invoice sequence continues from existing INV-00156 → next is 157.
     */
    public function run(): void
    {
        $this->ensureLogoFiles();

        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Dispatch Logistics',
                'address' => 'Next to Hass Petroleum, opposite Barwago Hotel, Berbera, Saaxil, Somaliland',
                'phone' => '00252634422444',
                'email' => 'Dispatchlogistics@gmail.com',
                'logo_path' => 'logos/logo.png',
                'bank_details' => 'AMAL BANK: 1013144282 / ZAAD: 433443',
                'invoice_prefix' => 'INV-',
                'quotation_prefix' => 'QUO-',
                'receipt_prefix' => 'RCT-',
                'next_invoice_number' => 157,
                'next_quotation_number' => 1,
                'next_receipt_number' => 1,
            ]
        );
    }

    /**
     * Keep Filament upload path (storage/app/public/logos) and PDF/admin brand path (public/images) in sync.
     */
    protected function ensureLogoFiles(): void
    {
        $publicLogo = public_path('images/logo.png');
        $storageDir = storage_path('app/public/logos');
        $storageLogo = $storageDir . DIRECTORY_SEPARATOR . 'logo.png';

        File::ensureDirectoryExists(dirname($publicLogo));
        File::ensureDirectoryExists($storageDir);

        if (File::exists($publicLogo) && ! File::exists($storageLogo)) {
            File::copy($publicLogo, $storageLogo);
        }

        if (File::exists($storageLogo) && ! File::exists($publicLogo)) {
            File::copy($storageLogo, $publicLogo);
        }
    }
}
