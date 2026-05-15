@extends('layouts.app')

@section('title', 'Nueva lecturacion - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Lecturaciones</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar lecturacion</h1>
                </div>
                <a href="{{ route('tecnico.lecturas.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Volver</a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('tecnico.lecturas.store') }}" class="grid gap-5">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-medium">Medidor activo</label>
                        <select id="medidor-select" name="id_medidor" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            <option value="">Selecciona un medidor</option>
                            @foreach ($medidoresDisponibles as $medidor)
                                <option value="{{ $medidor->id_medidor }}" data-anterior="{{ $medidor->lectura_sugerida }}" @selected(old('id_medidor') == $medidor->id_medidor)>{{ $medidor->numero_serie }} - {{ $medidor->codigo_usuario }} - {{ $medidor->socio_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-medium">Fecha de lectura</label><input type="date" name="fecha_lectura" value="{{ old('fecha_lectura', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Lectura anterior</label><input id="lectura-anterior" name="lectura_anterior" value="{{ old('lectura_anterior') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Lectura actual</label><input name="lectura_actual" value="{{ old('lectura_actual') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                        <div><label class="mb-2 block text-sm font-medium">Observaciones</label><input name="observaciones" value="{{ old('observaciones') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none"></div>
                    </div>
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700">Guardar lecturacion</button>
                </form>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const medidorSelect = document.getElementById('medidor-select');
    const lecturaAnterior = document.getElementById('lectura-anterior');

    const syncLecturaAnterior = () => {
        const selected = medidorSelect?.selectedOptions?.[0];
        if (!selected || !lecturaAnterior) return;
        const suggested = selected.dataset.anterior;
        if (suggested && !lecturaAnterior.value) {
            lecturaAnterior.value = suggested;
        }
    };

    medidorSelect?.addEventListener('change', () => {
        if (lecturaAnterior) {
            lecturaAnterior.value = medidorSelect.selectedOptions[0]?.dataset.anterior || '';
        }
    });

    syncLecturaAnterior();
</script>
@endpush
