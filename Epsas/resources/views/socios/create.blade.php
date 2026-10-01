@extends('layouts.app')

@section('title', 'Registrar socio - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Socios</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar nuevo socio</h1>
                </div>
                <a href="{{ route('admin.socios.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Volver
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.socios.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @include('socios._form')

                <div class="grid gap-3 sm:flex sm:flex-wrap sm:justify-end">
                    <a href="{{ route('admin.socios.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">
                        Cancelar
                    </a>
                    <button class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">
                        Guardar socio
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
