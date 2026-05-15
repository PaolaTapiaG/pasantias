<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';
    protected $primaryKey = 'id_metodo_pago';

    protected $fillable = [
        'nombre',
        'descripcion',
        'requiere_referencia',
        'estado',
    ];

    protected $casts = [
        'requiere_referencia' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'id_metodo_pago');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_metodo_pago');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function getEsEfectivoAttribute(): bool
    {
        return str_contains(mb_strtolower($this->nombre ?? ''), 'efectivo');
    }
}
