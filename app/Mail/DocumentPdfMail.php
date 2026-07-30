<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Setting;
use App\Services\DocumentPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentPdfMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Model $document,
        public string $recipientEmail,
        public ?string $customSubject = null,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        $settings = Setting::query()->first();

        return new Envelope(
            from: new Address(
                $settings?->email ?: config('mail.from.address'),
                $settings?->business_name ?: config('mail.from.name'),
            ),
            subject: $this->customSubject ?: $this->defaultSubject($settings?->business_name),
        );
    }

    public function content(): Content
    {
        $settings = Setting::query()->first();

        return new Content(
            markdown: 'emails.document-pdf',
            with: [
                'documentLabel' => $this->documentLabel(),
                'documentNumber' => $this->document->number,
                'clientName' => $this->document->client?->name ?? 'Client',
                'businessName' => $settings?->business_name ?? 'Dispatch Logistics',
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = app(DocumentPdfService::class);
        $filename = $pdf->filename($this->document);
        $output = $pdf->pdfFor($this->document)->output();

        return [
            Attachment::fromData(fn () => $output, $filename)
                ->withMime('application/pdf'),
        ];
    }

    protected function defaultSubject(?string $businessName): string
    {
        $business = $businessName ?: 'Dispatch Logistics';

        return "{$this->documentLabel()} {$this->document->number} from {$business}";
    }

    protected function documentLabel(): string
    {
        return match (true) {
            $this->document instanceof Invoice => 'Invoice',
            $this->document instanceof Quotation => 'Quotation',
            $this->document instanceof Receipt => 'Receipt',
            default => 'Document',
        };
    }
}
