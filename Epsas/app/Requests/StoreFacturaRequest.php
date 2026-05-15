<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Factura;

class StoreFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_factura'  => ['required', 'string', 'max:50', 'unique:facturas,numero_factura'],
            'fecha_emision'   => ['required', 'date', 'before_or_equal:today'],
            'fecha_pago'      => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'consumo_m3'      => ['required', 'numeric', 'min:0'],
            'monto_consumo'   => ['required', 'numeric', 'min:0'],
            'cargo_fijo'      => ['required', 'numeric', 'min:0'],
            'recargo_mora'    => ['nullable', 'numeric', 'min:0'],
            'id_socio'        => ['required', 'integer', 'exists:socios,id_socio'],
            'id_periodo'      => ['required', 'integer', 'exists:periodos_fabricacion,id_periodo'],
            'id_lectura'      => ['required', 'integer', 'exists:lecturas,id_lectura', 'unique:facturas,id_lectura'],
            'id_metodo_pago'  => ['nullable', 'integer', 'exists:metodos_pago,id_metodo_pago'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_factura.required'        => 'El número de factura es obligatorio.',
            'numero_factura.unique'          => 'Este número de factura ya existe.',
            'fecha_emision.required'         => 'La fecha de emisión es obligatoria.',
            'fecha_emision.before_or_equal'  => 'La fecha de emisión no puede ser futura.',
            'fecha_pago.after_or_equal'      => 'La fecha de pago no puede ser anterior a la fecha de emisión.',
            'consumo_m3.required'            => 'El consumo en m³ es obligatorio.',
            'consumo_m3.min'                 => 'El consumo no puede ser negativo.',
            'monto_consumo.required'         => 'El monto de consumo es obligatorio.',
            'monto_consumo.min'              => 'El monto de consumo no puede ser negativo.',
            'cargo_fijo.required'            => 'El cargo fijo es obligatorio.',
            'cargo_fijo.min'                 => 'El cargo fijo no puede ser negativo.',
            'recargo_mora.min'               => 'El recargo por mora no puede ser negativo.',
            'id_socio.required'              => 'Debe seleccionar un socio.',
            'id_socio.exists'                => 'El socio seleccionado no existe.',
            'id_periodo.required'            => 'Debe seleccionar un período de facturación.',
            'id_periodo.exists'              => 'El período seleccionado no existe.',
            'id_lectura.required'            => 'Debe seleccionar una lectura.',
            'id_lectura.exists'              => 'La lectura seleccionada no existe.',
            'id_lectura.unique'              => 'Esta lectura ya tiene una factura generada.',
            'id_metodo_pago.exists'          => 'El método de pago seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'numero_factura' => 'número de factura',
            'fecha_emision'  => 'fecha de emisión',
            'fecha_pago'     => 'fecha de pago',
            'consumo_m3'     => 'consumo en m³',
            'monto_consumo'  => 'monto de consumo',
            'cargo_fijo'     => 'cargo fijo',
            'recargo_mora'   => 'recargo por mora',
            'id_socio'       => 'socio',
            'id_periodo'     => 'período de facturación',
            'id_lectura'     => 'lectura',
            'id_metodo_pago' => 'método de pago',
        ];
    }

    /**
     * Validaciones cruzadas adicionales.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            // 1. Verificar que la lectura pertenece al socio indicado
            if ($this->id_lectura && $this->id_socio) {
                $lectura = \App\Models\Lectura::with('medidor')->find($this->id_lectura);

                if ($lectura && $lectura->medidor && $lectura->medidor->id_socio != $this->id_socio) {
                    $validator->errors()->add(
                        'id_lectura',
                        'La lectura seleccionada no corresponde al socio indicado.'
                    );
                }
            }

            // 2. Verificar que el período no esté cerrado
            $periodo = \App\Models\PeriodoFacturacion::find($this->id_periodo);
            if ($periodo && $periodo->cerrado) {
                $validator->errors()->add(
                    'id_periodo',
                    'No se puede generar una factura para un período cerrado.'
                );
            }

            // 3. Verificar que el socio esté activo
            $socio = \App\Models\Socio::find($this->id_socio);
            if ($socio && $socio->estado !== 'activo') {
                $validator->errors()->add(
                    'id_socio',
                    'No se puede generar una factura para un socio inactivo o suspendido.'
                );
            }
        });
    }
}