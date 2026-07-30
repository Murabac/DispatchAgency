<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Actions\ConvertQuotationToInvoiceAction;
use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\QuotationResource;
use App\Support\Money;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConvertQuotationToInvoiceAction::header(),
            DocumentPdfActions::headerGroup('quotation'),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Quotation')
                    ->schema([
                        Infolists\Components\TextEntry::make('number')->weight('bold'),
                        Infolists\Components\TextEntry::make('client.name')->label('Client'),
                        Infolists\Components\TextEntry::make('reference')->placeholder('—'),
                        Infolists\Components\TextEntry::make('date')->date(),
                        Infolists\Components\TextEntry::make('valid_until')->date()->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        Infolists\Components\TextEntry::make('convertedInvoice.number')
                            ->label('Invoice')
                            ->placeholder('—')
                            ->visible(fn ($record) => filled($record->converted_invoice_id)),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Totals')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->state(fn ($record) => Money::format($record->subtotal)),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('Tax')
                            ->state(fn ($record) => Money::format($record->tax_amount)),
                        Infolists\Components\TextEntry::make('total')
                            ->state(fn ($record) => Money::format($record->total))
                            ->weight('bold'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => filled($record->notes)),
            ]);
    }
}
