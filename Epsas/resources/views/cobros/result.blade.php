@extends('layouts.app')

@section('title', 'Pago registrado - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Secretaria',
            'headerTitle' => 'Pago registrado',
            'companyName' => $sharedCompanySettings['company_name'] ?? 'EPSAS EL PORTILLO',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Pago completado</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">Cobro registrado correctamente</h2>
                        <p class="mt-2 text-sm text-slate-500">Ya puedes descargar o imprimir el recibo sin volver a la vista de facturacion.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:justify-end">
                        <a href="{{ route('secretaria.operaciones.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-100">
                            Volver a atencion de caja
                        </a>
                        <a href="{{ route('secretaria.cobros.show', $paymentResult['socio_id']) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700">
                            Registrar otro pago
                        </a>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Socio</p>
                    <p class="theme-text mt-3 text-lg font-bold text-slate-900">{{ $paymentResult['socio_nombre'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Metodo</p>
                    <p class="theme-text mt-3 text-lg font-bold text-slate-900">{{ $paymentResult['metodo_pago'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Total pagado</p>
                    <p class="theme-text mt-3 text-2xl font-bold text-emerald-700">Bs {{ number_format((float) $paymentResult['total_pagado'], 2) }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Cambio</p>
                    <p class="theme-text mt-3 text-2xl font-bold text-slate-900">Bs {{ number_format((float) $paymentResult['cambio'], 2) }}</p>
                </article>
            </section>

            @if ($primaryFactura)
                <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Acciones rapidas</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Recibo principal {{ $primaryFactura->numero_factura }}</h2>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('secretaria.facturas.pdf', $primaryFactura) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                Descargar PDF
                            </a>
                            @if (auth()->user()?->cachedRoleNames()?->contains('administrador'))
                                <a href="{{ route('tecnico.reconexiones.index', ['socio' => $paymentResult['socio_id']]) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                    Sugerir reconexion
                                </a>
                            @endif
                            <a href="{{ route('secretaria.facturas.print', $primaryFactura) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Imprimir recibo
                            </a>
                            <a href="{{ route('secretaria.facturas.show', $primaryFactura) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Ver detalle
                            </a>
                        </div>
                    </div>
                </section>
            @endif

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Factura</th>
                                <th class="px-5 py-4">Periodo</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Total</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach ($facturas as $factura)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $factura->numero_factura }}</td>
                                    <td class="px-5 py-4">{{ $factura->periodo?->nombre ?: 'Sin periodo' }}</td>
                                    <td class="px-5 py-4">{{ $factura->socio?->persona?->nombre_completo }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">Bs {{ number_format((float) $factura->total, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('secretaria.facturas.pdf', $factura) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                PDF
                                            </a>
                                            <a href="{{ route('secretaria.facturas.print', $factura) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                Imprimir
                                            </a>
                                            <a href="{{ route('secretaria.facturas.show', $factura) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                Ver
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
