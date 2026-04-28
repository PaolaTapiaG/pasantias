@extends('layouts.app')

@section('title', 'Nuevo empleado - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Empleados</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar empleado</h1>
                </div>
                <a href="{{ route('admin.empleados.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Volver
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            @include('empleados.partials.form', [
                'action' => route('admin.empleados.store'),
                'method' => 'POST',
                'empleado' => null,
                'roles' => $roles,
                'submitLabel' => 'Guardar empleado',
            ])
        </main>
    </div>
</div>
@endsection
