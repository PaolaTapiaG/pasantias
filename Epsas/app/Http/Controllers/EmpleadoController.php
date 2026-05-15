<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::with(['persona', 'rol', 'user'])
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id_empleado');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->whereHas('persona', function ($persona) use ($term) {
                    $persona->where('nombres', 'ilike', "%{$term}%")
                        ->orWhere('apellidos', 'ilike', "%{$term}%")
                        ->orWhere('cedula_identidad', 'ilike', "%{$term}%")
                        ->orWhere('email', 'ilike', "%{$term}%");
                })->orWhereHas('rol', fn ($rol) => $rol->where('nombre', 'ilike', "%{$term}%"));
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('rol')) {
            $query->where('id_rol', $request->rol);
        }

        return view('empleados.index', [
            'empleados' => $query->paginate(12)->withQueryString(),
            'roles' => Rol::orderBy('nombre')->get(),
            'totales' => [
                'activos' => Empleado::where('estado', 'activo')->count(),
                'inactivos' => Empleado::where('estado', 'inactivo')->count(),
                'tecnicos' => Empleado::whereHas('rol', fn ($rol) => $rol->where('nombre', 'tecnico'))->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('empleados.create', [
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEmpleado($request);
        $passwordTemporal = $this->generateTemporaryPassword();

        $resultado = DB::transaction(function () use ($data, $request, $passwordTemporal) {
            $persona = Persona::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'foto_path' => $this->storeEmployeePhoto($request),
            ]);

            $empleado = Empleado::create([
                'fecha_ingreso' => $data['fecha_ingreso'],
                'estado' => $data['estado'],
                'id_persona' => $persona->id_persona,
                'id_rol' => $data['id_rol'],
            ]);

            $user = User::create([
                'name' => $persona->nombre_completo,
                'email' => $persona->email,
                'id_persona' => $persona->id_persona,
                'password' => Hash::make($passwordTemporal),
                'must_change_password' => true,
            ]);

            $this->syncUserRole($user, $empleado->rol);
            $this->dispatchSmsPassword($persona, $passwordTemporal);

            return compact('empleado', 'passwordTemporal');
        });

        return redirect()
            ->route('admin.empleados.show', $resultado['empleado'])
            ->with('success', 'Empleado registrado correctamente. Se creo su acceso al sistema.')
            ->with('sms_preview', app()->isLocal() ? $resultado['passwordTemporal'] : null);
    }

    public function show(Empleado $empleado)
    {
        $empleado->load([
            'persona',
            'rol',
            'user',
            'cobros.factura',
            'lecturas.medidor',
            'medidoresInstalados.socio.persona',
        ]);

        return view('empleados.show', [
            'empleado' => $empleado,
        ]);
    }

    public function edit(Empleado $empleado)
    {
        $empleado->load(['persona', 'user']);

        return view('empleados.edit', [
            'empleado' => $empleado,
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Empleado $empleado)
    {
        $empleado->load(['persona', 'rol', 'user']);
        $data = $this->validateEmpleado($request, $empleado);

        DB::transaction(function () use ($data, $request, $empleado) {
            $fotoPath = $this->storeEmployeePhoto($request, $empleado->persona->foto_path);

            $empleado->persona->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'foto_path' => $fotoPath,
            ]);

            $empleado->update([
                'fecha_ingreso' => $data['fecha_ingreso'],
                'estado' => $data['estado'],
                'id_rol' => $data['id_rol'],
            ]);

            $user = $empleado->user ?: User::create([
                'name' => $empleado->persona->nombre_completo,
                'email' => $empleado->persona->email,
                'id_persona' => $empleado->persona->id_persona,
                'password' => Hash::make($this->generateTemporaryPassword()),
                'must_change_password' => true,
            ]);

            $user->update([
                'name' => $empleado->persona->fresh()->nombre_completo,
                'email' => $data['email'],
                'id_persona' => $empleado->persona->id_persona,
            ]);

            $this->syncUserRole($user->fresh(), $empleado->rol()->withDefault()->first());
        });

        return redirect()
            ->route('admin.empleados.show', $empleado)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    private function validateEmpleado(Request $request, ?Empleado $empleado = null): array
    {
        $personaId = $empleado?->id_persona;
        $userId = $empleado?->user?->id;

        return $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'cedula_identidad' => [
                'required',
                'string',
                'max:30',
                Rule::unique('personas', 'cedula_identidad')->ignore($personaId, 'id_persona'),
            ],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('personas', 'email')->ignore($personaId, 'id_persona'),
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'fecha_ingreso' => ['required', 'date'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'suspendido'])],
            'id_rol' => ['required', 'exists:roles,id_rol'],
        ], [
            'email.unique' => 'El correo ya esta registrado por otro usuario o persona.',
        ]);
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

        $directory = public_path('uploads/empleados');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('foto');
        $filename = 'empleado_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/empleados/' . $filename;
    }

    private function syncUserRole(User $user, ?Rol $rolEmpleado): void
    {
        if (!$rolEmpleado) {
            return;
        }

        $role = Role::where('name', $rolEmpleado->nombre)->first();
        if (!$role) {
            return;
        }

        $user->roles()->sync([$role->id]);
    }

    private function dispatchSmsPassword(Persona $persona, string $passwordTemporal): void
    {
        Log::info('[SMS EMPLEADO] Credenciales temporales', [
            'telefono' => $persona->telefono,
            'mensaje' => "Hola {$persona->nombre_completo}, tu contrasena temporal de EPSAS es {$passwordTemporal}.",
        ]);
    }
}
