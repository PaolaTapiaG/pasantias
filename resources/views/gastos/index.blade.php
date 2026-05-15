@extends('layouts.app')

@section('title', 'Gastos - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700 dark:text-amber-300">Egresos</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Registro de gastos</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.gastos.export', ['format' => 'excel'] + request()->query()) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">Exportar Excel</a>
                    <a href="{{ route('admin.gastos.export', ['format' => 'pdf'] + request()->query()) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">Exportar PDF</a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
                <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="theme-text text-xl font-semibold text-slate-900">Anotar gasto</h2>
                    <form method="POST" action="{{ route('admin.gastos.store') }}" class="mt-6 grid gap-5">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Fecha</label>
                            <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Concepto</label>
                            <input name="concepto" value="{{ old('concepto') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Categoria</label>
                            <input name="categoria" value="{{ old('categoria') }}" placeholder="Mantenimiento, combustible, oficina..." class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Monto</label>
                            <input name="monto" value="{{ old('monto') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Descripcion</label>
                            <textarea name="descripcion" rows="4" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('descripcion') }}</textarea>
                        </div>
                        <button class="rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">Guardar gasto</button>
                    </form>
                </section>

                <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="theme-text text-xl font-semibold text-slate-900">Historial de egresos</h2>
                            <p class="theme-muted mt-2 text-sm text-slate-500">Total del periodo: Bs {{ number_format((float) $totalGastos, 2) }}</p>
                        </div>
                        <form method="GET" class="grid gap-3 sm:grid-cols-3">
                            <input type="date" name="desde" value="{{ $desde }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            <input type="date" name="hasta" value="{{ $hasta }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Filtrar</button>
                        </form>
                    </div>

                    <div class="mt-6 space-y-4">
                        @forelse ($gastos as $gasto)
                            <article class="theme-soft rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="theme-text text-lg font-semibold text-slate-900">{{ $gasto->concepto }}</h3>
                                        <p class="theme-muted mt-1 text-sm text-slate-500">{{ $gasto->categoria }} · {{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</p>
                                        @if ($gasto->descripcion)
                                            <p class="theme-muted mt-2 text-sm text-slate-500">{{ $gasto->descripcion }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-amber-600">Bs {{ number_format((float) $gasto->monto, 2) }}</p>
                                        <p class="theme-muted mt-1 text-xs text-slate-500">{{ $gasto->empleado?->persona?->nombre_completo ?? 'Sin responsable' }}</p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">No hay gastos registrados en este rango.</div>
                        @endforelse
                    </div>

                    <div class="mt-6">{{ $gastos->links() }}</div>
                </section>
            </div>
        </main>
    </div>
</div>
@endsection
