<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Cobranza</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Pagos registrados</h2>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $cobranza->count() }}</span>
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($cobranza->take(8) as $cobro)
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $cobro->factura?->socio?->persona?->nombre_completo }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($cobro->fecha_cobro)->format('d/m/Y') }} · {{ $cobro->metodoPago?->nombre }}</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-900">Bs {{ number_format((float) $cobro->monto_pagado, 2) }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No hay cobros en el rango seleccionado.</p>
        @endforelse
    </div>
</section>
