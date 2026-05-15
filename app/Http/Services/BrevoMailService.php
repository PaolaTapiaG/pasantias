<?php

namespace App\Http\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrevoMailService
{
    public function send(string $recipient, object $mailable, array $settings): void
    {
        if (!$mailable instanceof Mailable) {
            throw new \InvalidArgumentException('El correo debe ser una instancia de Mailable.');
        }

        $apiKey = $settings['api_key'] ?? null;
        $fromAddress = $settings['from_address'] ?? null;
        $fromName = $settings['from_name'] ?? 'EPSAS';

        if (!$apiKey || !$fromAddress) {
            throw new \RuntimeException('Falta la configuracion API de Brevo.');
        }

        $html = method_exists($mailable, 'render')
            ? $mailable->render()
            : null;

        if (!$html) {
            throw new \RuntimeException('No se pudo renderizar el contenido del correo.');
        }

        $subject = $mailable->envelope()->subject
            ?? $mailable->subject
            ?? 'Notificacion EPSAS';

        $text = trim(Str::of(strip_tags($html))->replace('&nbsp;', ' ')->squish()->toString());

        $response = Http::acceptJson()
            ->withHeaders([
                'api-key' => $apiKey,
                'content-type' => 'application/json',
            ])
            ->timeout(20)
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $fromName,
                    'email' => $fromAddress,
                ],
                'to' => [
                    ['email' => $recipient],
                ],
                'subject' => $subject,
                'htmlContent' => $html,
                'textContent' => $text,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Brevo API rechazo el correo: ' . $response->body());
        }
    }
}
