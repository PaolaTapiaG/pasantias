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
        'nombre',        // 'Efectivo', 'Transferencia', 'QR', etc.
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'creado_en' => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'id_metodo_pago');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_metodo_pago');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}