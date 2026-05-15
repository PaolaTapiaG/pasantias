<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según política de autorización
    }

    public function rules(): array
    {
        return [
            // ── Datos de la persona ──────────────────────────────
            'nombre'            => ['required', 'string', 'max:100'],
            'apellidos'         => ['required', 'string', 'max:100'],
            'cedula_identidad'  => ['required', 'string', 'max:20', 'unique:personas,cedula_identidad'],
            'telefono'          => ['nullable', 'string', 'max:20'],
            'correo_electronico'=> ['nullable', 'email', 'max:150', 'unique:personas,correo_electronico'],

            // ── Datos del socio ──────────────────────────────────
            'numero_socio'      => ['required', 'string', 'max:50', 'unique:socios,numero_socio'],
            'direccion'         => ['required', 'string', 'max:255'],
            'fecha_registro'    => ['required', 'date', 'before_or_equal:today'],
            'estado'            => ['required', 'string', 'in:activo,inactivo,suspendido'],
            'sector_id'         => ['required', 'integer', 'exists:sectores,sector_id'],
            'id_tarifa'         => ['required', 'integer', 'exists:tarifas,id_tarifa'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'             => 'El nombre es obligatorio.',
            'apellidos.required'          => 'Los apellidos son obligatorios.',
            'cedula_identidad.required'   => 'La cédula de identidad es obligatoria.',
            'cedula_identidad.unique'     => 'Ya existe una persona con esa cédula de identidad.',
            'correo_electronico.email'    => 'El correo electrónico no tiene un formato válido.',
            'correo_electronico.unique'   => 'Este correo electrónico ya está registrado.',
            'numero_socio.required'       => 'El número de socio es obligatorio.',
            'numero_socio.unique'         => 'Este número de socio ya existe.',
            'direccion.required'          => 'La dirección es obligatoria.',
            'fecha_registro.required'     => 'La fecha de registro es obligatoria.',
            'fecha_registro.before_or_equal' => 'La fecha de registro no puede ser futura.',
            'estado.in'                   => 'El estado debe ser: activo, inactivo o suspendido.',
            'sector_id.required'          => 'Debe seleccionar un sector.',
            'sector_id.exists'            => 'El sector seleccionado no existe.',
            'id_tarifa.required'          => 'Debe seleccionar una tarifa.',
            'id_tarifa.exists'            => 'La tarifa seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre'             => 'nombre',
            'apellidos'          => 'apellidos',
            'cedula_identidad'   => 'cédula de identidad',
            'telefono'           => 'teléfono',
            'correo_electronico' => 'correo electrónico',
            'numero_socio'       => 'número de socio',
            'direccion'          => 'dirección',
            'fecha_registro'     => 'fecha de registro',
            'estado'             => 'estado',
            'sector_id'          => 'sector',
            'id_tarifa'          => 'tarifa',
        ];
    }
}