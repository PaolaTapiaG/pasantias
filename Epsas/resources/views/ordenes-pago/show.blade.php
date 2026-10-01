@extends('layouts.app')

@section('title', 'Revision de pago QR - EPSAS')

@section('content')
@php
    $proofIsImage = $orden->comprobante_path && \Illuminate\Support\Str::endsWith(strtolower($orden->comprobante_path), ['.jpg', '.jpeg', '.png', '.webp']);
    $badge = match ($orden->estado) {
        'aprobada' => 'bg-emerald-100 text-emerald-700',
        'en_revision' => 'bg-blue-100 text-blue-700',
        'rechazada' => 'bg-rose-100 text-rose-700',
        'pendiente' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Revision de pago',
            'headerTitle' => $orden->codigo,
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success') || session('error') || $errors->any())
                <div class="mb-6 rounded-2xl border px-4 py-3 text-sm font-semibold {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                    {{ session('success') ?: session('error') ?: $errors->first() }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Acciones de revision</p>
                        <p class="mt-1 text-sm text-slate-500">Verifica el comprobante antes de registrar cobros y marcar facturas pagadas.</p>
                    </div>
                    <a href="{{ route('secretaria.ordenes-pago.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:w-auto">
                        Volver al listado
                    </a>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.82fr_1.18fr]">
                <div class="space-y-6">
                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500">Total de la orden</p>
                                <p class="mt-2 text-4xl font-black text-slate-950">Bs {{ number_format((float) $orden->total, 2) }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $badge }}">{{ str_replace('_', ' ', ucfirst($orden->estado)) }}</span>
                        </div>
                        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-bold text-slate-500">Socio</dt>
                                <dd class="mt-1 font-extrabold text-slate-900">{{ $orden->socio?->codigo_display ?? $orden->socio?->numero_socio }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-bold text-slate-500">Cliente</dt>
                                <dd class="mt-1 font-extrabold text-slate-900">{{ $orden->socio?->persona?->nombre_completo ?? 'Sin nombre' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-bold text-slate-500">Sector</dt>
                                <dd class="mt-1 font-extrabold text-slate-900">{{ $orden->socio?->sector?->nombre ?? 'Sin sector' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-bold text-slate-500">Creada</dt>
                                <dd class="mt-1 font-extrabold text-slate-900">{{ optional($orden->created_at)->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>

                        @if ($orden->revisor)
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                Revisado por <strong>{{ $orden->revisor?->persona?->nombre_completo ?? 'Administrador' }}</strong>
                                @if ($orden->revisado_en)
                                    el {{ $orden->revisado_en->format('d/m/Y H:i') }}.
                                @endif
                            </div>
                        @endif
                    </article>

                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="theme-text text-xl font-bold text-slate-900">Comprobante del cliente</h2>
                        @if ($orden->comprobante_path)
                            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <dt class="font-bold text-slate-500">Entidad financiera</dt>
                                    <dd class="mt-1 font-extrabold text-slate-900">{{ $orden->entidad_financiera ?: 'No informada' }}</dd>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <dt class="font-bold text-slate-500">Referencia</dt>
                                    <dd class="mt-1 font-extrabold text-slate-900">{{ $orden->comprobante_referencia }}</dd>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <dt class="font-bold text-slate-500">Monto registrado</dt>
                                    <dd class="mt-1 font-extrabold text-slate-900">Bs {{ number_format((float) $orden->comprobante_monto, 2) }}</dd>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <dt class="font-bold text-slate-500">Fecha del pago</dt>
                                    <dd class="mt-1 font-extrabold text-slate-900">{{ optional($orden->comprobante_fecha)->format('d/m/Y') }}</dd>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <dt class="font-bold text-slate-500">Archivo</dt>
                                    <dd class="mt-1">
                                        <a href="{{ $orden->comprobante_url }}" target="_blank" rel="noopener noreferrer" class="font-extrabold text-orange-600 hover:text-orange-700">Abrir comprobante</a>
                                    </dd>
                                </div>
                            </dl>

                            @if ($orden->observaciones_cliente)
                                <div class="mt-4 rounded-2xl bg-orange-50 p-4 text-sm text-orange-900">
                                    <strong>Observacion del cliente:</strong> {{ $orden->observaciones_cliente }}
                                </div>
                            @endif

                            @if ($proofIsImage)
                                <img src="{{ $orden->comprobante_url }}" alt="Comprobante de pago" class="mt-5 max-h-[520px] w-full rounded-[1.5rem] border border-slate-200 object-contain">
                            @endif
                        @else
                            <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm font-semibold text-slate-500">
                                El cliente aun no subio comprobante.
                            </div>
                        @endif
                    </article>
                </div>

                <div class="space-y-6">
                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="theme-text text-xl font-bold text-slate-900">Conceptos que se pagaran</h2>
                        <p class="theme-muted mt-2 text-sm text-slate-500">Al aprobar, solo estos items pasan a pagados. Nada mas.</p>
                        <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Descripcion</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    @foreach ($orden->detalles as $detalle)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-slate-600">{{ $detalle->tipo }}</td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $detalle->descripcion }}</td>
                                            <td class="px-4 py-3 text-right font-black text-slate-950">Bs {{ number_format((float) $detalle->monto, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>

                    @if ($orden->estado === 'aprobada')
                        <article class="theme-card rounded-[2rem] border border-orange-200 bg-orange-50 p-6 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-orange-950">Enviar facturas al socio</h2>
                                    <p class="mt-2 text-sm leading-6 text-orange-900/80">Sin buscar en facturacion: descarga, envia PDF por correo o abre WhatsApp con mensaje listo desde esta orden.</p>
                                </div>
                                <form method="POST" action="{{ route('secretaria.ordenes-pago.send-invoices-email', $orden) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-orange-500 px-5 py-3 text-sm font-black text-white shadow-[0_14px_30px_rgba(249,115,22,.24)] transition hover:bg-orange-600 lg:w-auto">
                                        Enviar PDF por email
                                    </button>
                                </form>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($invoiceActions as $invoice)
                                    <div class="rounded-2xl border border-orange-100 bg-white p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="font-black text-slate-950">{{ $invoice['numero_factura'] }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $invoice['descripcion'] }} - Bs {{ number_format((float) $invoice['monto'], 2) }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ $invoice['pdf_url'] }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                                                    Descargar PDF
                                                </a>
                                                @if ($invoice['whatsapp_url'])
                                                    <a href="{{ $invoice['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 transition hover:bg-emerald-100">
                                                        WhatsApp
                                                    </a>
                                                @else
                                                    <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-400">
                                                        Sin telefono
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-4 text-xs font-semibold leading-5 text-orange-900/70">WhatsApp Web no permite adjuntar automaticamente un PDF sin API Business; descarga el PDF y adjuntalo manualmente en el chat abierto.</p>
                        </article>
                    @endif

                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="grid gap-5 lg:grid-cols-[1fr_220px] lg:items-start">
                            <div>
                                <h2 class="theme-text text-xl font-bold text-slate-900">Checklist anti-fraude</h2>
                                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                                    <p>1. El monto del comprobante debe ser Bs {{ number_format((float) $orden->total, 2) }}.</p>
                                    <p>2. La referencia bancaria no debe estar repetida en otra orden.</p>
                                    <p>3. La entidad financiera debe coincidir con el comprobante real.</p>
                                    <p>4. Si hay duda, rechaza y pide nuevo comprobante antes de registrar cobros.</p>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] bg-[#7b2286] p-4 text-center text-white">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-white/75">QR empresa</p>
                                @if ($qrSvg)
                                    <div class="mt-3 rounded-2xl bg-white p-3 [&_svg]:h-auto [&_svg]:w-full">
                                        {!! $qrSvg !!}
                                    </div>
                                @else
                                    <div class="mt-3 rounded-2xl border border-white/20 bg-white/10 px-4 py-6 text-sm font-semibold text-white">
                                        QR pendiente de configuracion bancaria oficial.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>

                    @if ($orden->estado === 'en_revision')
                        <section class="grid gap-6 lg:grid-cols-2">
                            <form method="POST" action="{{ route('secretaria.ordenes-pago.approve', $orden) }}" class="theme-card rounded-[2rem] border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                                @csrf
                                @method('PATCH')
                                <h2 class="text-xl font-bold text-emerald-950">Aprobar pago</h2>
                                <p class="mt-2 text-sm leading-6 text-emerald-900/80">Esto registrara cobros, marcara las facturas seleccionadas como pagadas y, si corresponde, generara reconexion tecnica.</p>
                                <textarea name="notas_revision" rows="3" placeholder="Nota opcional de revision" class="mt-4 w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm outline-none"></textarea>
                                <button class="mt-4 w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                                    Aprobar y registrar cobro
                                </button>
                            </form>

                            <form method="POST" action="{{ route('secretaria.ordenes-pago.reject', $orden) }}" class="theme-card rounded-[2rem] border border-rose-200 bg-rose-50 p-6 shadow-sm">
                                @csrf
                                @method('PATCH')
                                <h2 class="text-xl font-bold text-rose-950">Rechazar comprobante</h2>
                                <p class="mt-2 text-sm leading-6 text-rose-900/80">Usa esta opcion si el monto, referencia, fecha o captura no coincide con la orden.</p>
                                <textarea name="notas_revision" rows="3" placeholder="Motivo del rechazo" class="mt-4 w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm outline-none" required></textarea>
                                <button class="mt-4 w-full rounded-2xl bg-rose-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-rose-700">
                                    Rechazar
                                </button>
                            </form>
                        </section>
                    @elseif ($orden->notas_revision)
                        <div class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="theme-text text-xl font-bold text-slate-900">Notas de revision</h2>
                            <p class="theme-muted mt-3 text-sm leading-6 text-slate-600">{{ $orden->notas_revision }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
