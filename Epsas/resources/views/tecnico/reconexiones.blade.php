@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Servicio',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Cortes de servicio', 'href' => route('tecnico.cortes.index'), 'variant' => 'soft'],
    ],
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Solicitar reconexion</h2>
            <form method="POST" action="{{ route('tecnico.reconexiones.store') }}" class="mt-6 grid gap-4" data-tech-order-form="reconexion">
                @csrf
                <input
                    type="search"
                    data-socio-catalog-search
                    data-url="{{ route('api.tecnico.socios.catalogo', [], false) }}"
                    placeholder="Buscar socio, CI o zona"
                    class="theme-soft h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none"
                >
                <select name="id_socio" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none" data-socio-select>
                    <option value="">Socio reconectado</option>
                    @foreach ($socios as $socio)
                        @php($signal = $billingSignals->get($socio->id_socio))
                        <option
                            value="{{ $socio->id_socio }}"
                            data-zona="{{ $socio->sector?->nombre }}"
                            data-lat="{{ $socio->latitud }}"
                            data-lng="{{ $socio->longitud }}"
                            data-referencia="{{ $signal && $signal['ultima_fecha_pago'] ? 'Pago validado el ' . \Illuminate\Support\Carbon::parse($signal['ultima_fecha_pago'])->format('d/m/Y') . ($signal['ultimo_cobro_id'] ? ' · Cobro #' . $signal['ultimo_cobro_id'] : '') : '' }}"
                            data-resumen="{{ $signal ? 'Pendiente Bs ' . number_format($signal['total_pendiente'], 2) . ' · último cobro ' . ($signal['ultimo_cobro_id'] ?: 'sin registro') : 'Sin datos comerciales' }}"
                            @selected(old('id_socio', $selectedSocioId) == $socio->id_socio)
                        >
                            {{ $socio->codigo_display }} · {{ $socio->persona?->nombre_completo }}
                        </option>
                    @endforeach
                </select>
                <input name="referencia" value="{{ old('referencia') }}" placeholder="Comprobante o validacion" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none" data-referencia-input>
                <div class="grid gap-4 md:grid-cols-2">
                    <input type="date" name="fecha_programada" value="{{ old('fecha_programada', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <input name="zona" value="{{ old('zona') }}" placeholder="Zona o barrio" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none" data-zona-input>
                <div class="grid gap-4 md:grid-cols-2">
                    <input name="coord_x" value="{{ old('coord_x') }}" placeholder="Latitud de reconexion" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="coord_y" value="{{ old('coord_y') }}" placeholder="Longitud de reconexion" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div class="rounded-[1.75rem] border border-slate-200 p-3 dark:border-slate-800">
                    <x-geo-map
                        id="reconexion-map"
                        :lat="(float) old('coord_x', -21.5355)"
                        :lng="(float) old('coord_y', -64.7296)"
                        :zoom="16"
                        height="280px"
                        :picker="true"
                        lat-input="[data-tech-order-form='reconexion'] [name='coord_x']"
                        lng-input="[data-tech-order-form='reconexion'] [name='coord_y']"
                    />
                    <p class="mt-3 text-xs font-semibold text-slate-500">Confirma el punto donde se ejecutara la reconexion.</p>
                </div>
                <textarea name="descripcion" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Observaciones de reconexion">{{ old('descripcion') }}</textarea>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-socio-summary>
                    Selecciona un socio para recuperar automáticamente la última señal de pago registrada.
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    La solicitud queda pendiente hasta que secretaria o administración la aprueben.
                </div>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Enviar solicitud</button>
            </form>
        </article>

        <article class="space-y-6">
            <div class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Notificaciones para reconexion</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($attentionQueue as $candidate)
                        <a href="{{ route('tecnico.reconexiones.index', ['socio' => $candidate['id_socio']]) }}" class="block rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 transition hover:bg-emerald-100">
                            <p class="text-sm font-semibold text-slate-900">{{ $candidate['codigo'] }} · {{ $candidate['nombre'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $candidate['zona'] }}</p>
                            <p class="mt-2 text-sm text-emerald-700">{{ $candidate['motivo'] }}</p>
                            @if ($candidate['ultima_fecha_pago'])
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Pago validado el {{ \Illuminate\Support\Carbon::parse($candidate['ultima_fecha_pago'])->format('d/m/Y') }}
                                    @if ($candidate['ultimo_cobro_id'])
                                        · Cobro #{{ $candidate['ultimo_cobro_id'] }}
                                    @endif
                                </p>
                            @endif
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                            Aun no hay usuarios listos para reconexion.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Pendientes del dia</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentOrders as $order)
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-900/60 dark:text-slate-300">
                        {{ $order->socio?->codigo_display ?? 'Sin socio' }} · {{ $order->referencia ?: 'Sin referencia' }} · {{ ucfirst($order->estado) }}
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">Aun no hay reconexiones registradas.</div>
                @endforelse
            </div>
            </div>
        </article>
    </section>

    @push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-tech-order-form="reconexion"]');
            if (!form) return;

            const socioSelect = form.querySelector('[data-socio-select]');
            const referenciaInput = form.querySelector('[data-referencia-input]');
            const zonaInput = form.querySelector('[data-zona-input]');
            const summary = form.querySelector('[data-socio-summary]');
            const search = form.querySelector('[data-socio-catalog-search]');
            const latInput = form.querySelector('[name="coord_x"]');
            const lngInput = form.querySelector('[name="coord_y"]');
            const map = document.getElementById('reconexion-map');

            const escapeOption = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);

            const sync = () => {
                const selected = socioSelect?.selectedOptions?.[0];
                if (!selected) return;

                if (zonaInput && !zonaInput.value && selected.dataset.zona) {
                    zonaInput.value = selected.dataset.zona;
                }

                if (referenciaInput && !referenciaInput.value && selected.dataset.referencia) {
                    referenciaInput.value = selected.dataset.referencia;
                }

                if (summary) {
                    summary.textContent = selected.dataset.resumen || 'Sin resumen comercial disponible.';
                }

                if (selected.dataset.lat && selected.dataset.lng && latInput && lngInput) {
                    if (!latInput.value) latInput.value = selected.dataset.lat;
                    if (!lngInput.value) lngInput.value = selected.dataset.lng;
                    map?.dispatchEvent(new CustomEvent('epsas:geo:set', {
                        detail: {
                            lat: latInput.value,
                            lng: lngInput.value,
                        },
                    }));
                }
            };

            const loadSocios = async (term) => {
                if (!search || !socioSelect || term.length < 2) return;

                const response = await fetch(`${search.dataset.url}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) return;

                const data = await response.json();
                const placeholder = socioSelect.querySelector('option[value=""]')?.textContent || 'Socio reconectado';
                socioSelect.innerHTML = `<option value="">${escapeOption(placeholder)}</option>` + (data.items || []).map((item) => `
                    <option
                        value="${escapeOption(item.value)}"
                        data-zona="${escapeOption(item.zona)}"
                        data-lat="${escapeOption(item.latitud)}"
                        data-lng="${escapeOption(item.longitud)}"
                        data-referencia="${escapeOption(item.referencia_reconexion)}"
                        data-resumen="${escapeOption(item.resumen)}"
                    >${escapeOption(item.label)}</option>
                `).join('');
                sync();
            };

            let searchTimer = null;
            search?.addEventListener('input', (event) => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => loadSocios(event.target.value.trim()), 280);
            });

            socioSelect?.addEventListener('change', sync);
            sync();
        })();
    </script>
    @endpush
@endcomponent
