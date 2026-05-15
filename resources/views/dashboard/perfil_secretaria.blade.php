@extends('layouts.app')

@section('title', 'Mi Perfil - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebarsec')

    <div data-secretaria-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Mi Perfil</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Configuración de la cuenta</h1>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm" style="background-color: #334155;">
                    <div class="p-8">
                        <form action="{{ route('secretaria.perfil.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="flex flex-col items-center mb-8">
                                <div class="relative group">
                                    <div class="h-48 w-48 overflow-hidden rounded-[2rem] bg-slate-700 border-4 border-slate-600">
                                        @if(auth()->user()->persona?->foto_url)
                                            <img id="preview" src="{{ auth()->user()->persona->foto_url }}" alt="Foto perfil" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-5xl font-bold text-slate-400">
                                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-4 text-center">
                                        <label for="foto" class="cursor-pointer rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">
                                            Cambiar foto de perfil
                                        </label>
                                        <input type="file" id="foto" name="foto" class="hidden" accept="image/*" onchange="previewImage(this)">
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-300">Nombre completo</label>
                                    <input type="text" name="name" value="{{ auth()->user()->name }}" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-300">Correo electrónico</label>
                                    <input type="email" name="email" value="{{ auth()->user()->email }}" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-slate-300">Teléfono</label>
                                    <input type="text" name="telefono" value="{{ auth()->user()->persona?->telefono }}" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>

                            <div class="mt-10 rounded-2xl bg-slate-700/50 p-6 border border-slate-600">
                                <h3 class="text-lg font-semibold text-white mb-6">Cambiar contraseña</h3>
                                <div class="grid gap-6 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-300">Contraseña actual</label>
                                        <input type="password" name="current_password" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-300">Nueva contraseña</label>
                                        <input type="password" name="password" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-300">Confirmar nueva contraseña</label>
                                        <input type="password" name="password_confirmation" class="w-full rounded-xl border-none bg-slate-700 px-4 py-3 text-white outline-none focus:ring-2 focus:ring-emerald-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="rounded-xl bg-orange-600 px-8 py-3 font-bold text-white shadow-lg transition hover:bg-orange-500">
                                    Guardar perfil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
