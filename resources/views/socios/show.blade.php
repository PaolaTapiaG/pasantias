@extends('layouts.app')

@section('title', 'Detalle del socio - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Socios</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $socio->persona?->nombre_completo }}</h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.socios.edit', $socio) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Editar
                    </a>
                    <a href="{{ route('admin.socios.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Volver
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-slate-900">Informacion general</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $socio->oculto ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $socio->oculto ? 'Oculto' : ucfirst($socio->estado) }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Numero de socio</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->numero_socio }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cedula</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->persona?->cedula_identidad }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Correo</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->persona?->email ?: 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Telefono</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->persona?->telefono ?: 'No registrado' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Direccion</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->direccion ?: 'No registrada' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Relacion de servicio</h2>
                    <dl class="mt-6 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Zona / sector</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->sector?->nombre }} - {{ $socio->sector?->zona }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tarifa</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $socio->tarifa?->nombre }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Medidores</dt>
                            <dd class="mt-2 space-y-2">
                                @forelse ($socio->medidores as $medidor)
                                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                        <span class="font-semibold">{{ $medidor->numero_serie }}</span>
                                        <span class="text-slate-500"> · instalado {{ optional($medidor->fecha_instalacion)->format('d/m/Y') ?: 'sin fecha' }}</span>
                                    </div>
                                @empty
                                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Sin medidores asignados.</div>
                                @endforelse
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            @if ($socio->oculto && $socio->motivo_ocultacion)
                <section class="mt-6 rounded-[2rem] border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-amber-800">Auditoria de ocultacion</h2>
                    <p class="mt-3 text-sm leading-7 text-amber-700">{{ $socio->motivo_ocultacion }}</p>
                    @if ($socio->oculto_en)
                        <p class="mt-2 text-xs font-medium uppercase tracking-[0.2em] text-amber-600">
                            Registrado el {{ $socio->oculto_en->format('d/m/Y H:i') }}
                        </p>
                    @endif
                </section>
            @endif
        </main>
    </div>
</div>
@endsection
