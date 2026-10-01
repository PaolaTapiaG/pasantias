<?php

namespace App\Services;

class BankWebhookVerifier
{
    public function isValid(string $body, int $timestamp, string $signature, string $secret, int $toleranceSeconds = 300): bool
    {
        if ($secret === '' || $timestamp <= 0 || $signature === '') {
            return false;
        }

        if (abs(now()->timestamp - $timestamp) > $toleranceSeconds) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$body, $secret), $signature);
    }
}
