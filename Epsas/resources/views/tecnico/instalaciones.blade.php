@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Campo',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Ver medidores', 'href' => route('tecnico.medidores.index'), 'variant' => 'soft'],
    ],
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Nueva instalacion</h2>
            <form method="POST" action="{{ route('tecnico.instalaciones.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4 xl:grid-cols-2" data-tech-order-form="instalacion">
                @csrf
                <input
                    type="search"
                    data-socio-catalog-search
                    data-url="{{ route('api.tecnico.socios.catalogo', [], false) }}"
                    placeholder="Buscar usuario, CI o zona"
                    class="theme-soft h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none"
                >
                <select name="id_socio" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none" data-socio-select>
                    <option value="">Seleccionar usuario solicitante</option>
                    @foreach ($socios as $socio)
                        <option value="{{ $socio->id_socio }}" data-zona="{{ $socio->sector?->nombre }}" data-direccion="{{ $socio->direccion }}" data-lat="{{ $socio->latitud }}" data-lng="{{ $socio->longitud }}" @selected(old('id_socio') == $socio->id_socio)>
                            {{ $socio->codigo_display }} · {{ $socio->persona?->nombre_completo }}
                        </option>
                    @endforeach
                </select>
                <div class="space-y-2">
                    <input name="referencia" value="{{ old('referencia') }}" placeholder="Codigo de solicitud o referencia de campo" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <p class="text-xs text-slate-500">Usa el código de la solicitud si existe; si no, escribe una referencia corta para identificar el trabajo.</p>
                </div>
                <input name="zona" value="{{ old('zona') }}" placeholder="Zona" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none" data-zona-input>
                <input name="fecha_ejecucion" type="date" value="{{ old('fecha_ejecucion') }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <input type="date" name="fecha_programada" value="{{ old('fecha_programada', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <select name="prioridad" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <option value="alta">Alta</option>
                    <option value="media" selected>Media</option>
                    <option value="baja">Baja</option>
                </select>
                <input name="coord_x" value="{{ old('coord_x') }}" placeholder="Latitud" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <input name="coord_y" value="{{ old('coord_y') }}" placeholder="Longitud" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <div class="xl:col-span-2 rounded-[1.75rem] border border-slate-200 p-3 dark:border-slate-800">
                    <x-geo-map
                        id="instalacion-map"
                        :lat="(float) old('coord_x', -21.5355)"
                        :lng="(float) old('coord_y', -64.7296)"
                        :zoom="16"
                        height="300px"
                        :picker="true"
                        lat-input="[data-tech-order-form='instalacion'] [name='coord_x']"
                        lng-input="[data-tech-order-form='instalacion'] [name='coord_y']"
                    />
                    <p class="mt-3 text-xs font-semibold text-slate-500">Marca el punto exacto de la nueva instalacion.</p>
                </div>
                <input name="numero_serie" value="{{ old('numero_serie') }}" placeholder="Numero real del medidor" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <input name="lectura_inicial" type="number" min="0" step="0.01" value="{{ old('lectura_inicial') }}" placeholder="Lectura inicial" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <label class="theme-soft xl:col-span-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Foto opcional de instalacion
                    <input type="file" name="evidencia" accept="image/*" class="mt-2 block w-full text-sm">
                </label>
                <textarea name="descripcion" class="theme-soft xl:col-span-2 min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Checklist de conexión, materiales y observaciones">{{ old('descripcion') }}</textarea>
                <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" data-socio-summary>
                    Selecciona un usuario para autocompletar zona y usar coordenadas reales del lugar de instalación.
                </div>
                <button type="submit" class="xl:col-span-2 rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Registrar instalacion</button>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Checklist de alta</h2>
            <div class="mt-5 grid gap-3">
                @forelse ($recentOrders as $order)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                        {{ $order->referencia ?: 'Solicitud sin referencia' }} · {{ $order->zona ?: 'Sin zona' }} · {{ ucfirst($order->estado) }}
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">Aun no hay instalaciones registradas.</div>
                @endforelse
            </div>
        </article>
    </section>

    @push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-tech-order-form="instalacion"]');
            if (!form) return;

            const socioSelect = form.querySelector('[data-socio-select]');
            const zonaInput = form.querySelector('[data-zona-input]');
            const summary = form.querySelector('[data-socio-summary]');
            const search = form.querySelector('[data-socio-catalog-search]');
            const latInput = form.querySelector('[name="coord_x"]');
            const lngInput = form.querySelector('[name="coord_y"]');
            const map = document.getElementById('instalacion-map');

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

                if (summary) {
                    summary.textContent = selected.dataset.direccion
                        ? `Direccion registrada: ${selected.dataset.direccion}`
                        : 'No hay direccion previa; usa latitud y longitud para dejar trazabilidad exacta.';
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
                const placeholder = socioSelect.querySelector('option[value=""]')?.textContent || 'Seleccionar usuario solicitante';
                socioSelect.innerHTML = `<option value="">${escapeOption(placeholder)}</option>` + (data.items || []).map((item) => `
                    <option
                        value="${escapeOption(item.value)}"
                        data-zona="${escapeOption(item.zona)}"
                        data-direccion="${escapeOption(item.direccion)}"
                        data-lat="${escapeOption(item.latitud)}"
                        data-lng="${escapeOption(item.longitud)}"
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
