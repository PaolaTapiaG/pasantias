<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Lectura;

class StoreLecturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_lectura'    => ['required', 'date', 'before_or_equal:today'],
            'lectura_anterior' => ['required', 'numeric', 'min:0'],
            'lectura_actual'   => ['required', 'numeric', 'min:0', 'gte:lectura_anterior'],
            'consumo_m3'       => ['nullable', 'numeric', 'min:0'],
            'observaciones'    => ['nullable', 'string', 'max:500'],
            'id_medidor'       => ['required', 'integer', 'exists:medidores,id_medidor'],
            'id_empleado'      => ['required', 'integer', 'exists:empleados,id_empleado'],
            'id_periodo'       => ['required', 'integer', 'exists:periodos_fabricacion,id_periodo'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_lectura.required'       => 'La fecha de lectura es obligatoria.',
            'fecha_lectura.before_or_equal'=> 'La fecha de lectura no puede ser futura.',
            'lectura_anterior.required'    => 'La lectura anterior es obligatoria.',
            'lectura_anterior.numeric'     => 'La lectura anterior debe ser un número.',
            'lectura_anterior.min'         => 'La lectura anterior no puede ser negativa.',
            'lectura_actual.required'      => 'La lectura actual es obligatoria.',
            'lectura_actual.numeric'       => 'La lectura actual debe ser un número.',
            'lectura_actual.gte'           => 'La lectura actual debe ser mayor o igual a la lectura anterior.',
            'consumo_m3.min'               => 'El consumo no puede ser negativo.',
            'observaciones.max'            => 'Las observaciones no pueden superar 500 caracteres.',
            'id_medidor.required'          => 'Debe seleccionar un medidor.',
            'id_medidor.exists'            => 'El medidor seleccionado no existe.',
            'id_empleado.required'         => 'Debe seleccionar el empleado lector.',
            'id_empleado.exists'           => 'El empleado seleccionado no existe.',
            'id_periodo.required'          => 'Debe seleccionar un período de facturación.',
            'id_periodo.exists'            => 'El período seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'fecha_lectura'    => 'fecha de lectura',
            'lectura_anterior' => 'lectura anterior',
            'lectura_actual'   => 'lectura actual',
            'consumo_m3'       => 'consumo en m³',
            'observaciones'    => 'observaciones',
            'id_medidor'       => 'medidor',
            'id_empleado'      => 'empleado',
            'id_periodo'       => 'período de facturación',
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     * Verifica que el período no esté cerrado y que el medidor
     * no tenga ya una lectura en ese período.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            // 1. Verificar que el período esté abierto
            $periodo = \App\Models\PeriodoFacturacion::find($this->id_periodo);
            if ($periodo && $periodo->cerrado) {
                $validator->errors()->add(
                    'id_periodo',
                    'No se puede registrar una lectura en un período que ya está cerrado.'
                );
            }

            // 2. Verificar que el medidor no tenga ya una lectura en ese período
            $duplicada = Lectura::where('id_medidor', $this->id_medidor)
                                ->where('id_periodo', $this->id_periodo)
                                ->exists();

            if ($duplicada) {
                $validator->errors()->add(
                    'id_medidor',
                    'Este medidor ya tiene una lectura registrada en el período seleccionado.'
                );
            }
        });
    }
}