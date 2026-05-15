<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_serie'           => ['required', 'string', 'max:100', 'unique:medidores,numero_serie'],
            'marca'                  => ['required', 'string', 'max:100'],
            'modelo'                 => ['nullable', 'string', 'max:100'],
            'fecha_instalacion'      => ['required', 'date', 'before_or_equal:today'],
            'estado'                 => ['required', 'string', 'in:activo,inactivo,dañado,reemplazado'],
            'id_socio'               => ['required', 'integer', 'exists:socios,id_socio'],
            'id_empleado_instalador' => ['required', 'integer', 'exists:empleados,id_empleado'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_serie.required'            => 'El número de serie es obligatorio.',
            'numero_serie.unique'              => 'Ya existe un medidor con ese número de serie.',
            'marca.required'                   => 'La marca del medidor es obligatoria.',
            'fecha_instalacion.required'       => 'La fecha de instalación es obligatoria.',
            'fecha_instalacion.before_or_equal'=> 'La fecha de instalación no puede ser futura.',
            'estado.in'                        => 'El estado debe ser: activo, inactivo, dañado o reemplazado.',
            'id_socio.required'                => 'Debe seleccionar un socio.',
            'id_socio.exists'                  => 'El socio seleccionado no existe.',
            'id_empleado_instalador.required'  => 'Debe seleccionar el empleado instalador.',
            'id_empleado_instalador.exists'    => 'El empleado instalador seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'numero_serie'           => 'número de serie',
            'marca'                  => 'marca',
            'modelo'                 => 'modelo',
            'fecha_instalacion'      => 'fecha de instalación',
            'estado'                 => 'estado',
            'id_socio'               => 'socio',
            'id_empleado_instalador' => 'empleado instalador',
        ];
    }
}