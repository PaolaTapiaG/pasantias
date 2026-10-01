@extends('layouts.app')

@section('title', 'Editar medidor - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
    $mapLat = is_numeric(old('latitud', $medidor->latitud)) ? (float) old('latitud', $medidor->latitud) : -21.5355;
    $mapLng = is_numeric(old('longitud', $medidor->longitud)) ? (float) old('longitud', $medidor->longitud) : -64.7296;
    $mapMarkers = is_numeric(old('latitud', $medidor->latitud)) && is_numeric(old('longitud', $medidor->longitud)) ? [[
        'type' => 'medidor',
        'lat' => $mapLat,
        'lng' => $mapLng,
        'title' => 'Medidor '.$medidor->numero_serie,
        'description' => $medidor->socio?->persona?->nombre_completo,
        'category' => 'Medidor',
    ]] : [];
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Medidores',
            'headerTitle' => 'Editar medidor ' . $medidor->numero_serie,
        ])

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de medidor</p>
                        <p class="mt-1 text-sm text-slate-500">La navegacion queda en el contenido para mantener limpio el header.</p>
                    </div>
                    <a href="{{ route('tecnico.medidores.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Volver</a>
                </div>
            </section>

            <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 grid gap-4 md:grid-cols-2">
                    <div class="theme-soft rounded-2xl border p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Socio vinculado</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $medidor->socio?->persona?->nombre_completo }}</p>
                        <p class="mt-1 text-xs text-blue-700">{{ $medidor->socio?->codigo_display }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl border p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tecnico actual</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $medidor->empleadoInstalador?->persona?->nombre_completo ?? 'No asignado' }}</p>
                    </div>
                </div>

                <div class="mb-6 rounded-[1.6rem] border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-900">
                    Si el equipo fue reemplazado, marca este medidor como <span class="font-semibold">Reemplazado</span> y registra un nuevo medidor activo para el mismo socio.
                    La siguiente lectura debe comenzar desde <span class="font-semibold">0</span> o desde la lectura inicial del nuevo equipo.
                </div>

                <form method="POST" action="{{ route('tecnico.medidores.update', $medidor) }}" class="grid gap-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-5 md:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-medium">Numero de serie</label><input name="numero_serie" value="{{ old('numero_serie', $medidor->numero_serie) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Estado</label><select name="estado" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"><option value="activo" @selected(old('estado', $medidor->estado) === 'activo')>Activo</option><option value="inactivo" @selected(old('estado', $medidor->estado) === 'inactivo')>Inactivo</option><option value="danado" @selected(old('estado', $medidor->estado) === 'danado')>Danado</option><option value="reemplazado" @selected(old('estado', $medidor->estado) === 'reemplazado')>Reemplazado</option></select></div>
                        <div><label class="mb-2 block text-sm font-medium">Marca</label><input name="marca" value="{{ old('marca', $medidor->marca) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Modelo</label><input name="modelo" value="{{ old('modelo', $medidor->modelo) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Fecha de instalacion</label><input type="date" name="fecha_instalacion" value="{{ old('fecha_instalacion', optional($medidor->fecha_instalacion)->format('Y-m-d')) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Tecnico instalador</label><select name="id_empleado_instalador" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"><option value="">Sin asignar</option>@foreach ($tecnicos as $tecnico)<option value="{{ $tecnico->id_empleado }}" @selected((string) old('id_empleado_instalador', $medidor->id_empleado_instalador) === (string) $tecnico->id_empleado)>{{ $tecnico->persona?->nombre_completo ?? ('Tecnico #' . $tecnico->id_empleado) }}</option>@endforeach</select></div>
                        <div><label for="medidor-latitud" class="mb-2 block text-sm font-medium">Latitud</label><input id="medidor-latitud" name="latitud" value="{{ old('latitud', $medidor->latitud) }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label for="medidor-longitud" class="mb-2 block text-sm font-medium">Longitud</label><input id="medidor-longitud" name="longitud" value="{{ old('longitud', $medidor->longitud) }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
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
                            :markers="$mapMarkers"
                        />
                        <p class="mt-3 text-xs font-semibold text-slate-500">Ajusta el marcador si el medidor cambio de ubicacion.</p>
                    </div>
                    <input type="hidden" name="id_socio" value="{{ $medidor->id_socio }}">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700">Actualizar medidor</button>
                </form>
            </section>
        </main>
    </div>
</div>
@endsection
