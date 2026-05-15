<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'id_factura';

    protected $fillable = [
        'numero_factura',
        'fecha_emision',
        'fecha_pago',
        'consumo_m3',
        'monto_consumo',
        'cargo_fijo',
        'recargo_mora',
        'descuentos',
        'precio_m3_aplicado',
        'cargo_fijo_aplicado',
        'estado',
        'id_socio',
        'id_lectura',
        'id_periodo',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_pago' => 'date',
        'consumo_m3' => 'decimal:2',
        'monto_consumo' => 'decimal:2',
        'cargo_fijo' => 'decimal:2',
        'recargo_mora' => 'decimal:2',
        'descuentos' => 'decimal:2',
        'precio_m3_aplicado' => 'decimal:4',
        'cargo_fijo_aplicado' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoFacturacion::class, 'id_periodo', 'id_periodo');
    }

    public function lectura(): BelongsTo
    {
        return $this->belongsTo(Lectura::class, 'id_lectura', 'id_lectura');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class, 'id_factura', 'id_factura');
    }
}
