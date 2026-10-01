<?php

namespace Tests\Unit;

use App\Models\OrdenPago;
use App\Services\BankPaymentConfirmationPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BankPaymentConfirmationPolicyTest extends TestCase
{
    public function test_accepts_exact_bob_payment_for_pending_order(): void
    {
        $order = new OrdenPago(['estado' => 'pendiente', 'total' => 125.50]);

        (new BankPaymentConfirmationPolicy)->validate($order, [
            'currency' => 'BOB',
            'reference' => 'BANK-001',
            'amount' => 125.50,
        ]);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidPayments')]
    public function test_rejects_invalid_bank_confirmations(string $state, array $payment): void
    {
        $this->expectException(\RuntimeException::class);

        $order = new OrdenPago(['estado' => $state, 'total' => 125.50]);
        (new BankPaymentConfirmationPolicy)->validate($order, $payment);
    }

    public static function invalidPayments(): array
    {
        return [
            'already approved' => ['aprobada', ['currency' => 'BOB', 'reference' => 'R1', 'amount' => 125.50]],
            'wrong currency' => ['pendiente', ['currency' => 'USD', 'reference' => 'R2', 'amount' => 125.50]],
            'missing reference' => ['pendiente', ['currency' => 'BOB', 'reference' => '', 'amount' => 125.50]],
            'underpayment' => ['pendiente', ['currency' => 'BOB', 'reference' => 'R3', 'amount' => 100]],
            'overpayment' => ['pendiente', ['currency' => 'BOB', 'reference' => 'R4', 'amount' => 130]],
        ];
    }
}
