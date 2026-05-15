@extends('layouts.app')

@section('title', 'Medidores - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif

    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Operaciones tecnicas</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Gestion de medidores</h1>
                    <p class="mt-2 text-sm text-slate-500">Vista general con filtros, exportacion y acceso claro para registrar o editar.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('tecnico.medidores.export', ['format' => 'excel'] + request()->query()) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">Exportar Excel</a>
                    <a href="{{ route('tecnico.medidores.export', ['format' => 'pdf'] + request()->query()) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">Exportar PDF</a>
                    <a href="{{ route('tecnico.medidores.create') }}" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Registrar medidor</a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

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
                <div class="overflow-x-auto">
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
                                        <a href="{{ route('tecnico.medidores.edit', $medidor) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Editar</a>
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
