<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Factura;

class StoreCobroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_factura'      => ['required', 'integer', 'exists:facturas,id_factura'],
            'id_empleado'     => ['required', 'integer', 'exists:empleados,id_empleado'],
            'id_metodo_pago'  => ['required', 'integer', 'exists:metodos_pago,id_metodo_pago'],
            'monto'           => ['required', 'numeric', 'min:0.01'],
            'fecha_cobro'     => ['required', 'date', 'before_or_equal:now'],
            'observaciones'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_factura.required'         => 'Debe seleccionar una factura.',
            'id_factura.exists'           => 'La factura seleccionada no existe.',
            'id_empleado.required'        => 'Debe seleccionar el empleado cobrador.',
            'id_empleado.exists'          => 'El empleado seleccionado no existe.',
            'id_metodo_pago.required'     => 'Debe seleccionar un método de pago.',
            'id_metodo_pago.exists'       => 'El método de pago seleccionado no existe.',
            'monto.required'              => 'El monto del cobro es obligatorio.',
            'monto.numeric'               => 'El monto debe ser un valor numérico.',
            'monto.min'                   => 'El monto debe ser mayor a cero.',
            'fecha_cobro.required'        => 'La fecha del cobro es obligatoria.',
            'fecha_cobro.before_or_equal' => 'La fecha del cobro no puede ser futura.',
            'observaciones.max'           => 'Las observaciones no pueden superar 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_factura'     => 'factura',
            'id_empleado'    => 'empleado',
            'id_metodo_pago' => 'método de pago',
            'monto'          => 'monto',
            'fecha_cobro'    => 'fecha de cobro',
            'observaciones'  => 'observaciones',
        ];
    }

    /**
     * Validaciones cruzadas adicionales.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $factura = Factura::find($this->id_factura);

            if (! $factura) {
                return; // La regla exists ya lo detectó
            }

            // 1. Verificar que la factura no esté ya pagada
            if ($factura->estaPagada()) {
                $validator->errors()->add(
                    'id_factura',
                    'Esta factura ya se encuentra pagada.'
                );
                return;
            }

            // 2. Verificar que el monto no supere el total de la factura
            $totalFactura = $factura->monto_consumo + $factura->cargo_fijo + ($factura->recargo_mora ?? 0);
            $montoCobrado = Factura::find($this->id_factura)
                ->cobros()
                ->sum('monto');

            $saldoPendiente = $totalFactura - $montoCobrado;

            if ($this->monto > $saldoPendiente) {
                $validator->errors()->add(
                    'monto',
                    "El monto ingresado ({$this->monto}) supera el saldo pendiente de la factura ({$saldoPendiente})."
                );
            }

            // 3. Verificar que el método de pago esté activo
            $metodoPago = \App\Models\MetodoPago::find($this->id_metodo_pago);
            if ($metodoPago && ! $metodoPago->activo) {
                $validator->errors()->add(
                    'id_metodo_pago',
                    'El método de pago seleccionado no está activo.'
                );
            }
        });
    }
}