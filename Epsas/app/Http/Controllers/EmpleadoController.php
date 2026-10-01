<?php

namespace App\Http\Controllers;

use App\Http\Services\CredentialNotificationService;
use App\Jobs\SendCredentialNotification;
use App\Models\Empleado;
use App\Models\Gasto;
use App\Models\Persona;
use App\Models\EmployeeRole;
use App\Models\AccessRole;
use App\Models\User;
use App\Support\PrivateMedia;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    private const IDENTITY_CACHE_KEY = 'empleados:identity-index:v1';

    public function __construct(private CredentialNotificationService $credentialNotifications)
    {
    }

    public function index(Request $request)
    {
        return view('empleados.index', [
            'empleados' => $this->employeePaginator($request),
            'roles' => EmployeeRole::cachedOrderedList(),
            'totales' => $this->employeeTotals(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/empleados', 'GET');

        $this->employeePaginator($request, url('/admin/empleados'));
        EmployeeRole::cachedOrderedList();
        $this->employeeIdentityIndex();
        EmployeeRole::cachedOrderedList()->each(fn (EmployeeRole $role) => $this->userRoleId($role));
        Cache::remember('queue.jobs-table-available', now()->addMinutes(30), fn () => Schema::hasTable('jobs'));
        $this->employeeTotals();
    }

    private function employeePaginator(Request $request, ?string $path = null)
    {
        $cacheKey = 'empleados.index.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('operations', 'empleados.index.'.$cacheKey, fn () => $this->employeeIndexQuery($request)
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/empleados'))
            ->appends($request->query())
            ->through(fn ($row) => $this->employeeRowForView($row)));
    }

    private function employeeIndexQuery(Request $request)
    {
        $query = DB::table('empleados as e')
            ->leftJoin('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->leftJoin('roles as r', 'r.id_rol', '=', 'e.id_rol')
            ->leftJoin('users as u', 'u.id_persona', '=', 'p.id_persona')
            ->select([
                'e.id_empleado',
                'e.fecha_ingreso',
                'e.estado',
                'e.id_persona',
                'e.id_rol',
                'e.salario_base',
                'e.bono_mensual',
                'e.aguinaldo_anual',
                'p.nombres',
                'p.apellidos',
                'p.cedula_identidad',
                'p.telefono',
                'p.email as persona_email',
                'p.foto_path',
                'r.nombre as rol_nombre',
                'u.id as user_id',
                'u.username',
                'u.email as user_email',
                'u.name as user_name',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->orderByDesc('e.fecha_ingreso')
            ->orderByDesc('e.id_empleado');

        if ($request->filled('buscar') && strlen(trim((string) $request->buscar)) >= 2) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%")
                    ->orWhere('p.email', 'ilike', "%{$term}%")
                    ->orWhere('r.nombre', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('e.estado', $request->estado);
        }

        if ($request->filled('rol')) {
            $query->where('e.id_rol', $request->rol);
        }

        return $query;
    }

    private function employeeRowForView(object $row): object
    {
        $row->fecha_ingreso = $row->fecha_ingreso ? Carbon::parse($row->fecha_ingreso) : null;
        $row->persona = (object) [
            'id_persona' => $row->id_persona,
            'nombres' => $row->nombres,
            'apellidos' => $row->apellidos,
            'nombre_completo' => $row->nombre_completo ?: trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
            'cedula_identidad' => $row->cedula_identidad,
            'telefono' => $row->telefono,
            'email' => $row->persona_email,
            'foto_path' => $row->foto_path,
            'foto_url' => $this->photoUrl($row->id_persona, $row->foto_path),
        ];
        $row->rol = $row->id_rol ? (object) [
            'id_rol' => $row->id_rol,
            'nombre' => $row->rol_nombre,
        ] : null;
        $row->user = $row->user_id ? (object) [
            'id' => $row->user_id,
            'id_persona' => $row->id_persona,
            'username' => $row->username,
            'email' => $row->user_email,
            'name' => $row->user_name,
        ] : null;

        return $row;
    }

    private function photoUrl(?int $personaId, ?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'uploads/')) {
            return asset($path);
        }

        return $personaId ? route('private-media.persona-photo', $personaId) : null;
    }

    public function create()
    {
        return view('empleados.create', [
            'roles' => EmployeeRole::cachedOrderedList(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $data = $this->validateEmpleado($request);
        $passwordTemporal = $this->generateTemporaryPassword();

        $resultado = DB::connection()->getDriverName() === 'pgsql'
            ? $this->createEmployeeAccount($data, $request, $passwordTemporal)
            : DB::transaction(fn () => $this->createEmployeeAccount($data, $request, $passwordTemporal));
        $this->rememberEmployeeIdentity($data, (int) $resultado['persona_id'], (int) $resultado['user_id']);

        $this->sendEmployeeWelcomeAfterResponse(
            (int) $resultado['user_id'],
            $resultado['passwordTemporal']
        );

        $this->flushEmployeeCaches((int) $resultado['empleado_id'], forgetIdentity: false);

        return redirect()
            ->route('admin.empleados.show', $resultado['empleado_id'])
            ->with('success', 'Empleado registrado correctamente. Se creo su acceso al sistema y se notifico por SMS y correo cuando fue posible.')
            ->with('sms_preview', app()->isLocal() ? $resultado['passwordTemporal'] : null);
    }

    public function show(Empleado $empleado)
    {
        $empleado = OperationalCache::remember(
            "empleado:detail:{$empleado->id_empleado}",
            fn () => Empleado::query()
                ->with(['persona', 'rol', 'user'])
                ->withCount(['cobros', 'lecturas', 'medidoresInstalados'])
                ->findOrFail($empleado->id_empleado),
            now()->addHours(6)
        );

        return view('empleados.show', [
            'empleado' => $empleado,
        ]);
    }

    public function edit(Empleado $empleado)
    {
        $empleado->load(['persona', 'user']);

        return view('empleados.edit', [
            'empleado' => $empleado,
            'roles' => EmployeeRole::cachedOrderedList(),
        ]);
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $empleado->load(['persona', 'rol', 'user']);
        $data = $this->validateEmpleado($request, $empleado);

        DB::transaction(function () use ($data, $request, $empleado) {
            $email = Str::lower($data['email']);
            $username = Str::lower($data['username']);
            $fotoPath = $this->storeEmployeePhoto($request, $empleado->persona->foto_path);

            $empleado->persona->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $email,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'foto_path' => $fotoPath,
            ]);

            $empleado->update([
                'fecha_ingreso' => $data['fecha_ingreso'],
                'estado' => $data['estado'],
                'id_rol' => $data['id_rol'],
                'salario_base' => $data['salario_base'],
                'bono_mensual' => $data['bono_mensual'],
                'aguinaldo_anual' => $data['aguinaldo_anual'],
            ]);

            $user = $empleado->user ?: User::create([
                'name' => $empleado->persona->nombre_completo,
                'username' => $username,
                'email' => $email,
                'id_persona' => $empleado->persona->id_persona,
                'password' => $this->generateTemporaryPassword(),
                'must_change_password' => true,
            ]);

            $user->update([
                'name' => $empleado->persona->fresh()->nombre_completo,
                'username' => $username,
                'email' => $email,
                'id_persona' => $empleado->persona->id_persona,
            ]);

            $this->syncUserRole($user->fresh(), EmployeeRole::cachedOrderedList()->firstWhere('id_rol', (int) $data['id_rol']));
        });

        $this->flushEmployeeCaches((int) $empleado->id_empleado);

        return redirect()
            ->route('admin.empleados.show', $empleado)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    private function validateEmpleado(Request $request, ?Empleado $empleado = null): array
    {
        $roleIds = EmployeeRole::cachedOrderedList()
            ->pluck('id_rol')
            ->map(fn ($id) => (string) $id)
            ->all();

        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'cedula_identidad' => ['required', 'string', 'max:30'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'username' => ['required', 'string', 'min:4', 'max:50'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fecha_ingreso' => ['required', 'date'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'suspendido'])],
            'id_rol' => ['required', Rule::in($roleIds)],
            'salario_base' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'bono_mensual' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'aguinaldo_anual' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'email.unique' => 'El correo ya esta registrado por otro usuario o persona.',
        ]);

        $data['salario_base'] = round((float) ($data['salario_base'] ?? 0), 2);
        $data['bono_mensual'] = round((float) ($data['bono_mensual'] ?? 0), 2);
        $data['aguinaldo_anual'] = round((float) ($data['aguinaldo_anual'] ?? 0), 2);

        $this->validateEmployeeAvailability($data, $empleado);

        return $data;
    }

    private function validateEmployeeAvailability(array $data, ?Empleado $empleado = null): void
    {
        $personaId = $empleado?->id_persona;
        $userId = $empleado?->user?->id;
        $messages = [];
        $index = $this->employeeIdentityIndex();
        $email = Str::lower((string) $data['email']);
        $username = Str::lower((string) $data['username']);

        $cedulaOwner = $index['cedulas'][$data['cedula_identidad']] ?? null;
        if ($cedulaOwner && (int) $cedulaOwner !== (int) $personaId) {
            $messages['cedula_identidad'] = 'La cedula de identidad ya esta registrada.';
        }

        $personaEmailOwner = $index['persona_emails'][$email] ?? null;
        $userEmailOwner = $index['user_emails'][$email] ?? null;
        if (($personaEmailOwner && (int) $personaEmailOwner !== (int) $personaId)
            || ($userEmailOwner && (int) $userEmailOwner !== (int) $userId)) {
            $messages['email'] = 'El correo ya esta registrado por otro usuario o persona.';
        }

        $usernameOwner = $index['usernames'][$username] ?? null;
        if ($usernameOwner && (int) $usernameOwner !== (int) $userId) {
            $messages['username'] = 'El usuario ya esta registrado.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function employeeIdentityIndex(): array
    {
        return Cache::remember(self::IDENTITY_CACHE_KEY, now()->addDay(), function () {
            $rows = DB::table('personas')
                ->selectRaw("'cedula' as type, cedula_identidad as value, id_persona as owner_id")
                ->whereNotNull('cedula_identidad')
                ->unionAll(
                    DB::table('personas')
                        ->selectRaw("'persona_email' as type, LOWER(email) as value, id_persona as owner_id")
                        ->whereNotNull('email')
                )
                ->unionAll(
                    DB::table('users')
                        ->selectRaw("'user_email' as type, LOWER(email) as value, id as owner_id")
                        ->whereNotNull('email')
                )
                ->unionAll(
                    DB::table('users')
                        ->selectRaw("'username' as type, LOWER(username) as value, id as owner_id")
                        ->whereNotNull('username')
                )
                ->get();

            $index = [
                'cedulas' => [],
                'persona_emails' => [],
                'user_emails' => [],
                'usernames' => [],
            ];

            foreach ($rows as $row) {
                $bucket = match ($row->type) {
                    'cedula' => 'cedulas',
                    'persona_email' => 'persona_emails',
                    'user_email' => 'user_emails',
                    'username' => 'usernames',
                    default => null,
                };

                if ($bucket && $row->value !== null && $row->value !== '') {
                    $index[$bucket][(string) $row->value] = (int) $row->owner_id;
                }
            }

            return $index;
        });
    }

    private function rememberEmployeeIdentity(array $data, int $personaId, int $userId): void
    {
        $index = Cache::get(self::IDENTITY_CACHE_KEY);

        if (! is_array($index)) {
            return;
        }

        $index['cedulas'][(string) $data['cedula_identidad']] = $personaId;
        $index['persona_emails'][Str::lower((string) $data['email'])] = $personaId;
        $index['user_emails'][Str::lower((string) $data['email'])] = $userId;
        $index['usernames'][Str::lower((string) $data['username'])] = $userId;

        Cache::put(self::IDENTITY_CACHE_KEY, $index, now()->addDay());
    }

    public function storeLaborExpense(Request $request, Empleado $empleado): RedirectResponse
    {
        $data = $request->validate([
            'tipo_egreso' => ['required', Rule::in(array_keys($this->laborExpenseTypes()))],
            'fecha_gasto' => ['nullable', 'date'],
            'monto' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        $empleado->loadMissing(['persona', 'rol']);
        $config = $this->laborExpenseTypes()[$data['tipo_egreso']];
        $defaultAmount = round((float) $empleado->{$config['field']}, 2);
        $amount = round((float) ($data['monto'] ?? $defaultAmount), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'monto' => 'Configura un monto mayor a cero antes de registrar este egreso laboral.',
            ]);
        }

        $roleName = ucfirst((string) ($empleado->rol?->nombre ?? 'Sin rol'));
        $employeeName = $empleado->persona?->nombre_completo ?? 'Empleado';
        $registeredBy = Auth::user()?->name ?: 'Sistema';
        $extraDescription = trim((string) ($data['descripcion'] ?? ''));

        Gasto::create([
            'fecha_gasto' => $data['fecha_gasto'] ?? now()->toDateString(),
            'concepto' => $config['label'].' - '.$employeeName,
            'categoria' => $config['category'],
            'descripcion' => trim('Rol: '.$roleName.'. Registrado por: '.$registeredBy.'. '.$extraDescription),
            'monto' => $amount,
            'id_empleado' => $empleado->id_empleado,
        ]);

        Cache::forget('dashboard:recent-operational-expenses');
        Cache::add('gastos:index:version', 1, now()->addYears(2));
        Cache::increment('gastos:index:version');
        OperationalCache::forget('dashboard:recent-operational-expenses');

        return redirect()
            ->route('admin.empleados.show', $empleado)
            ->with('success', $config['label'].' registrado como egreso correctamente.');
    }

    private function createEmployeeAccount(array $data, Request $request, string $passwordTemporal): array
    {
        $email = Str::lower($data['email']);
        $username = Str::lower($data['username']);
        $role = EmployeeRole::cachedOrderedList()->firstWhere('id_rol', (int) $data['id_rol']);
        $roleId = $this->userRoleId($role);

        if (! $roleId) {
            throw new \RuntimeException('No se encontro el rol de acceso para este empleado.');
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $now = now()->toDateTimeString();
            $row = DB::selectOne(<<<'SQL'
WITH persona_row AS (
    INSERT INTO personas (
        nombres, apellidos, cedula_identidad, telefono, email, fecha_nacimiento, foto_path, created_at, updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    RETURNING id_persona
),
empleado_row AS (
    INSERT INTO empleados (
        fecha_ingreso, estado, id_persona, id_rol, salario_base, bono_mensual, aguinaldo_anual, created_at, updated_at
    )
    SELECT ?, ?, id_persona, ?, ?, ?, ?, ?, ? FROM persona_row
    RETURNING id_empleado
),
user_row AS (
    INSERT INTO users (name, username, email, id_persona, password, must_change_password, created_at, updated_at)
    SELECT ?, ?, ?, id_persona, ?, true, ?, ? FROM persona_row
    RETURNING id
),
role_row AS (
    INSERT INTO role_user (user_id, user_roles_id)
    SELECT id, ? FROM user_row
    RETURNING user_id
)
SELECT
    (SELECT id_persona FROM persona_row) AS id_persona,
    (SELECT id_empleado FROM empleado_row) AS id_empleado,
    (SELECT id FROM user_row) AS user_id,
    (SELECT COUNT(*) FROM role_row) AS roles_inserted
SQL, [
                $data['nombres'],
                $data['apellidos'],
                $data['cedula_identidad'],
                $data['telefono'],
                $email,
                $data['fecha_nacimiento'] ?? null,
                $this->storeEmployeePhoto($request),
                $now,
                $now,
                $data['fecha_ingreso'],
                $data['estado'],
                $data['id_rol'],
                $data['salario_base'],
                $data['bono_mensual'],
                $data['aguinaldo_anual'],
                $now,
                $now,
                trim($data['nombres'].' '.$data['apellidos']),
                $username,
                $email,
                Hash::make($passwordTemporal),
                $now,
                $now,
                $roleId,
            ]);

            return [
                'persona_id' => (int) $row->id_persona,
                'empleado_id' => (int) $row->id_empleado,
                'user_id' => (int) $row->user_id,
                'passwordTemporal' => $passwordTemporal,
            ];
        }

        $persona = Persona::create([
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'cedula_identidad' => $data['cedula_identidad'],
            'telefono' => $data['telefono'],
            'email' => $email,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'foto_path' => $this->storeEmployeePhoto($request),
        ]);

        $empleado = Empleado::create([
            'fecha_ingreso' => $data['fecha_ingreso'],
            'estado' => $data['estado'],
            'id_persona' => $persona->id_persona,
            'id_rol' => $data['id_rol'],
            'salario_base' => $data['salario_base'],
            'bono_mensual' => $data['bono_mensual'],
            'aguinaldo_anual' => $data['aguinaldo_anual'],
        ]);

        $user = User::create([
            'name' => $persona->nombre_completo,
            'username' => $username,
            'email' => $email,
            'id_persona' => $persona->id_persona,
            'password' => $passwordTemporal,
            'must_change_password' => true,
        ]);

        $this->syncUserRole($user, $role, replaceExisting: false);

        return [
            'persona_id' => (int) $persona->id_persona,
            'empleado_id' => (int) $empleado->id_empleado,
            'user_id' => (int) $user->id,
            'passwordTemporal' => $passwordTemporal,
        ];
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(Str::random(4)) . random_int(1000, 9999);
    }

    private function storeEmployeePhoto(Request $request, ?string $currentPath = null): ?string
    {
        if (!$request->hasFile('foto')) {
            return $currentPath;
        }

        $file = $request->file('foto');
        if (!$file->isValid()) {
            return $currentPath;
        }

        return PrivateMedia::storeImage($file, 'empleados', 'empleado', $currentPath);
    }

    private function syncUserRole(User $user, ?EmployeeRole $rolEmpleado, bool $replaceExisting = true): void
    {
        $roleId = $this->userRoleId($rolEmpleado);

        if (!$roleId) {
            return;
        }

        if ($replaceExisting) {
            DB::table('role_user')->where('user_id', $user->id)->delete();
        }

        DB::table('role_user')->insert([
            'user_id' => $user->id,
            'user_roles_id' => $roleId,
        ]);
        Cache::forget("user:{$user->getKey()}:role-names");
    }

    private function userRoleId(?EmployeeRole $rolEmpleado): ?int
    {
        if (!$rolEmpleado) {
            return null;
        }

        $aliases = [
            'admin' => 'administrador',
            'administrador' => 'administrador',
            'secretaria' => 'secretaria',
            'tecnico' => 'tecnico',
        ];

        $roleName = Str::lower(trim((string) $rolEmpleado->nombre));
        $lookup = $aliases[$roleName] ?? $roleName;

        return Cache::remember("user-role-id:{$lookup}", now()->addDay(), fn () => AccessRole::query()
            ->whereRaw('lower(name) = ?', [$lookup])
            ->value('id'));
    }

    private function employeeTotals(): array
    {
        return OperationalCache::rememberDomain('operations', 'empleados.index.totales', function () {
            $summary = Empleado::query()
                ->leftJoin('roles', 'roles.id_rol', '=', 'empleados.id_rol')
                ->selectRaw("
                    COUNT(*) FILTER (WHERE empleados.estado = 'activo') as activos,
                    COUNT(*) FILTER (WHERE empleados.estado = 'inactivo') as inactivos,
                    COUNT(*) FILTER (WHERE LOWER(COALESCE(roles.nombre, '')) = 'tecnico') as tecnicos
                ")
                ->first();

            return [
                'activos' => (int) ($summary?->activos ?? 0),
                'inactivos' => (int) ($summary?->inactivos ?? 0),
                'tecnicos' => (int) ($summary?->tecnicos ?? 0),
            ];
        });
    }

    private function laborExpenseTypes(): array
    {
        return [
            'salario' => [
                'field' => 'salario_base',
                'label' => 'Pago de salario',
                'category' => 'Pago de salarios',
            ],
            'bono' => [
                'field' => 'bono_mensual',
                'label' => 'Bono laboral',
                'category' => 'Bonos laborales',
            ],
            'aguinaldo' => [
                'field' => 'aguinaldo_anual',
                'label' => 'Aguinaldo',
                'category' => 'Aguinaldos',
            ],
        ];
    }

    private function sendEmployeeWelcomeAfterResponse(int $userId, string $temporaryPassword): void
    {
        $connection = $this->credentialQueueConnection();

        if ($connection) {
            SendCredentialNotification::dispatch($userId, 'employee_welcome', $temporaryPassword)
                ->onConnection($connection);

            return;
        }

        app()->terminating(function () use ($userId, $temporaryPassword) {
            try {
                $user = User::query()->with('persona')->find($userId);

                if ($user) {
                    $this->credentialNotifications->sendEmployeeWelcome($user, $temporaryPassword);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    private function credentialQueueConnection(): ?string
    {
        $default = (string) config('queue.default', 'sync');

        if ($default !== 'sync') {
            return $default;
        }

        return Cache::remember('queue.jobs-table-available', now()->addMinutes(30), fn () => Schema::hasTable('jobs'))
            ? 'database'
            : null;
    }

    private function flushEmployeeCaches(?int $empleadoId = null, bool $forgetIdentity = true): void
    {
        OperationalCache::bumpDomain('operations');
        Cache::forget('empleados:index:totales');
        if ($forgetIdentity) {
            Cache::forget(self::IDENTITY_CACHE_KEY);
        }

        if ($empleadoId) {
            OperationalCache::forget("empleado:detail:{$empleadoId}");
            OperationalCache::forget("route-binding:App.Models.Empleado:id_empleado:{$empleadoId}");
        }
    }
}
