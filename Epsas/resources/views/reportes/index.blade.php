@extends('layouts.app')

@section('title', 'Reportes - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Reportes</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Analisis operativo</h1>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[0.8fr_0.8fr_1fr_auto]">
                    <input type="date" name="desde" value="{{ $desde }}" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <input type="date" name="hasta" value="{{ $hasta }}" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <select name="periodo" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los periodos</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id_periodo }}" @selected((string) $periodoId === (string) $periodo->id_periodo)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Actualizar</button>
                </form>
            </section>

            <section class="mt-6 grid gap-4 md:grid-cols-4">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Recaudado</p><p class="mt-3 text-3xl font-bold text-slate-900">Bs {{ number_format((float) $resumen['recaudado'], 2) }}</p></article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Cobros</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ $resumen['cobros'] }}</p></article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Consumo facturado</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format((float) $resumen['consumo_m3'], 2) }} m3</p></article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Saldo moroso</p><p class="mt-3 text-3xl font-bold text-slate-900">Bs {{ number_format((float) $resumen['saldo_moroso'], 2) }}</p></article>
            </section>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                @include('reportes.cobranza')
                @include('reportes.consumos')
                @include('reportes.morosos')
            </div>
        </main>
    </div>
</div>
@endsection
