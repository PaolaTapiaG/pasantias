<?php

namespace App\Models;

use App\Services\WaterBillingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarifa extends Model
{
    protected $table      = 'tarifas';
    protected $primaryKey = 'id_tarifa';

    protected $fillable = [
        'nombre',
        'tipo_uso',
        'precio_m3_base',
        'consumo_minimo_m3',
        'cargo_fijo',
        'fecha_vigencia',
        'estado',
    ];

    protected $casts = [
        'precio_m3_base'    => 'decimal:2',
        'consumo_minimo_m3' => 'decimal:2',
        'cargo_fijo'        => 'decimal:2',
        'fecha_vigencia'    => 'date',
    ];

    // ── Scopes ─────────────────────────────────
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    // ── Métodos de negocio ─────────────────────
    /**
     * Calcula el monto a cobrar según el consumo en m³.
     */
    public function calcularMonto(float $consumoM3): float
    {
        return $this->calcularDesglose($consumoM3)['water_charge'];
    }

    public function calcularDesglose(float $consumoM3): array
    {
        return app(WaterBillingService::class)->breakdown($consumoM3);
    }

    // ── Relaciones ─────────────────────────────
    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class, 'id_tarifa', 'id_tarifa');
    }
}
