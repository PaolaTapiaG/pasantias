@extends('layouts.app')

@section('title', 'Registrar pago - EPSAS')

@section('content')
@php
    $selectedSocioJson = json_encode($selectedSocio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.16),_transparent_18%),linear-gradient(180deg,_#f8fbff_0%,_#eef5ff_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-700">Pagos</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar pago de {{ $selectedSocio['nombre_completo'] }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Aqui se registra el cobro y se generan QR separados para cuota y multa.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('secretaria.cobros.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Volver a deudores
                    </a>
                    <a href="{{ route('secretaria.facturas.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Ver facturacion
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                <section
                    class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm"
                    data-payment-view
                    data-selected-socio='{{ $selectedSocioJson }}'
                    data-old-invoices='@json(old('factura_ids', []))'
                    data-old-amount='@json(old('cantidad_pagada', ''))'
                >
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Socio</p>
                                <p class="mt-1 text-base font-semibold text-slate-900">{{ $selectedSocio['nombre_completo'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $selectedSocio['codigo_display'] }} | CI {{ $selectedSocio['cedula_identidad'] ?: 'Sin registro' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Contacto</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $selectedSocio['telefono'] ?: 'Sin telefono' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $selectedSocio['email'] ?: 'Sin correo' }}</p>
                            </div>
                        </div>
                    </div>

                    @if (count($selectedSocio['facturas_pendientes']) === 0)
                        <div class="mt-6 rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-4 py-6 text-sm text-emerald-700">
                            Este socio ya no tiene pagos pendientes. Puedes revisar su historial a la derecha o volver a la lista de deudores.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('secretaria.cobros.store', $selectedSocio['id_socio']) }}" class="mt-6 space-y-6">
                        @csrf

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha de pago</label>
                                <input
                                    type="date"
                                    name="fecha_pago"
                                    value="{{ old('fecha_pago', $selectedDate) }}"
                                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                >
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Tipo de pago</label>
                                <select
                                    name="id_metodo_pago"
                                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                >
                                    @foreach ($metodosPago as $metodo)
                                        <option value="{{ $metodo->id_metodo_pago }}" @selected((string) old('id_metodo_pago', optional($metodosPago->first())->id_metodo_pago) === (string) $metodo->id_metodo_pago)>
                                            {{ $metodo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Referencia</label>
                                <input
                                    type="text"
                                    name="comprobante"
                                    value="{{ old('comprobante') }}"
                                    placeholder="REC, QR, transferencia..."
                                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                >
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50/90">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                            <th class="px-4 py-4">Pagar</th>
                                            <th class="px-4 py-4">Fecha</th>
                                            <th class="px-4 py-4">Descripcion</th>
                                            <th class="px-4 py-4">Cuota</th>
                                            <th class="px-4 py-4">Multa</th>
                                            <th class="px-4 py-4">Pendiente</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                        @foreach ($selectedSocio['facturas_pendientes'] as $factura)
                                            <tr>
                                                <td class="px-4 py-4">
                                                    <label class="inline-flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            name="factura_ids[]"
                                                            value="{{ $factura['id_factura'] }}"
                                                            data-invoice-checkbox
                                                            class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                                        >
                                                        <span class="font-semibold text-slate-900">{{ $factura['numero_factura'] }}</span>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-4">{{ $factura['fecha_emision'] }}</td>
                                                <td class="px-4 py-4">
                                                    <p class="font-medium text-slate-900">{{ $factura['descripcion'] }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $factura['periodo'] }} | {{ ucfirst($factura['estado']) }}</p>
                                                </td>
                                                <td class="px-4 py-4">Bs {{ number_format((float) $factura['subtotal'], 2) }}</td>
                                                <td class="px-4 py-4 text-rose-600">Bs {{ number_format((float) $factura['recargo_mora'], 2) }}</td>
                                                <td class="px-4 py-4 font-semibold text-slate-900">Bs {{ number_format((float) $factura['pendiente'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1fr_0.78fr]">
                            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Detalle seleccionado</h3>
                                <div data-selected-items class="mt-4 space-y-3 hidden"></div>
                                <div data-empty-state class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                                    Selecciona las cuotas o multas que el socio desea pagar en este momento.
                                </div>
                            </div>

                            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-900 p-5 text-white shadow-sm">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200">Resumen del pago</h3>
                                <div class="mt-5 space-y-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-300">Subtotal</span>
                                        <span>Bs <span data-summary-subtotal>0.00</span></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-300">Multas</span>
                                        <span>Bs <span data-summary-mora>0.00</span></span>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-white/10 pt-4 text-base font-semibold">
                                        <span>Total a cobrar</span>
                                        <span>Bs <span data-summary-total>0.00</span></span>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <label class="mb-2 block text-sm font-semibold text-white">Cantidad pagada</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="cantidad_pagada"
                                        data-paid-amount
                                        class="h-12 w-full rounded-2xl border border-white/15 bg-white/10 px-4 text-sm text-white outline-none placeholder:text-slate-300 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-500/20"
                                        placeholder="0.00"
                                    >
                                </div>

                                <div class="mt-4 flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm">
                                    <span class="text-slate-300">Cambio</span>
                                    <span class="font-semibold">Bs <span data-summary-change>0.00</span></span>
                                </div>

                                <button
                                    type="submit"
                                    data-submit-button
                                    class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300"
                                    @disabled(count($selectedSocio['facturas_pendientes']) === 0)
                                >
                                    Guardar pago
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">QR personalizados</h2>
                        <p class="mt-2 text-sm text-slate-500">Separamos el QR de cuota y el QR de multa para que el socio pague solo lo que corresponda.</p>

                        <div class="mt-5 space-y-5">
                            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">QR cuota de agua</p>
                                        <p class="mt-1 text-xs text-slate-500">Monto personalizado segun las cuotas pendientes.</p>
                                    </div>
                                    <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">Bs {{ number_format((float) $qrCuotaMonto, 2) }}</span>
                                </div>
                                @if ($qrCuotaSvg)
                                    <div class="mt-4 flex justify-center rounded-2xl bg-white p-4">{!! $qrCuotaSvg !!}</div>
                                @else
                                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">No hay monto de cuota pendiente.</div>
                                @endif
                            </div>

                            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">QR multa o mora</p>
                                        <p class="mt-1 text-xs text-slate-500">Si desea pagar la multa aparte, usa este QR.</p>
                                    </div>
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Bs {{ number_format((float) $qrMoraMonto, 2) }}</span>
                                </div>
                                @if ($qrMoraSvg)
                                    <div class="mt-4 flex justify-center rounded-2xl bg-white p-4">{!! $qrMoraSvg !!}</div>
                                @else
                                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">Este socio no tiene multa pendiente.</div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-600">Historial</p>
                                <h2 class="mt-1 text-lg font-semibold text-slate-900">Ultimos cobros del socio</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $cobrosSocio->count() }}</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($cobrosSocio as $cobro)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $cobro->factura?->numero_factura ?: 'Sin factura' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ optional($cobro->fecha_cobro)->format('d/m/Y') }} | {{ $cobro->metodoPago?->nombre ?: 'Sin metodo' }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Bs {{ number_format((float) $cobro->monto_pagado, 2) }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                    Este socio aun no tiene cobros registrados.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </div>
        </main>
    </div>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-payment-view]');

        if (!root) {
            return;
        }

        const selectedSocio = JSON.parse(root.dataset.selectedSocio);
        const invoices = selectedSocio.facturas_pendientes ?? [];
        const oldInvoices = root.dataset.oldInvoices ? JSON.parse(root.dataset.oldInvoices) : [];
        const oldAmount = root.dataset.oldAmount ? JSON.parse(root.dataset.oldAmount) : '';
        const paidAmountInput = root.querySelector('[data-paid-amount]');
        const subtotalNode = root.querySelector('[data-summary-subtotal]');
        const moraNode = root.querySelector('[data-summary-mora]');
        const totalNode = root.querySelector('[data-summary-total]');
        const changeNode = root.querySelector('[data-summary-change]');
        const selectedItemsNode = root.querySelector('[data-selected-items]');
        const emptyStateNode = root.querySelector('[data-empty-state]');
        const submitButton = root.querySelector('[data-submit-button]');
        const checkboxes = Array.from(root.querySelectorAll('[data-invoice-checkbox]'));

        const formatCurrency = (value) => Number(value || 0).toFixed(2);
        const selectedSet = new Set(oldInvoices.map(String));

        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectedSet.has(String(checkbox.value));
        });

        paidAmountInput.value = oldAmount;

        const render = () => {
            const selectedItems = invoices.filter((invoice) =>
                checkboxes.some((checkbox) => checkbox.checked && String(checkbox.value) === String(invoice.id_factura))
            );

            const subtotal = selectedItems.reduce((carry, item) => carry + Number(item.subtotal || 0), 0);
            const moraTotal = selectedItems.reduce((carry, item) => carry + Number(item.recargo_mora || 0), 0);
            const total = selectedItems.reduce((carry, item) => carry + Number(item.pendiente || 0), 0);
            const paidAmount = Number(paidAmountInput.value || 0);
            const change = Math.max(0, paidAmount - total);

            subtotalNode.textContent = formatCurrency(subtotal);
            moraNode.textContent = formatCurrency(moraTotal);
            totalNode.textContent = formatCurrency(total);
            changeNode.textContent = formatCurrency(change);

            selectedItemsNode.innerHTML = selectedItems.map((item) => `
                <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-sm">
                    <div>
                        <p class="font-semibold text-slate-900">${item.numero_factura}</p>
                        <p class="mt-1 text-xs text-slate-500">${item.descripcion}</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-900">Bs ${formatCurrency(item.pendiente)}</p>
                </div>
            `).join('');

            selectedItemsNode.classList.toggle('hidden', selectedItems.length === 0);
            emptyStateNode.classList.toggle('hidden', selectedItems.length > 0);
            submitButton.disabled = selectedItems.length === 0 || total <= 0 || paidAmount < total;
        };

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', render));
        paidAmountInput.addEventListener('input', render);
        render();
    })();
</script>
@endsection
