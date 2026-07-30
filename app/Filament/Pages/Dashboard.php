<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function mount(): void
    {
        $this->filters ??= [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->endOfYear()->toDateString(),
            'currency' => 'USD',
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\DatePicker::make('startDate')
                            ->label('From')
                            ->default(now()->startOfYear())
                            ->native(false)
                            ->displayFormat('F j, Y')
                            ->closeOnDateSelection()
                            ->live(),
                        Forms\Components\DatePicker::make('endDate')
                            ->label('To')
                            ->default(now()->endOfYear())
                            ->native(false)
                            ->displayFormat('F j, Y')
                            ->closeOnDateSelection()
                            ->live(),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD - US Dollar ($)',
                            ])
                            ->default('USD')
                            ->selectablePlaceholder(false)
                            ->native(false),
                    ])
                    ->extraAttributes(['class' => 'dl-dash-filters']),
            ]);
    }

    public function getColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStatsOverview::class,
            \App\Filament\Widgets\LastInvoices::class,
            \App\Filament\Widgets\InvoiceStatusPieChart::class,
        ];
    }
}
