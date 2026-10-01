<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comunicado extends Model
{
    protected $table = 'comunicados';

    protected $primaryKey = 'id_comunicado';

    protected $fillable = [
        'titulo',
        'contenido',
        'estado',
        'importante',
        'publicar_desde',
        'publicar_hasta',
        'id_empleado',
        'aprobado_por',
        'aprobado_en',
    ];

    protected $casts = [
        'importante' => 'boolean',
        'publicar_desde' => 'datetime',
        'publicar_hasta' => 'datetime',
        'aprobado_en' => 'datetime',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'aprobado_por', 'id_empleado');
    }
}
