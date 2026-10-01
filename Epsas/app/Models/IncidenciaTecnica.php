<?php

namespace App\Models;

use App\Models\Concerns\CachesOperationalRouteBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IncidenciaTecnica extends Model
{
    use CachesOperationalRouteBinding;

    protected $table = 'incidencias_tecnicas';
    protected $primaryKey = 'id_incidencia';

    protected $fillable = [
        'tipo',
        'prioridad',
        'estado',
        'zona',
        'fecha_reporte',
        'descripcion',
        'evidencia_path',
        'coord_x',
        'coord_y',
        'id_socio',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_reporte' => 'datetime',
        'coord_x' => 'decimal:7',
        'coord_y' => 'decimal:7',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function getEvidenciaUrlAttribute(): ?string
    {
        if (!$this->evidencia_path) {
            return null;
        }

        if (Str::startsWith($this->evidencia_path, ['http://', 'https://'])) {
            return $this->evidencia_path;
        }

        return route('private-media.incident-evidence', $this);
    }
}
