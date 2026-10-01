@extends('layouts.app')

@section('title', 'Perfil tecnico - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebartec')

    <div data-tech-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl md:sticky md:top-0 dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2.5 sm:px-6 sm:py-4 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-orange-600">Mi perfil</p>
                    <h1 class="mt-1 text-[1.28rem] font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-2xl">Configuracion del tecnico</h1>
                    <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400 sm:mt-2">Actualiza tus datos, foto de perfil y contraseña sin salir del panel técnico.</p>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
            @if ($user?->must_change_password)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-sm">
                    Debes cambiar tu contrasena temporal para poder ingresar a los demas modulos.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('tecnico.configuracion.profile.update') }}" enctype="multipart/form-data" data-profile-password-form class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-[220px_1fr]">
                    <div class="space-y-4">
                        <div class="flex h-40 items-center justify-center rounded-[2rem] border border-dashed border-orange-200 bg-orange-50/60">
                            @if ($user?->persona?->foto_url)
                                <img src="{{ $user->persona->foto_url }}" alt="Foto tecnico" class="h-32 w-32 rounded-[1.75rem] object-cover">
                            @else
                                <div class="flex h-32 w-32 items-center justify-center rounded-[1.75rem] bg-orange-100 text-4xl font-bold text-orange-600">
                                    {{ strtoupper(substr($user?->name ?? 'T', 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Foto de perfil</label>
                            <input type="file" name="photo" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">
                        </div>
                    </div>

                    <div class="grid gap-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre completo</label>
                                <input name="name" value="{{ old('name', $user?->name) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo</label>
                                <input name="email" type="email" value="{{ old('email', $user?->email) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono</label>
                                <input name="phone" value="{{ old('phone', $user?->persona?->telefono) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                        </div>

                        <div class="rounded-[1.6rem] border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/40">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Cambiar contraseña</h2>
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Contraseña actual</label>
                                    <input name="current_password" type="password" autocomplete="current-password" @required($user?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    @error('current_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nueva contraseña</label>
                                    <input name="new_password" type="password" autocomplete="new-password" @required($user?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    @error('new_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Confirmar nueva contraseña</label>
                                    <input name="new_password_confirmation" type="password" autocomplete="new-password" @required($user?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    <p data-password-match-message class="mt-2 hidden text-xs font-semibold text-rose-600">La nueva contrasena y la confirmacion deben ser iguales.</p>
                                    @error('new_password_confirmation')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">
                                Guardar perfil
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
