<?php

namespace App\Providers;

use App\Services\DocumentNumberService;
use App\Services\PaymentService;
use App\Services\QuotationConversionService;
use App\Services\TaxApplicationService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentNumberService::class);
        $this->app->singleton(TaxApplicationService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(QuotationConversionService::class);
        $this->app->singleton(\App\Services\DocumentPdfService::class);
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
