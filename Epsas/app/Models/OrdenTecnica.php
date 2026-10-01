<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenTecnica extends Model
{
    protected $table = 'ordenes_tecnicas';
    protected $primaryKey = 'id_orden';

    protected $fillable = [
        'tipo',
        'estado',
        'prioridad',
        'fecha_programada',
        'fecha_ejecucion',
        'zona',
        'referencia',
        'descripcion',
        'coord_x',
        'coord_y',
        'id_socio',
        'id_medidor',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_ejecucion' => 'date',
        'coord_x' => 'decimal:7',
        'coord_y' => 'decimal:7',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class, 'id_medidor', 'id_medidor');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
