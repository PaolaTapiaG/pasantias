<?php

namespace App\Models;

use App\Models\Concerns\CachesOperationalRouteBinding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Persona extends Model
{
    use CachesOperationalRouteBinding;

    protected $table      = 'personas';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula_identidad',
        'telefono',
        'email',
        'fecha_nacimiento',
        'foto_path',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    // ── Accessors ──────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) {
            return null;
        }

        if (Str::startsWith($this->foto_path, ['http://', 'https://'])) {
            return $this->foto_path;
        }

        return route('private-media.persona-photo', $this);
    }

    // ── Relaciones ─────────────────────────────
    public function socio(): HasOne
    {
        return $this->hasOne(Socio::class, 'id_persona', 'id_persona');
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'id_persona', 'id_persona');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_persona', 'id_persona');
    }
}
