<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Actions\ConvertQuotationToInvoiceAction;
use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConvertQuotationToInvoiceAction::header(),
            DocumentPdfActions::headerGroup('quotation'),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
