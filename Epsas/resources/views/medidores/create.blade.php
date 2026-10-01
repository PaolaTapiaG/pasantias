@extends('layouts.app')

@section('title', 'Registrar medidor - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
    $mapLat = is_numeric(old('latitud')) ? (float) old('latitud') : -21.5355;
    $mapLng = is_numeric(old('longitud')) ? (float) old('longitud') : -64.7296;
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Medidores',
            'headerTitle' => 'Registrar nuevo medidor',
        ])

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de medidor</p>
                        <p class="mt-1 text-sm text-slate-500">Vuelve al listado sin cargar acciones en el header.</p>
                    </div>
                    <a href="{{ route('tecnico.medidores.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Volver</a>
                </div>
            </section>

            <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="theme-muted mb-6 text-sm text-slate-500">Solo aparecen socios sin medidor activo para evitar duplicidad con el registro inicial del socio.</p>
                <div class="mb-6 rounded-[1.6rem] border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-900">
                    Usa este registro cuando el socio reciba un medidor nuevo. Si viene por reemplazo, el medidor anterior debe quedar como <span class="font-semibold">Reemplazado</span> y la nueva lectura empezara desde <span class="font-semibold">0</span> o desde la lectura inicial del equipo.
                </div>
                <form method="POST" action="{{ route('tecnico.medidores.store') }}" class="grid gap-5">
                    @csrf
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium">Numero de serie</label>
                            <input name="numero_serie" value="{{ old('numero_serie', $nextNumeroMedidor ?? null) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            <p class="mt-2 text-xs text-slate-500">Numero correlativo sugerido automaticamente.</p>
                        </div>
                        <div><label class="mb-2 block text-sm font-medium">Estado</label><select name="estado" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="danado">Danado</option><option value="reemplazado">Reemplazado</option></select></div>
                        <div><label class="mb-2 block text-sm font-medium">Marca</label><input name="marca" value="{{ old('marca') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Modelo</label><input name="modelo" value="{{ old('modelo') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Fecha de instalacion</label><input type="date" name="fecha_instalacion" value="{{ old('fecha_instalacion', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Tecnico instalador</label><select name="id_empleado_instalador" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"><option value="">Sin asignar</option>@foreach ($tecnicos as $tecnico)<option value="{{ $tecnico->id_empleado }}" @selected(old('id_empleado_instalador') == $tecnico->id_empleado)>{{ $tecnico->persona?->nombre_completo ?? ('Tecnico #' . $tecnico->id_empleado) }}</option>@endforeach</select></div>
                        <div>
                            <label for="medidor-latitud" class="mb-2 block text-sm font-medium">Latitud</label>
                            <input id="medidor-latitud" name="latitud" value="{{ old('latitud') }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                        </div>
                        <div>
                            <label for="medidor-longitud" class="mb-2 block text-sm font-medium">Longitud</label>
                            <input id="medidor-longitud" name="longitud" value="{{ old('longitud') }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Socio asociado</label>
                        <select id="medidor-socio" name="id_socio" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            <option value="">Selecciona un socio</option>
                            @foreach ($sociosDisponibles as $socio)
                                <option value="{{ $socio->id_socio }}" data-lat="{{ $socio->latitud }}" data-lng="{{ $socio->longitud }}" @selected(old('id_socio') == $socio->id_socio)>{{ $socio->codigo_display }} - {{ $socio->persona?->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-[1.75rem] border border-slate-200 p-3">
                        <x-geo-map
                            id="medidor-map"
                            :lat="$mapLat"
                            :lng="$mapLng"
                            :zoom="16"
                            height="320px"
                            :picker="true"
                            lat-input="#medidor-latitud"
                            lng-input="#medidor-longitud"
                        />
                        <p class="mt-3 text-xs font-semibold text-slate-500">Selecciona el socio para proponer su ubicacion o marca el punto exacto del medidor.</p>
                    </div>
                    <button type="submit" class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Guardar medidor</button>
                </form>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const socioSelect = document.getElementById('medidor-socio');
        const latInput = document.getElementById('medidor-latitud');
        const lngInput = document.getElementById('medidor-longitud');
        const map = document.getElementById('medidor-map');

        const syncLocation = () => {
            const selected = socioSelect?.selectedOptions?.[0];
            if (!selected?.dataset.lat || !selected?.dataset.lng || !latInput || !lngInput) return;
            if (!latInput.value) latInput.value = selected.dataset.lat;
            if (!lngInput.value) lngInput.value = selected.dataset.lng;
            map?.dispatchEvent(new CustomEvent('epsas:geo:set', {
                detail: {
                    lat: latInput.value,
                    lng: lngInput.value,
                },
            }));
        };

        socioSelect?.addEventListener('change', syncLocation);
        syncLocation();
    })();
</script>
@endpush
