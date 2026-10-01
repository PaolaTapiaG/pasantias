<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->must_change_password || $request->routeIs([
            'logout',
            'private-media.persona-photo',
            'admin.perfil.*',
            'admin.configuracion.profile.update',
            'secretaria.perfil.*',
            'tecnico.configuracion.*',
        ])) {
            return $next($request);
        }

        $route = match (true) {
            $user->hasRole('administrador') => 'admin.perfil.index',
            $user->hasRole('secretaria') => 'secretaria.perfil.index',
            $user->hasRole('tecnico') => 'tecnico.configuracion.index',
            default => null,
        };

        abort_unless($route, 403);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Debes cambiar tu contrasena temporal antes de continuar.',
                'redirect' => route($route),
            ], 423);
        }

        return redirect()->route($route)->with('error', 'Debes cambiar tu contrasena temporal antes de continuar.');
    }
}
