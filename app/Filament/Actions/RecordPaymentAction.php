<?php

namespace App\Filament\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class RecordPaymentAction
{
    public static function header(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading('Record payment')
            ->modalSubmitActionLabel('Save payment')
            ->visible(fn (Invoice $record): bool => static::canRecord($record))
            ->form(fn (Invoice $record): array => static::formSchema($record))
            ->action(function (Invoice $record, array $data, Action $action): void {
                $invoice = $record->fresh();

                if (! static::canRecord($invoice)) {
                    Notification::make()
                        ->danger()
                        ->title('Payment not allowed')
                        ->body('This invoice has no remaining balance.')
                        ->send();

                    $action->halt();
                }

                $payment = $invoice->payments()->create([
                    'amount' => $data['amount'],
                    'method' => $data['method'],
                    'paid_at' => $data['paid_at'],
                    'note' => $data['note'] ?? null,
                ]);

                $payment->loadMissing('receipt');
                $receiptNumber = $payment->receipt?->number ?? 'generated';

                Notification::make()
                    ->success()
                    ->title('Payment recorded')
                    ->body("Receipt {$receiptNumber} was created automatically. Invoice balance updated.")
                    ->send();
            })
            ->after(function (): void {
                // Handled by page listeners / refreshFormData on Edit/View pages.
            });
    }

    public static function canRecord(Invoice $invoice): bool
    {
        $invoice = $invoice->fresh() ?? $invoice;

        return $invoice->balance_due > 0
            && $invoice->status !== InvoiceStatus::Cancelled
            && (float) $invoice->total > 0;
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function formSchema(Invoice $record): array
    {
        $balance = (float) $record->fresh()->balance_due;

        return [
            Forms\Components\Placeholder::make('balance_info')
                ->label('Remaining balance')
                ->content(Money::format($balance)),
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
        ];
    }
}
