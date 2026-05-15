<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // ── Relaciones ─────────────────────────────
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'id_rol', 'id_rol');
    }
}