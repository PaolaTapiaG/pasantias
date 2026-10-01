<?php

namespace App\Mail;

use App\Models\OrdenPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PaymentOrderInvoicesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrdenPago $orden,
        public Collection $facturas,
        private array $pdfAttachments
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura(s) pagada(s) - Orden ' . $this->orden->codigo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-order-invoices',
            with: [
                'orden' => $this->orden,
                'facturas' => $this->facturas,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->pdfAttachments)
            ->map(fn (array $pdf) => Attachment::fromData(
                fn () => $pdf['content'],
                $pdf['filename']
            )->withMime('application/pdf'))
            ->all();
    }

    public function apiAttachments(): array
    {
        return $this->pdfAttachments;
    }
}
