<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function nextInvoiceNumber(): string
    {
        return $this->allocate(DocumentType::Invoice);
    }

    public function nextQuotationNumber(): string
    {
        return $this->allocate(DocumentType::Quotation);
    }

    public function nextReceiptNumber(): string
    {
        return $this->allocate(DocumentType::Receipt);
    }

    public function next(DocumentType $type): string
    {
        return $this->allocate($type);
    }

    /**
     * Preview the next number without consuming it.
     */
    public function peek(DocumentType $type): string
    {
        $settings = Setting::query()->firstOrFail();

        return $this->format(
            (string) $settings->{$type->prefixColumn()},
            (int) $settings->{$type->numberColumn()},
            $type->padding(),
        );
    }

    /**
     * @return array{invoice: string, quotation: string, receipt: string}
     */
    public function peekAll(): array
    {
        return [
            'invoice' => $this->peek(DocumentType::Invoice),
            'quotation' => $this->peek(DocumentType::Quotation),
            'receipt' => $this->peek(DocumentType::Receipt),
        ];
    }

    public function format(string $prefix, int $number, int $padding = 5): string
    {
        return $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Set the next sequence value (the number that will be used on the next allocate).
     */
    public function setNextNumber(DocumentType $type, int $nextNumber): void
    {
        if ($nextNumber < 1) {
            throw new RuntimeException('Next document number must be at least 1.');
        }

        DB::transaction(function () use ($type, $nextNumber) {
            $settings = Setting::query()->lockForUpdate()->firstOrFail();
            $settings->update([
                $type->numberColumn() => $nextNumber,
            ]);
        });
    }

    public function setPrefix(DocumentType $type, string $prefix): void
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            throw new RuntimeException('Document prefix cannot be empty.');
        }

        DB::transaction(function () use ($type, $prefix) {
            $settings = Setting::query()->lockForUpdate()->firstOrFail();
            $settings->update([
                $type->prefixColumn() => $prefix,
            ]);
        });
    }

    protected function allocate(DocumentType $type): string
    {
        return DB::transaction(function () use ($type) {
            $settings = Setting::query()->lockForUpdate()->firstOrFail();

            $prefix = (string) $settings->{$type->prefixColumn()};
            $number = (int) $settings->{$type->numberColumn()};
            $padding = $type->padding();

            // Skip ahead if a number was manually inserted / already exists.
            do {
                $formatted = $this->format($prefix, $number, $padding);
                $exists = $this->numberExists($type, $formatted);
                $number++;
            } while ($exists);

            $settings->update([
                $type->numberColumn() => $number,
            ]);

            return $formatted;
        });
    }

    protected function numberExists(DocumentType $type, string $number): bool
    {
        return match ($type) {
            DocumentType::Invoice => Invoice::query()->where('number', $number)->exists(),
            DocumentType::Quotation => Quotation::query()->where('number', $number)->exists(),
            DocumentType::Receipt => Receipt::query()->where('number', $number)->exists(),
        };
    }
}
