<?php

namespace App\Services;

use App\Models\MetodoPago;
use Illuminate\Validation\ValidationException;

class PaymentAmountPolicy
{
    public function validate(MetodoPago $method, float $paidAmount, float $selectedTotal, ?string $reference): void
    {
        $paidAmount = round($paidAmount, 2);
        $selectedTotal = round($selectedTotal, 2);

        if ($paidAmount <= 0 || $selectedTotal <= 0) {
            throw ValidationException::withMessages([
                'cantidad_pagada' => 'El monto a cobrar debe ser mayor a cero.',
            ]);
        }

        if ($method->es_efectivo && $paidAmount < $selectedTotal) {
            throw ValidationException::withMessages([
                'cantidad_pagada' => 'La cantidad en efectivo no cubre el total seleccionado.',
            ]);
        }

        if (! $method->es_efectivo && abs($paidAmount - $selectedTotal) > 0.009) {
            throw ValidationException::withMessages([
                'cantidad_pagada' => 'Para '.$method->nombre.' el monto debe coincidir exactamente con el total seleccionado.',
            ]);
        }

        if ($method->requiere_referencia && blank($reference)) {
            throw ValidationException::withMessages([
                'comprobante' => 'Debes registrar una referencia o comprobante para '.$method->nombre.'.',
            ]);
        }
    }
}
