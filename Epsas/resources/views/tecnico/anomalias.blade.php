@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Anomalias',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Ver medidores', 'href' => route('tecnico.medidores.index'), 'variant' => 'soft'],
    ],
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Reportar anomalia</h2>

            <form method="POST" action="{{ route('tecnico.anomalias.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                @csrf
                <input
                    type="search"
                    data-meter-catalog-search
                    data-url="{{ route('api.tecnico.medidores.catalogo', [], false) }}"
                    data-target="anomalia-medidor"
                    placeholder="Buscar medidor, socio o zona"
                    class="theme-soft h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none"
                >
                <select id="anomalia-medidor" name="id_medidor" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <option value="">Seleccionar medidor</option>
                    @foreach ($medidoresDisponibles as $medidor)
                        <option value="{{ $medidor->id_medidor }}" @selected(old('id_medidor') == $medidor->id_medidor)>
                            {{ $medidor->numero_serie }} · {{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }}
                        </option>
                    @endforeach
                </select>
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="tipo" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="">Tipo de anomalia</option>
                        <option value="medidor_danado" @selected(old('tipo') === 'medidor_danado')>Medidor dañado</option>
                        <option value="manipulado" @selected(old('tipo') === 'manipulado')>Manipulado</option>
                        <option value="lectura_inconsistente" @selected(old('tipo') === 'lectura_inconsistente')>Lectura inconsistente</option>
                        <option value="fuga_visible" @selected(old('tipo') === 'fuga_visible')>Fuga visible</option>
                    </select>
                    <select name="prioridad" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="alta" @selected(old('prioridad') === 'alta')>Alta</option>
                        <option value="media" @selected(old('prioridad', 'media') === 'media')>Media</option>
                        <option value="baja" @selected(old('prioridad') === 'baja')>Baja</option>
                    </select>
                </div>
                <input type="date" name="fecha_reporte" value="{{ old('fecha_reporte', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <textarea name="descripcion" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Detalle de la anomalia y evidencia encontrada">{{ old('descripcion') }}</textarea>
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Foto de evidencia</label>
                    <input type="file" name="evidencia" accept="image/*" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none">
                    <p class="mt-2 text-xs text-slate-500">Sube una foto del daño, la manipulación o la fuga visible.</p>
                </div>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Registrar anomalia</button>
            </form>

            <div class="mt-5 rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                Al registrar la anomalia el sistema intenta cargar automaticamente la multa configurada sobre la factura pendiente del socio.
            </div>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Cola operativa</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentAnomalias as $anomalia)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $anomalia->medidor?->numero_serie }} · {{ $anomalia->medidor?->socio?->codigo_display }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $anomalia->medidor?->socio?->persona?->nombre_completo }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $anomalia->prioridad === 'alta' ? 'bg-rose-100 text-rose-700' : ($anomalia->prioridad === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ ucfirst($anomalia->prioridad) }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $anomalia->descripcion }}</p>
                        @if ((float) $anomalia->monto_multa > 0)
                            <p class="mt-2 text-xs font-semibold text-orange-600">
                                Multa asociada: Bs {{ number_format((float) $anomalia->monto_multa, 2) }}
                            </p>
                        @endif
                        @if ($anomalia->evidencia_url)
                            <a href="{{ $anomalia->evidencia_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 block overflow-hidden rounded-2xl border border-slate-200">
                                <img src="{{ $anomalia->evidencia_url }}" alt="Evidencia de anomalia" class="h-40 w-full object-cover">
                            </a>
                        @endif
                        <div class="mt-3 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ str_replace('_', ' ', ucfirst($anomalia->tipo)) }}</span>
                            <span>{{ optional($anomalia->fecha_reporte)->format('d/m/Y') }}</span>
                            <span>{{ ucfirst(str_replace('_', ' ', $anomalia->estado)) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                        Aun no hay anomalias registradas.
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    @push('scripts')
    <script>
        (() => {
            const search = document.querySelector('[data-meter-catalog-search][data-target="anomalia-medidor"]');
            const select = document.getElementById('anomalia-medidor');
            if (!search || !select) return;

            const escapeOption = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);

            const load = async (term) => {
                if (term.length < 2) return;

                const response = await fetch(`${search.dataset.url}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) return;

                const data = await response.json();
                const placeholder = select.querySelector('option[value=""]')?.textContent || 'Seleccionar medidor';
                select.innerHTML = `<option value="">${escapeOption(placeholder)}</option>` + (data.items || []).map((item) => `
                    <option value="${escapeOption(item.value)}">${escapeOption(item.label)}</option>
                `).join('');
            };

            let timer = null;
            search.addEventListener('input', (event) => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => load(event.target.value.trim()), 280);
            });
        })();
    </script>
    @endpush
@endcomponent
