<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Zaad = 'zaad';
    case EvcPlus = 'evc_plus';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::Zaad => 'ZAAD',
            self::EvcPlus => 'EVC Plus',
            self::Cash => 'Cash',
            self::Other => 'Other',
        };
    }
}
