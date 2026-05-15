@extends('layouts.app')

@section('title', 'Detalle de factura - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl print:hidden dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Facturacion</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $factura->numero_factura }}</h1>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('secretaria.cobros.show', $factura->id_socio) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Registrar pago
                    </a>
                    <a href="{{ $pdfUrl }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Descargar PDF
                    </a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        Enviar por WhatsApp
                    </a>
                    <a href="{{ $gmailUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                        Enviar por Gmail
                    </a>
                    <a href="{{ $printUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Imprimir
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm print:hidden">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="theme-text text-xl font-semibold text-slate-900">Datos del socio</h2>
                            <p class="theme-muted mt-2 text-sm text-slate-500">Recibo electronico listo para impresion o descarga en PDF.</p>
                        </div>
                        @if (!empty($company['company_logo']))
                            <img src="{{ asset($company['company_logo']) }}" alt="Logo empresa" class="h-16 rounded-2xl border border-slate-200 bg-white p-2">
                        @endif
                    </div>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Nombre</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->socio?->persona?->nombre_completo }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Codigo de usuario</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $billingBreakdown['codigo_usuario'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sector</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->socio?->sector?->nombre }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tarifa</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->socio?->tarifa?->nombre }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Telefono</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->socio?->persona?->telefono ?: 'Sin registro' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Correo</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->socio?->persona?->email ?: 'Sin registro' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Datos de facturacion y lectura</h2>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Periodo</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->periodo?->nombre }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estado</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ ucfirst($factura->estado) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Fecha de emision</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ optional($factura->fecha_emision)->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Fecha de pago</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ optional($factura->fecha_pago)->format('d/m/Y') ?: 'Pendiente' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Lectura anterior</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ number_format((float) $billingBreakdown['previous_reading'], 2) }} m3</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Lectura actual</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ number_format((float) $billingBreakdown['current_reading'], 2) }} m3</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Consumo del periodo</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ number_format((float) $billingBreakdown['consumed_m3'], 2) }} m3</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Medidor</dt>
                            <dd class="theme-text mt-1 text-sm text-slate-800">{{ $factura->lectura?->medidor?->numero_serie ?: 'Sin medidor' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Resumen del recibo</h2>
                    <a href="{{ route('secretaria.facturas.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 print:hidden">
                        Volver al listado
                    </a>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                    <div class="theme-soft rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cargo fijo agua</p>
                        <p class="theme-text mt-2 text-lg font-semibold text-slate-900">Bs {{ number_format((float) $billingBreakdown['fixed_charge'], 2) }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Excedente</p>
                        <p class="theme-text mt-2 text-lg font-semibold text-slate-900">Bs {{ number_format((float) $billingBreakdown['excess_charge'], 2) }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Alcantarillado</p>
                        <p class="theme-text mt-2 text-lg font-semibold text-slate-900">Bs {{ number_format((float) $billingBreakdown['sewer_fixed_charge'], 2) }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Mora anterior</p>
                        <p class="theme-text mt-2 text-lg font-semibold text-slate-900">Bs {{ number_format((float) $billingBreakdown['mora_saldo_anterior'], 2) }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl bg-rose-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-rose-500">Corte / reconexion</p>
                        <p class="mt-2 text-lg font-semibold text-rose-700">Bs {{ number_format((float) $billingBreakdown['cutoff_penalty'], 2) }}</p>
                    </div>
                    <div class="theme-soft rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total facturado</p>
                        <p class="theme-text mt-2 text-lg font-bold text-slate-900">Bs {{ number_format((float) $factura->total, 2) }}</p>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-emerald-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">Pagado</p>
                        <p class="mt-2 text-lg font-semibold text-emerald-800">Bs {{ number_format((float) $resumenCobro['pagado'], 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-blue-500">Pendiente</p>
                        <p class="mt-2 text-lg font-bold text-blue-800">Bs {{ number_format((float) $resumenCobro['pendiente'], 2) }}</p>
                    </div>
                </div>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-slate-900">Cobros registrados</h2>
                    <p class="text-sm text-slate-500">Cada cobro queda guardado en facturacion y disponible para PDF o impresion.</p>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Metodo</th>
                                <th class="px-4 py-3">Monto</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Referencia</th>
                                <th class="px-4 py-3">Empleado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($factura->cobros as $cobro)
                                <tr>
                                    <td class="px-4 py-3">{{ optional($cobro->fecha_cobro)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $cobro->metodoPago?->nombre ?: 'Sin metodo' }}</td>
                                    <td class="px-4 py-3">Bs {{ number_format((float) $cobro->monto_pagado, 2) }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($cobro->estado) }}</td>
                                    <td class="px-4 py-3">{{ $cobro->comprobante ?: 'Sin referencia' }}</td>
                                    <td class="px-4 py-3">{{ $cobro->empleado?->persona?->nombre_completo ?: 'Sin empleado' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        Esta factura no tiene cobros registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
