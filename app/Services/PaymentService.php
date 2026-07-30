<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
    ) {}

    /**
     * @param  array{amount: float|int|string, method: string|PaymentMethod, paid_at: mixed, note?: ?string}  $data
     */
    public function record(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $this->assertCanAcceptPayment($invoice, (float) $data['amount']);

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'] instanceof PaymentMethod
                    ? $data['method']->value
                    : $data['method'],
                'paid_at' => $data['paid_at'],
                'note' => $data['note'] ?? null,
            ]);

            return $payment->load('receipt');
        });
    }

    public function createReceiptFor(Payment $payment): Receipt
    {
        $payment->loadMissing('invoice');

        return Receipt::query()->create([
            'number' => $this->documentNumbers->nextReceiptNumber(),
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'client_id' => $payment->invoice->client_id,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
            'method' => $payment->method instanceof PaymentMethod
                ? $payment->method->value
                : $payment->method,
        ]);
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);

            $payment->receipt()?->delete();
            $payment->delete();

            $invoice->recalculatePaymentStatus();
        });
    }

    public function assertCanAcceptPayment(Invoice $invoice, float $amount): void
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'amount' => 'Cannot record a payment on a cancelled invoice.',
            ]);
        }

        if ((float) $invoice->total <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Cannot record a payment on an invoice with no total.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $remaining = $this->remainingBalance($invoice);

        if ($amount - $remaining > 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot exceed the remaining balance of $' . number_format($remaining, 2) . '.',
            ]);
        }
    }

    public function remainingBalance(Invoice $invoice): float
    {
        $paid = round((float) $invoice->payments()->sum('amount'), 2);

        return round((float) $invoice->total - $paid, 2);
    }
}
