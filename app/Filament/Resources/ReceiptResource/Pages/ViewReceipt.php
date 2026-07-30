<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\ReceiptResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocumentPdfActions::headerGroup('receipt'),
        ];
    }
}
