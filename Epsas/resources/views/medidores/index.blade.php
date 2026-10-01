@extends('layouts.app')

@section('title', 'Medidores - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
    $registerConsumptionRoute = $isAdmin ? route('tecnico.lecturas.create') : route('tecnico.consumo.index');
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif

    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Operaciones tecnicas',
            'headerTitle' => 'Gestion de medidores',
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
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de medidores</p>
                        <p class="mt-1 text-sm text-slate-500">Vista general con filtros, exportacion y accesos de operacion.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ $registerConsumptionRoute }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-100 sm:w-auto">Registrar consumo</a>
                        <a href="{{ route('tecnico.medidores.export', ['format' => 'excel'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">Exportar Excel</a>
                        <a href="{{ route('tecnico.medidores.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 sm:w-auto">Exportar PDF</a>
                        @if ($isAdmin)
                            <a href="{{ route('tecnico.medidores.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Registrar medidor</a>
                        @endif
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm font-medium text-slate-500">Total medidores</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm font-medium text-slate-500">Activos</p><p class="mt-3 text-3xl font-bold text-emerald-600">{{ $stats['activos'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm font-medium text-slate-500">Danados</p><p class="mt-3 text-3xl font-bold text-amber-600">{{ $stats['danados'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm font-medium text-slate-500">Reemplazados</p><p class="mt-3 text-3xl font-bold text-sky-600">{{ $stats['reemplazados'] }}</p></article>
            </section>

            <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_220px_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por serie, socio, CI o marca..." class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                    <select name="estado" class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                        <option value="">Todos los estados</option>
                        <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                        <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
                        <option value="danado" @selected(request('estado') === 'danado')>Danado</option>
                        <option value="reemplazado" @selected(request('estado') === 'reemplazado')>Reemplazado</option>
                    </select>
                    <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Filtrar</button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-3 p-3 md:hidden">
                    @forelse ($medidores as $medidor)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="wrap-break-word text-sm font-semibold text-slate-900">{{ $medidor->numero_serie }}</h2>
                                    <p class="mt-1 wrap-break-word text-sm text-slate-600">{{ $medidor->socio?->persona?->nombre_completo ?? 'Sin socio asignado' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $medidor->estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : ($medidor->estado === 'danado' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                    {{ ucfirst($medidor->estado) }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-sm">
                                <p><span class="font-medium text-slate-500">Socio:</span> {{ $medidor->socio?->codigo_display ?? 'Sin codigo' }}</p>
                                <p><span class="font-medium text-slate-500">Sector:</span> {{ $medidor->socio?->sector?->nombre ?: 'Sin sector' }}</p>
                                <p><span class="font-medium text-slate-500">Tecnico:</span> {{ $medidor->empleadoInstalador?->persona?->nombre_completo ?? 'No asignado' }}</p>
                                <p><span class="font-medium text-slate-500">Instalacion:</span> {{ optional($medidor->fecha_instalacion)->format('d/m/Y') ?: 'Sin fecha' }}</p>
                                @if ($medidor->marca || $medidor->modelo)
                                    <p><span class="font-medium text-slate-500">Equipo:</span> {{ trim(($medidor->marca ?: '').' '.($medidor->modelo ?: '')) }}</p>
                                @endif
                            </div>

                            @if ($isAdmin)
                                <a href="{{ route('tecnico.medidores.edit', $medidor->id_medidor) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Editar medidor</a>
                            @else
                                <p class="mt-4 rounded-xl bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">Solo lectura</p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No se encontraron medidores con los filtros actuales.</p>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Serie</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Tecnico</th>
                                <th class="px-5 py-4">Fecha</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($medidores as $medidor)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $medidor->numero_serie }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $medidor->marca ?: 'Sin marca' }}{{ $medidor->modelo ? ' - ' . $medidor->modelo : '' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $medidor->socio?->persona?->nombre_completo }}</div>
                                        <div class="mt-1 text-xs text-blue-700">{{ $medidor->socio?->codigo_display }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $medidor->socio?->sector?->nombre ?: 'Sin sector' }}</div>
                                    </td>
                                    <td class="px-5 py-4">{{ $medidor->empleadoInstalador?->persona?->nombre_completo ?? 'No asignado' }}</td>
                                    <td class="px-5 py-4">{{ optional($medidor->fecha_instalacion)->format('d/m/Y') ?: 'Sin fecha' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $medidor->estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : ($medidor->estado === 'danado' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                            {{ ucfirst($medidor->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($isAdmin)
                                            <a href="{{ route('tecnico.medidores.edit', $medidor->id_medidor) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Editar</a>
                                        @else
                                            <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">Solo lectura</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No se encontraron medidores con los filtros actuales.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $medidores->links() }}
            </div>
        </main>
    </div>
</div>
@endsection
