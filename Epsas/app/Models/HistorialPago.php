<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPago extends Model
{
    protected $table = 'historial_pagos';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'tipo_evento',
        'descripcion',
        'monto',
        'id_socio',
        'id_factura',
        'id_cobro',
        'id_empleado',
        'fecha_evento',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }

    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class, 'id_cobro', 'id_cobro');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
