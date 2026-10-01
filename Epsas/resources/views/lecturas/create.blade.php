@extends('layouts.app')

@section('title', 'Nueva lecturacion - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
    $historyRoute = route('tecnico.lecturas.index');
    $quickRoute = $isAdmin ? route('tecnico.lecturas.create') : route('tecnico.consumo.index');
    $storeRoute = route('tecnico.lecturas.store');
    $catalogUrl = route('api.tecnico.medidores.catalogo', [], false);
    $readingLat = old('latitud');
    $readingLng = old('longitud');
    $mapLat = is_numeric($readingLat) ? (float) $readingLat : -21.5355;
    $mapLng = is_numeric($readingLng) ? (float) $readingLng : -64.7296;
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Lecturaciones',
            'headerTitle' => 'Registrar lectura completa',
        ])
        {{--
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Lecturaciones</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar lectura completa</h1>
                    <p class="mt-2 text-sm text-slate-500">Formulario técnico detallado con lectura sugerida, fecha y observaciones en un espacio más compacto.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tecnico.consumo.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Registro rápido</a>
                    <a href="{{ route('tecnico.lecturas.index') }}" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Volver al historial</a>
                </div>
            </div>
        </header>

        --}}

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de lectura</p>
                        <p class="mt-1 text-sm text-slate-500">Formulario tecnico detallado con lectura sugerida, fecha y observaciones.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        @unless ($isAdmin)
                            <a href="{{ $quickRoute }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Registro rapido</a>
                        @endunless
                        <a href="{{ $historyRoute }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto">Volver al historial</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="grid gap-5">
                        @csrf
                        <input type="hidden" name="redirect_to" value="tecnico.lecturas.index">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Medidor activo</label>
                            <input
                                type="search"
                                data-meter-catalog-search
                                data-url="{{ $catalogUrl }}"
                                data-target="medidor-select"
                                placeholder="Buscar por medidor, socio o zona"
                                class="theme-soft mb-3 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none"
                            >
                            <select id="medidor-select" name="id_medidor" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                                <option value="">Selecciona un medidor</option>
                                @foreach ($medidoresDisponibles as $medidor)
                                    <option
                                        value="{{ $medidor->id_medidor }}"
                                        data-anterior="{{ $medidor->lectura_sugerida }}"
                                        data-lat="{{ $medidor->latitud }}"
                                        data-lng="{{ $medidor->longitud }}"
                                        data-meta="{{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }} · {{ $medidor->zona ?? 'Sin zona' }} · ultima {{ $medidor->ultima_fecha ?? 'sin lectura' }}"
                                        @selected(old('id_medidor') == $medidor->id_medidor)
                                    >
                                        {{ $medidor->numero_serie }} · {{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="medidor-meta" class="mt-2 text-xs text-slate-500">Selecciona un medidor para ver el contexto de la ultima lectura.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Fecha de lectura</label>
                                <input type="date" name="fecha_lectura" value="{{ old('fecha_lectura', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Lectura anterior</label>
                                <input id="lectura-anterior" name="lectura_anterior" value="{{ old('lectura_anterior') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Lectura actual</label>
                                <input name="lectura_actual" value="{{ old('lectura_actual') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Observaciones cortas</label>
                                <input name="observaciones" value="{{ old('observaciones') }}" placeholder="Ej. acceso restringido, lectura observada" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label for="lectura-latitud" class="mb-2 block text-sm font-medium text-slate-700">Latitud de lectura</label>
                                <input id="lectura-latitud" name="latitud" value="{{ $readingLat }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label for="lectura-longitud" class="mb-2 block text-sm font-medium text-slate-700">Longitud de lectura</label>
                                <input id="lectura-longitud" name="longitud" value="{{ $readingLng }}" inputmode="decimal" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-slate-200 p-3">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-700">Ubicacion de la lectura</p>
                                <button type="button" data-geo-current data-geo-map-target="#lectura-map" data-geo-status-target="#lectura-geo-status" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                    Usar ubicacion actual
                                </button>
                            </div>
                            <p id="lectura-geo-status" data-geo-status class="mb-3 min-h-5 text-sm text-slate-500" role="status" aria-live="polite"></p>
                            <x-geo-map
                                id="lectura-map"
                                :lat="$mapLat"
                                :lng="$mapLng"
                                :zoom="16"
                                height="300px"
                                :picker="true"
                                lat-input="#lectura-latitud"
                                lng-input="#lectura-longitud"
                            />
                            <p class="mt-3 text-xs font-semibold text-slate-500">Selecciona un medidor para proponer su ubicacion o marca el punto donde se tomo la lectura.</p>
                        </div>

                        <div>
                            <label for="lectura-evidencia" class="mb-2 block text-sm font-medium text-slate-700">Foto del medidor</label>
                            <input id="lectura-evidencia" name="evidencia" type="file" accept="image/*" capture="environment" data-reading-evidence data-preview-target="#lectura-evidencia-preview" class="block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            <img id="lectura-evidencia-preview" alt="Vista previa de la evidencia" class="mt-3 hidden h-36 w-36 rounded-xl border border-slate-200 object-cover">
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700">Guardar lectura</button>
                            @unless ($isAdmin)
                                <a href="{{ route('tecnico.anomalias.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reportar anomalía</a>
                            @endunless
                        </div>
                    </form>
                </article>

                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Guía rápida</h2>
                    <div class="mt-5 space-y-4 text-sm text-slate-600">
                        <div class="rounded-2xl bg-blue-50 px-4 py-4">
                            El sistema te sugiere la lectura anterior para evitar búsquedas manuales.
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            Si la lectura actual es atípica, registra la observación y luego escala la anomalía.
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            Mantén el mismo medidor seleccionado hasta guardar para no perder la referencia sugerida.
                        </div>
                    </div>

                    <div class="mt-6 rounded-[1.8rem] border border-dashed border-slate-300 px-4 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Disponibles</p>
                        <p class="mt-3 text-3xl font-bold text-slate-900">{{ count($medidoresDisponibles) }}</p>
                        <p class="mt-1 text-sm text-slate-500">medidor(es) activos listos para lectura</p>
                    </div>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const medidorSelect = document.getElementById('medidor-select');
    const lecturaAnterior = document.getElementById('lectura-anterior');
    const medidorMeta = document.getElementById('medidor-meta');
    const meterSearch = document.querySelector('[data-meter-catalog-search][data-target="medidor-select"]');
    const lecturaLatitud = document.getElementById('lectura-latitud');
    const lecturaLongitud = document.getElementById('lectura-longitud');
    const lecturaMap = document.getElementById('lectura-map');

    const escapeOption = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char]);

    const syncReadingLocation = (selected) => {
        if (!selected?.dataset.lat || !selected?.dataset.lng || !lecturaLatitud || !lecturaLongitud) return;

        lecturaLatitud.value = selected.dataset.lat;
        lecturaLongitud.value = selected.dataset.lng;
        lecturaMap?.dispatchEvent(new CustomEvent('epsas:geo:set', {
            detail: {
                lat: selected.dataset.lat,
                lng: selected.dataset.lng,
            },
        }));
    };

    const syncLecturaAnterior = () => {
        const selected = medidorSelect?.selectedOptions?.[0];
        if (!selected) return;

        if (lecturaAnterior && selected.dataset.anterior && !lecturaAnterior.value) {
            lecturaAnterior.value = selected.dataset.anterior;
        }

        if (medidorMeta) {
            medidorMeta.textContent = selected.dataset.meta || 'Selecciona un medidor para ver el contexto de la última lectura.';
        }
    };

    medidorSelect?.addEventListener('change', () => {
        const selected = medidorSelect.selectedOptions[0];
        if (lecturaAnterior) {
            lecturaAnterior.value = selected?.dataset.anterior || '';
        }
        if (medidorMeta) {
            medidorMeta.textContent = selected?.dataset.meta || 'Selecciona un medidor para ver el contexto de la última lectura.';
        }
    });

    medidorSelect?.addEventListener('change', () => syncReadingLocation(medidorSelect.selectedOptions[0]));

    const loadMeterOptions = async (term) => {
        if (!meterSearch || !medidorSelect || term.length < 2) return;

        const response = await fetch(`${meterSearch.dataset.url}?q=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            return;
        }

        const data = await response.json();
        const placeholder = medidorSelect.querySelector('option[value=""]')?.textContent || 'Selecciona un medidor';

        medidorSelect.innerHTML = `<option value="">${escapeOption(placeholder)}</option>` + (data.items || []).map((item) => `
            <option value="${escapeOption(item.value)}" data-anterior="${escapeOption(item.anterior)}" data-meta="${escapeOption(item.meta)}" data-lat="${escapeOption(item.latitud)}" data-lng="${escapeOption(item.longitud)}">
                ${escapeOption(item.label)}
            </option>
        `).join('');

        syncLecturaAnterior();
        syncReadingLocation(medidorSelect?.selectedOptions?.[0]);
    };

    let meterTimer = null;
    meterSearch?.addEventListener('input', (event) => {
        window.clearTimeout(meterTimer);
        meterTimer = window.setTimeout(() => loadMeterOptions(event.target.value.trim()), 280);
    });

    syncLecturaAnterior();
    syncReadingLocation(medidorSelect?.selectedOptions?.[0]);
</script>
@endpush
