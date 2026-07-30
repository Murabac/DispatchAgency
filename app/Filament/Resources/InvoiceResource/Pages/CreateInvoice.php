<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\DocumentNumberService;
use App\Services\TaxApplicationService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number'] = app(DocumentNumberService::class)->nextInvoiceNumber();
        $data['subtotal'] = 0;
        $data['tax_amount'] = 0;
        $data['total'] = 0;
        $data['amount_paid'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(TaxApplicationService::class)->applyActiveTaxes($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
