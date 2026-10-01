@extends('layouts.app')

@section('title', 'Socios - EPSAS')

@php
    $statusStyles = [
        'activo' => 'bg-emerald-100 text-emerald-700',
        'inactivo' => 'bg-slate-200 text-slate-700',
        'suspendido' => 'bg-amber-100 text-amber-700',
        'cortado' => 'bg-rose-100 text-rose-700',
    ];
@endphp

@section('content')
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Socios',
            'headerTitle' => 'Gestion de socios',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de socios</p>
                        <p class="mt-1 text-sm text-slate-500">Exporta el listado filtrado o registra un nuevo socio desde aqui.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ route('admin.socios.export', ['format' => 'excel'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">Exportar Excel</a>
                        <a href="{{ route('admin.socios.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Exportar PDF</a>
                        <a href="{{ route('admin.socios.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Registrar socio</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form id="socios-filter-form" method="GET" class="grid gap-4 lg:grid-cols-[1.5fr_0.75fr_0.75fr_0.75fr_auto]">
                    <input
                        id="buscar"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Buscar por nombre, CI, numero de socio..."
                        class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                        autocomplete="off"
                    >
                    <select name="estado" class="filter-change h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los estados</option>
                        @foreach (['activo', 'inactivo', 'suspendido', 'cortado'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    <select name="sector" class="filter-change h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todas las zonas</option>
                        @foreach ($sectores as $sector)
                            <option value="{{ $sector->id_sector }}" @selected((string) request('sector') === (string) $sector->id_sector)>
                                {{ $sector->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <select name="visibilidad" class="filter-change h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Visibles y ocultos</option>
                        <option value="visibles" @selected(request('visibilidad') === 'visibles')>Solo visibles</option>
                        <option value="ocultos" @selected(request('visibilidad') === 'ocultos')>Solo ocultos</option>
                    </select>
                    <a href="{{ route('admin.socios.index') }}" class="inline-flex h-11 w-full min-w-0 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 lg:w-auto">
                        Limpiar
                    </a>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Identificacion</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Telefono</th>
                                <th class="px-5 py-4">Sector</th>
                                <th class="px-5 py-4">Tarifa</th>
                                <th class="px-5 py-4">Medidor</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4">Auditoria</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($socios as $socio)
                                <tr class="align-top hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">#{{ $socio->id_socio }}</div>
                                        <div class="mt-1 text-xs text-blue-700">{{ $socio->codigo_display }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $socio->nombre_completo ?: 'Sin nombre' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">CI {{ $socio->cedula_identidad ?: 'Sin CI' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $socio->direccion ?: 'Sin direccion' }}</div>
                                    </td>
                                    <td class="px-5 py-4">{{ $socio->telefono ?: 'No registrado' }}</td>
                                    <td class="px-5 py-4">{{ $socio->sector_nombre }}{{ $socio->sector_zona ? ' - ' . $socio->sector_zona : '' }}</td>
                                    <td class="px-5 py-4">{{ $socio->tarifa_nombre ?: 'Sin tarifa' }}</td>
                                    <td class="px-5 py-4">{{ $socio->medidor_numero_serie ?: 'Sin medidor' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-2">
                                            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$socio->estado] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ ucfirst($socio->estado) }}
                                            </span>
                                            @if ($socio->oculto)
                                                <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                    Oculto
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="max-w-xs text-xs text-slate-500">
                                            {{ $socio->motivo_ocultacion ?: 'Sin observaciones' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end">
                                            <div class="flex max-w-md flex-wrap justify-end gap-2">
                                                <a href="{{ route('admin.socios.show', $socio->id_socio) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Ver perfil
                                                </a>
                                                <a href="{{ route('admin.socios.edit', $socio->id_socio) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                                                    Editar
                                                </a>
                                                <a href="{{ route('admin.socios.carnet', $socio->id_socio) }}" target="_blank" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                    Carnet
                                                </a>

                                                @if ($socio->estado !== 'activo')
                                                    <form method="POST" action="{{ route('admin.socios.activate', $socio->id_socio) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                            Activo
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($socio->estado !== 'inactivo')
                                                    <form method="POST" action="{{ route('admin.socios.deactivate', $socio->id_socio) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                                            Inactivo
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (!$socio->oculto)
                                                    <form method="POST" action="{{ route('admin.socios.hide', $socio->id_socio) }}" class="flex flex-wrap justify-end gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input name="motivo_ocultacion" placeholder="Motivo de ocultacion" class="h-9 min-w-[180px] rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs outline-none focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100" required>
                                                        <button class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                                            Ocultar
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.socios.unhide', $socio->id_socio) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                                            Mostrar
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron socios con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $socios->links() }}
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('socios-filter-form');
    const searchInput = document.getElementById('buscar');
    const instantFilters = form.querySelectorAll('.filter-change');
    let debounceId;

    const submitFilters = () => form.requestSubmit();

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceId);
        debounceId = setTimeout(submitFilters, 350);
    });

    instantFilters.forEach((field) => {
        field.addEventListener('change', submitFilters);
    });
});
</script>
@endsection
