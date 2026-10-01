<?php

namespace App\Http\Controllers;

use App\Models\BankPaymentEvent;
use App\Models\OrdenPago;
use App\Services\PaymentOrderService;
use App\Services\BankWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BankPaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentOrderService $payments, BankWebhookVerifier $verifier): JsonResponse
    {
        $this->verifySignature($request, $verifier);

        $data = Validator::make($request->json()->all(), [
            'event_id' => ['required', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'in:payment.confirmed'],
            'order_code' => ['required', 'string', 'max:30'],
            'reference' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'in:BOB'],
            'paid_at' => ['required', 'date'],
        ])->validate();

        $provider = (string) config('services.bank_webhook.provider', 'bank');
        $payloadHash = hash('sha256', $request->getContent());

        try {
            $event = DB::transaction(function () use ($provider, $data, $payloadHash): BankPaymentEvent {
                return BankPaymentEvent::query()->firstOrCreate(
                    ['provider' => $provider, 'event_id' => $data['event_id']],
                    [
                        'event_type' => $data['event_type'],
                        'order_code' => $data['order_code'],
                        'reference' => $data['reference'],
                        'amount' => round((float) $data['amount'], 2),
                        'currency' => $data['currency'],
                        'status' => 'received',
                        'payload_hash' => $payloadHash,
                        'payload' => $data,
                    ]
                );
            });
        } catch (QueryException) {
            $event = BankPaymentEvent::query()
                ->where('provider', $provider)
                ->where('event_id', $data['event_id'])
                ->firstOrFail();
        }

        if ($event->status === 'processed') {
            return response()->json(['status' => 'already_processed'], 200);
        }

        abort_unless(hash_equals($event->payload_hash, $payloadHash), 409, 'El identificador del evento ya existe con otro contenido.');

        try {
            $order = OrdenPago::query()->where('codigo', $data['order_code'])->firstOrFail();
            $result = $payments->approveAutomatically($order, $data, $provider);

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'id_orden_pago' => $result['orden']->id_orden_pago,
                'error_message' => null,
            ]);

            return response()->json(['status' => 'processed', 'order' => $result['orden']->codigo]);
        } catch (\Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
            Log::error('Bank payment webhook failed', [
                'provider' => $provider,
                'event_id' => $data['event_id'],
                'order_code' => $data['order_code'],
                'exception' => $exception,
            ]);

            return response()->json(['status' => 'rejected'], 422);
        }
    }

    private function verifySignature(Request $request, BankWebhookVerifier $verifier): void
    {
        $secret = (string) config('services.bank_webhook.secret');
        $timestamp = (int) $request->header('X-EPSAS-Timestamp', 0);
        $signature = (string) $request->header('X-EPSAS-Signature', '');
        $tolerance = (int) config('services.bank_webhook.tolerance_seconds', 300);

        abort_unless($secret !== '', 503, 'Webhook bancario no configurado.');
        abort_unless(
            $verifier->isValid($request->getContent(), $timestamp, $signature, $secret, $tolerance),
            401,
            'Firma invalida o solicitud expirada.'
        );
    }
}
