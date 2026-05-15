@extends('layouts.app')

@section('title', 'Panel Tecnico - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebartec')

    <div data-tech-main class="min-h-screen md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur-xl">
            <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Tecnico</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Panel operativo</h1>
                </div>
                <div class="hidden items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm sm:flex">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-sm font-bold text-blue-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#0f172a_0%,#1d4ed8_52%,#1e3a8a_100%)] px-6 py-8 text-white shadow-[0_24px_50px_rgba(15,23,42,0.25)] sm:px-8">
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-blue-100/80">EPSAS</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Bienvenido, {{ Auth::user()->name }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-50/90 sm:text-base">
                        Supervisa medidores, registra lecturas y manten el control tecnico del sistema desde una vista mas ordenada y adaptable.
                    </p>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Acciones rapidas</h3>
                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('tecnico.medidores.index') }}" class="rounded-2xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                            Gestionar medidores
                        </a>
                        <a href="{{ route('tecnico.lecturas.index') }}" class="rounded-2xl bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100">
                            Registrar lecturas
                        </a>
                        <a href="{{ route('tecnico.mantenimiento.index') }}" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                            Ver mantenimiento
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Medidores registrados</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ \App\Models\Medidor::count() }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Activos</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-600">{{ \App\Models\Medidor::where('estado', 'activo')->count() }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Lecturas cargadas</p>
                    <p class="mt-3 text-3xl font-bold text-cyan-600">{{ \App\Models\Lectura::count() }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Pendientes tecnicos</p>
                    <p class="mt-3 text-3xl font-bold text-amber-600">{{ \App\Models\Medidor::whereIn('estado', ['inactivo', 'danado'])->count() }}</p>
                </article>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-2">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900">Medidores</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-500">Consulta el padrón técnico, actualiza estados y registra nuevas instalaciones.</p>
                    <a href="{{ route('tecnico.medidores.index') }}" class="mt-6 inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Ir a medidores
                    </a>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900">Lecturas</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-500">Mantén visible el seguimiento de consumos y el historial operativo de campo.</p>
                    <a href="{{ route('tecnico.lecturas.index') }}" class="mt-6 inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Ir a lecturas
                    </a>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection
