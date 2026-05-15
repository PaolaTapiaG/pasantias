<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Empleado;
use App\Models\Rol;

class CheckRolEmpleado
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Obtener el ID del empleado del usuario autenticado o de la solicitud
        $idEmpleado = $request->user()?->id_empleado ?? $request->input('id_empleado');

        if (!$idEmpleado) {
            return response()->json([
                'mensaje' => 'No autorizado. ID de empleado requerido.',
                'code'    => 'EMPLOYEE_ID_MISSING',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el empleado y su rol
        $empleado = Empleado::with('rol')
            ->find($idEmpleado);

        if (!$empleado) {
            return response()->json([
                'mensaje' => 'Empleado no encontrado.',
                'code'    => 'EMPLOYEE_NOT_FOUND',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($empleado->estado !== 'activo') {
            return response()->json([
                'mensaje' => 'El empleado no está activo.',
                'code'    => 'EMPLOYEE_INACTIVE',
            ], Response::HTTP_FORBIDDEN);
        }

        // Validar el rol si se especificaron roles requeridos
        if (!empty($roles)) {
            $rolEmpleado = $empleado->rol?->nombre;

            if (!in_array($rolEmpleado, $roles)) {
                return response()->json([
                    'mensaje'      => "No posee permisos para acceder a este recurso. Rol requerido: " . implode(', ', $roles),
                    'code'         => 'INSUFFICIENT_ROLE',
                    'rol_requerido' => $roles,
                    'rol_actual'   => $rolEmpleado,
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Agregar el empleado a la solicitud para usarlo después
        $request->merge([
            'empleado_autenticado' => $empleado,
            'id_empleado_actual'   => $idEmpleado,
        ]);

        return $next($request);
    }
}
