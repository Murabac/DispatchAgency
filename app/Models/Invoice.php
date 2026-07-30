<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'client_id',
        'reference',
        'date',
        'due_date',
        'source_quotation_id',
        'subtotal',
        'tax_amount',
        'total',
        'amount_paid',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sourceQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'source_quotation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('taxes');

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
            'total' => round($subtotal + $taxAmount, 2),
        ]);

        $this->recalculatePaymentStatus();
    }

    public function recalculatePaymentStatus(): void
    {
        $amountPaid = round((float) $this->payments()->sum('amount'), 2);
        $total = (float) $this->total;
        $status = $this->status;

        if ($status === InvoiceStatus::Cancelled) {
            $this->update(['amount_paid' => $amountPaid]);

            return;
        }

        if ($amountPaid <= 0) {
            $status = match (true) {
                $status === InvoiceStatus::Draft => InvoiceStatus::Draft,
                $this->due_date && $this->due_date->isPast() => InvoiceStatus::Overdue,
                default => InvoiceStatus::Sent,
            };
        } elseif ($amountPaid + 0.001 >= $total) {
            $status = InvoiceStatus::Paid;
        } else {
            $status = InvoiceStatus::Partial;
        }

        $this->update([
            'amount_paid' => $amountPaid,
            'status' => $status,
        ]);
    }
}
