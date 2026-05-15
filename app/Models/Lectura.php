<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lectura extends Model
{
    protected $table = 'lecturas';
    protected $primaryKey = 'id_lectura';

    protected $fillable = [
        'fecha_lectura',
        'lectura_anterior',
        'lectura_actual',
        'observaciones',
        'id_medidor',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_lectura' => 'date',
        'lectura_anterior' => 'decimal:2',
        'lectura_actual' => 'decimal:2',
        'consumo_m3' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class, 'id_medidor', 'id_medidor');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_lectura', 'id_lectura');
    }
}
