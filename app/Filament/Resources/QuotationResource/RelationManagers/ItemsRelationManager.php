<?php

namespace App\Filament\Resources\QuotationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Line items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('code')
                    ->maxLength(50)
                    ->placeholder('Optional'),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit price')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->default(0)
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated()
                    ->default(0),
                Forms\Components\Hidden::make('sort_order')->default(0),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('USD')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->alignEnd()
                    ->weight('medium'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add item')
                    ->after(fn () => $this->getOwnerRecord()->recalculateTotals()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn () => $this->getOwnerRecord()->recalculateTotals()),
                Tables\Actions\DeleteAction::make()
                    ->after(fn () => $this->getOwnerRecord()->recalculateTotals()),
            ])
            ->emptyStateHeading('No line items')
            ->emptyStateDescription('Add services or charges for this quotation.');
    }

    protected static function updateTotal(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?: 0);
        $unitPrice = (float) ($get('unit_price') ?: 0);
        $set('total', round($quantity * $unitPrice, 2));
    }
}
