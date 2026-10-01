@extends('layouts.app')

@section('title', 'Mi perfil - Secretaria')

@section('content')
<div class="page-background min-h-screen bg-white">
    @include('slideboard.sidebarsec')

    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Secretaria',
            'headerTitle' => 'Mi perfil',
            'companyName' => $sharedCompanySettings['company_name'] ?? 'EPSAS EL PORTILLO',
            'userName' => $user->name,
            'userEmail' => $user->email,
            'profilePhoto' => $user->persona?->foto_url,
        ])

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($user->must_change_password)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-sm">
                    Debes cambiar tu contrasena temporal para poder ingresar a los demas modulos.
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[0.8fr_1.2fr]">
                    <aside class="bg-[linear-gradient(160deg,#047857_0%,#0f9f6e_58%,#d1fae5_100%)] p-8 text-white">
                        <p class="text-xs font-black uppercase tracking-[0.32em] text-emerald-100">Perfil</p>
                        <h2 class="mt-4 text-3xl font-black">Configuracion de secretaria</h2>
                        <p class="mt-4 text-sm leading-7 text-emerald-50">Actualiza tus datos, foto de perfil y contrasena sin salir del panel.</p>

                        <div class="mt-8 flex items-center gap-4 rounded-[1.5rem] border border-white/20 bg-white/15 p-4 backdrop-blur">
                            <div class="h-16 w-16 overflow-hidden rounded-2xl bg-white text-2xl font-black text-emerald-700">
                                @if ($user->persona?->foto_url)
                                    <img src="{{ $user->persona->foto_url }}" alt="Foto de perfil" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">{{ $user->name }}</p>
                                <p class="truncate text-xs text-emerald-100">{{ $user->email }}</p>
                            </div>
                        </div>
                    </aside>

                    <form method="POST" action="{{ route('secretaria.perfil.update') }}" enctype="multipart/form-data" data-profile-password-form class="grid gap-5 p-6 sm:p-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="text-sm font-black text-slate-900" for="photo">Foto de perfil</label>
                            <input id="photo" type="file" name="photo" accept="image/*" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-sm file:font-black file:text-white focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100">
                            @error('photo')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-black text-slate-900" for="name">Nombre completo</label>
                                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100">
                                @error('name')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-sm font-black text-slate-900" for="email">Correo</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100">
                                @error('email')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-black text-slate-900" for="phone">Telefono</label>
                            <input id="phone" name="phone" value="{{ old('phone', $user->persona?->telefono) }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100">
                            @error('phone')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-5">
                            <p class="text-sm font-black text-emerald-900">Cambiar contrasena</p>
                            <p class="mt-1 text-xs font-semibold text-emerald-700">Completa estos campos solo si necesitas actualizar tu acceso.</p>

                            <div class="mt-4 grid gap-5 sm:grid-cols-3">
                                <div>
                                    <label class="text-xs font-black text-slate-700" for="current_password">Actual</label>
                                    <input id="current_password" type="password" name="current_password" autocomplete="current-password" @required($user->must_change_password) class="mt-2 h-12 w-full rounded-2xl border border-emerald-100 bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                    @error('current_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-xs font-black text-slate-700" for="new_password">Nueva</label>
                                    <input id="new_password" type="password" name="new_password" autocomplete="new-password" @required($user->must_change_password) class="mt-2 h-12 w-full rounded-2xl border border-emerald-100 bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                    @error('new_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-xs font-black text-slate-700" for="new_password_confirmation">Confirmar</label>
                                    <input id="new_password_confirmation" type="password" name="new_password_confirmation" autocomplete="new-password" @required($user->must_change_password) class="mt-2 h-12 w-full rounded-2xl border border-emerald-100 bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                                    <p data-password-match-message class="mt-2 hidden text-xs font-semibold text-rose-600">La nueva contrasena y la confirmacion deben ser iguales.</p>
                                    @error('new_password_confirmation')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('dashboard') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 px-5 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                                Volver
                            </a>
                            <button class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
