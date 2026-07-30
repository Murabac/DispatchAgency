<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->number }}</title>
    @include('pdf.partials.styles')
</head>
<body>
@include('pdf.partials.header')

<div class="page-body">
<div class="doc-title">Quotation</div>

<table class="meta-strip">
    <tr>
        <td>
            <span class="label">Number</span>
            <span class="value">{{ $quotation->number }}</span>
        </td>
        <td>
            <span class="label">Date</span>
            <span class="value">{{ $quotation->date?->format('d M Y') }}</span>
        </td>
        <td>
            <span class="label">Reference</span>
            <span class="value">{{ $quotation->reference ?: '—' }}</span>
        </td>
        <td>
            <span class="label">Valid until</span>
            <span class="value">{{ $quotation->valid_until?->format('d M Y') ?: '—' }}</span>
        </td>
    </tr>
</table>

<div class="section-label">Bill to</div>
<div class="bill-box">
    <div class="bill-name">{{ $quotation->client?->name }}</div>
    @if ($quotation->client?->attn)<div class="bill-line">Attn: {{ $quotation->client->attn }}</div>@endif
    @if ($quotation->client?->phone || $quotation->client?->email)
        <div class="bill-line">{{ collect([$quotation->client?->phone, $quotation->client?->email])->filter()->implode('  ·  ') }}</div>
    @endif
    @if ($quotation->client?->address)
        <div class="bill-line">{{ preg_replace('/\s+/', ' ', $quotation->client->address) }}</div>
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
        @foreach ($quotation->items as $index => $item)
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
            <td class="text-right">${{ number_format((float) $quotation->subtotal, 2) }}</td>
        </tr>
        @forelse ($quotation->taxes as $tax)
            <tr>
                <td>{{ $tax->name }} ({{ rtrim(rtrim(number_format((float) $tax->rate, 4), '0'), '.') }}%)</td>
                <td class="text-right">${{ number_format((float) $tax->amount, 2) }}</td>
            </tr>
        @empty
            @if ((float) $quotation->tax_amount > 0)
                <tr>
                    <td>Tax</td>
                    <td class="text-right">${{ number_format((float) $quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
        @endforelse
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">${{ number_format((float) $quotation->total, 2) }}</td>
        </tr>
    </table>
</div>

@include('pdf.partials.page-end', [
    'bankDetails' => null,
    'notes' => $quotation->notes,
])
</div>
</body>
</html>
