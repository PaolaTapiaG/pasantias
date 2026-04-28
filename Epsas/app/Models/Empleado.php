<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleados';
    protected $primaryKey = 'id_empleado';

    protected $fillable = [
        'fecha_ingreso',
        'estado',
        'id_persona',
        'id_rol',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'creado_en'     => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'id_empleado');
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'id_empleado');
    }

    public function medidoresInstalados()
    {
        return $this->hasMany(Medidor::class, 'id_empleado_instalador');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Persona::class,
            'id_persona',
            'id_persona',
            'id_persona',
            'id_persona'
        );
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
