@extends('layouts.app')

@section('title', 'Iniciar sesion - EPSAS')

@section('content')
<div class="min-h-screen bg-[linear-gradient(180deg,#1f6ec0_0%,#155dab_46%,#0f4f95_100%)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-[0_30px_80px_rgba(7,42,89,0.35)] lg:grid-cols-[1.02fr_0.98fr]">
            <section class="relative hidden overflow-hidden bg-[linear-gradient(180deg,#2a82d6_0%,#1760af_56%,#114c91_100%)] lg:block">
                <div class="absolute inset-y-[-8%] right-[-22%] w-[50%] rounded-l-[999px] bg-white"></div>
                <div class="absolute left-10 top-10 h-24 w-24 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-10 -left-10 h-44 w-44 rounded-full bg-[radial-gradient(circle_at_30%_30%,#5cb1ff_0%,#3389df_55%,#165fab_100%)]"></div>
                <div class="absolute bottom-0 left-36 h-36 w-36 rounded-full bg-[#5da7ef]"></div>

                <div class="relative z-10 flex h-full max-w-sm flex-col justify-center px-12 py-14">
                    <p class="mb-6 text-xs font-semibold uppercase tracking-[0.42em] text-blue-100/95">
                        Sistema EPSAS
                    </p>

                    <h1 class="text-5xl font-extrabold leading-none tracking-tight text-white">
                        Bienvenido
                    </h1>

                    <p class="mt-6 text-base leading-8 text-blue-50/90">
                        Plataforma integral para la gestion de socios, medidores, lecturas, facturas y cobros.
                    </p>

                    <div class="mt-8 inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-white/95">
                        Acceso seguro al sistema
                    </div>
                </div>
            </section>

            <section class="bg-white px-6 py-8 sm:px-8 lg:px-12 lg:py-12">
                <div class="mx-auto flex h-full w-full max-w-md flex-col justify-center">
                    <div class="mb-8">
                        <p class="text-sm font-medium uppercase tracking-[0.25em] text-blue-700 lg:hidden">EPSAS</p>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                            Iniciar sesion
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Ingresa con tu usuario o correo para acceder al sistema.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="login" class="mb-2 block text-sm font-semibold text-slate-700">
                                Usuario o correo
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M2.94 6.34A2 2 0 014.66 5h10.68a2 2 0 011.72 1.34L10 10.59 2.94 6.34z" />
                                        <path d="M18 8.12l-7.56 4.25a1 1 0 01-.98 0L2 8.12V14a2 2 0 002 2h12a2 2 0 002-2V8.12z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    id="login"
                                    name="login"
                                    value="{{ old('login') }}"
                                    required
                                    autofocus
                                    placeholder="usuario o nombre@ejemplo.com"
                                    class="h-12 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 @error('login') border-red-300 focus:ring-red-100 @else border-slate-200 focus:ring-blue-100 @enderror"
                                >
                            </div>
                            @error('login')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                                Contrasena
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5 8V6a5 5 0 1110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2V6a3 3 0 10-6 0v2h6z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    placeholder="Ingresa tu contrasena"
                                    class="h-12 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 @error('password') border-red-300 focus:ring-red-100 @else border-slate-200 focus:ring-blue-100 @enderror"
                                >
                            </div>
                            @error('password')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="remember" class="flex items-center gap-3 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-200"
                            >
                            <span>Recordarme</span>
                        </label>

                        <div class="space-y-3 pt-2">
                            <button
    type="submit"
    class="flex h-12 w-full items-center justify-center rounded-2xl bg-[linear-gradient(180deg,#1b6cc2,#0d57a9)] text-sm font-bold text-white shadow-[0_14px_28px_rgba(13,87,169,0.22)] transition hover:opacity-95 focus:outline-none focus:ring-4 focus:ring-blue-200 cursor-pointer"
>
    Iniciar sesion
</button>

                            <a
                                href="{{ route('password.request') }}"
                                class="flex h-12 w-full items-center justify-center rounded-2xl border border-blue-200 bg-white text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
                            >
                                Recuperar la cuenta
                            </a>
                        </div>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        Si olvidaste tu contrasena, puedes solicitar un codigo de recuperacion.
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
