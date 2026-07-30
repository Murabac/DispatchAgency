<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Actions\RecordPaymentAction;
use App\Filament\Resources\InvoiceResource;
use App\Support\Money;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewInvoice extends ViewRecord
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
            Actions\EditAction::make(),
        ];
    }

    #[On('payment-updated')]
    public function refreshAfterPayment(): void
    {
        $this->refresh();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Invoice')
                    ->schema([
                        Infolists\Components\TextEntry::make('number')->weight('bold'),
                        Infolists\Components\TextEntry::make('client.name')->label('Client'),
                        Infolists\Components\TextEntry::make('reference')->placeholder('—'),
                        Infolists\Components\TextEntry::make('date')->date(),
                        Infolists\Components\TextEntry::make('due_date')->date()->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        Infolists\Components\TextEntry::make('sourceQuotation.number')
                            ->label('From quotation')
                            ->placeholder('—'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Balances')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->state(fn ($record) => Money::format($record->subtotal)),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('Tax')
                            ->state(fn ($record) => Money::format($record->tax_amount)),
                        Infolists\Components\TextEntry::make('total')
                            ->state(fn ($record) => Money::format($record->total))
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('amount_paid')
                            ->label('Paid')
                            ->state(fn ($record) => Money::format($record->amount_paid)),
                        Infolists\Components\TextEntry::make('balance_due')
                            ->label('Balance due')
                            ->state(fn ($record) => Money::format($record->balance_due))
                            ->color(fn ($record) => $record->balance_due > 0 ? 'warning' : 'success'),
                    ])
                    ->columns(5),
            ]);
    }
}
