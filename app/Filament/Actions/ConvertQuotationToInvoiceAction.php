<?php

namespace App\Filament\Actions;

use App\Filament\Resources\InvoiceResource;
use App\Models\Quotation;
use App\Services\QuotationConversionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Validation\ValidationException;

class ConvertQuotationToInvoiceAction
{
    public static function header(): Action
    {
        return Action::make('convertToInvoice')
            ->label('Convert to Invoice')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Convert quotation to invoice')
            ->modalDescription('This will create a draft invoice with the same client, line items, and taxes. The quotation will be marked as converted.')
            ->modalSubmitActionLabel('Convert')
            ->visible(fn (Quotation $record): bool => $record->canConvertToInvoice())
            ->action(function (Quotation $record) {
                try {
                    $invoice = app(QuotationConversionService::class)->convert($record);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Conversion failed')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Invoice created')
                    ->body("Draft invoice {$invoice->number} was created from this quotation.")
                    ->send();

                return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
            });
    }

    public static function table(): TableAction
    {
        return TableAction::make('convertToInvoice')
            ->label('Convert')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Convert quotation to invoice')
            ->modalDescription('Create a draft invoice from this accepted quotation?')
            ->modalSubmitActionLabel('Convert')
            ->visible(fn (Quotation $record): bool => $record->canConvertToInvoice())
            ->action(function (Quotation $record) {
                try {
                    $invoice = app(QuotationConversionService::class)->convert($record);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Conversion failed')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Invoice created')
                    ->body("Draft invoice {$invoice->number} was created.")
                    ->send();

                return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
            });
    }
}
