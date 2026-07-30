<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'attn',
        'phone',
        'email',
        'address',
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->invoices()->sum('total');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->invoices()->sum('amount_paid');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total_invoiced - $this->total_paid;
    }
}
