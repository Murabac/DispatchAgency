<?php

namespace App\Filament\Resources;

use App\Enums\DocumentType;
use App\Enums\QuotationStatus;
use App\Filament\Actions\ConvertQuotationToInvoiceAction;
use App\Filament\Actions\DocumentPdfActions;
use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quotation')
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
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Valid until')
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(collect(QuotationStatus::cases())->mapWithKeys(
                                fn (QuotationStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required()
                            ->default(QuotationStatus::Draft->value)
                            ->native(false),
                        Forms\Components\Placeholder::make('number_display')
                            ->label('Number')
                            ->content(function (?Quotation $record): string {
                                if ($record?->number) {
                                    return $record->number;
                                }

                                return 'Next: ' . app(DocumentNumberService::class)->peek(DocumentType::Quotation);
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
                            ->content(fn (?Quotation $record): string => Money::format($record?->subtotal ?? 0)),
                        Forms\Components\Placeholder::make('tax_display')
                            ->label('Tax')
                            ->content(fn (?Quotation $record): string => Money::format($record?->tax_amount ?? 0)),
                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(fn (?Quotation $record): string => Money::format($record?->total ?? 0)),
                    ])
                    ->columns(3)
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
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid until')
                    ->date()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (QuotationStatus $state): string => $state->label())
                    ->color(fn (QuotationStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('convertedInvoice.number')
                    ->label('Invoice')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(QuotationStatus::cases())->mapWithKeys(
                        fn (QuotationStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ConvertQuotationToInvoiceAction::table(),
                DocumentPdfActions::tableGroup('quotation'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No quotations yet')
            ->emptyStateDescription('Create a quotation, then convert accepted ones into invoices.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'convertedInvoice']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
