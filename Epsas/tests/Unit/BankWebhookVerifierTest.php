<?php

namespace Tests\Unit;

use App\Services\BankWebhookVerifier;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BankWebhookVerifierTest extends TestCase
{
    public function test_accepts_a_recent_valid_signature(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');
        $body = '{"event_id":"evt-1"}';
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'secret');

        $this->assertTrue((new BankWebhookVerifier)->isValid($body, $timestamp, $signature, 'secret'));
    }

    public function test_rejects_modified_payload_and_expired_requests(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.original', 'secret');
        $verifier = new BankWebhookVerifier;

        $this->assertFalse($verifier->isValid('modified', $timestamp, $signature, 'secret'));
        $this->assertFalse($verifier->isValid('original', $timestamp - 301, $signature, 'secret'));
    }
}
