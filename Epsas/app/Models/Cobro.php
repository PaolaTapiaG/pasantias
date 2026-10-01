<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cobro extends Model
{
    protected $table = 'cobros';
    protected $primaryKey = 'id_cobro';
    public $timestamps = false;

    protected $fillable = [
        'fecha_cobro',
        'monto_pagado',
        'monto_pendiente',
        'estado',
        'comprobante',
        'id_factura',
        'id_metodo_pago',
        'id_empleado',
        'id_orden_pago',
        'origen_pago',
        'id_cierre_caja',
        'referencia_externa',
        'estado_conciliacion',
        'confirmado_por',
        'confirmado_en',
    ];

    protected $casts = [
        'fecha_cobro' => 'date',
        'confirmado_en' => 'datetime',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function ordenPago(): BelongsTo
    {
        return $this->belongsTo(OrdenPago::class, 'id_orden_pago', 'id_orden_pago');
    }

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class, 'id_cierre_caja', 'id_cierre_caja');
    }
}
