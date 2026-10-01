<?php

namespace App\Models;

use App\Models\Concerns\CachesOperationalRouteBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MedidorAnomalia extends Model
{
    use CachesOperationalRouteBinding;

    protected $table = 'medidor_anomalias';
    protected $primaryKey = 'id_anomalia';

    protected $fillable = [
        'fecha_reporte',
        'tipo',
        'prioridad',
        'estado',
        'descripcion',
        'evidencia_path',
        'monto_multa',
        'id_medidor',
        'id_empleado',
        'id_factura_multa',
    ];

    protected $casts = [
        'fecha_reporte' => 'date',
        'monto_multa' => 'decimal:2',
    ];

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class, 'id_medidor', 'id_medidor');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function facturaMulta(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura_multa', 'id_factura');
    }

    public function getEvidenciaUrlAttribute(): ?string
    {
        if (!$this->evidencia_path) {
            return null;
        }

        if (Str::startsWith($this->evidencia_path, ['http://', 'https://'])) {
            return $this->evidencia_path;
        }

        return route('private-media.anomaly-evidence', $this);
    }
}
