<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTax;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationConversionService
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
    ) {}

    public function convert(Quotation $quotation): Invoice
    {
        return DB::transaction(function () use ($quotation) {
            $quotation = Quotation::query()
                ->with(['items', 'taxes'])
                ->lockForUpdate()
                ->findOrFail($quotation->id);

            if (! $quotation->canConvertToInvoice()) {
                throw ValidationException::withMessages([
                    'status' => $quotation->converted_invoice_id
                        ? 'This quotation has already been converted to an invoice.'
                        : 'Only accepted quotations can be converted to an invoice.',
                ]);
            }

            if ($quotation->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one line item before converting to an invoice.',
                ]);
            }

            $invoice = Invoice::query()->create([
                'number' => $this->documentNumbers->nextInvoiceNumber(),
                'client_id' => $quotation->client_id,
                'reference' => $quotation->reference,
                'date' => now()->toDateString(),
                'due_date' => null,
                'source_quotation_id' => $quotation->id,
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'total' => $quotation->total,
                'amount_paid' => 0,
                'status' => InvoiceStatus::Draft,
                'notes' => $quotation->notes,
            ]);

            foreach ($quotation->items as $item) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => $item->description,
                    'code' => $item->code,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'sort_order' => $item->sort_order,
                ]);
            }

            foreach ($quotation->taxes as $tax) {
                InvoiceTax::query()->create([
                    'invoice_id' => $invoice->id,
                    'tax_id' => $tax->tax_id,
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                    'amount' => $tax->amount,
                    'sort_order' => $tax->sort_order,
                ]);
            }

            $quotation->update([
                'status' => QuotationStatus::Converted,
                'converted_invoice_id' => $invoice->id,
            ]);

            return $invoice->fresh(['items', 'taxes', 'client']);
        });
    }
}
