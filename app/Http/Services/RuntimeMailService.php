<?php

namespace App\Http\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class RuntimeMailService
{
    public function __construct(private BrevoMailService $brevoMailService)
    {
    }

    public function send(string $recipient, object $mailable): void
    {
        $settings = SystemSetting::getValue('mail', []);

        if (($settings['mailer'] ?? 'log') === 'brevo_api') {
            $this->brevoMailService->send($recipient, $mailable, $settings);
            return;
        }

        if (($settings['mailer'] ?? 'log') === 'smtp' && !empty($settings['host'])) {
            $this->applySmtpConfig($settings);
        }

        Mail::to($recipient)->send($mailable);
    }

    private function applySmtpConfig(array $settings): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['host'] ?? '127.0.0.1');
        Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? 587));
        Config::set('mail.mailers.smtp.username', $settings['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $settings['password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $settings['encryption'] ?? null);
        Config::set('mail.from.address', $settings['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $settings['from_name'] ?? config('mail.from.name'));
    }
}
