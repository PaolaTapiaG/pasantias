<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenPago extends Model
{
    protected $table = 'ordenes_pago';
    protected $primaryKey = 'id_orden_pago';

    protected $fillable = [
        'codigo',
        'id_socio',
        'total',
        'estado',
        'metodo',
        'access_token',
        'fecha_vencimiento',
        'comprobante_path',
        'comprobante_referencia',
        'entidad_financiera',
        'comprobante_monto',
        'comprobante_fecha',
        'observaciones_cliente',
        'notas_revision',
        'revisado_por',
        'revisado_en',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'comprobante_monto' => 'decimal:2',
        'comprobante_fecha' => 'date',
        'fecha_vencimiento' => 'datetime',
        'revisado_en' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenPagoDetalle::class, 'id_orden_pago', 'id_orden_pago');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class, 'id_orden_pago', 'id_orden_pago');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'revisado_por', 'id_empleado');
    }

    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante_path
            ? route('secretaria.ordenes-pago.proof', $this)
            : null;
    }
}
