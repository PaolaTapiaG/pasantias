<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            El sistema creara automaticamente el usuario de acceso del empleado con este correo y enviara una contrasena temporal por SMS al telefono registrado.
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Nombres</label>
                <input name="nombres" value="{{ old('nombres', $empleado?->persona?->nombres) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('nombres') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Apellidos</label>
                <input name="apellidos" value="{{ old('apellidos', $empleado?->persona?->apellidos) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('apellidos') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Cedula de identidad</label>
                <input name="cedula_identidad" value="{{ old('cedula_identidad', $empleado?->persona?->cedula_identidad) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('cedula_identidad') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Telefono</label>
                <input name="telefono" value="{{ old('telefono', $empleado?->persona?->telefono) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('telefono') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Correo</label>
                <input name="email" type="email" value="{{ old('email', $empleado?->persona?->email) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('email') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Foto</label>
                <input name="foto" type="file" accept="image/*" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('foto') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                @if ($empleado?->persona?->foto_url)
                    <img src="{{ $empleado->persona->foto_url }}" alt="Foto actual" class="mt-3 h-20 w-20 rounded-2xl object-cover ring-1 ring-slate-200">
                @endif
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha de nacimiento</label>
                <input name="fecha_nacimiento" type="date" value="{{ old('fecha_nacimiento', optional($empleado?->persona?->fecha_nacimiento)->format('Y-m-d')) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('fecha_nacimiento') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Rol</label>
                <select name="id_rol" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id_rol }}" @selected((string) old('id_rol', $empleado?->id_rol) === (string) $rol->id_rol)>{{ ucfirst($rol->nombre) }}</option>
                    @endforeach
                </select>
                @error('id_rol') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha de ingreso</label>
                <input name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', optional($empleado?->fecha_ingreso)->format('Y-m-d') ?? now()->toDateString()) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('fecha_ingreso') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Estado</label>
                <select name="estado" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('estado', $empleado?->estado ?? 'activo') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                {{ $submitLabel }}
            </button>
        </div>
    </form>
</section>
