@extends('layouts.app')

@section('title', 'Operacion de secretaria - EPSAS')

@section('content')
<div class="page-background min-h-screen bg-slate-50">
    @include($isAdmin ? 'slideboard.sidebaradmin' : 'slideboard.sidebarsec')

    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => $isAdmin ? 'Administracion' : 'Secretaria',
            'headerTitle' => 'Atencion, caja y derivaciones',
        ])

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
            @endif
            @if (session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">{{ session('error') }}</div>
            @endif

            <section class="grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
                <div class="rounded-lg border border-emerald-200 bg-emerald-700 p-6 text-white shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-100">Turno diario</p>
                    <h2 class="mt-3 text-3xl font-black">Caja {{ ucfirst($cash?->estado ?? 'sin abrir') }}</h2>
                    <p class="mt-2 text-sm text-emerald-50">{{ $employee->persona?->nombre_completo }} · {{ now()->translatedFormat('d F Y') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if (! $cash)
                            <form method="POST" action="{{ route('secretaria.operaciones.caja.abrir') }}">
                                @csrf
                                <button class="rounded-lg bg-white px-5 py-3 text-sm font-black text-emerald-800">Abrir caja</button>
                            </form>
                        @elseif ($cash->estado === 'abierta')
                            <form method="POST" action="{{ route('secretaria.operaciones.caja.cerrar') }}" class="flex flex-1 flex-col gap-3 sm:flex-row">
                                @csrf
                                @method('PATCH')
                                <input name="observaciones" placeholder="Observaciones del cierre" class="min-w-0 flex-1 rounded-lg border border-white/30 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-emerald-100">
                                <button class="rounded-lg bg-white px-5 py-3 text-sm font-black text-emerald-800">Cerrar caja</button>
                            </form>
                        @elseif ($isAdmin && $cash->estado === 'cerrada')
                            <form method="POST" action="{{ route('secretaria.operaciones.caja.revisar', $cash) }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-lg bg-white px-5 py-3 text-sm font-black text-emerald-800">Marcar revisada</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @foreach ([
                        ['label' => 'Cobros', 'value' => $cashSummary['cantidad_cobros']],
                        ['label' => 'Efectivo', 'value' => 'Bs '.number_format($cashSummary['efectivo'], 2)],
                        ['label' => 'QR', 'value' => 'Bs '.number_format($cashSummary['qr'], 2)],
                        ['label' => 'Total', 'value' => 'Bs '.number_format($cashSummary['total'], 2)],
                    ] as $stat)
                        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-xl font-black text-slate-950">{{ $stat['value'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-6 rounded-lg border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Atencion de caja</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Cobros y facturas pendientes</h2>
                        <p class="mt-1 text-sm text-slate-500">Busca por socio, CI o medidor. El cobro abre el flujo normal y al finalizar permite imprimir, descargar o enviar la factura.</p>
                    </div>
                    <form method="GET" action="{{ route('secretaria.operaciones.index') }}" class="grid w-full gap-3 sm:grid-cols-[1fr_auto] xl:max-w-xl">
                        <input
                            name="buscar_pago"
                            value="{{ $cajaSearch }}"
                            placeholder="Buscar socio, CI o medidor"
                            class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                        >
                        <button class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-black text-white hover:bg-emerald-800">Buscar</button>
                    </form>
                </div>

                @if ($cajaSearchTooShort)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                        Escribe al menos 2 caracteres para buscar sin hacer consultas innecesarias.
                    </div>
                @endif

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-500">Socios con deuda</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $cajaResumen['socios_con_pendientes'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-500">Facturas pendientes</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $cajaResumen['facturas_pendientes'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-700">Total por cobrar</p>
                        <p class="mt-2 text-2xl font-black text-emerald-800">Bs {{ number_format((float) $cajaResumen['total_pendiente'], 2) }}</p>
                    </article>
                </div>

                <div class="mt-5 grid gap-5 xl:grid-cols-[0.95fr_1.05fr]">
                    <article class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <h3 class="text-sm font-black text-slate-900">Socios para cobrar</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($deudoresCaja as $deudor)
                                <div class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-950">{{ $deudor->nombre_completo ?: 'Sin nombre' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">
                                            {{ $deudor->numero_socio ?: 'Sin codigo' }} · CI {{ $deudor->cedula_identidad ?: 'sin registro' }} · Medidor {{ $deudor->numero_medidor ?: 'sin medidor' }}
                                        </p>
                                        <p class="mt-1 text-sm font-semibold text-emerald-700">{{ $deudor->facturas_pendientes_count }} facturas · Bs {{ number_format((float) $deudor->total_pendiente, 2) }}</p>
                                    </div>
                                    <a href="{{ route('secretaria.cobros.show', $deudor->id_socio) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-black text-white hover:bg-emerald-800">
                                        Abrir cobro
                                    </a>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-center text-sm text-slate-500">No hay socios con facturas pendientes para este filtro.</div>
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <h3 class="text-sm font-black text-slate-900">Facturas pendientes</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">Factura</th>
                                        <th class="px-4 py-3">Socio</th>
                                        <th class="px-4 py-3">Vence</th>
                                        <th class="px-4 py-3">Saldo</th>
                                        <th class="px-4 py-3 text-right">Accion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @forelse ($facturasPendientesCaja as $factura)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-slate-950">
                                                {{ $factura->numero_factura }}
                                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $factura->periodo ?: 'Sin periodo' }} · {{ ucfirst($factura->estado) }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ $factura->socio_nombre ?: 'Sin socio' }}
                                                <p class="mt-1 text-xs text-slate-500">{{ $factura->numero_socio ?: 'Sin codigo' }} · {{ $factura->numero_medidor ?: 'Sin medidor' }}</p>
                                            </td>
                                            <td class="px-4 py-3">{{ $factura->fecha_fin_cobro ? \Carbon\Carbon::parse($factura->fecha_fin_cobro)->format('d/m/Y') : 'Sin fecha' }}</td>
                                            <td class="px-4 py-3 font-black text-emerald-700">Bs {{ number_format((float) $factura->saldo, 2) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('secretaria.cobros.show', $factura->id_socio) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-100">
                                                    Cobrar
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No hay facturas pendientes para mostrar.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Atencion al socio</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Registrar solicitud o reclamo</h2>

                    <form method="POST" action="{{ route('secretaria.operaciones.solicitudes.store') }}" class="mt-5 grid gap-4">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select name="tipo" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm" required>
                                <option value="reclamo_consumo">Reclamo de consumo</option>
                                <option value="fuga">Fuga</option>
                                <option value="corte">Corte de agua</option>
                                <option value="lectura_incorrecta">Lectura incorrecta</option>
                                <option value="inspeccion">Solicitud de inspeccion</option>
                                <option value="cambio_titular">Cambio de titular</option>
                                <option value="otro">Otro</option>
                            </select>
                            <select name="prioridad" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm" required>
                                <option value="media">Prioridad media</option>
                                <option value="alta">Prioridad alta</option>
                                <option value="baja">Prioridad baja</option>
                            </select>
                        </div>
                        <select name="id_socio" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                            <option value="">Socio no identificado</option>
                            @foreach ($socios as $socio)
                                <option value="{{ $socio->id_socio }}">{{ $socio->numero_socio }} · {{ $socio->nombre }} · {{ $socio->zona }}</option>
                            @endforeach
                        </select>
                        <input name="zona" value="{{ old('zona') }}" placeholder="Zona o barrio" class="h-11 rounded-lg border border-slate-200 px-3 text-sm" required>
                        <textarea name="descripcion" rows="4" placeholder="Describe la consulta, reclamo o solicitud" class="rounded-lg border border-slate-200 px-3 py-3 text-sm" required>{{ old('descripcion') }}</textarea>
                        <label class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                            <input type="checkbox" name="derivar" value="1" class="h-4 w-4">
                            Derivar el caso a un tecnico
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select name="id_tecnico" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                                <option value="">Seleccionar tecnico</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id_empleado }}">{{ $technician->nombre }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="fecha_programada" min="{{ today()->toDateString() }}" class="h-11 rounded-lg border border-slate-200 px-3 text-sm">
                        </div>
                        <button class="rounded-lg bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Registrar y derivar</button>
                    </form>
                </article>

                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Seguimiento</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950">Solicitudes recientes</h2>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $requests->count() }}</span>
                    </div>
                    <div class="mt-5 grid gap-3">
                        @forelse ($requests as $item)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-slate-950">#{{ $item->id_incidencia }} · {{ ucfirst(str_replace('_', ' ', $item->tipo)) }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $item->socio_nombre ?: 'Sin socio' }} · {{ $item->zona }} · {{ $item->responsable_nombre ?: 'Sin responsable' }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700">{{ ucfirst(str_replace('_', ' ', $item->estado)) }}</span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item->descripcion }}</p>
                                <form method="POST" action="{{ route('secretaria.operaciones.solicitudes.update', $item->id_incidencia) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="estado" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs">
                                        @foreach (['abierta', 'en_proceso', 'cerrada'] as $status)
                                            <option value="{{ $status }}" @selected($item->estado === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-black text-white">Actualizar</button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">No hay solicitudes registradas.</div>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-2">
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Coordinacion tecnica</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Ordenes derivadas</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($technicalOrders as $order)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-slate-950">#{{ $order->id_orden }} · {{ ucfirst($order->tipo) }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $order->tecnico_nombre ?: 'Sin tecnico' }} · {{ $order->socio_nombre ?: 'Sin socio' }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-sky-700">{{ ucfirst(str_replace('_', ' ', $order->estado)) }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $order->zona }} · {{ $order->referencia }}</p>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">No hay ordenes derivadas.</div>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Comunicados</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Preparar aviso para aprobacion</h2>
                    <form method="POST" action="{{ route('secretaria.operaciones.comunicados.store') }}" class="mt-5 grid gap-3">
                        @csrf
                        <input name="titulo" placeholder="Titulo del comunicado" class="h-11 rounded-lg border border-slate-200 px-3 text-sm" required>
                        <textarea name="contenido" rows="3" placeholder="Contenido del aviso" class="rounded-lg border border-slate-200 px-3 py-3 text-sm" required></textarea>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="datetime-local" name="publicar_desde" class="h-11 rounded-lg border border-slate-200 px-3 text-sm">
                            <input type="datetime-local" name="publicar_hasta" class="h-11 rounded-lg border border-slate-200 px-3 text-sm">
                        </div>
                        <label class="flex items-center gap-3 text-sm font-semibold text-slate-700"><input type="checkbox" name="importante" value="1"> Marcar como importante</label>
                        <button class="rounded-lg bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700">Enviar para aprobacion</button>
                    </form>

                    <div class="mt-6 grid gap-3">
                        @forelse ($communications as $communication)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-black text-slate-950">{{ $communication->titulo }}</p>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-amber-700">{{ ucfirst(str_replace('_', ' ', $communication->estado)) }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $communication->contenido }}</p>
                                @if ($isAdmin && $communication->estado === 'pendiente_aprobacion')
                                    <form method="POST" action="{{ route('secretaria.operaciones.comunicados.update', $communication) }}" class="mt-3">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="estado" value="publicado">
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white">Aprobar y publicar</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No hay comunicados preparados.</div>
                        @endforelse
                    </div>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection
