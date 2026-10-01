@extends('layouts.app')

@section('title', 'Perfil del administrador - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Perfil',
            'headerTitle' => 'Mi perfil administrativo',
        ])

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($adminProfile?->must_change_password)
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

            <form method="POST" action="{{ route('admin.perfil.update') }}" enctype="multipart/form-data" data-profile-password-form class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="theme-text text-xl font-semibold text-slate-900">Datos del administrador</h2>
                        <p class="theme-muted mt-2 text-sm text-slate-500">Actualiza nombre, correo, telefono, foto y contrasena.</p>
                    </div>
                    <span class="w-fit rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">Perfil</span>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-[220px_1fr]">
                    <div class="space-y-4">
                        <div class="flex h-40 items-center justify-center rounded-[2rem] border border-dashed border-blue-200 bg-blue-50/70">
                            @if ($adminProfile?->persona?->foto_url)
                                <img src="{{ $adminProfile->persona->foto_url }}" alt="Foto administrador" class="h-32 w-32 rounded-[1.75rem] object-cover">
                            @else
                                <div class="flex h-32 w-32 items-center justify-center rounded-[1.75rem] bg-blue-100 text-4xl font-bold text-blue-600">
                                    {{ strtoupper(substr($adminProfile?->name ?? 'A', 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Foto de perfil</label>
                            <input type="file" name="admin_photo" accept="image/*" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">
                        </div>
                    </div>

                    <div class="grid gap-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre completo</label>
                                <input name="admin_name" value="{{ old('admin_name', $adminProfile?->name) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo</label>
                                <input name="admin_email" type="email" value="{{ old('admin_email', $adminProfile?->email) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono</label>
                                <input name="admin_phone" value="{{ old('admin_phone', $adminProfile?->persona?->telefono) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                        </div>

                        <div class="rounded-[1.6rem] border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/40">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Cambiar contrasena</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Contrasena actual</label>
                                    <input name="current_password" type="password" autocomplete="current-password" @required($adminProfile?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    @error('current_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nueva contrasena</label>
                                    <input name="new_password" type="password" autocomplete="new-password" @required($adminProfile?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    @error('new_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Confirmar nueva contrasena</label>
                                    <input name="new_password_confirmation" type="password" autocomplete="new-password" @required($adminProfile?->must_change_password) class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                    <p data-password-match-message class="mt-2 hidden text-xs font-semibold text-rose-600">La nueva contrasena y la confirmacion deben ser iguales.</p>
                                    @error('new_password_confirmation')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto">
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
