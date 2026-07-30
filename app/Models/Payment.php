<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'paid_at',
        'note',
    ];

    /**
     * Skip model boot side-effects when PaymentService orchestrates create/delete.
     */
    public static bool $skipLifecycleHooks = false;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (static::$skipLifecycleHooks) {
                return;
            }

            $invoice = Invoice::query()->findOrFail($payment->invoice_id);
            app(PaymentService::class)->assertCanAcceptPayment($invoice, (float) $payment->amount);
        });

        static::created(function (Payment $payment): void {
            if (static::$skipLifecycleHooks) {
                return;
            }

            $payment->loadMissing('invoice');
            app(PaymentService::class)->createReceiptFor($payment);
            $payment->invoice->recalculatePaymentStatus();
        });

        static::deleting(function (Payment $payment): void {
            if (static::$skipLifecycleHooks) {
                return;
            }

            $payment->receipt()?->delete();
        });

        static::deleted(function (Payment $payment): void {
            if (static::$skipLifecycleHooks) {
                return;
            }

            $payment->invoice?->recalculatePaymentStatus();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
