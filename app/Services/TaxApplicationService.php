<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;

class TaxApplicationService
{
    public function applyActiveTaxes(Model $document): void
    {
        if (! $document instanceof Quotation && ! $document instanceof Invoice) {
            throw new \InvalidArgumentException('Taxes can only be applied to quotations or invoices.');
        }

        $document->taxes()->delete();

        $taxes = Tax::query()->active()->get();

        foreach ($taxes as $index => $tax) {
            $document->taxes()->create([
                'tax_id' => $tax->id,
                'name' => $tax->name,
                'rate' => $tax->rate,
                'amount' => 0,
                'sort_order' => $tax->sort_order ?: $index,
            ]);
        }

        $document->load('taxes');
        $document->recalculateTotals();
    }
}
