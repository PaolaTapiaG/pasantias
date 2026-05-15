<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Consumos</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Mayor consumo</h2>
        </div>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $consumos->count() }}</span>
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($consumos->take(8) as $consumo)
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $consumo['socio'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $consumo['codigo'] }} · {{ $consumo['facturas'] }} facturas</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">{{ number_format((float) $consumo['consumo_total'], 2) }} m3</p>
                        <p class="mt-1 text-xs text-slate-500">Bs {{ number_format((float) $consumo['monto_total'], 2) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No hay facturas para el periodo seleccionado.</p>
        @endforelse
    </div>
</section>
