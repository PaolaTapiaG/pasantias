@extends('layouts.app')

@section('title', 'Reportes - EPSAS')

@php
    $financeChartRows = collect($financeBars ?? []);
    $expenseChartRows = collect($expenseSegments ?? []);

    $financeChart = [
        'type' => 'bar',
        'theme' => 'orange',
        'value' => 'currency',
        'labels' => $financeChartRows->pluck('label')->values(),
        'datasets' => [
            [
                'label' => 'Ingresos',
                'data' => $financeChartRows->pluck('ingresos')->map(fn ($value) => (float) $value)->values(),
                'backgroundColor' => '#ff7a1a',
                'borderColor' => '#ff7a1a',
            ],
            [
                'label' => 'Egresos',
                'data' => $financeChartRows->pluck('egresos')->map(fn ($value) => (float) $value)->values(),
                'backgroundColor' => '#475569',
                'borderColor' => '#475569',
            ],
        ],
    ];

    $expenseChart = [
        'type' => 'doughnut',
        'theme' => 'orange',
        'value' => 'percent',
        'labels' => $expenseChartRows->pluck('categoria')->values(),
        'datasets' => [[
            'label' => 'Gastos',
            'data' => $expenseChartRows->pluck('percent')->map(fn ($value) => (float) $value)->values(),
            'backgroundColor' => $expenseChartRows->pluck('color')->values(),
        ]],
    ];
@endphp

@section('content')
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Reportes',
            'headerTitle' => 'Ingresos y egresos',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de reportes</p>
                        <p class="mt-1 text-sm text-slate-500">Consulta ingresos, egresos y tendencias sin mezclar registro operativo de gastos.</p>
                    </div>
                </div>
            </section>

            <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[0.8fr_0.8fr_1fr_auto]">
                    <input type="date" name="desde" value="{{ $desde }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input type="date" name="hasta" value="{{ $hasta }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <select name="periodo" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="">Todos los periodos</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id_periodo }}" @selected((string) $periodoId === (string) $periodo->id_periodo)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Actualizar</button>
                </form>
            </section>

            <section class="mt-6 grid gap-4 md:grid-cols-5">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Ingresos</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">Bs {{ number_format((float) $resumen['recaudado'], 2) }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Egresos</p><p class="mt-3 text-3xl font-bold text-amber-600">Bs {{ number_format((float) $resumen['egresos'], 2) }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Movimientos</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $resumen['cobros'] }}</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Consumo facturado</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ number_format((float) $resumen['consumo_m3'], 2) }} m3</p></article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Saldo moroso</p><p class="mt-3 text-3xl font-bold text-rose-600">Bs {{ number_format((float) $resumen['saldo_moroso'], 2) }}</p></article>
            </section>

            <section class="mt-6 grid gap-4 md:grid-cols-3 xl:grid-cols-4">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Usuarios nuevos del mes</p>
                    <p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $resumen['nuevos_socios_mes'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Multas del mes</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">Bs {{ number_format((float) $resumen['multas_mes'], 2) }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Lecturaciones del mes</p>
                    <p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $resumen['lecturas_mes'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Balance neto</p>
                    <p class="mt-3 text-3xl font-bold {{ ($resumen['recaudado'] - $resumen['egresos']) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Bs {{ number_format((float) ($resumen['recaudado'] - $resumen['egresos']), 2) }}</p>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Flujo financiero</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Comparativo de ingresos y egresos por fecha.</p>
                    <div class="mt-6 h-80">
                        @if ($financeBars->isNotEmpty())
                            <canvas id="financeChart" data-epsas-chart='@json($financeChart)'></canvas>
                        @else
                            <div class="rounded-3xl border border-dashed border-slate-200 px-6 py-20 text-center text-sm text-slate-500">No hay ingresos ni egresos en este rango.</div>
                        @endif
                    </div>
                </article>
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Distribucion de gastos</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Categorias registradas dentro del rango seleccionado.</p>
                    <div class="mt-6 flex flex-col items-center gap-6">
                        <div class="h-72 w-full">
                            <canvas id="expenseChart" data-epsas-chart='@json($expenseChart)'></canvas>
                        </div>
                        <div class="w-full space-y-3">
                            @foreach ($expenseSegments as $segment)
                                <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <span class="h-3 w-3 rounded-full" style="background: {{ $segment['color'] }}"></span>
                                        {{ $segment['categoria'] }}
                                    </span>
                                    <span class="text-sm font-bold text-slate-900">{{ number_format((float) $segment['percent'], 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Indicadores mensuales</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Usuarios nuevos y lecturaciones registradas durante el año.</p>
                    <div class="mt-6 space-y-4">
                        @foreach ($actividadMensual as $mes)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-500">
                                    <span>{{ $mes['mes'] }}</span>
                                    <span>{{ $mes['usuarios'] }} usuarios - {{ $mes['lecturas'] }} lecturas</span>
                                </div>
                                <div class="grid gap-1">
                                    <div class="h-3 rounded-full bg-slate-100">
                                        <div class="h-3 rounded-full bg-[#ff7a1a]" style="width: {{ round(((int) $mes['usuarios'] / $monthlyMax) * 100, 2) }}%"></div>
                                    </div>
                                    <div class="h-3 rounded-full bg-slate-100">
                                        <div class="h-3 rounded-full bg-[#475569]" style="width: {{ round(((int) $mes['lecturas'] / $monthlyMax) * 100, 2) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex flex-wrap gap-3 pt-2 text-xs font-semibold text-slate-500">
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#ff7a1a]"></span>Usuarios</span>
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#475569]"></span>Lecturaciones</span>
                        </div>
                    </div>
                </article>
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Resumen ejecutivo</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Lectura rapida del comportamiento mensual.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ($actividadMensual->take(6) as $mes)
                            <div class="theme-soft rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="theme-text text-base font-semibold text-slate-900">{{ $mes['mes'] }}</p>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Ingresos: Bs {{ number_format((float) $mes['ingresos'], 2) }}</p>
                                <p class="theme-muted text-sm text-slate-500">Egresos: Bs {{ number_format((float) $mes['egresos'], 2) }}</p>
                                <p class="theme-muted text-sm text-slate-500">Usuarios: {{ $mes['usuarios'] }}</p>
                                <p class="theme-muted text-sm text-slate-500">Lecturas: {{ $mes['lecturas'] }}</p>
                                <div class="mt-3 grid gap-1">
                                    <div class="h-2 rounded-full bg-white">
                                        <div class="h-2 rounded-full bg-[#ff7a1a]" style="width: {{ round(((float) $mes['ingresos'] / $monthlyMoneyMax) * 100, 2) }}%"></div>
                                    </div>
                                    <div class="h-2 rounded-full bg-white">
                                        <div class="h-2 rounded-full bg-[#475569]" style="width: {{ round(((float) $mes['egresos'] / $monthlyMoneyMax) * 100, 2) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                @include('reportes.cobranza')
                @include('reportes.consumos')
                @include('reportes.morosos')
            </div>

            <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="theme-text text-xl font-semibold text-slate-900">Ultimos egresos</h2>
                        <p class="theme-muted mt-2 text-sm text-slate-500">Resumen de gastos incluidos en el reporte.</p>
                    </div>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse ($gastos->take(8) as $gasto)
                        <div class="theme-soft flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="theme-text text-sm font-semibold text-slate-900">{{ $gasto->concepto }}</p>
                                <p class="theme-muted text-xs text-slate-500">{{ $gasto->categoria }} · {{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</p>
                            </div>
                            <span class="text-sm font-bold text-amber-600">Bs {{ number_format((float) $gasto->monto, 2) }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">No se registraron egresos en este rango.</div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
