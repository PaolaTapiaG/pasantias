<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Morosidad</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Socios con saldo pendiente</h2>
        </div>
        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $morosos->count() }}</span>
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($morosos->take(8) as $moroso)
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $moroso['socio'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $moroso['codigo'] }} · {{ $moroso['facturas_pendientes'] }} pendientes</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">Bs {{ number_format((float) $moroso['saldo'], 2) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Ultima: {{ $moroso['ultima_factura'] ?: 'Sin fecha' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No hay socios morosos por ahora.</p>
        @endforelse
    </div>
</section>
