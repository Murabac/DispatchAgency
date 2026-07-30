<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Support\Money;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Client')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('attn')->placeholder('—'),
                        Infolists\Components\TextEntry::make('phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('email')->placeholder('—'),
                        Infolists\Components\TextEntry::make('address')->columnSpanFull()->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Balances')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_invoiced')
                            ->label('Total invoiced')
                            ->state(fn ($record) => Money::format($record->total_invoiced)),
                        Infolists\Components\TextEntry::make('total_paid')
                            ->label('Total paid')
                            ->state(fn ($record) => Money::format($record->total_paid)),
                        Infolists\Components\TextEntry::make('balance')
                            ->label('Outstanding')
                            ->state(fn ($record) => Money::format($record->balance))
                            ->color(fn ($record) => $record->balance > 0 ? 'warning' : 'success'),
                    ])
                    ->columns(3),
            ]);
    }
}
