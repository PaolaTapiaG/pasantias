<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medidor extends Model
{
    protected $table = 'medidores';
    protected $primaryKey = 'id_medidor';

    protected $fillable = [
        'numero_serie',
        'marca',
        'modelo',
        'fecha_instalacion',
        'estado',
        'id_socio',
        'id_empleado_instalador',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'id_socio', 'id_socio');
    }

    public function empleadoInstalador(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado_instalador', 'id_empleado');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'id_medidor', 'id_medidor');
    }
}
