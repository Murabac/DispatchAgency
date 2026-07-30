<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Support\DashboardMetrics;
use App\Support\Money;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DashboardStatsOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.dashboard-stats';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getViewData(): array
    {
        $range = DashboardMetrics::range($this->filters);
        $start = $range['start'];
        $end = $range['end'];

        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();
        $year = DashboardMetrics::periodTotals($yearStart, $yearEnd);
        $prevYear = DashboardMetrics::periodTotals(
            $yearStart->copy()->subYear(),
            $yearEnd->copy()->subYear()
        );

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $month = DashboardMetrics::periodTotals($monthStart, $monthEnd);
        $prevMonth = DashboardMetrics::periodTotals(
            $monthStart->copy()->subMonth()->startOfMonth(),
            $monthStart->copy()->subMonth()->endOfMonth()
        );

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $today = DashboardMetrics::periodTotals($todayStart, $todayEnd);
        $yesterday = DashboardMetrics::periodTotals(
            $todayStart->copy()->subDay(),
            $todayEnd->copy()->subDay()
        );

        $paid = DashboardMetrics::statusBucket(InvoiceStatus::Paid, $start, $end);
        $unpaid = DashboardMetrics::statusBucket(
            [InvoiceStatus::Sent, InvoiceStatus::Partial],
            $start,
            $end,
            useBalance: true
        );
        $overdue = DashboardMetrics::statusBucket(InvoiceStatus::Overdue, $start, $end, useBalance: true);

        return [
            'cards' => [
                [
                    'tone' => 'year',
                    'label' => 'This Year',
                    'amount' => Money::format($year['total']),
                    'meta' => $year['count'] . ' invoices',
                    'trend' => DashboardMetrics::percentChange($year['total'], $prevYear['total']),
                    'show_trend' => true,
                ],
                [
                    'tone' => 'month',
                    'label' => 'This month',
                    'amount' => Money::format($month['total']),
                    'meta' => $month['count'] . ' invoices',
                    'trend' => DashboardMetrics::percentChange($month['total'], $prevMonth['total']),
                    'show_trend' => true,
                ],
                [
                    'tone' => 'today',
                    'label' => 'Today',
                    'amount' => Money::format($today['total']),
                    'meta' => $today['count'] . ' invoices',
                    'trend' => DashboardMetrics::percentChange($today['total'], $yesterday['total']),
                    'show_trend' => true,
                ],
                [
                    'tone' => 'paid',
                    'label' => 'Paid Invoice(s)',
                    'amount' => Money::format($paid['total']),
                    'meta' => $paid['count'] . ' invoices',
                    'trend' => null,
                    'show_trend' => false,
                ],
                [
                    'tone' => 'unpaid',
                    'label' => 'Unpaid Invoice(s)',
                    'amount' => Money::format($unpaid['total']),
                    'meta' => $unpaid['count'] . ' invoices',
                    'trend' => null,
                    'show_trend' => false,
                ],
                [
                    'tone' => 'overdue',
                    'label' => 'Overdue invoice(s)',
                    'amount' => Money::format($overdue['total']),
                    'meta' => $overdue['count'] . ' invoices',
                    'trend' => null,
                    'show_trend' => false,
                ],
            ],
        ];
    }
}
