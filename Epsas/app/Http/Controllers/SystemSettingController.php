<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\SystemSetting;
use App\Support\OperationalCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        return $this->sectionView('empresa');
    }

    public function company(): View
    {
        return $this->sectionView('empresa');
    }

    public function carnet(): View
    {
        return $this->sectionView('carnet');
    }

    private function sectionView(string $section): View
    {
        $settings = $this->settingsBundle();

        return view('configuracion.index', [
            'section' => $section,
            'system' => $settings['general'],
            'messaging' => $settings['messaging'],
            'mail' => $settings['mail'],
            'carnet' => [
                'fee' => (float) ($settings['general']['carnet_fee'] ?? 10),
                'back_text' => $settings['general']['carnet_back_text'] ?? $this->defaultCarnetBackText(),
            ],
            'adminProfile' => Auth::user(),
            'messageStats' => $this->messageStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $this->settingsBundle();
        $this->messageStats();
    }

    private function settingsBundle(): array
    {
        return OperationalCache::rememberDomain('settings', 'configuracion.settings-bundle', function () {
            $defaults = [
                'general' => $this->defaultGeneralSettings(),
                'messaging' => $this->defaultMessagingSettings(),
                'mail' => $this->defaultMailSettings(),
            ];
            $rows = SystemSetting::query()
                ->whereIn('key', array_keys($defaults))
                ->get(['key', 'value'])
                ->keyBy('key');

            foreach ($defaults as $key => $value) {
                $stored = $rows->get($key)?->value;
                $defaults[$key] = is_array($stored) ? array_replace($value, $stored) : $value;
            }

            return $defaults;
        });
    }

    private function messageStats(): array
    {
        return OperationalCache::rememberDomain('operations', 'configuracion.message-stats', function () {
            $summary = SmsMessage::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE status IN ('sent', 'queued', 'accepted', 'delivered', 'logged')) as sent,
                    COUNT(*) FILTER (WHERE status = 'failed') as failed,
                    MAX(created_at) as last_message_at
                ")
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'sent' => (int) ($summary?->sent ?? 0),
                'failed' => (int) ($summary?->failed ?? 0),
                'last_message_at' => $summary?->last_message_at,
            ];
        });
    }

    public function update(Request $request): RedirectResponse
    {
        Log::info('[SYSTEM SETTINGS UPDATE] Incoming request', [
            'has_company_logo' => $request->hasFile('company_logo'),
            'content_type' => $request->header('Content-Type'),
        ]);

        $data = $request->validate([
            'config_section' => ['nullable', 'string', Rule::in(['empresa', 'carnet'])],
            'company_name' => ['nullable', 'string', 'max:120'],
            'company_alias' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_nit' => ['nullable', 'string', 'max:40'],
            'company_logo' => [
                'nullable',
                'image',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $dimensions = @getimagesize($value->getRealPath());
                    $ratio = is_array($dimensions) && ($dimensions[1] ?? 0) > 0
                        ? $dimensions[0] / $dimensions[1]
                        : 0;

                    if ($ratio < 1.6 || $ratio > 6) {
                        $fail('El logo institucional debe ser horizontal para mostrarse correctamente en facturas y documentos.');
                    }
                },
            ],
            'company_description' => ['nullable', 'string', 'max:500'],
            'payment_static_qr_payload' => ['nullable', 'string', 'max:1000'],
            'carnet_fee' => ['nullable', 'numeric', 'min:0'],
            'carnet_back_text' => ['nullable', 'string', 'max:700'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_label' => ['nullable', 'string', 'max:120'],
            'map_icon' => ['nullable', 'string', 'max:40'],
            'theme_preference' => ['nullable', 'string', 'in:light,dark'],
            'multa_reconexion' => ['nullable', 'numeric', 'min:0'],
            'multa_mora' => ['nullable', 'numeric', 'min:0'],
            'multa_retraso' => ['nullable', 'numeric', 'min:0'],
            'included_m3' => ['nullable', 'numeric', 'min:0'],
            'fixed_charge' => ['nullable', 'numeric', 'min:0'],
            'excess_rate' => ['nullable', 'numeric', 'min:0'],
            'cutoff_threshold_m3' => ['nullable', 'numeric', 'min:0'],
            'reconnection_fee' => ['nullable', 'numeric', 'min:0'],
            'sewer_fixed_charge' => ['nullable', 'numeric', 'min:0'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'sms_driver' => ['nullable', 'string', Rule::in(['log', 'android_gateway', 'twilio'])],
            'sms_gateway_provider' => ['nullable', 'string', Rule::in(['smsgate', 'generic'])],
            'sms_country_code' => ['nullable', 'string', 'max:8'],
            'sms_sender_name' => ['nullable', 'string', 'max:80'],
            'sms_gateway_url' => ['nullable', 'string', 'max:255'],
            'sms_gateway_username' => ['nullable', 'string', 'max:150'],
            'sms_gateway_password' => ['nullable', 'string', 'max:255'],
            'sms_gateway_api_key' => ['nullable', 'string', 'max:500'],
            'sms_gateway_device_id' => ['nullable', 'string', 'max:120'],
            'sms_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'mail_mailer' => ['nullable', 'string', Rule::in(['log', 'brevo_api'])],
            'mail_api_key' => ['nullable', 'string', 'max:500'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ]);

        $general = SystemSetting::getValue('general', $this->defaultGeneralSettings());
        $messaging = SystemSetting::getValue('messaging', $this->defaultMessagingSettings());
        $mail = SystemSetting::getValue('mail', $this->defaultMailSettings());
        $logoPath = $general['company_logo'] ?? null;

        if ($request->hasFile('company_logo') && $request->file('company_logo')->isValid()) {
            Log::info('[SYSTEM SETTINGS UPDATE] Company logo detected', [
                'original_name' => $request->file('company_logo')->getClientOriginalName(),
                'mime_type' => $request->file('company_logo')->getMimeType(),
                'size' => $request->file('company_logo')->getSize(),
            ]);

            if ($logoPath && Str::startsWith($logoPath, 'storage/')) {
                Storage::disk('public')->delete(Str::after($logoPath, 'storage/'));
            }

            $filename = 'empresa_logo_'.now()->format('YmdHis').'_'.Str::random(8).'.'.strtolower($request->file('company_logo')->extension() ?: 'png');
            $stored = $request->file('company_logo')->storeAs('empresa', $filename, 'public');
            $logoPath = 'storage/'.$stored;

            Log::info('[SYSTEM SETTINGS UPDATE] Company logo stored', [
                'stored_relative_path' => $stored,
                'public_path' => $logoPath,
                'exists_on_disk' => $stored ? Storage::disk('public')->exists($stored) : false,
            ]);
        } elseif ($request->hasFile('company_logo')) {
            Log::warning('[SYSTEM SETTINGS UPDATE] Company logo file arrived but is invalid');
        }

        SystemSetting::putValue('general', [
            'company_name' => $this->inputValue($data, 'company_name', $general['company_name'] ?? 'EPSAS'),
            'company_alias' => $this->inputValue($data, 'company_alias', $general['company_alias'] ?? null),
            'support_email' => $this->inputValue($data, 'support_email', $general['support_email'] ?? null),
            'support_phone' => $this->inputValue($data, 'support_phone', $general['support_phone'] ?? null),
            'address' => $this->inputValue($data, 'address', $general['address'] ?? null),
            'timezone' => $data['timezone'] ?? ($general['timezone'] ?? config('app.timezone', 'America/La_Paz')),
            'date_format' => $data['date_format'] ?? ($general['date_format'] ?? 'd/m/Y'),
            'currency' => $data['currency'] ?? ($general['currency'] ?? 'Bs'),
            'company_email' => $this->inputValue($data, 'company_email', $general['company_email'] ?? null),
            'company_phone' => $this->inputValue($data, 'company_phone', $general['company_phone'] ?? null),
            'company_nit' => $data['company_nit'] ?? ($general['company_nit'] ?? null),
            'company_logo' => $logoPath,
            'company_description' => $this->inputValue($data, 'company_description', $general['company_description'] ?? null),
            'payment_static_qr_payload' => $data['payment_static_qr_payload'] ?? ($general['payment_static_qr_payload'] ?? null),
            'carnet_fee' => round((float) ($data['carnet_fee'] ?? ($general['carnet_fee'] ?? 10)), 2),
            'carnet_back_text' => $data['carnet_back_text'] ?? ($general['carnet_back_text'] ?? $this->defaultCarnetBackText()),
            'gps_latitude' => array_key_exists('gps_latitude', $data) && $data['gps_latitude'] !== null ? (float) $data['gps_latitude'] : ($general['gps_latitude'] ?? null),
            'gps_longitude' => array_key_exists('gps_longitude', $data) && $data['gps_longitude'] !== null ? (float) $data['gps_longitude'] : ($general['gps_longitude'] ?? null),
            'map_label' => $this->inputValue($data, 'map_label', $general['map_label'] ?? null),
            'map_icon' => $this->inputValue($data, 'map_icon', $general['map_icon'] ?? 'water'),
            'theme_preference' => $this->inputValue($data, 'theme_preference', $general['theme_preference'] ?? 'light'),
            'multa_reconexion' => (float) ($general['multa_reconexion'] ?? 0),
            'multa_mora' => (float) ($general['multa_mora'] ?? 0),
            'multa_retraso' => (float) ($general['multa_retraso'] ?? 0),
            'included_m3' => (float) ($general['included_m3'] ?? 10),
            'fixed_charge' => (float) ($general['fixed_charge'] ?? 20),
            'excess_rate' => (float) ($general['excess_rate'] ?? 3),
            'cutoff_threshold_m3' => (float) ($general['cutoff_threshold_m3'] ?? 30),
            'reconnection_fee' => (float) ($general['reconnection_fee'] ?? 30),
            'sewer_fixed_charge' => (float) ($general['sewer_fixed_charge'] ?? 0),
            'maintenance_mode' => (bool) ($general['maintenance_mode'] ?? false),
        ]);

        if ($request->hasAny(['sms_driver', 'sms_gateway_provider', 'sms_country_code', 'sms_sender_name', 'sms_gateway_url', 'sms_enabled', 'email_enabled'])) {
            SystemSetting::putValue('messaging', [
                'sms_driver' => $data['sms_driver'] ?? ($messaging['sms_driver'] ?? 'log'),
                'sms_country_code' => $data['sms_country_code'] ?? ($messaging['sms_country_code'] ?? '+591'),
                'sms_sender_name' => $data['sms_sender_name'] ?? ($messaging['sms_sender_name'] ?? 'EPSAS'),
                'sms_notifications_phone' => $messaging['sms_notifications_phone'] ?? null,
                'sms_gateway_provider' => $data['sms_gateway_provider'] ?? ($messaging['sms_gateway_provider'] ?? 'smsgate'),
                'sms_gateway_url' => $data['sms_gateway_url'] ?? null,
                'sms_gateway_api_key' => filled($data['sms_gateway_api_key'] ?? null) ? $data['sms_gateway_api_key'] : ($messaging['sms_gateway_api_key'] ?? null),
                'sms_gateway_username' => $data['sms_gateway_username'] ?? null,
                'sms_gateway_password' => filled($data['sms_gateway_password'] ?? null) ? $data['sms_gateway_password'] : ($messaging['sms_gateway_password'] ?? null),
                'sms_gateway_device_id' => $data['sms_gateway_device_id'] ?? null,
                'sms_gateway_header' => $messaging['sms_gateway_header'] ?? 'X-API-Key',
                'sms_enabled' => (bool) ($data['sms_enabled'] ?? false),
                'email_enabled' => (bool) ($data['email_enabled'] ?? false),
            ]);
        }

        if ($request->hasAny(['mail_mailer', 'mail_api_key', 'mail_from_address', 'mail_from_name'])) {
            SystemSetting::putValue('mail', [
            'mailer' => $data['mail_mailer'] ?? ($mail['mailer'] ?? 'brevo_api'),
                'api_key' => filled($data['mail_api_key'] ?? null) ? $data['mail_api_key'] : ($mail['api_key'] ?? null),
                'from_address' => $data['mail_from_address'] ?? ($mail['from_address'] ?? null),
                'from_name' => $data['mail_from_name'] ?? ($mail['from_name'] ?? 'EPSAS'),
            ]);
        }

        Cache::forget('shared_company_settings');
        Cache::forget('configuracion:message-stats');
        Cache::forget('configuracion:settings-bundle');
        Cache::forget('system_setting:general');
        Cache::forget('system_setting:messaging');
        Cache::forget('system_setting:mail');
        OperationalCache::bumpDomain('settings');
        OperationalCache::bumpDomain('operations');
        $redirectRoute = match ($data['config_section'] ?? 'empresa') {
            'carnet' => 'admin.configuracion.carnet',
            default => 'admin.configuracion.empresa',
        };

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'La configuracion del sistema se actualizo correctamente.');
    }

    private function inputValue(array $data, string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $data) ? $data[$key] : $fallback;
    }

    private function defaultGeneralSettings(): array
    {
        return [
            'company_name' => 'EPSAS',
            'company_alias' => 'Panel administrativo',
            'support_email' => null,
            'support_phone' => null,
            'address' => null,
            'timezone' => config('app.timezone', 'America/La_Paz'),
            'date_format' => 'd/m/Y',
            'currency' => 'Bs',
            'company_email' => null,
            'company_phone' => null,
            'company_nit' => null,
            'company_logo' => null,
            'company_description' => null,
            'payment_static_qr_payload' => null,
            'carnet_fee' => 10,
            'carnet_back_text' => $this->defaultCarnetBackText(),
            'gps_latitude' => -16.500000,
            'gps_longitude' => -68.150000,
            'map_label' => 'Oficina central EPSAS',
            'map_icon' => 'water',
            'theme_preference' => 'light',
            'multa_reconexion' => 0,
            'multa_mora' => 0,
            'multa_retraso' => 0,
            'included_m3' => 10,
            'fixed_charge' => 20,
            'excess_rate' => 3,
            'cutoff_threshold_m3' => 30,
            'reconnection_fee' => 30,
            'sewer_fixed_charge' => 0,
            'maintenance_mode' => false,
        ];
    }

    private function defaultMessagingSettings(): array
    {
        return [
            'sms_driver' => config('sms.default', 'log'),
            'sms_country_code' => config('sms.default_country_code', '+591'),
            'sms_sender_name' => 'EPSAS',
            'sms_notifications_phone' => null,
            'sms_gateway_provider' => 'smsgate',
            'sms_gateway_url' => null,
            'sms_gateway_api_key' => null,
            'sms_gateway_username' => null,
            'sms_gateway_password' => null,
            'sms_gateway_device_id' => null,
            'sms_gateway_header' => 'X-API-Key',
            'sms_enabled' => true,
            'email_enabled' => true,
        ];
    }

    private function defaultMailSettings(): array
    {
        return [
            'mailer' => 'brevo_api',
            'api_key' => null,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name', 'EPSAS'),
        ];
    }

    private function defaultCarnetBackText(): string
    {
        return 'Este carnet identifica al socio registrado en EPSAS. En caso de extravio, comuniquese con administracion para su reposicion.';
    }
}
