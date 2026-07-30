<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Actions\RecordPaymentAction;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RecordPaymentAction::header()
                ->after(function (): void {
                    $this->refreshAfterPayment();
                }),
            DocumentPdfActions::headerGroup('invoice'),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    #[On('payment-updated')]
    public function refreshAfterPayment(): void
    {
        $this->record->refresh();
        $this->fillForm();
        $this->dispatch('$refresh');
    }
}
