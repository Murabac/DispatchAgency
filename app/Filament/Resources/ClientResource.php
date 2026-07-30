<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client details')
                    ->description('Company or person billed on quotations and invoices.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Client name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('attn')
                            ->label('Attn')
                            ->maxLength(255)
                            ->placeholder('Contact person'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('attn')
                    ->label('Attn')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_invoiced')
                    ->label('Invoiced')
                    ->state(fn (Client $record): string => Money::format($record->total_invoiced))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Paid')
                    ->state(fn (Client $record): string => Money::format($record->total_paid))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->state(fn (Client $record): string => Money::format($record->balance))
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn (Client $record): string => $record->balance > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No clients yet')
            ->emptyStateDescription('Add your first client to start creating quotations and invoices.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('invoices');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
