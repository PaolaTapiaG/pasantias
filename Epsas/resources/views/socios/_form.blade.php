@php
    $editing = isset($socio);
    $persona = $editing ? $socio->persona : null;
    $medidor = $editing ? $socio->medidorActivo : null;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Datos personales</h3>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nombres" class="mb-2 block text-sm font-medium text-slate-700">Nombres</label>
                <input id="nombres" name="nombres" type="text" value="{{ old('nombres', $persona?->nombres) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('nombres') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="apellidos" class="mb-2 block text-sm font-medium text-slate-700">Apellidos</label>
                <input id="apellidos" name="apellidos" type="text" value="{{ old('apellidos', $persona?->apellidos) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('apellidos') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="cedula_identidad" class="mb-2 block text-sm font-medium text-slate-700">Cedula de identidad</label>
                <input id="cedula_identidad" name="cedula_identidad" type="text" value="{{ old('cedula_identidad', $persona?->cedula_identidad) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('cedula_identidad') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="telefono" class="mb-2 block text-sm font-medium text-slate-700">Telefono</label>
                <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $persona?->telefono) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" required>
                @error('telefono') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fecha_nacimiento" class="mb-2 block text-sm font-medium text-slate-700">Fecha de nacimiento</label>
                <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="{{ old('fecha_nacimiento', optional($persona?->fecha_nacimiento)->format('Y-m-d')) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" required>
                @error('fecha_nacimiento') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Correo electronico</label>
                <input id="email" name="email" type="email" value="{{ old('email', $persona?->email) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('email') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="direccion" class="mb-2 block text-sm font-medium text-slate-700">Direccion</label>
                <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $socio->direccion ?? null) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('direccion') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Relacion operativa</h3>
        <div class="mt-5 grid gap-4">
            <div>
                <label for="id_sector" class="mb-2 block text-sm font-medium text-slate-700">Zona / sector</label>
                <select id="id_sector" name="id_sector" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <option value="">Selecciona un sector</option>
                    @foreach ($sectores as $sector)
                        <option value="{{ $sector->id_sector }}" @selected(old('id_sector', $socio->id_sector ?? null) == $sector->id_sector)>
                            {{ $sector->nombre }} - {{ $sector->zona }}
                        </option>
                    @endforeach
                </select>
                @error('id_sector') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="id_tarifa" class="mb-2 block text-sm font-medium text-slate-700">Tarifa</label>
                <select id="id_tarifa" name="id_tarifa" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <option value="">Selecciona una tarifa</option>
                    @foreach ($tarifas as $tarifa)
                        <option value="{{ $tarifa->id_tarifa }}" @selected(old('id_tarifa', $socio->id_tarifa ?? null) == $tarifa->id_tarifa)>
                            {{ $tarifa->nombre }} - Bs {{ number_format((float) $tarifa->precio_m3_base, 2) }}/m3
                        </option>
                    @endforeach
                </select>
                @error('id_tarifa') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="estado" class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                <select id="estado" name="estado" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @foreach (['activo', 'inactivo', 'suspendido', 'cortado'] as $estado)
                        <option value="{{ $estado }}" @selected(old('estado', $socio->estado ?? 'activo') === $estado)>{{ ucfirst($estado) }}</option>
                    @endforeach
                </select>
                @error('estado') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-900">Medidor asociado</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="numero_serie" class="mb-2 block text-sm font-medium text-slate-700">Numero de medidor</label>
                        <input id="numero_serie" name="numero_serie" type="text" value="{{ old('numero_serie', $medidor?->numero_serie) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                        @error('numero_serie') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fecha_instalacion" class="mb-2 block text-sm font-medium text-slate-700">Fecha de instalacion</label>
                        <input id="fecha_instalacion" name="fecha_instalacion" type="date" value="{{ old('fecha_instalacion', optional($medidor?->fecha_instalacion)->format('Y-m-d')) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('fecha_instalacion') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
