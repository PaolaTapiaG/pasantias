<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoFacturacion extends Model
{
    use HasFactory;

    protected $table = 'periodos_facturacion';
    protected $primaryKey = 'id_periodo';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'cerrado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'cerrado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_periodo', 'id_periodo');
    }

    public function scopeAbiertos($query)
    {
        return $query->where('cerrado', false);
    }

    public function scopeCerrados($query)
    {
        return $query->where('cerrado', true);
    }
}
