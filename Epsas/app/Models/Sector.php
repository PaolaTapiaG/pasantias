<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $table      = 'sectores';
    protected $primaryKey = 'id_sector';

    protected $fillable = [
        'nombre',
        'descripcion',
        'zona',
    ];

    // ── Relaciones ─────────────────────────────
    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class, 'id_sector', 'id_sector');
    }

    public function sociosActivos(): HasMany
    {
        return $this->hasMany(Socio::class, 'id_sector', 'id_sector')
                    ->where('estado', 'activo');
    }
}