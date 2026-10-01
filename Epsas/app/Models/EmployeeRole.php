<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EmployeeRole extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'id_rol', 'id_rol');
    }

    public static function cachedOrderedList(): Collection
    {
        return Cache::remember('roles:ordered-list', now()->addHour(), fn () => static::query()
            ->orderBy('nombre')
            ->get());
    }
}
