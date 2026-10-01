<?php

namespace App\Mail;

use App\Models\OrdenPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentOrderApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrdenPago $orden)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago aprobado - Orden ' . $this->orden->codigo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-order-approved',
            with: [
                'orden' => $this->orden,
            ],
        );
    }
}
