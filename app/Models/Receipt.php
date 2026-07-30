<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'number',
        'payment_id',
        'invoice_id',
        'client_id',
        'amount',
        'paid_at',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getBalanceRemainingAttribute(): float
    {
        $invoice = $this->invoice;

        if (! $invoice) {
            return 0.0;
        }

        // Balance after this payment: sum of payments up to and including this one.
        $paidThroughThis = (float) $invoice->payments()
            ->where(function ($query) {
                $query->where('paid_at', '<', $this->paid_at)
                    ->orWhere(function ($q) {
                        $q->whereDate('paid_at', $this->paid_at)
                            ->where('id', '<=', $this->payment_id);
                    });
            })
            ->sum('amount');

        return round((float) $invoice->total - $paidThroughThis, 2);
    }
}
