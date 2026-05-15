@extends('layouts.app')

@section('title', 'Codigo de recuperacion - EPSAS')

@section('content')
<div class="min-h-screen bg-slate-100 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl items-center justify-center">
        <div class="w-full max-w-xl rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 sm:p-10">
            <div class="mb-8">
                <p class="text-sm font-medium uppercase tracking-[0.25em] text-blue-700">EPSAS</p>
                <h1 class="mt-2 text-3xl font-semibold text-slate-900">Restablecer contrasena</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Escribe el codigo recibido por SMS y define una nueva contrasena.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('sms_debug_code'))
                <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Entorno local: el codigo SMS generado es <span class="font-semibold">{{ session('sms_debug_code') }}</span>.
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset.sms') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Correo electronico</label>
                    <input
                        name="email"
                        type="email"
                        value="{{ old('email', $email) }}"
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Codigo SMS</label>
                    <input
                        name="codigo"
                        inputmode="numeric"
                        maxlength="6"
                        value="{{ old('codigo') }}"
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nueva contrasena</label>
                    <input
                        name="password"
                        type="password"
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contrasena</label>
                    <input
                        name="password_confirmation"
                        type="password"
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                </div>

                <button
                    type="submit"
                    class="flex h-12 w-full items-center justify-center rounded-2xl bg-[linear-gradient(180deg,#1b6cc2,#0d57a9)] text-sm font-bold text-white shadow-[0_14px_28px_rgba(13,87,169,0.22)] transition hover:opacity-95"
                >
                    Restablecer contrasena
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                    Solicitar otro codigo
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
