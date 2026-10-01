<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Console\Command;

class LocalRouteBenchmarkCommand extends Command
{
    protected $signature = 'app:local-route-benchmark {--role=administrador : Rol a simular}';

    protected $description = 'Mide rutas principales en entorno local simulando un usuario autenticado por rol';

    public function handle(Kernel $kernel): int
    {
        if (! app()->environment('local')) {
            $this->warn('Este benchmark solo esta disponible en local.');

            return self::SUCCESS;
        }

        $role = (string) $this->option('role');
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->with(['persona', 'roles'])
            ->first();

        if (! $user) {
            $this->error("No existe un usuario con rol {$role}.");

            return self::FAILURE;
        }

        $user->must_change_password = false;
        $rows = [];
        $active = false;
        $queryCount = 0;
        $queryMs = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$active, &$queryCount, &$queryMs): void {
            if (! $active) {
                return;
            }

            $queryCount++;
            $queryMs += $query->time;
        });

        foreach ($this->urisForRole($role) as $uri) {
            Auth::guard()->setUser($user);
            $request = Request::create($uri, 'GET', server: [
                'HTTP_HOST' => '127.0.0.1:8000',
                'HTTPS' => 'off',
            ]);
            $request->setUserResolver(fn () => $user);

            $queryCount = 0;
            $queryMs = 0.0;
            $active = true;
            $startedAt = hrtime(true);

            try {
                $response = $kernel->handle($request);
                $kernel->terminate($request, $response);
            } catch (\Throwable $exception) {
                $active = false;
                $rows[] = [
                    $uri,
                    'ERR',
                    number_format((hrtime(true) - $startedAt) / 1_000_000, 2),
                    number_format($queryMs, 2),
                    $queryCount,
                    Str::limit($exception->getMessage(), 70),
                ];

                continue;
            }

            $active = false;
            $status = $response->getStatusCode();
            $target = $this->redirectTarget($response);

            $rows[] = [
                $uri,
                (string) $status,
                number_format((hrtime(true) - $startedAt) / 1_000_000, 2),
                number_format($queryMs, 2),
                $queryCount,
                $target,
            ];
        }

        $this->table(['Ruta', 'Estado', 'App ms', 'BD ms', 'Consultas', 'Detalle'], $rows);

        return self::SUCCESS;
    }

    private function urisForRole(string $role): array
    {
        return match ($role) {
            'secretaria' => [
                '/dashboard',
                '/admin/socios',
                '/admin/facturas',
                '/admin/cobros',
                '/admin/ordenes-pago',
                '/admin/reportes',
                '/admin/operaciones-secretaria',
                '/admin/perfil-secretaria',
            ],
            'tecnico' => [
                '/dashboard',
                '/admin/medidores',
                '/admin/lecturas',
                '/admin/lecturas/crear',
                '/admin/consumo',
                '/admin/anomalias',
                '/admin/cortes',
                '/admin/reconexiones',
                '/admin/incidencias',
            ],
            default => [
                '/dashboard',
                '/admin/socios',
                '/admin/empleados',
                '/admin/tarifas',
                '/admin/medidores',
                '/admin/lecturas',
                '/admin/lecturas/crear',
                '/admin/facturas',
                '/admin/cobros',
                '/admin/gastos',
                '/admin/reportes',
                '/admin/configuracion',
            ],
        };
    }

    private function redirectTarget(Response $response): string
    {
        if (! $response->isRedirection()) {
            return '';
        }

        return (string) $response->headers->get('Location', '');
    }
}
