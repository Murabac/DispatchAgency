<?php

namespace App\Filament\Resources;

use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Services\DocumentNumberService;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('attn'),
                                Forms\Components\TextInput::make('phone'),
                                Forms\Components\TextInput::make('email')->email(),
                            ]),
                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->placeholder('Optional reference'),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due date')
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(collect(InvoiceStatus::cases())->mapWithKeys(
                                fn (InvoiceStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required()
                            ->default(InvoiceStatus::Draft->value)
                            ->native(false)
                            ->helperText('Partial and Paid update automatically when payments are recorded.'),
                        Forms\Components\Placeholder::make('number_display')
                            ->label('Number')
                            ->content(function (?Invoice $record): string {
                                if ($record?->number) {
                                    return $record->number;
                                }

                                return 'Next: ' . app(DocumentNumberService::class)->peek(DocumentType::Invoice);
                            }),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                        '2xl' => 4,
                    ]),
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\Placeholder::make('subtotal_display')
                            ->label('Subtotal')
                            ->content(fn (?Invoice $record): string => Money::format($record?->subtotal ?? 0)),
                        Forms\Components\Placeholder::make('tax_display')
                            ->label('Tax')
                            ->content(fn (?Invoice $record): string => Money::format($record?->tax_amount ?? 0)),
                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(fn (?Invoice $record): string => Money::format($record?->total ?? 0)),
                        Forms\Components\Placeholder::make('paid_display')
                            ->label('Paid')
                            ->content(fn (?Invoice $record): string => Money::format($record?->amount_paid ?? 0)),
                        Forms\Components\Placeholder::make('balance_display')
                            ->label('Balance due')
                            ->content(fn (?Invoice $record): string => Money::format($record?->balance_due ?? 0)),
                    ])
                    ->columns(5)
                    ->visibleOn('edit'),
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
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->state(fn (Invoice $record): string => Money::format($record->balance_due))
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn (Invoice $record): string => $record->balance_due > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(
                        fn (InvoiceStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                DocumentPdfActions::tableGroup('invoice'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('Create an invoice directly, or convert an accepted quotation.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
