<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoAdministrativo extends Model
{
    protected $table = 'ingresos_administrativos';

    protected $primaryKey = 'id_ingreso';

    protected $fillable = [
        'fecha_ingreso',
        'concepto',
        'categoria',
        'descripcion',
        'monto',
        'id_socio',
        'id_empleado',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'monto' => 'decimal:2',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
