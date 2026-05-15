<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" class="grid gap-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre</label>
                <input name="nombre" value="{{ old('nombre', $tarifa?->nombre) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('nombre') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Tipo de uso</label>
                <select name="tipo_uso" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @foreach (['domestico' => 'Domestico', 'comercial' => 'Comercial'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('tipo_uso', $tarifa?->tipo_uso ?? 'domestico') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tipo_uso') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Precio por m3 (Bs)</label>
                <input name="precio_m3_base" type="number" step="0.01" min="0" value="{{ old('precio_m3_base', $tarifa?->precio_m3_base ?? '3.50') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('precio_m3_base') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Consumo minimo (m3)</label>
                <input name="consumo_minimo_m3" type="number" step="0.01" min="0" value="{{ old('consumo_minimo_m3', $tarifa?->consumo_minimo_m3 ?? '0') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('consumo_minimo_m3') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Cargo fijo (Bs)</label>
                <input name="cargo_fijo" type="number" step="0.01" min="0" value="{{ old('cargo_fijo', $tarifa?->cargo_fijo ?? '0') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('cargo_fijo') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha de vigencia</label>
                <input name="fecha_vigencia" type="date" value="{{ old('fecha_vigencia', optional($tarifa?->fecha_vigencia)->format('Y-m-d') ?? now()->toDateString()) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                @error('fecha_vigencia') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Estado</label>
                <select name="estado" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    @foreach (['activa' => 'Activa', 'inactiva' => 'Inactiva'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('estado', $tarifa?->estado ?? 'activa') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
            El monto de consumo se calculara multiplicando los metros cubicos leidos por el precio definido aqui. Puedes separar tarifas para uso domestico y comercial desde este modulo.
        </div>

        <div class="flex justify-end">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                {{ $submitLabel }}
            </button>
        </div>
    </form>
</section>
