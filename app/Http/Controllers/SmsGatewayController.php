<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\SystemSetting;
use App\Http\Services\SmsGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsGatewayController extends Controller
{
    public function __construct(
        private readonly SmsGatewayService $smsGatewayService
    ) {
    }

    public function index(Request $request): View
    {
        $query = SmsMessage::query()->orderByDesc('sent_at')->orderByDesc('id');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('recipient_phone', 'ilike', "%{$term}%")
                    ->orWhere('recipient_name', 'ilike', "%{$term}%")
                    ->orWhere('type', 'ilike', "%{$term}%")
                    ->orWhere('message', 'ilike', "%{$term}%");
            });
        }

        return view('configuracion.sms-gateway', [
            'messages' => $query->paginate(15)->withQueryString(),
            'messaging' => SystemSetting::getValue('messaging', [
                'sms_driver' => config('sms.default', 'log'),
                'sms_country_code' => config('sms.default_country_code', '+591'),
                'sms_sender_name' => 'EPSAS',
                'sms_enabled' => true,
                'email_enabled' => true,
            ]),
            'stats' => [
                'total' => SmsMessage::count(),
                'today' => SmsMessage::whereDate('created_at', today())->count(),
                'failed' => SmsMessage::where('status', 'failed')->count(),
                'last_message_at' => SmsMessage::max('created_at'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_phone' => ['required', 'string', 'max:30'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:5', 'max:480'],
        ]);

        $this->smsGatewayService->send(
            $data['recipient_phone'],
            $data['message'],
            $data['type'],
            ['origin' => 'dashboard_manual_test'],
            $data['recipient_name'] ?? null
        );

        return redirect()
            ->route('admin.configuracion.sms-gateway')
            ->with('success', 'Mensaje procesado por el gateway correctamente.');
    }
}
