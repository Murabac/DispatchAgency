<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Filament\Resources\ReceiptResource;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function form(Form $form): Form
    {
        $invoice = $this->getOwnerRecord()->fresh();
        $balance = (float) $invoice->balance_due;

        return $form
            ->schema([
                Forms\Components\Placeholder::make('balance_info')
                    ->label('Remaining balance')
                    ->content(Money::format($balance))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->rule('gt:0')
                    ->rule('lte:' . max($balance, 0.01))
                    ->default($balance > 0 ? round($balance, 2) : null)
                    ->helperText('Cannot exceed the remaining balance.'),
                Forms\Components\Select::make('method')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $method) => [$method->value => $method->label()]
                    ))
                    ->required()
                    ->native(false)
                    ->default(PaymentMethod::BankTransfer->value),
                Forms\Components\DatePicker::make('paid_at')
                    ->label('Payment date')
                    ->required()
                    ->default(now())
                    ->native(false),
                Forms\Components\TextInput::make('note')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->alignEnd()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                Tables\Columns\TextColumn::make('receipt.number')
                    ->label('Receipt')
                    ->url(fn ($record) => $record->receipt
                        ? ReceiptResource::getUrl('view', ['record' => $record->receipt])
                        : null)
                    ->color('primary')
                    ->weight('medium')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('note')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->defaultSort('paid_at', 'desc')
            ->headerActions([])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete payment')
                    ->modalDescription('This will also remove the linked receipt and recalculate the invoice balance.')
                    ->after(function (): void {
                        $this->getOwnerRecord()->refresh();
                        $this->getOwnerRecord()->recalculatePaymentStatus();
                        $this->dispatch('payment-updated');

                        Notification::make()
                            ->success()
                            ->title('Payment removed')
                            ->body('Invoice balance and status were recalculated.')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Use Record payment at the top of the page to log a payment and generate a receipt.');
    }
}
