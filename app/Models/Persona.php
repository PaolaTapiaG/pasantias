<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Persona extends Model
{
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

        // Si ya es una URL completa
        if (Str::startsWith($this->foto_path, ['http://', 'https://'])) {
            return $this->foto_path;
        }

        // Si la ruta ya incluye 'storage/' o 'uploads/'
        if (Str::startsWith($this->foto_path, ['storage/', 'uploads/'])) {
            return asset($this->foto_path);
        }

        // Si la ruta empieza con 'perfiles/' (nuestro nuevo estándar)
        if (Str::startsWith($this->foto_path, 'perfiles/')) {
            return asset('storage/' . $this->foto_path);
        }

        // Fallback: intentar con storage/
        return asset('storage/' . $this->foto_path);
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
