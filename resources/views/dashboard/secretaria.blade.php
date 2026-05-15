@extends('layouts.app')

@section('title', 'Panel Secretaria - EPSAS')

@php
    $companySettings = $sharedCompanySettings ?? [];
    $currentUser = $sharedAuthUser ?? $user ?? auth()->user();
    $profilePhoto = $currentUser?->persona?->foto_url;
@endphp

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
@php
    $dashboardStats = \Illuminate\Support\Facades\Cache::remember('dashboard.secretaria.stats', now()->addMinutes(10), function () {
        return [
            'socios' => \App\Models\Socio::count(),
            'facturas' => \App\Models\Factura::count(),
            'cobros' => \App\Models\Cobro::count(),
        ];
    });

    $stats = [
        [
            'label' => 'Socios',
            'value' => $dashboardStats['socios'],
            'tone' => 'emerald',
            'detail' => 'Clientes registrados',
        ],
        [
            'label' => 'Facturas',
            'value' => $dashboardStats['facturas'],
            'tone' => 'cyan',
            'detail' => 'Facturas emitidas',
        ],
        [
            'label' => 'Cobros',
            'value' => $dashboardStats['cobros'],
            'tone' => 'lime',
            'detail' => 'Pagos registrados',
        ],
        [
            'label' => 'Estado',
            'value' => 'En linea',
            'tone' => 'green',
            'detail' => 'Sistema operativo correctamente',
        ],
    ];

    $cards = [
        [
            'title' => 'Gestion de socios',
            'description' => 'Registra nuevos socios, actualiza información y gestiona los clientes del sistema.',
            'items' => ['Crear socios', 'Ver información', 'Actualizar datos'],
            'route' => 'admin.socios.index',
            'tone' => 'emerald',
        ],
        [
            'title' => 'Facturas',
            'description' => 'Emite facturas, consulta historial y realiza seguimiento de todas las facturas.',
            'items' => ['Crear facturas', 'Ver historial', 'Generar reportes'],
            'route' => 'secretaria.facturas.index',
            'tone' => 'cyan',
        ],
        [
            'title' => 'Cobros',
            'description' => 'Registra pagos, gestiona métodos de cobro y realiza seguimiento de ingresos.',
            'items' => ['Registrar cobros', 'Ver historial', 'Generar comprobantes'],
            'route' => 'secretaria.cobros.index',
            'tone' => 'lime',
        ],
        [
            'title' => 'Lecturas',
            'description' => 'Consulta y gestiona las lecturas de medidores del sistema de agua.',
            'items' => ['Ver lecturas', 'Registrar lecturas', 'Historial medidores'],
            'route' => 'tecnico.lecturas.index',
            'tone' => 'green',
        ],
    ];
@endphp

<div class="page-background min-h-screen">
    @include('slideboard.sidebarsec')

    <div data-secretaria-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        data-sidebar-toggle
                        class="hidden h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 md:flex"
                        aria-label="Expandir o contraer sidebar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" data-sidebar-toggle-icon class="h-5 w-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 6.75l4.5 5-4.5 5" />
                        </svg>
                    </button>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Secretaria</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Panel de control</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900">{{ $currentUser->name }}</p>
                        <p class="text-xs text-slate-500">{{ $currentUser->email }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-bold text-emerald-700">
                        @if ($profilePhoto)
                            <img src="{{ $profilePhoto }}" alt="Foto secretaria" class="h-11 w-11 rounded-2xl object-cover">
                        @else
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Bienvenida -->
            <section class="mb-8 overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#059669_0%,#047857_58%,#065f46_100%)] px-6 py-8 text-white shadow-[0_24px_50px_rgba(5,150,105,0.25)] sm:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-emerald-100/80">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">
                        Bienvenida, {{ $currentUser->name }}
                    </h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-emerald-50/90 sm:text-base">
                        Monitorea el crecimiento de socios y la recaudación mensual desde un solo lugar.
                    </p>
                </div>
            </section>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Tarjetas de Estadísticas Principales -->
            <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                        <div class="mt-2 flex items-center gap-1 text-[10px] text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                            </svg>
                            <span>Actualizado ahora</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <!-- Gráficos -->
            <section class="mb-8 grid gap-6 lg:grid-cols-2">
                <!-- Gráfico de Ingresos -->
                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Ingresos Mensuales (Bs)</h3>
                        <span class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-600">Total: {{ number_format($monthEarnings, 2) }}</span>
                    </div>
                    <div class="h-64">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </article>

                <!-- Gráfico de Nuevos Socios -->
                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Nuevos Socios por Mes</h3>
                        <span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-600">Este mes: {{ $chartData->last()['socios'] }}</span>
                    </div>
                    <div class="h-64">
                        <canvas id="sociosChart"></canvas>
                    </div>
                </article>
            </section>

            <!-- Tabla de Nuevos Socios y Facturación -->
            <section>
                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Registro de Socios y Facturación</h3>
                        <a href="{{ route('admin.socios.index') }}" class="rounded-xl bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                            Ver todos los socios
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400">
                                    <th class="pb-4 font-medium">Socio</th>
                                    <th class="pb-4 font-medium">Nro. Socio</th>
                                    <th class="pb-4 font-medium">Última Factura</th>
                                    <th class="pb-4 font-medium text-right">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($newSocios as $socio)
                                    @php $factura = $socio->facturas->first(); @endphp
                                    <tr class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-2xl bg-emerald-50 text-sm font-bold text-emerald-700 flex items-center justify-center border border-emerald-100">
                                                    {{ strtoupper(substr($socio->persona?->nombres, 0, 1)) }}
                                                </div>
                                                <span class="font-bold text-slate-900">{{ $socio->persona?->nombre_completo }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 text-slate-500 font-mono">{{ $socio->numero_socio }}</td>
                                        <td class="py-4">
                                            @if($factura)
                                                <div class="flex flex-col">
                                                    <span class="text-slate-900 font-bold text-base">Bs {{ number_format($factura->total, 2) }}</span>
                                                    <span class="text-[11px] text-slate-400 font-medium uppercase">{{ $factura->fecha_emision->format('d M, Y') }}</span>
                                                </div>
                                            @else
                                                <span class="text-slate-300 italic">Sin facturas</span>
                                            @endif
                                        </td>
                                        <td class="py-4 text-right">
                                            @if($factura)
                                                <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-bold 
                                                    {{ $factura->estado === 'pagada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ strtoupper($factura->estado) }}
                                                </span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </main>
     </div>
 </div>

@push('scripts')
 <script>
     // Gráfico de Ingresos
     const ctxEarnings = document.getElementById('earningsChart').getContext('2d');
     new Chart(ctxEarnings, {
         type: 'line',
         data: {
             labels: {!! json_encode($chartData->pluck('month')) !!},
             datasets: [{
                 label: 'Ingresos (Bs)',
                 data: {!! json_encode($chartData->pluck('earnings')) !!},
                 borderColor: '#10b981',
                 backgroundColor: 'rgba(16, 185, 129, 0.1)',
                 fill: true,
                 tension: 0.4,
                 borderWidth: 3,
                 pointRadius: 4,
                 pointBackgroundColor: '#10b981'
             }]
         },
         options: {
             responsive: true,
             maintainAspectRatio: false,
             plugins: {
                 legend: { display: false }
             },
             scales: {
                 y: {
                     beginAtZero: true,
                     grid: { color: '#f1f5f9' },
                     ticks: { font: { size: 11 } }
                 },
                 x: {
                     grid: { display: false },
                     ticks: { font: { size: 11 } }
                 }
             }
         }
     });

     // Gráfico de Nuevos Socios
     const ctxSocios = document.getElementById('sociosChart').getContext('2d');
     new Chart(ctxSocios, {
         type: 'bar',
         data: {
             labels: {!! json_encode($chartData->pluck('month')) !!},
             datasets: [{
                 label: 'Nuevos Socios',
                 data: {!! json_encode($chartData->pluck('socios')) !!},
                 backgroundColor: '#3b82f6',
                 borderRadius: 8,
                 borderSkipped: false,
             }]
         },
         options: {
             responsive: true,
             maintainAspectRatio: false,
             plugins: {
                 legend: { display: false }
             },
             scales: {
                 y: {
                     beginAtZero: true,
                     ticks: { 
                        stepSize: 1,
                        font: { size: 11 } 
                     },
                     grid: { color: '#f1f5f9' }
                 },
                 x: {
                     grid: { display: false },
                     ticks: { font: { size: 11 } }
                 }
             }
         }
     });
 </script>
 @endpush
@endsection
