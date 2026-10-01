<?php

namespace App\Services;

use App\Models\OrdenPago;

class BankPaymentConfirmationPolicy
{
    public function validate(OrdenPago $order, array $payment): void
    {
        if (! in_array($order->estado, ['pendiente', 'en_revision'], true)) {
            throw new \RuntimeException('La orden no acepta una confirmacion bancaria.');
        }

        if ($order->fecha_vencimiento?->isPast()) {
            throw new \RuntimeException('La orden de pago vencio y debe generarse nuevamente.');
        }

        if (($payment['currency'] ?? null) !== 'BOB') {
            throw new \RuntimeException('La moneda confirmada por el banco no es valida.');
        }

        if (blank($payment['reference'] ?? null)) {
            throw new \RuntimeException('La confirmacion bancaria no incluye referencia.');
        }

        if (abs((float) $order->total - round((float) ($payment['amount'] ?? 0), 2)) > 0.009) {
            throw new \RuntimeException('El monto confirmado por el banco no coincide con la orden.');
        }
    }
}
