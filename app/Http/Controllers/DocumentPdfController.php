<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Services\DocumentPdfService;

class DocumentPdfController extends Controller
{
    public function __construct(
        protected DocumentPdfService $pdfs,
    ) {}

    public function downloadInvoice(Invoice $invoice)
    {
        return $this->pdfs->download($invoice);
    }

    public function printInvoice(Invoice $invoice)
    {
        return $this->pdfs->stream($invoice);
    }

    public function downloadQuotation(Quotation $quotation)
    {
        return $this->pdfs->download($quotation);
    }

    public function printQuotation(Quotation $quotation)
    {
        return $this->pdfs->stream($quotation);
    }

    public function downloadReceipt(Receipt $receipt)
    {
        return $this->pdfs->download($receipt);
    }

    public function printReceipt(Receipt $receipt)
    {
        return $this->pdfs->stream($receipt);
    }
}
