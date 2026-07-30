<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetrics
{
    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function range(?array $filters): array
    {
        $start = filled($filters['startDate'] ?? null)
            ? Carbon::parse($filters['startDate'])->startOfDay()
            : now()->startOfYear();

        $end = filled($filters['endDate'] ?? null)
            ? Carbon::parse($filters['endDate'])->endOfDay()
            : now()->endOfYear();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return compact('start', 'end');
    }

    public static function invoicesQuery(?Carbon $start = null, ?Carbon $end = null): Builder
    {
        return Invoice::query()
            ->when($start, fn (Builder $q) => $q->whereDate('date', '>=', $start))
            ->when($end, fn (Builder $q) => $q->whereDate('date', '<=', $end));
    }

    /**
     * @return array{total: float, count: int}
     */
    public static function periodTotals(Carbon $start, Carbon $end): array
    {
        $query = static::invoicesQuery($start, $end)
            ->where('status', '!=', InvoiceStatus::Cancelled);

        return [
            'total' => (float) (clone $query)->sum('total'),
            'count' => (clone $query)->count(),
        ];
    }

    public static function percentChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @param  InvoiceStatus|array<int, InvoiceStatus>  $statuses
     * @return array{total: float, count: int}
     */
    public static function statusBucket(InvoiceStatus|array $statuses, Carbon $start, Carbon $end, bool $useBalance = false): array
    {
        $statuses = is_array($statuses) ? $statuses : [$statuses];

        $query = static::invoicesQuery($start, $end)->whereIn('status', $statuses);

        if ($useBalance) {
            $total = (float) (clone $query)
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
                ->value('balance');
        } else {
            $total = (float) (clone $query)->sum('total');
        }

        return [
            'total' => $total,
            'count' => (clone $query)->count(),
        ];
    }

    /**
     * Display order matching the reference dashboard legend.
     *
     * @return list<InvoiceStatus>
     */
    public static function chartStatuses(): array
    {
        return [
            InvoiceStatus::Sent,
            InvoiceStatus::Paid,
            InvoiceStatus::Partial,
            InvoiceStatus::Overdue,
            InvoiceStatus::Cancelled,
            InvoiceStatus::Draft,
        ];
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    public static function monthlyByStatus(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $buckets = [];
        $statuses = static::chartStatuses();

        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('F');
            $buckets[$key] = array_fill_keys(
                array_map(fn (InvoiceStatus $s) => $s->value, $statuses),
                0.0
            );
            $cursor->addMonth();
        }

        $rows = static::invoicesQuery($start, $end)
            ->selectRaw('DATE_FORMAT(`date`, "%Y-%m") as ym, status, COALESCE(SUM(total), 0) as amount')
            ->groupBy('ym', 'status')
            ->get();

        foreach ($rows as $row) {
            if (! isset($buckets[$row->ym])) {
                continue;
            }

            $status = $row->status instanceof InvoiceStatus
                ? $row->status->value
                : (string) $row->status;

            if (array_key_exists($status, $buckets[$row->ym])) {
                $buckets[$row->ym][$status] = (float) $row->amount;
            }
        }

        $palette = static::statusPalette();
        $datasets = [];

        foreach ($statuses as $status) {
            $data = [];
            foreach ($buckets as $month) {
                $data[] = round($month[$status->value], 2);
            }

            $datasets[] = [
                'label' => $status === InvoiceStatus::Sent ? 'Unpaid' : $status->label(),
                'data' => $data,
                'borderColor' => $palette[$status->value]['border'],
                'backgroundColor' => $palette[$status->value]['fill'],
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
            ];
        }

        return compact('labels', 'datasets');
    }

    /**
     * @return array{labels: list<string>, data: list<int>, colors: list<string>}
     */
    public static function statusCounts(Carbon $start, Carbon $end): array
    {
        $statuses = static::chartStatuses();
        $tally = collect($statuses)
            ->mapWithKeys(fn (InvoiceStatus $s) => [$s->value => 0])
            ->all();

        $invoices = static::invoicesQuery($start, $end)->get(['status']);

        foreach ($invoices as $invoice) {
            $value = $invoice->status instanceof InvoiceStatus
                ? $invoice->status->value
                : (string) $invoice->status;

            if (isset($tally[$value])) {
                $tally[$value]++;
            }
        }

        $palette = static::statusPalette();
        $labels = [];
        $data = [];
        $colors = [];

        foreach ($statuses as $status) {
            $labels[] = $status === InvoiceStatus::Sent ? 'Unpaid' : $status->label();
            $data[] = $tally[$status->value];
            $colors[] = $palette[$status->value]['border'];
        }

        return compact('labels', 'data', 'colors');
    }

    /**
     * @return array<string, array{border: string, fill: string}>
     */
    public static function statusPalette(): array
    {
        return [
            InvoiceStatus::Sent->value => ['border' => '#f5c518', 'fill' => 'rgba(245, 197, 24, 0.18)'],
            InvoiceStatus::Paid->value => ['border' => '#22c55e', 'fill' => 'rgba(34, 197, 94, 0.18)'],
            InvoiceStatus::Partial->value => ['border' => '#3b82f6', 'fill' => 'rgba(59, 130, 246, 0.18)'],
            InvoiceStatus::Overdue->value => ['border' => '#ef4444', 'fill' => 'rgba(239, 68, 68, 0.18)'],
            InvoiceStatus::Cancelled->value => ['border' => '#111827', 'fill' => 'rgba(17, 24, 39, 0.12)'],
            InvoiceStatus::Draft->value => ['border' => '#9ca3af', 'fill' => 'rgba(156, 163, 175, 0.18)'],
        ];
    }
}
