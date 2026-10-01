@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Sistema',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Reportes tecnicos', 'href' => route('tecnico.reportes-tecnicos.index'), 'variant' => 'soft'],
    ],
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.95fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Registrar incidencia</h2>
            <form method="POST" action="{{ route('tecnico.incidencias.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="tipo" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="fuga_grande">Fuga grande</option>
                        <option value="corte_general">Corte general</option>
                        <option value="baja_presion">Baja presion</option>
                        <option value="problema_bomba">Problema en bomba</option>
                    </select>
                    <select name="prioridad" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="alta">Alta</option>
                        <option value="media" selected>Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <input name="zona" value="{{ old('zona') }}" placeholder="Zona / barrio afectado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input type="datetime-local" name="fecha_reporte" value="{{ old('fecha_reporte', now()->format('Y-m-d\\TH:i')) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <input name="coord_x" value="{{ old('coord_x') }}" placeholder="Latitud" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="coord_y" value="{{ old('coord_y') }}" placeholder="Longitud" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="abierta">Abierta</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="cerrada">Cerrada</option>
                    </select>
                </div>
                <div class="rounded-[1.75rem] border border-slate-200 p-3 dark:border-slate-800">
                    <x-geo-map
                        id="incidencia-map"
                        :lat="(float) old('coord_x', -21.5355)"
                        :lng="(float) old('coord_y', -64.7296)"
                        :zoom="15"
                        height="300px"
                        :picker="true"
                        lat-input="[name='coord_x']"
                        lng-input="[name='coord_y']"
                    />
                    <p class="mt-3 text-xs font-semibold text-slate-500">Marca el lugar de la rotura, fuga o incidencia reportada.</p>
                </div>
                <textarea name="descripcion" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Describe el evento, alcance y evidencia disponible">{{ old('descripcion') }}</textarea>
                <div class="grid gap-4 md:grid-cols-2">
                    <input name="gasto_concepto" value="{{ old('gasto_concepto') }}" placeholder="Concepto del gasto o trabajo requerido" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="gasto_categoria" value="{{ old('gasto_categoria', 'Incidencias') }}" placeholder="Categoria de egreso" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="gasto_monto" value="{{ old('gasto_monto') }}" placeholder="Monto estimado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="materiales_utilizados" value="{{ old('materiales_utilizados') }}" placeholder="Materiales o repuestos necesarios" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Si completas concepto, monto o materiales, el sistema enviará ese requerimiento al reporte de egresos del administrador.
                </div>
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Foto de evidencia</label>
                    <input type="file" name="evidencia" accept="image/*" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none">
                    <p class="mt-2 text-xs text-slate-500">Adjunta una imagen de la fuga, zona afectada o equipo comprometido.</p>
                </div>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Guardar incidencia</button>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Matriz de prioridad</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentIncidencias as $incident)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ucfirst(str_replace('_', ' ', $incident->tipo)) }} · {{ $incident->zona }}</p>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $incident->prioridad === 'alta' ? 'bg-rose-100 text-rose-700' : ($incident->prioridad === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ ucfirst($incident->prioridad) }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $incident->descripcion }}</p>
                        @if ($incident->evidencia_url)
                            <a href="{{ $incident->evidencia_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 block overflow-hidden rounded-2xl border border-slate-200">
                                <img src="{{ $incident->evidencia_url }}" alt="Evidencia de incidencia" class="h-40 w-full object-cover">
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">Aun no hay incidencias registradas.</div>
                @endforelse
            </div>
        </article>
    </section>
@endcomponent
