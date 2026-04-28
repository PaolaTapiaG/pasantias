@extends('layouts.app')

@section('title', 'Buscar deudores - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.16),_transparent_18%),linear-gradient(180deg,_#f8fbff_0%,_#eef5ff_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-700">Pagos</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Socios deudores</h1>
                    <p class="mt-2 text-sm text-slate-500">Selecciona un socio desde la tabla para abrir su ventana de pago.</p>
                </div>
                <a href="{{ route('secretaria.facturas.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Ver facturacion
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Usuarios con pagos pendientes</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $resumen['socios_con_pendientes'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Multas pendientes</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">Bs {{ number_format((float) $resumen['multas_pendientes'], 2) }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Monto total por recaudar</p>
                    <p class="mt-3 text-3xl font-bold text-cyan-700">Bs {{ number_format((float) $resumen['monto_total_pendiente'], 2) }}</p>
                </article>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_auto]">
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $search }}"
                        placeholder="Buscar por nombre, CI o numero de socio"
                        class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                    >
                    <button class="h-12 rounded-2xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Buscar
                    </button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Codigo</th>
                                <th class="px-5 py-4">CI</th>
                                <th class="px-5 py-4">Pendientes</th>
                                <th class="px-5 py-4">Cuotas</th>
                                <th class="px-5 py-4">Multas</th>
                                <th class="px-5 py-4">Total</th>
                                <th class="px-5 py-4 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($socios as $socio)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $socio['nombre_completo'] }}</td>
                                    <td class="px-5 py-4">{{ $socio['codigo_display'] }}</td>
                                    <td class="px-5 py-4">{{ $socio['cedula_identidad'] ?: 'Sin registro' }}</td>
                                    <td class="px-5 py-4">{{ count($socio['facturas_pendientes']) }}</td>
                                    <td class="px-5 py-4">Bs {{ number_format((float) $socio['subtotal_pendiente'], 2) }}</td>
                                    <td class="px-5 py-4 text-rose-600">Bs {{ number_format((float) $socio['recargos_pendientes'], 2) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">Bs {{ number_format((float) $socio['total_pendiente'], 2) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('secretaria.cobros.show', $socio['id_socio']) }}" class="rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100">
                                            Abrir pago
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron socios con pagos pendientes para este filtro.
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
