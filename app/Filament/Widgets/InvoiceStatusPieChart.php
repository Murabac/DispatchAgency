<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class InvoiceStatusPieChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Overview chart';

    protected static ?string $description = 'Pie chart counting invoices per status';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $range = DashboardMetrics::range($this->filters);
        $chart = DashboardMetrics::statusCounts($range['start'], $range['end']);

        return [
            'datasets' => [
                [
                    'label' => 'Invoices',
                    'data' => $chart['data'],
                    'backgroundColor' => $chart['colors'],
                    'borderWidth' => 0,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getCachedData(): array
    {
        return $this->getData();
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 14,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
