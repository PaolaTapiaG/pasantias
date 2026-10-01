@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $editing = isset($socio);
    $persona = $editing ? $socio->persona : null;
    $medidor = $editing ? $socio->medidorActivo : null;
    $defaultLat = -21.5355;
    $defaultLng = -64.7296;
    $socioLat = old('latitud', $editing ? $socio->latitud : null);
    $socioLng = old('longitud', $editing ? $socio->longitud : null);
    $medidorLat = old('medidor_latitud', $medidor?->latitud ?? $socioLat);
    $medidorLng = old('medidor_longitud', $medidor?->longitud ?? $socioLng);
    $socioMapLat = is_numeric($socioLat) ? (float) $socioLat : $defaultLat;
    $socioMapLng = is_numeric($socioLng) ? (float) $socioLng : $defaultLng;
    $medidorMapLat = is_numeric($medidorLat) ? (float) $medidorLat : $socioMapLat;
    $medidorMapLng = is_numeric($medidorLng) ? (float) $medidorLng : $socioMapLng;
    $socioMarkers = is_numeric($socioLat) && is_numeric($socioLng) ? [[
        'type' => 'socio',
        'lat' => (float) $socioLat,
        'lng' => (float) $socioLng,
        'title' => 'Ubicacion del socio',
        'description' => old('direccion', $socio->direccion ?? 'Direccion del socio'),
        'category' => 'Socio',
    ]] : [];
    $medidorMarkers = is_numeric($medidorLat) && is_numeric($medidorLng) ? [[
        'type' => 'medidor',
        'lat' => (float) $medidorLat,
        'lng' => (float) $medidorLng,
        'title' => 'Ubicacion del medidor',
        'description' => old('numero_serie', $medidor?->numero_serie ?? ($nextNumeroMedidor ?? 'Medidor')),
        'category' => 'Medidor',
    ]] : [];
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
                <label for="foto" class="mb-2 block text-sm font-medium text-slate-700">Foto para carnet</label>
                <input id="foto" name="foto" type="file" accept="image/*" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('foto') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                @if ($persona?->foto_url)
                    <img src="{{ $persona->foto_url }}" alt="Foto actual del socio" class="mt-3 h-24 w-24 rounded-2xl object-cover ring-1 ring-slate-200">
                @endif
            </div>
            <div class="sm:col-span-2">
                <label for="direccion" class="mb-2 block text-sm font-medium text-slate-700">Direccion</label>
                <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $socio->direccion ?? null) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('direccion') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="socio-latitud" class="mb-2 block text-sm font-medium text-slate-700">Latitud del socio</label>
                        <input id="socio-latitud" name="latitud" value="{{ $socioLat }}" inputmode="decimal" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('latitud') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="socio-longitud" class="mb-2 block text-sm font-medium text-slate-700">Longitud del socio</label>
                        <input id="socio-longitud" name="longitud" value="{{ $socioLng }}" inputmode="decimal" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('longitud') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <x-geo-map
                        id="socio-location-map"
                        :lat="$socioMapLat"
                        :lng="$socioMapLng"
                        :zoom="15"
                        height="280px"
                        :picker="true"
                        lat-input="#socio-latitud"
                        lng-input="#socio-longitud"
                        :markers="$socioMarkers"
                    />
                </div>
                <p class="mt-3 text-xs font-semibold text-slate-500">Haz clic o arrastra el marcador para guardar la ubicacion exacta del socio.</p>
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
                        <input id="numero_serie" name="numero_serie" type="text" value="{{ old('numero_serie', $medidor?->numero_serie ?? ($nextNumeroMedidor ?? null)) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                        @unless($editing)
                            <p class="mt-2 text-xs text-slate-500">Numero sugerido automaticamente. Puedes cambiarlo solo si corresponde.</p>
                        @endunless
                        @error('numero_serie') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fecha_instalacion" class="mb-2 block text-sm font-medium text-slate-700">Fecha de instalacion</label>
                        <input id="fecha_instalacion" name="fecha_instalacion" type="date" value="{{ old('fecha_instalacion', optional($medidor?->fecha_instalacion)->format('Y-m-d')) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('fecha_instalacion') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="medidor-latitud" class="mb-2 block text-sm font-medium text-slate-700">Latitud del medidor</label>
                        <input id="medidor-latitud" name="medidor_latitud" value="{{ $medidorLat }}" inputmode="decimal" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('medidor_latitud') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="medidor-longitud" class="mb-2 block text-sm font-medium text-slate-700">Longitud del medidor</label>
                        <input id="medidor-longitud" name="medidor_longitud" value="{{ $medidorLng }}" inputmode="decimal" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        @error('medidor_longitud') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-geo-map
                            id="medidor-location-map"
                            :lat="$medidorMapLat"
                            :lng="$medidorMapLng"
                            :zoom="16"
                            height="260px"
                            :picker="true"
                            lat-input="#medidor-latitud"
                            lng-input="#medidor-longitud"
                            :markers="$medidorMarkers"
                        />
                        <button type="button" data-copy-socio-location class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 sm:w-auto">
                            Usar ubicacion del socio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
    (() => {
        const socioLat = document.getElementById('socio-latitud');
        const socioLng = document.getElementById('socio-longitud');
        const medidorLat = document.getElementById('medidor-latitud');
        const medidorLng = document.getElementById('medidor-longitud');
        const medidorMap = document.getElementById('medidor-location-map');

        document.querySelector('[data-copy-socio-location]')?.addEventListener('click', () => {
            if (!socioLat?.value || !socioLng?.value || !medidorLat || !medidorLng) return;

            medidorLat.value = socioLat.value;
            medidorLng.value = socioLng.value;
            medidorMap?.dispatchEvent(new CustomEvent('epsas:geo:set', {
                detail: {
                    lat: socioLat.value,
                    lng: socioLng.value,
                },
            }));
        });
    })();
</script>
@endpush
