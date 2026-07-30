<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\QuotationStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTax;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\DocumentNumberService;
use App\Services\TaxApplicationService;
use App\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('receipts')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoice_taxes')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('invoices')->truncate();
        DB::table('quotation_taxes')->truncate();
        DB::table('quotation_items')->truncate();
        DB::table('quotations')->truncate();
        DB::table('clients')->truncate();
        DB::table('taxes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Re-apply real business defaults + reset counters (INV continues from 157).
        $this->call(BusinessSettingsSeeder::class);

        Tax::query()->create([
            'name' => 'VAT',
            'rate' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Tax::query()->create([
            'name' => 'Service Tax',
            'rate' => 2,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $clients = collect([
            [
                'name' => 'Berbera Port Shipping Co.',
                'attn' => 'Ahmed Hassan',
                'phone' => '00252634411001',
                'email' => 'ops@berberaport.example',
                'address' => 'Port Road, Berbera, Somaliland',
            ],
            [
                'name' => 'Saaxil Trading LLC',
                'attn' => 'Fatima Mohamed',
                'phone' => '00252634522022',
                'email' => 'accounts@saaxiltrading.example',
                'address' => 'Main Street, Berbera, Saaxil',
            ],
            [
                'name' => 'Horn of Africa Logistics',
                'attn' => 'Omar Ali',
                'phone' => '00252634633033',
                'email' => 'finance@hoalogistics.example',
                'address' => 'Industrial Area, Hargeisa',
            ],
            [
                'name' => 'Red Sea Importers',
                'attn' => 'Yasmin Abdi',
                'phone' => '00252634744044',
                'email' => 'yasmin@redsea.example',
                'address' => 'Near Customs Gate, Berbera',
            ],
        ])->map(fn (array $data) => Client::query()->create($data));

        $numbers = app(DocumentNumberService::class);
        $taxes = app(TaxApplicationService::class);

        // Quotation 1 - accepted, converted to invoice below
        $q1 = Quotation::query()->create([
            'number' => $numbers->nextQuotationNumber(),
            'client_id' => $clients[0]->id,
            'reference' => 'BL-2026-0142',
            'date' => now()->subDays(12),
            'valid_until' => now()->addDays(18),
            'status' => QuotationStatus::Accepted,
            'notes' => 'Customs clearance and inland handling for container shipment.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
        $this->addItems($q1, [
            ['Customs clearance - 1x 40ft container', 'CL-40', 1, 450],
            ['Port handling charges', 'PHC', 1, 180],
            ['Documentation & agency fee', 'DOC', 1, 95],
        ]);
        $taxes->applyActiveTaxes($q1);
        $q1->refresh();

        // Quotation 2 - sent
        $q2 = Quotation::query()->create([
            'number' => $numbers->nextQuotationNumber(),
            'client_id' => $clients[1]->id,
            'reference' => 'RFQ-88',
            'date' => now()->subDays(4),
            'valid_until' => now()->addDays(26),
            'status' => QuotationStatus::Sent,
            'notes' => 'Quotation valid for 30 days.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
        $this->addItems($q2, [
            ['Import clearance - mixed cargo', 'CL-MIX', 1, 320],
            ['Transport to warehouse', 'TRN', 2, 75],
        ]);
        $taxes->applyActiveTaxes($q2);

        // Quotation 3 - draft
        $q3 = Quotation::query()->create([
            'number' => $numbers->nextQuotationNumber(),
            'client_id' => $clients[3]->id,
            'reference' => null,
            'date' => now(),
            'valid_until' => now()->addDays(14),
            'status' => QuotationStatus::Draft,
            'notes' => null,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
        $this->addItems($q3, [
            ['Export documentation package', 'EXP-DOC', 1, 150],
        ]);
        $taxes->applyActiveTaxes($q3);

        // Invoice from quotation 1 - partial payment (INV-00157)
        $inv1 = Invoice::query()->create([
            'number' => $numbers->nextInvoiceNumber(),
            'client_id' => $clients[0]->id,
            'reference' => $q1->reference,
            'date' => now()->subDays(8),
            'due_date' => now()->addDays(7),
            'source_quotation_id' => $q1->id,
            'status' => InvoiceStatus::Sent,
            'notes' => 'Payment terms: 50% on clearance, balance on delivery.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
        ]);
        foreach ($q1->items as $item) {
            InvoiceItem::query()->create([
                'invoice_id' => $inv1->id,
                'description' => $item->description,
                'code' => $item->code,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'sort_order' => $item->sort_order,
            ]);
        }
        foreach ($q1->taxes as $tax) {
            InvoiceTax::query()->create([
                'invoice_id' => $inv1->id,
                'tax_id' => $tax->tax_id,
                'name' => $tax->name,
                'rate' => $tax->rate,
                'amount' => $tax->amount,
                'sort_order' => $tax->sort_order,
            ]);
        }
        $inv1->recalculateTotals();
        $inv1->update(['status' => InvoiceStatus::Sent]);
        $q1->update([
            'status' => QuotationStatus::Converted,
            'converted_invoice_id' => $inv1->id,
        ]);

        Payment::query()->create([
            'invoice_id' => $inv1->id,
            'amount' => round((float) $inv1->fresh()->total * 0.5, 2),
            'method' => PaymentMethod::BankTransfer,
            'paid_at' => now()->subDays(6),
            'note' => 'First installment - AMAL BANK',
        ]);

        // Invoice 2 - fully paid
        $inv2 = Invoice::query()->create([
            'number' => $numbers->nextInvoiceNumber(),
            'client_id' => $clients[2]->id,
            'reference' => 'JOB-551',
            'date' => now()->subDays(20),
            'due_date' => now()->subDays(5),
            'status' => InvoiceStatus::Sent,
            'notes' => null,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
        ]);
        $this->addInvoiceItems($inv2, [
            ['Transit cargo supervision', 'SUP', 3, 60],
            ['Storage (3 days)', 'STR', 3, 40],
        ]);
        $taxes->applyActiveTaxes($inv2);
        $inv2->update(['status' => InvoiceStatus::Sent]);
        $inv2->refresh();

        Payment::query()->create([
            'invoice_id' => $inv2->id,
            'amount' => (float) $inv2->total,
            'method' => PaymentMethod::Zaad,
            'paid_at' => now()->subDays(10),
            'note' => 'Paid in full via ZAAD',
        ]);

        // Invoice 3 - unpaid / outstanding
        $inv3 = Invoice::query()->create([
            'number' => $numbers->nextInvoiceNumber(),
            'client_id' => $clients[1]->id,
            'reference' => 'PO-1099',
            'date' => now()->subDays(3),
            'due_date' => now()->addDays(12),
            'status' => InvoiceStatus::Sent,
            'notes' => 'Bank details on invoice footer.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
        ]);
        $this->addInvoiceItems($inv3, [
            ['Customs brokerage - vehicle import', 'CL-VEH', 1, 550],
            ['Inspection coordination', 'INSP', 1, 120],
        ]);
        $taxes->applyActiveTaxes($inv3);
        $inv3->update(['status' => InvoiceStatus::Sent]);

        // Invoice 4 - overdue (unpaid, past due)
        $inv4 = Invoice::query()->create([
            'number' => $numbers->nextInvoiceNumber(),
            'client_id' => $clients[3]->id,
            'reference' => 'URG-22',
            'date' => now()->subDays(25),
            'due_date' => now()->subDays(10),
            'status' => InvoiceStatus::Sent,
            'notes' => 'Follow up - payment overdue.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
        ]);
        $this->addInvoiceItems($inv4, [
            ['Urgent clearance - LCL', 'CL-LCL', 1, 280],
            ['Demurrage coordination', 'DEM', 1, 95],
        ]);
        $taxes->applyActiveTaxes($inv4);
        $inv4->recalculatePaymentStatus();

        // Invoice 5 - draft
        $inv5 = Invoice::query()->create([
            'number' => $numbers->nextInvoiceNumber(),
            'client_id' => $clients[3]->id,
            'reference' => null,
            'date' => now(),
            'due_date' => null,
            'status' => InvoiceStatus::Draft,
            'notes' => 'Draft - awaiting final charges.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
        ]);
        $this->addInvoiceItems($inv5, [
            ['General agency fee', 'AGF', 1, 200],
        ]);
        $taxes->applyActiveTaxes($inv5);

        // Extra invoices across the year so dashboard charts have shape
        $spread = [
            [now()->startOfYear()->addDays(10), InvoiceStatus::Paid, $clients[0]->id, [['Jan clearance package', 'JAN', 1, 820]], PaymentMethod::BankTransfer, true],
            [now()->startOfYear()->addMonths(1)->addDays(5), InvoiceStatus::Paid, $clients[1]->id, [['Feb port handling', 'FEB', 2, 190]], PaymentMethod::Zaad, true],
            [now()->startOfYear()->addMonths(2)->addDays(8), InvoiceStatus::Sent, $clients[2]->id, [['Mar documentation', 'MAR', 1, 260]], null, false],
            [now()->startOfYear()->addMonths(3)->addDays(3), InvoiceStatus::Partial, $clients[0]->id, [['Apr container clearance', 'APR', 1, 980]], PaymentMethod::BankTransfer, 'half'],
            [now()->startOfYear()->addMonths(4)->addDays(12), InvoiceStatus::Paid, $clients[3]->id, [['May agency retainer', 'MAY', 1, 400]], PaymentMethod::Cash, true],
            [now()->startOfYear()->addMonths(5)->addDays(6), InvoiceStatus::Partial, $clients[1]->id, [['Jun multi-cargo ops', 'JUN', 1, 2400], ['Jun storage', 'JUN-S', 5, 55]], PaymentMethod::Zaad, 'half'],
            [now()->startOfYear()->addMonths(6)->addDays(2), InvoiceStatus::Paid, $clients[2]->id, [['Jul supervision', 'JUL', 4, 95]], PaymentMethod::EvcPlus, true],
            [now()->startOfYear()->addMonths(6)->addDays(18), InvoiceStatus::Sent, $clients[0]->id, [['Jul export pack', 'JUL-E', 1, 340]], null, false],
        ];

        foreach ($spread as [$date, $status, $clientId, $items, $method, $pay]) {
            $invoice = Invoice::query()->create([
                'number' => $numbers->nextInvoiceNumber(),
                'client_id' => $clientId,
                'reference' => 'SEED-' . $date->format('Ym'),
                'date' => $date,
                'due_date' => $date->copy()->addDays(14),
                'status' => InvoiceStatus::Sent,
                'notes' => null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'amount_paid' => 0,
            ]);
            $this->addInvoiceItems($invoice, $items);
            $taxes->applyActiveTaxes($invoice);
            $invoice->update(['status' => $status === InvoiceStatus::Partial ? InvoiceStatus::Sent : $status]);
            $invoice->refresh();

            if ($pay === true) {
                Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => (float) $invoice->total,
                    'method' => $method,
                    'paid_at' => $date->copy()->addDays(3),
                    'note' => 'Seeded full payment',
                ]);
            } elseif ($pay === 'half') {
                Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => round((float) $invoice->total * 0.5, 2),
                    'method' => $method,
                    'paid_at' => $date->copy()->addDays(4),
                    'note' => 'Seeded partial payment',
                ]);
            } elseif ($status === InvoiceStatus::Sent) {
                $invoice->update(['status' => InvoiceStatus::Sent]);
            }
        }

        $this->command?->info('Sample data seeded: clients, quotations, invoices across the year, payments & receipts.');
    }

    /**
     * @param  array<int, array{0: string, 1: ?string, 2: float|int, 3: float|int}>  $rows
     */
    protected function addItems(Quotation $quotation, array $rows): void
    {
        foreach ($rows as $index => [$description, $code, $quantity, $unitPrice]) {
            QuotationItem::query()->create([
                'quotation_id' => $quotation->id,
                'description' => $description,
                'code' => $code,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: ?string, 2: float|int, 3: float|int}>  $rows
     */
    protected function addInvoiceItems(Invoice $invoice, array $rows): void
    {
        foreach ($rows as $index => [$description, $code, $quantity, $unitPrice]) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'code' => $code,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
