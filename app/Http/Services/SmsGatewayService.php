<?php

namespace App\Http\Services;

use App\Models\SmsMessage;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsGatewayService
{
    public function send(string $phone, string $message, string $type = 'general', array $meta = [], ?string $name = null): SmsMessage
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        $settings = SystemSetting::getValue('messaging', []);
        $driver = (string) ($settings['sms_driver'] ?? config('sms.default', 'log'));

        $payloadMeta = array_merge($meta, [
            'driver' => $driver,
            'original_phone' => $phone,
            'normalized_phone' => $normalizedPhone,
        ]);

        if (!($settings['sms_enabled'] ?? true)) {
            return SmsMessage::create([
                'recipient_phone' => $normalizedPhone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'disabled',
                'sent_at' => null,
                'meta' => array_merge($payloadMeta, ['reason' => 'sms_disabled']),
            ]);
        }

        if ($driver === 'android_gateway' && !empty($settings['sms_gateway_url'])) {
            return $this->sendWithAndroidGateway($normalizedPhone, $message, $type, $payloadMeta, $name, $settings);
        }

        if ($driver === 'twilio' && $this->hasTwilioConfiguration()) {
            return $this->sendWithTwilio($normalizedPhone, $message, $type, $payloadMeta, $name);
        }

        $sms = SmsMessage::create([
            'recipient_phone' => $normalizedPhone,
            'recipient_name' => $name,
            'type' => $type,
            'message' => $message,
            'status' => 'logged',
            'sent_at' => now(),
            'meta' => $payloadMeta,
        ]);

        Log::info('[LOCAL SMS GATEWAY]', [
            'phone' => $normalizedPhone,
            'type' => $type,
            'message' => $message,
            'meta' => $payloadMeta,
        ]);

        return $sms;
    }

    private function sendWithAndroidGateway(string $phone, string $message, string $type, array $meta, ?string $name, array $settings): SmsMessage
    {
        try {
            if (($settings['sms_gateway_provider'] ?? 'generic') === 'smsgate') {
                return $this->sendWithSmsGate($phone, $message, $type, $meta, $name, $settings);
            }

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $apiKey = $settings['sms_gateway_api_key'] ?? null;
            $headerName = $settings['sms_gateway_header'] ?? 'X-API-Key';

            if ($apiKey) {
                $headers[$headerName] = $apiKey;
            }

            $payload = [
                'phone' => $phone,
                'message' => $message,
            ];

            if (!empty($settings['sms_gateway_device_id'])) {
                $payload['deviceId'] = $settings['sms_gateway_device_id'];
                $payload['device_id'] = $settings['sms_gateway_device_id'];
            }

            $response = Http::withHeaders($headers)
                ->timeout(20)
                ->post($settings['sms_gateway_url'], $payload);

            $meta['provider'] = 'android_gateway';
            $meta['gateway_status'] = $response->status();
            $meta['gateway_body'] = $response->json() ?: $response->body();

            if ($response->successful()) {
                return SmsMessage::create([
                    'recipient_phone' => $phone,
                    'recipient_name' => $name,
                    'type' => $type,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta' => $meta,
                ]);
            }

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => null,
                'meta' => $meta,
            ]);
        } catch (\Throwable $exception) {
            $meta['provider'] = 'android_gateway';
            $meta['error'] = $exception->getMessage();

            Log::error('[ANDROID SMS GATEWAY ERROR]', [
                'phone' => $phone,
                'type' => $type,
                'message' => $message,
                'error' => $exception->getMessage(),
            ]);

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => null,
                'meta' => $meta,
            ]);
        }
    }

    private function sendWithSmsGate(string $phone, string $message, string $type, array $meta, ?string $name, array $settings): SmsMessage
    {
        try {
            $request = Http::acceptJson()
                ->contentType('application/json')
                ->timeout(20);

            if (!empty($settings['sms_gateway_username']) && !empty($settings['sms_gateway_password'])) {
                $request = $request->withBasicAuth(
                    $settings['sms_gateway_username'],
                    $settings['sms_gateway_password']
                );
            } elseif (!empty($settings['sms_gateway_api_key'])) {
                $headerName = $settings['sms_gateway_header'] ?? 'Authorization';
                $request = $request->withHeaders([
                    $headerName => str_starts_with($settings['sms_gateway_api_key'], 'Bearer ')
                        ? $settings['sms_gateway_api_key']
                        : 'Bearer ' . $settings['sms_gateway_api_key'],
                ]);
            }

            $payload = [
                'phoneNumbers' => [$phone],
                'textMessage' => [
                    'text' => $message,
                ],
            ];

            if (!empty($settings['sms_gateway_device_id'])) {
                $payload['deviceId'] = $settings['sms_gateway_device_id'];
            }

            $response = $request->post($settings['sms_gateway_url'], $payload);

            $meta['provider'] = 'smsgate';
            $meta['gateway_status'] = $response->status();
            $meta['gateway_body'] = $response->json() ?: $response->body();

            if (in_array($response->status(), [200, 201, 202], true)) {
                return SmsMessage::create([
                    'recipient_phone' => $phone,
                    'recipient_name' => $name,
                    'type' => $type,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta' => $meta,
                ]);
            }

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => null,
                'meta' => $meta,
            ]);
        } catch (\Throwable $exception) {
            $meta['provider'] = 'smsgate';
            $meta['error'] = $exception->getMessage();

            Log::error('[SMSGATE ERROR]', [
                'phone' => $phone,
                'type' => $type,
                'message' => $message,
                'error' => $exception->getMessage(),
            ]);

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => null,
                'meta' => $meta,
            ]);
        }
    }

    private function sendWithTwilio(string $phone, string $message, string $type, array $meta, ?string $name): SmsMessage
    {
        try {
            $options = ['body' => $message];

            $messagingServiceSid = config('services.twilio.messaging_service_sid');
            $from = config('services.twilio.from');

            if ($messagingServiceSid) {
                $options['messagingServiceSid'] = $messagingServiceSid;
            } elseif ($from) {
                $options['from'] = $from;
            }

            $response = $this->twilioClient()->messages->create($phone, $options);

            $meta['provider'] = 'twilio';
            $meta['twilio_sid'] = $response->sid ?? null;
            $meta['twilio_status'] = $response->status ?? null;

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => $response->status ?? 'queued',
                'sent_at' => now(),
                'meta' => $meta,
            ]);
        } catch (\Throwable $exception) {
            $meta['provider'] = 'twilio';
            $meta['error'] = $exception->getMessage();

            Log::error('[TWILIO SMS ERROR]', [
                'phone' => $phone,
                'type' => $type,
                'message' => $message,
                'error' => $exception->getMessage(),
            ]);

            return SmsMessage::create([
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => null,
                'meta' => $meta,
            ]);
        }
    }

    private function hasTwilioConfiguration(): bool
    {
        return filled(config('services.twilio.account_sid'))
            && filled(config('services.twilio.auth_token'))
            && (filled(config('services.twilio.from')) || filled(config('services.twilio.messaging_service_sid')));
    }

    private function twilioClient(): Client
    {
        return new Client(
            (string) config('services.twilio.account_sid'),
            (string) config('services.twilio.auth_token')
        );
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $value = trim($phone);
        $value = str_replace([' ', '-', '(', ')'], '', $value);

        if (str_starts_with($value, '00')) {
            return '+' . substr($value, 2);
        }

        if (str_starts_with($value, '+')) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return $phone;
        }

        if (strlen($digits) === 8) {
            return (string) config('sms.default_country_code', '+591') . $digits;
        }

        if (str_starts_with($digits, '591')) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }
}
