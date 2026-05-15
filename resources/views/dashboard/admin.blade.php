@extends('layouts.app')

@section('title', 'Panel administrador - EPSAS')

@section('content')
@php
    $companySettings = $sharedCompanySettings ?? [];
    $profilePhoto = $sharedAuthUser?->persona?->foto_url;
    $dashboardStats = \Illuminate\Support\Facades\Cache::remember('dashboard.admin.stats', now()->addMinutes(10), function () {
        return [
            'users' => \App\Models\User::count(),
            'roles' => \App\Models\Role::count(),
            'permissions' => \App\Models\Permission::count(),
        ];
    });

    $stats = [
        [
            'label' => 'Usuarios',
            'value' => $dashboardStats['users'],
            'tone' => 'blue',
            'detail' => 'Cuentas activas registradas',
        ],
        [
            'label' => 'Roles',
            'value' => $dashboardStats['roles'],
            'tone' => 'violet',
            'detail' => 'Perfiles del sistema',
        ],
        [
            'label' => 'Permisos',
            'value' => $dashboardStats['permissions'],
            'tone' => 'emerald',
            'detail' => 'Accesos configurados',
        ],
        [
            'label' => 'Estado',
            'value' => 'En linea',
            'tone' => 'amber',
            'detail' => 'Sistema operativo correctamente',
        ],
    ];

    $cards = [
        [
            'title' => 'Gestion de usuarios',
            'description' => 'Administra cuentas, accesos y estado de los usuarios del sistema.',
            'items' => ['Crear usuarios', 'Editar perfiles', 'Restablecer contrasenas'],
            'route' => 'admin.usuarios.index',
            'tone' => 'blue',
        ],
        [
            'title' => 'Roles y permisos',
            'description' => 'Define perfiles de acceso y permisos segun responsabilidades.',
            'items' => ['Asignar roles', 'Controlar permisos', 'Auditar accesos'],
            'route' => 'admin.permisos.index',
            'tone' => 'violet',
        ],
        [
            'title' => 'Configuracion',
            'description' => 'Ajusta parametros generales y opciones criticas del sistema.',
            'items' => ['Parametros base', 'Tarifas', 'Respaldos'],
            'route' => 'admin.configuracion.index',
            'tone' => 'emerald',
        ],
        [
            'title' => 'Auditoria',
            'description' => 'Consulta actividad del sistema y revisa eventos importantes.',
            'items' => ['Registro de cambios', 'Historial de accesos', 'Eventos de seguridad'],
            'route' => 'admin.auditoria.index',
            'tone' => 'amber',
        ],
    ];
@endphp

<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        data-sidebar-toggle
                        class="hidden h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 md:flex"
                        aria-label="Expandir o contraer sidebar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" data-sidebar-toggle-icon class="h-5 w-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 6.75l4.5 5-4.5 5" />
                        </svg>
                    </button>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Administrador</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Panel de control</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-sm font-bold text-blue-700">
                        @if ($profilePhoto)
                            <img src="{{ $profilePhoto }}" alt="Foto admin" class="h-11 w-11 rounded-2xl object-cover">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                <div class="overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#245fbe_0%,#1b50aa_58%,#183f8a_100%)] px-6 py-8 text-white shadow-[0_24px_50px_rgba(25,80,170,0.25)] sm:px-8">
                    <div class="max-w-3xl">
                        <p class="text-sm font-medium uppercase tracking-[0.24em] text-blue-100/80">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                        <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">
                            Bienvenido, {{ $user->name }}
                        </h2>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-50/90 sm:text-base">
                            Supervisa el sistema, controla usuarios y manten centralizada la operacion administrativa desde un panel moderno y ordenado.
                        </p>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Acciones rapidas</h3>
                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('admin.socios.index') }}" class="rounded-2xl bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                            Gestionar socios
                        </a>
                        <a href="{{ route('admin.configuracion.index') }}" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            Revisar configuracion
                        </a>
                        <a href="{{ route('admin.gastos.index') }}" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                            Registrar gastos
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $stat)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ $stat['detail'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="mt-8 grid gap-6 xl:grid-cols-2">
                @foreach ($cards as $card)
                    <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">{{ $card['title'] }}</h3>
                                <p class="mt-2 text-sm leading-7 text-slate-500">{{ $card['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                Admin
                            </span>
                        </div>

                        <ul class="mt-5 space-y-3 text-sm text-slate-600">
                            @foreach ($card['items'] as $item)
                                <li class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a
                            href="{{ route($card['route']) }}"
                            class="mt-6 inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Abrir modulo
                        </a>
                    </article>
                @endforeach
            </section>
        </main>
    </div>
</div>
@endsection
