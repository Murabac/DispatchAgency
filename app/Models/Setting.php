<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'business_name',
        'address',
        'phone',
        'email',
        'logo_path',
        'bank_details',
        'invoice_prefix',
        'quotation_prefix',
        'receipt_prefix',
        'next_invoice_number',
        'next_quotation_number',
        'next_receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'next_invoice_number' => 'integer',
            'next_quotation_number' => 'integer',
            'next_receipt_number' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    public function nextDocumentPreview(DocumentType $type): string
    {
        return app(DocumentNumberService::class)->format(
            (string) $this->{$type->prefixColumn()},
            (int) $this->{$type->numberColumn()},
            $type->padding(),
        );
    }
}
