<?php

namespace App\Support;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class CurrentEmployee
{
    public static function resolve(?User $user = null): Empleado
    {
        $user ??= Auth::user();
        $key = 'auth:current-employee:'.($user?->getKey() ?? 'fallback');

        return Cache::remember($key, now()->addDay(), function () use ($user) {
            if ($user?->id_persona) {
                $employee = Empleado::query()
                    ->with(['persona', 'rol'])
                    ->where('estado', 'activo')
                    ->where('id_persona', $user->id_persona)
                    ->first();

                if ($employee) {
                    return $employee;
                }
            }

            if ($user?->email) {
                $employee = Empleado::query()
                    ->with(['persona', 'rol'])
                    ->where('estado', 'activo')
                    ->whereHas('persona', fn ($query) => $query->where('email', $user->email))
                    ->first();

                if ($employee) {
                    return $employee;
                }
            }

            return Empleado::query()
                ->with(['persona', 'rol'])
                ->where('estado', 'activo')
                ->orderBy('id_empleado')
                ->firstOrFail();
        });
    }

    public static function id(?User $user = null): int
    {
        return (int) self::resolve($user)->id_empleado;
    }
}
