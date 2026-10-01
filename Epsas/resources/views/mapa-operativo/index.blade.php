@extends('layouts.app')

@section('title', 'Mapa operativo - EPSAS')

@section('content')
@php
    $roles = auth()->user()?->cachedRoleNames() ?? collect();
    $isAdmin = $roles->contains('administrador');
    $isSecretary = ! $isAdmin && $roles->contains('secretaria');
    $sidebar = $isAdmin ? 'slideboard.sidebaradmin' : ($isSecretary ? 'slideboard.sidebarsec' : 'slideboard.sidebartec');
    $mainAttr = $isAdmin ? 'data-admin-main' : ($isSecretary ? 'data-sidebar-main' : 'data-tech-main');
    $company = $company ?? [];
    $officeLabel = old('map_label', $company['map_label'] ?? ($company['company_name'] ?? 'Oficina central EPSAS'));
    $officeAddress = old('address', $company['address'] ?? 'Oficina principal');
    $officeLat = (float) old('gps_latitude', $center['lat'] ?? -21.5355);
    $officeLng = (float) old('gps_longitude', $center['lng'] ?? -64.7296);
    $legend = [
        ['label' => 'Oficina', 'type' => 'oficina', 'color' => 'bg-blue-600'],
        ['label' => 'Socios', 'type' => 'socio', 'color' => 'bg-green-600'],
        ['label' => 'Medidores', 'type' => 'medidor', 'color' => 'bg-cyan-600'],
        ['label' => 'Instalaciones', 'type' => 'instalacion', 'color' => 'bg-orange-500'],
        ['label' => 'Incidencias', 'type' => 'incidencia', 'color' => 'bg-red-600'],
        ['label' => 'Lecturas', 'type' => 'lectura', 'color' => 'bg-violet-600'],
        ['label' => 'Cortes', 'type' => 'corte', 'color' => 'bg-slate-900'],
        ['label' => 'Reconexiones', 'type' => 'reconexion', 'color' => 'bg-emerald-600'],
    ];
@endphp

<div class="page-background min-h-screen">
    @include($sidebar)

    <div {{ $mainAttr }} class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Mapa operativo',
            'headerTitle' => 'Geolocalizacion del sistema',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($warning)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-sm">
                    {{ $warning }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Operacion territorial</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">Mapa interactivo con OpenStreetMap</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Visualiza oficina, socios, medidores, instalaciones, incidencias, lecturas, cortes y reconexiones desde un solo punto de control.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                        @foreach ($legend as $item)
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                                <span class="h-2.5 w-2.5 rounded-full {{ $item['color'] }}"></span>
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                @forelse ($stats as $label => $value)
                    <article class="rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $value }}</p>
                    </article>
                @empty
                    <article class="col-span-full rounded-[1.35rem] border border-slate-200 bg-white p-4 text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 dark:text-slate-400">
                        Aun no hay puntos GPS registrados.
                    </article>
                @endforelse
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,.65fr)]">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <x-geo-map
                        id="operational-map"
                        :lat="$center['lat']"
                        :lng="$center['lng']"
                        :zoom="13"
                        height="min(72vh, 680px)"
                        :markers="$markers"
                        empty-text="Aun no hay coordenadas cargadas."
                    />
                </article>

                <aside class="grid gap-5">
                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70"
                        data-geo-route-panel
                        data-map-id="operational-map"
                        data-origin-lat="{{ number_format($officeLat, 7, '.', '') }}"
                        data-origin-lng="{{ number_format($officeLng, 7, '.', '') }}"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Rutas desde oficina</p>
                        <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-slate-100">Trazar recorrido</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Elige un socio, medidor, incidencia u orden tecnica con GPS para dibujar la ruta desde la oficina.
                        </p>

                        <label for="route-target" class="mt-4 block text-sm font-bold text-slate-800 dark:text-slate-100">Destino</label>
                        <select id="route-target" data-route-target class="theme-soft mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none">
                            <option>Cargando destinos...</option>
                        </select>

                        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            <button type="button" data-route-draw class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                                Trazar ruta
                            </button>
                            <button type="button" data-route-clear class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">
                                Limpiar ruta
                            </button>
                        </div>

                        <a data-route-open target="_blank" rel="noopener" class="mt-3 inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-100">
                            Abrir ruta externa
                        </a>

                        <p data-route-status class="geo-route-status mt-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Preparando destinos...
                        </p>
                    </section>

                    @if ($isAdmin)
                        <form method="POST" action="{{ route('mapa-operativo.office.update') }}" class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                            @csrf
                            @method('PATCH')

                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">Oficina principal</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-slate-100">Actualizar ubicacion</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Haz clic en el mapa o arrastra el marcador. La direccion guardada se mostrara en el portal ciudadano.
                            </p>

                            <label for="office-map-label" class="mt-4 block text-sm font-bold text-slate-800 dark:text-slate-100">Nombre en el mapa</label>
                            <input id="office-map-label" name="map_label" value="{{ $officeLabel }}" class="theme-soft mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none">

                            <label for="office-address" class="mt-4 block text-sm font-bold text-slate-800 dark:text-slate-100">Direccion publica</label>
                            <textarea id="office-address" name="address" rows="3" required class="theme-soft mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none">{{ $officeAddress }}</textarea>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="office-latitude" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Latitud</label>
                                    <input id="office-latitude" name="gps_latitude" value="{{ number_format($officeLat, 7, '.', '') }}" required class="theme-soft mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label for="office-longitude" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Longitud</label>
                                    <input id="office-longitude" name="gps_longitude" value="{{ number_format($officeLng, 7, '.', '') }}" required class="theme-soft mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none">
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-geo-map
                                    id="office-picker-map"
                                    :lat="$officeLat"
                                    :lng="$officeLng"
                                    :zoom="16"
                                    height="260px"
                                    :picker="true"
                                    lat-input="#office-latitude"
                                    lng-input="#office-longitude"
                                    :markers="[]"
                                />
                            </div>

                            <button type="submit" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white transition hover:bg-emerald-700">
                                Guardar ubicacion
                            </button>
                        </form>
                    @endif
                </aside>
            </section>
        </main>
    </div>
</div>
@endsection
