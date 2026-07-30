<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Receipt;
use App\Support\Money;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Receipt')
                    ->schema([
                        Infolists\Components\TextEntry::make('number')->weight('bold'),
                        Infolists\Components\TextEntry::make('client.name')->label('Client'),
                        Infolists\Components\TextEntry::make('invoice.number')->label('Invoice'),
                        Infolists\Components\TextEntry::make('paid_at')->label('Date')->date(),
                        Infolists\Components\TextEntry::make('method')
                            ->badge()
                            ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('amount')
                            ->state(fn (Receipt $record): string => Money::format($record->amount))
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('balance_remaining')
                            ->label('Balance after payment')
                            ->state(fn (Receipt $record): string => Money::format($record->balance_remaining)),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('method')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $method) => [$method->value => $method->label()]
                    )),
            ])
            ->actions([
                DocumentPdfActions::tableGroup('receipt'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No receipts yet')
            ->emptyStateDescription('Receipts are created automatically when you record a payment on an invoice.')
            ->emptyStateIcon('heroicon-o-receipt-percent');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'invoice', 'payment']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceipts::route('/'),
            'view' => Pages\ViewReceipt::route('/{record}'),
        ];
    }
}
