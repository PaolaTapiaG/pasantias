<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Socio extends Model
{
    protected $table = 'socios';
    protected $primaryKey = 'id_socio';

    protected $fillable = [
        'numero_socio',
        'direccion',
        'fecha_registro',
        'estado',
        'oculto',
        'motivo_ocultacion',
        'oculto_en',
        'oculto_por',
        'id_persona',
        'id_sector',
        'id_tarifa',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'oculto' => 'boolean',
        'oculto_en' => 'datetime',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeCortados($query)
    {
        return $query->where('estado', 'cortado');
    }

    public function scopePorSector($query, int $idSector)
    {
        return $query->where('id_sector', $idSector);
    }

    public function scopeVisibles($query)
    {
        return $query->where('oculto', false);
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->persona?->nombre_completo ?? '-';
    }

    public function getCodigoDisplayAttribute(): string
    {
        if (!empty($this->numero_socio)) {
            return $this->numero_socio;
        }

        return 'SOC-' . str_pad((string) $this->id_socio, 4, '0', STR_PAD_LEFT);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'id_sector', 'id_sector');
    }

    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class, 'id_tarifa', 'id_tarifa');
    }

    public function medidorActivo(): HasOne
    {
        return $this->hasOne(Medidor::class, 'id_socio', 'id_socio')
            ->where('estado', 'activo');
    }

    public function medidores(): HasMany
    {
        return $this->hasMany(Medidor::class, 'id_socio', 'id_socio');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_socio', 'id_socio');
    }

    public function facturasPendientes(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_socio', 'id_socio')
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial']);
    }

    public function historialPagos(): HasMany
    {
        return $this->hasMany(HistorialPago::class, 'id_socio', 'id_socio');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_socio', 'id_socio');
    }
}
