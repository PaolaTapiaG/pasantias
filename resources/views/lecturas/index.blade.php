@extends('layouts.app')

@section('title', 'Lecturaciones - EPSAS')

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
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Lecturaciones</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registro de lecturas</h1>
                    <p class="mt-2 text-sm text-slate-500">Captura consumos por medidor y controla el historico mensual.</p>
                </div>
                <a href="{{ route('tecnico.lecturas.create') }}" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Nueva lecturacion</a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">{{ session('success') }}</div>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Total lecturas</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Lecturas del mes</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $stats['mes'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Promedio consumo</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ number_format((float) $stats['promedio_consumo'], 2) }} m3</p></article>
            </section>

            <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1.3fr_0.8fr_0.8fr_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por serie, socio o CI..." class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                    <input type="date" name="desde" value="{{ request('desde') }}" class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                    <input type="date" name="hasta" value="{{ request('hasta') }}" class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                    <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Filtrar</button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Fecha</th>
                                <th class="px-5 py-4">Medidor</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Anterior</th>
                                <th class="px-5 py-4">Actual</th>
                                <th class="px-5 py-4">Consumo</th>
                                <th class="px-5 py-4">Lector</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($lecturas as $lectura)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">{{ optional($lectura->fecha_lectura)->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $lectura->medidor?->numero_serie }}</div></td>
                                    <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $lectura->medidor?->socio?->persona?->nombre_completo }}</div><div class="mt-1 text-xs text-blue-700">{{ $lectura->medidor?->socio?->codigo_display }}</div></td>
                                    <td class="px-5 py-4">{{ number_format((float) $lectura->lectura_anterior, 2) }}</td>
                                    <td class="px-5 py-4">{{ number_format((float) $lectura->lectura_actual, 2) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ number_format((float) $lectura->consumo_m3, 2) }} m3</td>
                                    <td class="px-5 py-4">{{ $lectura->empleado?->persona?->nombre_completo ?? 'Sin lector' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">No existen lecturaciones registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">{{ $lecturas->links() }}</div>
        </main>
    </div>
</div>
@endsection
