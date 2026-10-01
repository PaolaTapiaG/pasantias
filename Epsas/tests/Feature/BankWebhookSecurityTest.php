<?php

namespace Tests\Feature;

use App\Models\BankPaymentEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BankWebhookSecurityTest extends TestCase
{
    public function test_bank_webhook_rejects_unsigned_requests_before_processing(): void
    {
        config()->set('services.bank_webhook.secret', 'test-secret');

        $this->postJson('/webhooks/bank/payments', [])->assertUnauthorized();
    }

    public function test_bank_webhook_accepts_valid_signature_before_validating_payload(): void
    {
        config()->set('services.bank_webhook.secret', 'test-secret');
        $body = '{}';
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $this->call('POST', '/webhooks/bank/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_EPSAS_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_EPSAS_SIGNATURE' => $signature,
        ], $body)->assertUnprocessable();
    }

    public function test_bank_webhook_rejects_expired_signed_request(): void
    {
        config()->set('services.bank_webhook.secret', 'test-secret');
        $body = '{}';
        $timestamp = now()->subMinutes(10)->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $this->call('POST', '/webhooks/bank/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_EPSAS_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_EPSAS_SIGNATURE' => $signature,
        ], $body)->assertUnauthorized();
    }

    public function test_repeated_processed_bank_event_is_idempotent(): void
    {
        $this->createBankEventTable();
        config()->set('services.bank_webhook.secret', 'test-secret');
        config()->set('services.bank_webhook.provider', 'test-bank');

        $payload = [
            'event_id' => 'evt-processed-001',
            'event_type' => 'payment.confirmed',
            'order_code' => 'OP-TEST-001',
            'reference' => 'BANK-REF-001',
            'amount' => 100,
            'currency' => 'BOB',
            'paid_at' => now()->toIso8601String(),
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        BankPaymentEvent::create([
            'provider' => 'test-bank',
            'event_id' => $payload['event_id'],
            'event_type' => $payload['event_type'],
            'order_code' => $payload['order_code'],
            'reference' => $payload['reference'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'status' => 'processed',
            'payload_hash' => hash('sha256', $body),
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        $this->call('POST', '/webhooks/bank/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_EPSAS_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_EPSAS_SIGNATURE' => $signature,
        ], $body)
            ->assertOk()
            ->assertJsonPath('status', 'already_processed');

        $this->assertDatabaseCount('bank_payment_events', 1);
    }

    private function createBankEventTable(): void
    {
        Schema::dropIfExists('bank_payment_events');

        Schema::create('bank_payment_events', function (Blueprint $table): void {
            $table->id('id_event');
            $table->string('provider');
            $table->string('event_id');
            $table->string('event_type');
            $table->string('order_code');
            $table->string('reference');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('id_orden_pago')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
    }
}
