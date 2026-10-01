@extends('layouts.app')

@section('title', 'Panel Secretaria - EPSAS')

@php
    $stats = $secretariaStats ?? [
        'socios_activos' => 0,
        'facturas_pendientes' => 0,
        'qr_pendientes' => 0,
        'ingresos_mes' => 0,
        'solicitudes_abiertas' => 0,
    ];

    $secretariaWorkChart = [
        'type' => 'bar',
        'theme' => 'emerald',
        'legend' => false,
        'value' => 'number',
        'labels' => ['Socios', 'Facturas', 'QR', 'Solicitudes'],
        'datasets' => [[
            'label' => 'Atencion',
            'data' => [
                (int) $stats['socios_activos'],
                (int) $stats['facturas_pendientes'],
                (int) $stats['qr_pendientes'],
                (int) $stats['solicitudes_abiertas'],
            ],
        ]],
    ];

    $secretariaPendingChart = [
        'type' => 'doughnut',
        'theme' => 'emerald',
        'value' => 'number',
        'labels' => ['Facturas pendientes', 'QR por aprobar', 'Solicitudes abiertas'],
        'datasets' => [[
            'label' => 'Pendientes',
            'data' => [
                (int) $stats['facturas_pendientes'],
                (int) $stats['qr_pendientes'],
                (int) $stats['solicitudes_abiertas'],
            ],
        ]],
    ];
@endphp

@section('content')
<div class="page-background min-h-screen bg-white">
    @include('slideboard.sidebarsec')

    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Secretaria',
            'headerTitle' => 'Panel de secretaria',
            'companyName' => $sharedCompanySettings['company_name'] ?? 'EPSAS EL PORTILLO',
            'userName' => $user?->name ?? Auth::user()->name ?? '',
            'userEmail' => $user?->email ?? Auth::user()->email ?? '',
            'profilePhoto' => $user?->persona?->foto_url ?? null,
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.35fr_0.9fr]">
                    <div class="relative overflow-hidden bg-[linear-gradient(135deg,#047857_0%,#0f9f6e_48%,#d1fae5_100%)] p-7 text-white sm:p-9">
                        <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/15 blur-2xl"></div>
                        <div class="absolute bottom-0 right-0 h-32 w-72 rounded-tl-full bg-white/10"></div>
                        <p class="text-xs font-black uppercase tracking-[0.34em] text-emerald-100">Secretaria</p>
                        <h2 class="mt-4 max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Bienvenida, {{ \Illuminate\Support\Str::before($user?->name ?? Auth::user()->name ?? 'Rosa', ' ') }}
                        </h2>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-emerald-50 sm:text-base">
                            Gestiona cobros, facturaciones, socios y reportes de ingresos desde un panel limpio y preparado para atencion diaria.
                        </p>
                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('secretaria.cobros.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-lg shadow-emerald-950/10 transition hover:bg-emerald-50">
                                Registrar pagos
                            </a>
                            <a href="{{ route('secretaria.ordenes-pago.index', ['estado' => 'en_revision']) }}" class="inline-flex items-center justify-center rounded-2xl border border-white/35 px-5 py-3 text-sm font-black text-white transition hover:bg-white/10">
                                Revisar QR pendientes
                            </a>
                        </div>
                    </div>

                    <div class="grid content-center gap-4 bg-emerald-50/60 p-6 sm:p-8">
                        <article class="rounded-[1.5rem] border border-white bg-white p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Turno operativo</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ now()->translatedFormat('d F Y') }}</p>
                            <p class="mt-2 text-sm text-slate-500">Los pagos QR por aprobar aparecen tambien en la campanita del encabezado.</p>
                        </article>
                        <article class="rounded-[1.5rem] border border-emerald-100 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500">Ingresos del mes</p>
                            <p class="mt-2 text-3xl font-black text-emerald-700">Bs {{ number_format((float) $stats['ingresos_mes'], 2) }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Socios activos</p>
                    <p class="mt-3 text-4xl font-black text-slate-950">{{ number_format((int) $stats['socios_activos']) }}</p>
                    <a href="{{ route('admin.socios.index') }}" class="mt-4 inline-flex text-sm font-black text-emerald-700 hover:text-emerald-800">Ver socios</a>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Facturas pendientes</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ number_format((int) $stats['facturas_pendientes']) }}</p>
                    <a href="{{ route('secretaria.facturas.index') }}" class="mt-4 inline-flex text-sm font-black text-emerald-700 hover:text-emerald-800">Abrir facturaciones</a>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">QR por aprobar</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ number_format((int) $stats['qr_pendientes']) }}</p>
                    <a href="{{ route('secretaria.ordenes-pago.index', ['estado' => 'en_revision']) }}" class="mt-4 inline-flex text-sm font-black text-emerald-700 hover:text-emerald-800">Revisar ordenes</a>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Perfil activo</p>
                    <p class="mt-3 truncate text-xl font-black text-slate-950">{{ $user?->email ?? Auth::user()->email }}</p>
                    <a href="{{ route('secretaria.perfil.index') }}" class="mt-4 inline-flex text-sm font-black text-emerald-700 hover:text-emerald-800">Editar perfil</a>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Grafico de atencion</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Movimiento del panel</h3>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Caja</span>
                    </div>
                    <div class="mt-5 h-72">
                        <canvas data-epsas-chart='@json($secretariaWorkChart)'></canvas>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-sm">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-rose-500">Pendientes</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-950">Revision diaria</h3>
                    </div>
                    <div class="mt-5 h-72">
                        <canvas data-epsas-chart='@json($secretariaPendingChart)'></canvas>
                    </div>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Accesos directos</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Ventanas de trabajo</h3>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <a href="{{ route('secretaria.cobros.index') }}" class="group rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-100">
                            <p class="text-sm font-black text-emerald-800">Registrar pagos</p>
                            <p class="mt-2 text-sm leading-6 text-emerald-900/70">Busca socios deudores, registra pagos y revisa ordenes QR.</p>
                        </a>
                        <a href="{{ route('secretaria.facturas.index') }}" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50">
                            <p class="text-sm font-black text-slate-950">Facturaciones</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Consulta facturas, genera pendientes y envia recibos al usuario.</p>
                        </a>
                        <a href="{{ route('admin.socios.index') }}" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50">
                            <p class="text-sm font-black text-slate-950">Socios</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Revisa datos, telefonos, zonas, medidores y estado de cuenta.</p>
                        </a>
                        <a href="{{ route('secretaria.reportes.index') }}" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50">
                            <p class="text-sm font-black text-slate-950">Reportes ingresos</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Controla cobranza, flujo mensual y resumen financiero.</p>
                        </a>
                        <a href="{{ route('secretaria.operaciones.index') }}" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50">
                            <p class="text-sm font-black text-slate-950">Atencion, caja y tecnicos</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Abre caja, registra reclamos, deriva inspecciones y prepara comunicados.</p>
                        </a>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-rose-500">Revision</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Pagos QR recibidos</h3>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-600">{{ $pendingPaymentOrders->count() }}</span>
                    </div>

                    <div class="mt-6 grid gap-3">
                        @forelse ($pendingPaymentOrders as $order)
                            <a href="{{ route('secretaria.ordenes-pago.show', $order->codigo) }}" class="rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-950">{{ $order->codigo }}</p>
                                        <p class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $order->socio_nombre ?: 'Socio sin nombre' }} · {{ $order->numero_socio ?: 'Sin codigo' }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-emerald-700">Bs {{ number_format((float) ($order->comprobante_monto ?: $order->total), 2) }}</span>
                                </div>
                                <p class="mt-3 text-xs font-semibold text-slate-500">{{ $order->updated_at?->diffForHumans() ?? 'Reciente' }}</p>
                            </a>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm font-semibold text-slate-500">
                                No hay comprobantes QR pendientes por aprobar.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-2">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Caja</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Ultimos ingresos registrados</h3>
                        </div>
                        <a href="{{ route('secretaria.cobros.index') }}" class="text-sm font-black text-emerald-700 hover:text-emerald-800">Abrir</a>
                    </div>

                    <div class="mt-6 grid gap-3">
                        @forelse ($recentPayments as $payment)
                            <article class="rounded-[1.35rem] border border-slate-200 bg-white px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-950">{{ $payment->socio_nombre ?: 'Socio sin nombre' }}</p>
                                        <p class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $payment->numero_factura ?: 'Sin factura' }} · {{ $payment->metodo_pago ?: 'Metodo no registrado' }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-black text-emerald-700">Bs {{ number_format((float) $payment->monto_pagado, 2) }}</span>
                                </div>
                                <p class="mt-3 text-xs font-semibold text-slate-500">{{ $payment->fecha_cobro?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                            </article>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm font-semibold text-slate-500">
                                Todavia no hay pagos recientes registrados.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Operativo</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Reconexiones por aprobar</h3>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $pendingReconnectionApprovals->count() }}</span>
                    </div>

                    <div class="mt-6 grid gap-3">
                        @forelse ($pendingReconnectionApprovals as $order)
                            <article class="rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm font-black text-slate-950">{{ $order->socio?->codigo_display ?? 'Sin socio' }} · {{ $order->socio?->persona?->nombre_completo ?? 'Usuario' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $order->zona ?: 'Sin zona' }} · {{ $order->referencia ?: 'Sin referencia' }}</p>
                                <form method="POST" action="{{ route('secretaria.reconexiones.approve', $order->id_orden) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white transition hover:bg-emerald-700">Aprobar reconexion</button>
                                </form>
                            </article>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm font-semibold text-slate-500">
                                No hay solicitudes de reconexion pendientes.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
