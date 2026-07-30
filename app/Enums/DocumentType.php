<?php

namespace App\Enums;

enum DocumentType: string
{
    case Invoice = 'invoice';
    case Quotation = 'quotation';
    case Receipt = 'receipt';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Invoice',
            self::Quotation => 'Quotation',
            self::Receipt => 'Receipt',
        };
    }

    public function prefixColumn(): string
    {
        return match ($this) {
            self::Invoice => 'invoice_prefix',
            self::Quotation => 'quotation_prefix',
            self::Receipt => 'receipt_prefix',
        };
    }

    public function numberColumn(): string
    {
        return match ($this) {
            self::Invoice => 'next_invoice_number',
            self::Quotation => 'next_quotation_number',
            self::Receipt => 'next_receipt_number',
        };
    }

    public function padding(): int
    {
        return 5;
    }
}
