@extends('layouts.app')

@section('title', 'Reportes - EPSAS')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700 dark:text-blue-300">Reportes</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Ingresos y egresos</h1>
                </div>
                <a href="{{ route('admin.gastos.index') }}" class="rounded-2xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">Registrar gasto</a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="theme-muted text-sm text-slate-500">Cobros</p><p class="theme-text mt-3 text-3xl font-bold text-slate-900">{{ $resumen['cobros'] }}</p></article>
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
                    <div class="mt-6 h-[320px]">
                        <canvas id="finance-chart" class="h-full w-full"></canvas>
                    </div>
                </article>
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Distribucion de gastos</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Categorias registradas dentro del rango seleccionado.</p>
                    <div class="mt-6 h-[320px]">
                        <canvas id="expense-chart" class="h-full w-full"></canvas>
                    </div>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Indicadores mensuales</h2>
                    <p class="theme-muted mt-2 text-sm text-slate-500">Usuarios nuevos y lecturaciones registradas durante el año.</p>
                    <div class="mt-6 h-[320px]">
                        <canvas id="ops-chart" class="h-full w-full"></canvas>
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

@push('scripts')
<script>
    const ingresos = @json($ingresosPorDia);
    const egresos = @json($egresosPorDia);
    const gastos = @json($gastosPorCategoria);
    const actividadMensual = @json($actividadMensual);

    const labels = [...new Set([...ingresos.map(i => i.fecha), ...egresos.map(i => i.fecha)])].sort();
    const ingresoMap = Object.fromEntries(ingresos.map(i => [i.fecha, Number(i.total)]));
    const egresoMap = Object.fromEntries(egresos.map(i => [i.fecha, Number(i.total)]));

    new Chart(document.getElementById('finance-chart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: labels.map(label => ingresoMap[label] || 0),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Egresos',
                    data: labels.map(label => egresoMap[label] || 0),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.12)',
                    tension: 0.35,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } }
        }
    });

    new Chart(document.getElementById('expense-chart'), {
        type: 'doughnut',
        data: {
            labels: gastos.map(item => item.categoria),
            datasets: [{
                data: gastos.map(item => Number(item.total)),
                backgroundColor: ['#f59e0b', '#2563eb', '#10b981', '#ef4444', '#8b5cf6', '#14b8a6']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%' }
    });

    new Chart(document.getElementById('ops-chart'), {
        type: 'bar',
        data: {
            labels: actividadMensual.map(item => item.mes),
            datasets: [
                {
                    label: 'Usuarios nuevos',
                    data: actividadMensual.map(item => Number(item.usuarios)),
                    backgroundColor: 'rgba(37, 99, 235, 0.75)',
                    borderRadius: 10
                },
                {
                    label: 'Lecturaciones',
                    data: actividadMensual.map(item => Number(item.lecturas)),
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderRadius: 10
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endpush
