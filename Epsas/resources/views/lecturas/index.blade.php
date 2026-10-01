@extends('layouts.app')

@section('title', 'Lecturaciones - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
    $isSecretaria = auth()->user()?->hasRole('secretaria');
    $newReadingRoute = $isAdmin ? route('tecnico.lecturas.create') : route('tecnico.consumo.index');
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @elseif ($isSecretaria)
        @include('slideboard.sidebarsec')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Lecturaciones',
            'headerTitle' => 'Registro de lecturas',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section data-reading-live class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Actualizaciones en tiempo real</p>
                        <p class="mt-1 text-xs text-slate-500">Las nuevas lecturas apareceran aqui al registrarse.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600" role="status" aria-live="polite">
                        <span data-reading-connection-dot class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <span data-reading-connection-label>Conectando</span>
                    </span>
                </div>
                <div data-reading-notice class="mt-3 hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3" role="status" aria-live="polite" hidden>
                    <p class="text-sm font-semibold text-emerald-800">Nueva lecturacion registrada</p>
                    <p data-reading-notice-detail class="mt-1 text-sm text-emerald-700"></p>
                </div>
            </section>

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de lecturacion</p>
                        <p class="mt-1 text-sm text-slate-500">Captura consumos por medidor y controla el historico mensual.</p>
                    </div>
                    @unless ($isSecretaria)
                        <a href="{{ $newReadingRoute }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600 sm:w-auto">Nuevo consumo</a>
                    @endunless
                </div>
            </section>

            <div class="md:hidden">
                <section class="mobile-finance-hero overflow-hidden rounded-[2rem] px-5 py-5 text-slate-900 shadow-[0_24px_45px_rgba(249,115,22,0.18)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Panel de lecturas</p>
                            <h2 class="mt-2 text-[1.85rem] font-bold leading-tight text-slate-950">Hola, {{ Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}</h2>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/85 text-orange-500 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>

                    <div class="mobile-finance-balance mt-5 rounded-[1.8rem] px-5 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-slate-600">Promedio de consumo</p>
                                <p class="mt-2 text-4xl font-bold tracking-tight text-slate-950">{{ number_format((float) $stats['promedio_consumo'], 2) }}</p>
                                <p class="mt-1 text-sm font-medium text-orange-600">m3 registrados este periodo</p>
                            </div>
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-orange-500 text-white shadow-[0_14px_30px_rgba(249,115,22,0.28)]">+</span>
                        </div>
                    </div>
                </section>

                @if (session('success'))
                    <div class="mt-5 rounded-[1.4rem] border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">{{ session('success') }}</div>
                @endif

                <section class="mt-5 grid grid-cols-2 gap-4">
                    <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                        <p class="text-sm text-slate-500">Total lecturas</p>
                        <p data-reading-total="{{ $stats['total'] }}" class="mt-4 text-3xl font-bold text-slate-950">{{ $stats['total'] }}</p>
                    </article>
                    <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                        <p class="text-sm text-slate-500">Lecturas del mes</p>
                        <p data-reading-month-total="{{ $stats['mes'] }}" class="mt-4 text-3xl font-bold text-slate-950">{{ $stats['mes'] }}</p>
                    </article>
                </section>

                <section class="mt-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-950">Busqueda rapida</h3>
                        <span class="text-sm font-medium text-orange-500">Ver todo</span>
                    </div>
                    <form method="GET" class="grid gap-3">
                        <div class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por serie, socio o CI..." class="theme-soft h-11 w-full rounded-2xl border border-orange-100 bg-[#fff8f3] px-4 text-sm outline-none">
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <input type="date" name="desde" value="{{ request('desde') }}" class="theme-soft h-11 rounded-2xl border border-orange-100 bg-[#fff8f3] px-4 text-sm outline-none">
                                <input type="date" name="hasta" value="{{ request('hasta') }}" class="theme-soft h-11 rounded-2xl border border-orange-100 bg-[#fff8f3] px-4 text-sm outline-none">
                            </div>
                            <button class="mt-3 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-orange-500 px-4 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(249,115,22,0.25)] transition hover:bg-orange-600">Filtrar lecturas</button>
                        </div>
                    </form>
                </section>

                <section class="mt-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-950">Lecturas recientes</h3>
                        <span class="text-sm font-medium text-orange-500">Historial</span>
                    </div>
                    <div data-reading-cards class="grid gap-4">
                        @forelse ($lecturas as $lectura)
                            <article class="mobile-finance-card rounded-[1.65rem] p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-semibold text-slate-950">{{ $lectura->medidor?->socio?->persona?->nombre_completo }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ optional($lectura->fecha_lectura)->format('d/m/Y') }} · {{ $lectura->medidor?->numero_serie }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-orange-500">{{ number_format((float) $lectura->consumo_m3, 2) }}</p>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">m3</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl bg-[#fff8f3] px-3 py-3">
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Anterior</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float) $lectura->lectura_anterior, 2) }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-[#fff8f3] px-3 py-3">
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Actual</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float) $lectura->lectura_actual, 2) }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Lector</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $lectura->empleado?->persona?->nombre_completo ?? 'Sin lector' }}</p>
                                    </div>
                                    <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">{{ $lectura->medidor?->socio?->codigo_display }}</span>
                                </div>
                                @if ($lectura->evidencia_url)
                                    <a href="{{ $lectura->evidencia_url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-3 text-sm font-semibold text-blue-700">
                                        <img src="{{ $lectura->evidencia_url }}" alt="Evidencia de lectura" loading="lazy" class="h-14 w-14 rounded-xl border border-slate-200 object-cover">
                                        Ver foto del medidor
                                    </a>
                                @endif
                            </article>
                        @empty
                            <div data-reading-empty class="mobile-finance-card rounded-[1.6rem] px-4 py-10 text-center text-sm text-slate-500 shadow-sm">No existen lecturaciones registradas.</div>
                        @endforelse
                    </div>
                </section>

                <div class="mt-6">{{ $lecturas->links() }}</div>
            </div>

            <div class="hidden md:block">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">{{ session('success') }}</div>
                @endif

                <section class="grid gap-4 md:grid-cols-3">
                    <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Total lecturas</p><p data-reading-total="{{ $stats['total'] }}" class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p></article>
                    <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Lecturas del mes</p><p data-reading-month-total="{{ $stats['mes'] }}" class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $stats['mes'] }}</p></article>
                    <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Promedio consumo</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ number_format((float) $stats['promedio_consumo'], 2) }} m3</p></article>
                </section>

                <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="GET" class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.3fr_0.8fr_0.8fr_auto]">
                        <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por serie, socio o CI..." class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                        <input type="date" name="desde" value="{{ request('desde') }}" class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                        <input type="date" name="hasta" value="{{ request('hasta') }}" class="theme-soft h-11 rounded-xl border px-4 text-sm outline-none">
                        <button class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 md:col-span-2 xl:col-span-1">Filtrar</button>
                    </form>
                </section>

                <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div class="hidden overflow-x-auto md:block">
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
                                <th class="px-5 py-4">Evidencia</th>
                            </tr>
                        </thead>
                        <tbody data-reading-rows class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($lecturas as $lectura)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">{{ optional($lectura->fecha_lectura)->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $lectura->medidor?->numero_serie }}</div></td>
                                    <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $lectura->medidor?->socio?->persona?->nombre_completo }}</div><div class="mt-1 text-xs text-blue-700">{{ $lectura->medidor?->socio?->codigo_display }}</div></td>
                                    <td class="px-5 py-4">{{ number_format((float) $lectura->lectura_anterior, 2) }}</td>
                                    <td class="px-5 py-4">{{ number_format((float) $lectura->lectura_actual, 2) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ number_format((float) $lectura->consumo_m3, 2) }} m3</td>
                                    <td class="px-5 py-4">{{ $lectura->empleado?->persona?->nombre_completo ?? 'Sin lector' }}</td>
                                    <td class="px-5 py-4">
                                        @if ($lectura->evidencia_url)
                                            <a href="{{ $lectura->evidencia_url }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir evidencia de la lectura {{ $lectura->id_lectura }}">
                                                <img src="{{ $lectura->evidencia_url }}" alt="Foto del medidor" loading="lazy" class="h-12 w-12 rounded-lg border border-slate-200 object-cover">
                                            </a>
                                        @else
                                            <span class="text-slate-400">Sin foto</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr data-reading-empty><td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">No existen lecturaciones registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </section>

                <div class="mt-6">{{ $lecturas->links() }}</div>
            </div>
        </main>
    </div>
</div>
@endsection
