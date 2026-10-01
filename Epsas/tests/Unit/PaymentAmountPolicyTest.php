<?php

namespace Tests\Unit;

use App\Models\MetodoPago;
use App\Services\PaymentAmountPolicy;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentAmountPolicyTest extends TestCase
{
    public function test_cash_accepts_exact_amount_and_change(): void
    {
        $policy = new PaymentAmountPolicy;
        $cash = $this->method('Efectivo', false);

        $policy->validate($cash, 100, 100, null);
        $policy->validate($cash, 120, 100, null);

        $this->addToAssertionCount(2);
    }

    public function test_cash_rejects_underpayment(): void
    {
        $this->expectException(ValidationException::class);
        (new PaymentAmountPolicy)->validate($this->method('Efectivo', false), 99.99, 100, null);
    }

    public function test_qr_requires_exact_amount_and_reference(): void
    {
        $policy = new PaymentAmountPolicy;
        $qr = $this->method('QR', true);

        $policy->validate($qr, 100, 100, 'QR-001');
        $this->addToAssertionCount(1);

        try {
            $policy->validate($qr, 101, 100, 'QR-002');
            $this->fail('Expected exact amount validation.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(ValidationException::class);
        $policy->validate($qr, 100, 100, null);
    }

    private function method(string $name, bool $requiresReference): MetodoPago
    {
        return new MetodoPago([
            'nombre' => $name,
            'requiere_referencia' => $requiresReference,
            'estado' => 'activo',
        ]);
    }
}
