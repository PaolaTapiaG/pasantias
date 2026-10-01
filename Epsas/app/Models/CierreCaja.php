<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $primaryKey = 'id_cierre_caja';

    protected $fillable = [
        'fecha_caja',
        'estado',
        'cantidad_cobros',
        'total_efectivo',
        'total_qr',
        'total_transferencia',
        'total_general',
        'observaciones',
        'abierta_en',
        'cerrada_en',
        'revisada_en',
        'id_empleado',
        'revisado_por',
    ];

    protected $casts = [
        'fecha_caja' => 'date',
        'abierta_en' => 'datetime',
        'cerrada_en' => 'datetime',
        'revisada_en' => 'datetime',
        'total_efectivo' => 'decimal:2',
        'total_qr' => 'decimal:2',
        'total_transferencia' => 'decimal:2',
        'total_general' => 'decimal:2',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'revisado_por', 'id_empleado');
    }
}
