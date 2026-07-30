<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    @include('pdf.partials.styles')
</head>
<body>
@include('pdf.partials.header')

<div class="page-body">
<div class="doc-title">Invoice</div>

<table class="meta-strip">
    <tr>
        <td>
            <span class="label">Number</span>
            <span class="value">{{ $invoice->number }}</span>
        </td>
        <td>
            <span class="label">Date</span>
            <span class="value">{{ $invoice->date?->format('d M Y') }}</span>
        </td>
        <td>
            <span class="label">Reference</span>
            <span class="value">{{ $invoice->reference ?: '—' }}</span>
        </td>
        <td>
            <span class="label">Due date</span>
            <span class="value">{{ $invoice->due_date?->format('d M Y') ?: '—' }}</span>
        </td>
    </tr>
</table>

<div class="section-label">Bill to</div>
<div class="bill-box">
    <div class="bill-name">{{ $invoice->client?->name }}</div>
    @if ($invoice->client?->attn)<div class="bill-line">Attn: {{ $invoice->client->attn }}</div>@endif
    @if ($invoice->client?->phone || $invoice->client?->email)
        <div class="bill-line">{{ collect([$invoice->client?->phone, $invoice->client?->email])->filter()->implode('  ·  ') }}</div>
    @endif
    @if ($invoice->client?->address)
        <div class="bill-line">{{ preg_replace('/\s+/', ' ', $invoice->client->address) }}</div>
    @endif
</div>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 32px;">N°</th>
            <th>Description</th>
            <th class="text-right" style="width: 55px;">Qty</th>
            <th class="text-right" style="width: 80px;">Unit price</th>
            <th class="text-right" style="width: 80px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->description }}
                    @if ($item->code)<div class="muted">{{ $item->code }}</div>@endif
                </td>
                <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                <td class="text-right">${{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format((float) $item->total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">${{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        @forelse ($invoice->taxes as $tax)
            <tr>
                <td>{{ $tax->name }} ({{ rtrim(rtrim(number_format((float) $tax->rate, 4), '0'), '.') }}%)</td>
                <td class="text-right">${{ number_format((float) $tax->amount, 2) }}</td>
            </tr>
        @empty
            @if ((float) $invoice->tax_amount > 0)
                <tr>
                    <td>Tax</td>
                    <td class="text-right">${{ number_format((float) $invoice->tax_amount, 2) }}</td>
                </tr>
            @endif
        @endforelse
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">${{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
        <tr>
            <td>Amount paid</td>
            <td class="text-right">${{ number_format((float) $invoice->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td>Balance due</td>
            <td class="text-right"><strong>${{ number_format((float) $invoice->balance_due, 2) }}</strong></td>
        </tr>
    </table>
</div>

@include('pdf.partials.page-end', [
    'bankDetails' => $settings?->bank_details,
    'notes' => $invoice->notes,
])
</div>
</body>
</html>
