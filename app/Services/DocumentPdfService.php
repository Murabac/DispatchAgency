<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Eloquent\Model;

class DocumentPdfService
{
    public function forInvoice(Invoice $invoice): DomPdf
    {
        $invoice->loadMissing(['client', 'items', 'taxes']);

        return $this->make('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => Setting::query()->first(),
        ]);
    }

    public function forQuotation(Quotation $quotation): DomPdf
    {
        $quotation->loadMissing(['client', 'items', 'taxes']);

        return $this->make('pdf.quotation', [
            'quotation' => $quotation,
            'settings' => Setting::query()->first(),
        ]);
    }

    public function forReceipt(Receipt $receipt): DomPdf
    {
        $receipt->loadMissing(['client', 'invoice', 'payment']);

        return $this->make('pdf.receipt', [
            'receipt' => $receipt,
            'settings' => Setting::query()->first(),
        ]);
    }

    public function download(Model $document)
    {
        return $this->pdfFor($document)->download($this->filename($document));
    }

    public function stream(Model $document)
    {
        return $this->pdfFor($document)->stream($this->filename($document));
    }

    public function pdfFor(Model $document): DomPdf
    {
        return match (true) {
            $document instanceof Invoice => $this->forInvoice($document),
            $document instanceof Quotation => $this->forQuotation($document),
            $document instanceof Receipt => $this->forReceipt($document),
            default => throw new \InvalidArgumentException('Unsupported document type for PDF.'),
        };
    }

    public function filename(Model $document): string
    {
        $number = $document->number ?? 'document';

        return str_replace(['/', '\\', ' '], '-', $number) . '.pdf';
    }

    protected function make(string $view, array $data): DomPdf
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true);
    }
}
