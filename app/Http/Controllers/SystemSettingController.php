<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        return view('configuracion.index', [
            'system' => SystemSetting::getValue('general', $this->defaultGeneralSettings()),
            'messaging' => SystemSetting::getValue('messaging', $this->defaultMessagingSettings()),
            'mail' => SystemSetting::getValue('mail', $this->defaultMailSettings()),
            'adminProfile' => Auth::user()?->loadMissing('persona'),
            'messageStats' => [
                'total' => SmsMessage::count(),
                'sent' => SmsMessage::whereIn('status', ['sent', 'queued', 'accepted', 'delivered', 'logged'])->count(),
                'failed' => SmsMessage::where('status', 'failed')->count(),
                'last_message_at' => SmsMessage::max('created_at'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Log::info('[SYSTEM SETTINGS UPDATE] Incoming request', [
            'has_company_logo' => $request->hasFile('company_logo'),
            'content_type' => $request->header('Content-Type'),
        ]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
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
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'company_description' => ['nullable', 'string', 'max:500'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_label' => ['nullable', 'string', 'max:120'],
            'map_icon' => ['nullable', 'string', 'max:40'],
            'theme_preference' => ['required', 'string', 'in:light,dark'],
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
        ]);

        $general = SystemSetting::getValue('general', $this->defaultGeneralSettings());
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

            $filename = 'empresa_logo_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . strtolower($request->file('company_logo')->extension() ?: 'png');
            $stored = $request->file('company_logo')->storeAs('empresa', $filename, 'public');
            $logoPath = 'storage/' . $stored;

            Log::info('[SYSTEM SETTINGS UPDATE] Company logo stored', [
                'stored_relative_path' => $stored,
                'public_path' => $logoPath,
                'exists_on_disk' => $stored ? Storage::disk('public')->exists($stored) : false,
            ]);
        } elseif ($request->hasFile('company_logo')) {
            Log::warning('[SYSTEM SETTINGS UPDATE] Company logo file arrived but is invalid');
        }

        SystemSetting::putValue('general', [
            'company_name' => $data['company_name'],
            'company_alias' => $data['company_alias'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'support_phone' => $data['support_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'timezone' => $data['timezone'] ?? ($general['timezone'] ?? config('app.timezone', 'America/La_Paz')),
            'date_format' => $data['date_format'] ?? ($general['date_format'] ?? 'd/m/Y'),
            'currency' => $data['currency'] ?? ($general['currency'] ?? 'Bs'),
            'company_email' => $data['company_email'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_nit' => $data['company_nit'] ?? null,
            'company_logo' => $logoPath,
            'company_description' => $data['company_description'] ?? null,
            'gps_latitude' => isset($data['gps_latitude']) ? (float) $data['gps_latitude'] : null,
            'gps_longitude' => isset($data['gps_longitude']) ? (float) $data['gps_longitude'] : null,
            'map_label' => $data['map_label'] ?? null,
            'map_icon' => $data['map_icon'] ?? 'water',
            'theme_preference' => $data['theme_preference'],
            'multa_reconexion' => isset($data['multa_reconexion']) ? (float) $data['multa_reconexion'] : 0,
            'multa_mora' => isset($data['multa_mora']) ? (float) $data['multa_mora'] : 0,
            'multa_retraso' => isset($data['multa_retraso']) ? (float) $data['multa_retraso'] : 0,
            'included_m3' => isset($data['included_m3']) ? (float) $data['included_m3'] : 10,
            'fixed_charge' => isset($data['fixed_charge']) ? (float) $data['fixed_charge'] : 20,
            'excess_rate' => isset($data['excess_rate']) ? (float) $data['excess_rate'] : 3,
            'cutoff_threshold_m3' => isset($data['cutoff_threshold_m3']) ? (float) $data['cutoff_threshold_m3'] : 30,
            'reconnection_fee' => isset($data['reconnection_fee']) ? (float) $data['reconnection_fee'] : 30,
            'sewer_fixed_charge' => isset($data['sewer_fixed_charge']) ? (float) $data['sewer_fixed_charge'] : 0,
            'maintenance_mode' => (bool) ($data['maintenance_mode'] ?? false),
        ]);

        Cache::forget('shared_company_settings');

        return redirect()
            ->route('admin.configuracion.index')
            ->with('success', 'La configuracion del sistema se actualizo correctamente.');
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
            'mailer' => config('mail.default', 'log'),
            'host' => env('MAIL_HOST', 'smtp.example.com'),
            'port' => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_MAILER') === 'smtp' ? (env('MAIL_SCHEME') ?: 'tls') : 'tls',
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'api_key' => null,
            'from_address' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME', 'EPSAS'),
        ];
    }
}
