@php
    $companySettings = $sharedCompanySettings ?? [];
    $secUser = $sharedAuthUser ?? Auth::user();

    $secretariaNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Atencion de caja', 'route' => 'secretaria.operaciones.index', 'match' => ['secretaria.operaciones.*', 'secretaria.cobros.*', 'secretaria.facturas.*'], 'icon' => 'receipt'],
        ['label' => 'Lecturaciones', 'route' => 'tecnico.lecturas.index', 'match' => ['tecnico.lecturas.*'], 'icon' => 'receipt'],
        ['label' => 'Pagos QR', 'route' => 'secretaria.ordenes-pago.index', 'match' => ['secretaria.ordenes-pago.*'], 'icon' => 'invoice'],
        ['label' => 'Socios', 'route' => 'admin.socios.index', 'match' => ['admin.socios.*'], 'icon' => 'contacts'],
        ['label' => 'Mapa operativo', 'route' => 'mapa-operativo.index', 'match' => ['mapa-operativo.*'], 'icon' => 'map'],
        ['label' => 'Reportes ingresos', 'route' => 'secretaria.reportes.index', 'match' => ['secretaria.reportes.*'], 'icon' => 'chart'],
        ['label' => 'Mi perfil', 'route' => 'secretaria.perfil.index', 'match' => ['secretaria.perfil.*'], 'icon' => 'profile'],
    ];

    $iconPaths = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75h6.5v5.5h-6.5zm8 0h6.5v8.5h-6.5zm-8 7h6.5v5.5h-6.5zm8 4h6.5v1.5h-6.5z" />',
        'contacts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75h12.5v12.5H5.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1.75 1.75 0 113.5 0A1.75 1.75 0 019 10zm5.25 5.25a3.75 3.75 0 00-7.5 0" />',
        'invoice' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.75 3.75h6.5l3 3v10.5a1.5 1.5 0 01-1.5 1.5h-8A1.5 1.5 0 016.25 17.25v-12A1.5 1.5 0 017.75 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75v3h3M9 10h6M9 13h6M9 16h3" />',
        'receipt' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.75h9a1.75 1.75 0 011.75 1.75v10.75l-2.25-1.5-2.25 1.5-2.25-1.5-2.25 1.5-2.25-1.5-2.25 1.5V6.5A1.75 1.75 0 017.5 4.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.5h6M9 11.5h6M9 14.5h3" />',
        'operations' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.5 5.75h13v12.5h-13zM8.5 9h7M8.5 12h7M8.5 15h4" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25V5.75m0 12.5h12.5M9 15.75v-4.5m3 4.5v-7m3 7v-9.5" />',
        'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75l4.5-1.5 4.5 1.5 3.5-1.25v13.75l-3.5 1.25-4.5-1.5-4.5 1.5V5.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 4.25v13.75M14.75 5.75V19.5" />',
        'profile' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12.25a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM5.75 19.25a6.25 6.25 0 0112.5 0" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.75 4.75H7A2.25 2.25 0 004.75 7v10A2.25 2.25 0 007 19.25h3.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 15.25l3.5-3.5-3.5-3.5M17.25 11.75h-8.5" />',
    ];
@endphp

<div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-emerald-950/45 backdrop-blur-sm lg:hidden"></div>

<button
    type="button"
    data-sidebar-open
    class="fixed left-4 top-4 z-[72] flex h-11 w-11 items-center justify-center rounded-2xl border border-white/30 bg-emerald-700 text-white shadow-[0_18px_35px_rgba(6,95,70,0.28)] backdrop-blur-sm lg:hidden"
    aria-label="Abrir menu secretaria"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5M4.75 12h14.5M4.75 17.25h14.5" />
    </svg>
</button>

<aside
    data-secretaria-sidebar
    class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 -translate-x-full flex-col overflow-hidden border-r border-emerald-100/20 bg-[linear-gradient(180deg,#0f9f6e_0%,#047857_48%,#064e3b_100%)] text-white shadow-2xl transition duration-300 ease-out lg:z-40 lg:translate-x-0"
>
    <div class="flex h-full w-full flex-col px-4 py-5">
        <div data-sidebar-header class="flex items-center justify-between gap-3 px-2">
            <div class="flex min-w-0 items-center gap-3">
                @if (!empty($companySettings['company_logo']))
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[1.65rem] bg-white p-1.5 shadow-[0_16px_30px_rgba(6,78,59,0.22)]">
                        <img src="{{ asset($companySettings['company_logo']) }}" alt="Logo empresa" class="h-full w-full object-contain">
                    </div>
                @else
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.65rem] bg-white/15 text-lg font-black text-white">
                        {{ strtoupper(substr($companySettings['company_name'] ?? 'E', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0" data-sidebar-label>
                    <p class="truncate text-base font-semibold">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    <p class="truncate text-xs text-emerald-100">Panel secretaria</p>
                </div>
            </div>

            <button
                type="button"
                data-sidebar-toggle
                class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white transition hover:bg-white/15 md:flex"
                aria-label="Contraer menu"
            >
                <svg data-sidebar-toggle-icon xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                </svg>
            </button>

            <button
                type="button"
                data-sidebar-close
                class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white lg:hidden"
                aria-label="Cerrar menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <div data-sidebar-profile class="mt-6 rounded-[1.75rem] border border-white/15 bg-white/10 p-4 shadow-inner shadow-white/5 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-sm font-bold text-emerald-700">
                    @if ($secUser?->persona?->foto_url)
                        <img src="{{ $secUser->persona->foto_url }}" alt="Foto secretaria" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($secUser?->name ?? 'S', 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0" data-sidebar-label>
                    <p class="truncate text-sm font-semibold text-white">{{ $secUser?->name }}</p>
                    <p class="truncate text-xs text-emerald-100">{{ $secUser?->email }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex-1 overflow-y-auto overflow-x-hidden pr-1" data-sidebar-nav>
            <nav class="space-y-2">
                @foreach ($secretariaNav as $item)
                    @php($active = request()->routeIs(...$item['match']))
                    <a
                        href="{{ route($item['route']) }}"
                        class="{{ $active ? 'bg-white text-emerald-950 shadow-[0_14px_26px_rgba(255,255,255,0.18)]' : 'text-emerald-50 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-2xl px-3 py-3 transition"
                        data-sidebar-item
                        title="{{ $item['label'] }}"
                    >
                        <span class="{{ $active ? 'bg-emerald-100 text-emerald-700' : 'bg-white/10 text-white group-hover:bg-white/15' }} flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                {!! $iconPaths[$item['icon']] !!}
                            </svg>
                        </span>
                        <span class="truncate text-sm font-semibold" data-sidebar-label>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div data-sidebar-footer class="mt-6 border-t border-white/15 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-emerald-50 transition hover:bg-white/10 hover:text-white" data-sidebar-item>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white group-hover:bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            {!! $iconPaths['logout'] !!}
                        </svg>
                    </span>
                    <span class="truncate text-sm font-semibold" data-sidebar-label>Cerrar sesion</span>
                </button>
            </form>
        </div>
    </div>
</aside>
