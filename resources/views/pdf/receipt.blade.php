<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->number }}</title>
    @include('pdf.partials.styles')
</head>
<body>
@include('pdf.partials.header')

<div class="page-body">
<div class="doc-title">Receipt</div>

<table class="meta-strip">
    <tr>
        <td>
            <span class="label">Receipt No.</span>
            <span class="value">{{ $receipt->number }}</span>
        </td>
        <td>
            <span class="label">Date</span>
            <span class="value">{{ $receipt->paid_at?->format('d M Y') }}</span>
        </td>
        <td>
            <span class="label">Invoice</span>
            <span class="value">{{ $receipt->invoice?->number ?: '—' }}</span>
        </td>
        <td>
            <span class="label">Method</span>
            <span class="value">{{ $receipt->method?->label() ?? $receipt->method }}</span>
        </td>
    </tr>
</table>

<div class="section-label">Received from</div>
<div class="bill-box">
    <div class="bill-name">{{ $receipt->client?->name }}</div>
    @if ($receipt->client?->attn)<div class="bill-line">Attn: {{ $receipt->client->attn }}</div>@endif
    @if ($receipt->client?->phone || $receipt->client?->email)
        <div class="bill-line">{{ collect([$receipt->client?->phone, $receipt->client?->email])->filter()->implode('  ·  ') }}</div>
    @endif
</div>

<div class="section-label">Amount received</div>
<div class="receipt-amount">${{ number_format((float) $receipt->amount, 2) }}</div>
<div class="muted" style="margin-bottom: 10px;">Applied to invoice {{ $receipt->invoice?->number }}</div>

<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td>Invoice total</td>
            <td class="text-right">${{ number_format((float) ($receipt->invoice?->total ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td>This payment</td>
            <td class="text-right">${{ number_format((float) $receipt->amount, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>Balance remaining</td>
            <td class="text-right">${{ number_format((float) $receipt->balance_remaining, 2) }}</td>
        </tr>
    </table>
</div>

@include('pdf.partials.page-end', [
    'bankDetails' => $settings?->bank_details,
    'notes' => null,
])
</div>
</body>
</html>
