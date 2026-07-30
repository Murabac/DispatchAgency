<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'number',
        'client_id',
        'reference',
        'date',
        'valid_until',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'notes',
        'converted_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => QuotationStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(QuotationTax::class)->orderBy('sort_order');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function canConvertToInvoice(): bool
    {
        return $this->status === QuotationStatus::Accepted
            && $this->converted_invoice_id === null;
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('total');
        $taxAmount = 0.0;

        foreach ($this->taxes as $tax) {
            $amount = round($subtotal * ((float) $tax->rate / 100), 2);
            $tax->update(['amount' => $amount]);
            $taxAmount += $amount;
        }

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }
}
