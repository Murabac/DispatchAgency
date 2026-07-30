<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Support\DashboardMetrics;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class LastInvoices extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $heading = 'Last invoices';

    public function table(Table $table): Table
    {
        $range = DashboardMetrics::range($this->filters);

        return $table
            ->heading('Last invoices')
            ->description('Show list of the last 5 created invoices')
            ->query(
                Invoice::query()
                    ->with('client')
                    ->whereDate('date', '>=', $range['start'])
                    ->whereDate('date', '<=', $range['end'])
                    ->latest('date')
                    ->latest('id')
            )
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('reference_display')
                    ->label('Reference')
                    ->state(fn (Invoice $record): string => $record->reference ?: $record->client?->name ?: $record->number)
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->weight('medium')
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->iconButton()
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('print')
                    ->iconButton()
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (Invoice $record): string => route('pdf.invoice.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No invoices in this range')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
