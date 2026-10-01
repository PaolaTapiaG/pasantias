<?php

namespace App\Http\Services;

use App\Models\SystemSetting;

class RuntimeMailService
{
    public function __construct(private BrevoMailService $brevoMailService)
    {
    }

    public function send(string $recipient, object $mailable): void
    {
        $settings = $this->resolveMailSettings(SystemSetting::getValue('mail', []));
        $mailer = $settings['mailer'] ?? config('mail.default', 'log');

        if ($mailer === 'log') {
            throw new \RuntimeException('El correo real no esta configurado. Configura el canal Brevo API y su clave.');
        }

        if ($mailer === 'brevo_api') {
            $this->brevoMailService->send($recipient, $mailable, $settings);
            return;
        }

        throw new \RuntimeException('El canal de correo configurado no esta soportado. Usa Brevo API.');
    }

    private function resolveMailSettings(mixed $storedSettings): array
    {
        $stored = is_array($storedSettings) ? $storedSettings : [];
        $env = $this->envMailSettings();

        if (($stored['mailer'] ?? null) === null || ($stored['mailer'] ?? null) === 'log') {
            if (($env['mailer'] ?? null) === 'brevo_api') {
                return $env;
            }
        }
        $cleanStored = array_filter(
            $stored,
            fn ($value) => !($value === null || $value === '')
        );

        return array_replace($env, $cleanStored);
    }

    private function envMailSettings(): array
    {
        return [
            'mailer' => filled(config('services.brevo.api_key')) ? 'brevo_api' : 'log',
            'api_key' => config('services.brevo.api_key'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
    }
}
