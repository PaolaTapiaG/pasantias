@php
    $companySettings = $sharedCompanySettings ?? [];
    $adminUser = $sharedAuthUser ?? auth()->user()?->loadMissing('persona');

    $adminNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Socios', 'route' => 'admin.socios.index', 'match' => ['admin.socios.*'], 'icon' => 'contacts'],
        ['label' => 'Empleados', 'route' => 'admin.empleados.index', 'match' => ['admin.empleados.*'], 'icon' => 'users'],
        ['label' => 'Tarifas', 'route' => 'admin.tarifas.index', 'match' => ['admin.tarifas.*'], 'icon' => 'receipt'],
        ['label' => 'Medidores', 'route' => 'tecnico.medidores.index', 'match' => ['admin.medidores.*', 'tecnico.medidores.*'], 'icon' => 'meter'],
        ['label' => 'Lecturaciones', 'route' => 'tecnico.lecturas.index', 'match' => ['tecnico.lecturas.*'], 'icon' => 'document'],
        ['label' => 'Mapa operativo', 'route' => 'mapa-operativo.index', 'match' => ['mapa-operativo.*'], 'icon' => 'map'],
        ['label' => 'Atencion de caja', 'route' => 'secretaria.operaciones.index', 'match' => ['secretaria.operaciones.*', 'secretaria.cobros.*', 'secretaria.facturas.*'], 'icon' => 'receipt'],
        ['label' => 'Pagos QR', 'route' => 'secretaria.ordenes-pago.index', 'match' => ['secretaria.ordenes-pago.*'], 'icon' => 'receipt'],
        ['label' => 'Gastos', 'route' => 'admin.gastos.index', 'match' => ['admin.gastos.*'], 'icon' => 'receipt'],
        ['label' => 'Reportes', 'route' => 'secretaria.reportes.index', 'match' => ['secretaria.reportes.*'], 'icon' => 'document'],
        ['label' => 'Perfil', 'route' => 'admin.perfil.index', 'match' => ['admin.perfil.*'], 'icon' => 'users'],
        ['label' => 'Empresa y branding', 'route' => 'admin.configuracion.empresa', 'match' => ['admin.configuracion.index', 'admin.configuracion.empresa', 'admin.configuracion.sms-gateway'], 'icon' => 'settings'],
        ['label' => 'Carnetizacion', 'route' => 'admin.configuracion.carnet', 'match' => ['admin.configuracion.carnet'], 'icon' => 'shield'],
    ];

    $adminMobileNav = [
        ['label' => 'Menu', 'route' => null, 'icon' => 'dashboard', 'action' => 'open'],
        ['label' => 'Inicio', 'route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Socios', 'route' => 'admin.socios.index', 'match' => ['admin.socios.*'], 'icon' => 'contacts'],
        ['label' => 'Caja', 'route' => 'secretaria.operaciones.index', 'match' => ['secretaria.operaciones.*', 'secretaria.cobros.*', 'secretaria.facturas.*'], 'icon' => 'receipt'],
        ['label' => 'Ajustes', 'route' => 'admin.configuracion.empresa', 'match' => ['admin.configuracion.*', 'admin.perfil.*'], 'icon' => 'settings'],
    ];

    $iconPaths = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75h6.5v5.5h-6.5zm8 0h6.5v8.5h-6.5zm-8 7h6.5v5.5h-6.5zm8 4h6.5v1.5h-6.5z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.25 18v-1.25A2.75 2.75 0 0012.5 14h-5A2.75 2.75 0 004.75 16.75V18m12.5 0v-.75A2.25 2.25 0 0015 15m-7-6a2.75 2.75 0 105.5 0A2.75 2.75 0 008 9zm8-.75a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
        'contacts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75h12.5v12.5H5.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1.75 1.75 0 113.5 0A1.75 1.75 0 019 10zm5.25 5.25a3.75 3.75 0 00-7.5 0" />',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75l6 2.25v4.84c0 3.27-2.09 6.18-6 7.91-3.91-1.73-6-4.64-6-7.91V6l6-2.25z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 11.75l1.5 1.5 3-3" />',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.92c.97-.56 2.03-.56 3 0l.5.28c.3.17.66.2.98.07l.54-.22c1.05-.42 2.24.08 2.66 1.13l.22.54c.13.32.38.58.68.75l.5.28c.97.56 1.5 1.55 1.5 2.67s-.53 2.11-1.5 2.67l-.5.28a1.5 1.5 0 00-.68.75l-.22.54a2 2 0 01-2.66 1.13l-.54-.22a1.5 1.5 0 00-.98.07l-.5.28a3 3 0 01-3 0l-.5-.28a1.5 1.5 0 00-.98-.07l-.54.22a2 2 0 01-2.66-1.13l-.22-.54a1.5 1.5 0 00-.68-.75l-.5-.28A3.07 3.07 0 013 9.55c0-1.12.53-2.11 1.5-2.67l.5-.28c.3-.17.55-.43.68-.75l.22-.54a2 2 0 012.66-1.13l.54.22c.32.13.68.1.98-.07l.5-.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.38l3.12 3.12v9.38A1.75 1.75 0 0115 18H8.25A1.75 1.75 0 016.5 16.25V5.5A1.75 1.75 0 018.25 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v3.75h3.75M9 10.5h4.5M9 13.5h4.5" />',
        'receipt' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.75h9a1.75 1.75 0 011.75 1.75v10.75l-2.25-1.5-2.25 1.5-2.25-1.5-2.25 1.5-2.25-1.5-2.25 1.5V6.5A1.75 1.75 0 017.5 4.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.5h6M9 11.5h6" />',
        'invoice' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.75 3.75h6.5l3 3v10.5a1.5 1.5 0 01-1.5 1.5h-8A1.5 1.5 0 016.25 17.25v-12A1.5 1.5 0 017.75 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75v3h3M9 10h6M9 13h6M9 16h3" />',
        'meter' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25h12.5V9.5a6.25 6.25 0 10-12.5 0v8.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l2.5-2.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.75 18.25v-1.5m6.5 1.5v-1.5" />',
        'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75l4.5-1.5 4.5 1.5 3.5-1.25v13.75l-3.5 1.25-4.5-1.5-4.5 1.5V5.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 4.25v13.75M14.75 5.75V19.5" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.75 4.75H7A2.25 2.25 0 004.75 7v10A2.25 2.25 0 007 19.25h3.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 15.25l3.5-3.5-3.5-3.5M17.25 11.75h-8.5" />',
    ];
@endphp

<div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>

<button
    type="button"
    data-sidebar-open
    class="admin-sidebar-open-button fixed left-4 top-4 z-[72] flex h-11 w-11 items-center justify-center rounded-2xl border border-white/20 bg-slate-900/95 text-white shadow-[0_18px_35px_rgba(15,23,42,0.28)] backdrop-blur-sm lg:hidden"
    aria-label="Abrir menu"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5M4.75 12h14.5M4.75 17.25h14.5" />
    </svg>
</button>

<aside
    id="admin-sidebar"
    data-admin-sidebar
    style="background-color: #1d4fb6; background-image: none;"
    class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 shrink-0 -translate-x-full border-r border-white/10 text-white shadow-[0_20px_45px_rgba(17,55,120,0.28)] transition duration-300 ease-out lg:z-40 lg:translate-x-0"
>
    <div class="flex h-full w-full flex-col overflow-hidden px-4 py-5">
        <div data-sidebar-header class="flex items-center justify-between gap-3 px-2">
            <div class="flex min-w-0 items-center gap-3">
                @if (!empty($companySettings['company_logo']))
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[1.65rem] bg-white/15 p-1.5 shadow-inner shadow-white/10">
                        <img src="{{ asset($companySettings['company_logo']) }}" alt="Logo empresa" class="h-full w-full object-contain">
                    </div>
                @else
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.65rem] bg-white/15 text-lg font-black text-white shadow-inner shadow-white/10">
                        {{ strtoupper(substr($companySettings['company_name'] ?? 'E', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0" data-sidebar-label data-sidebar-persistent>
                    <p class="truncate text-base font-semibold">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    <p class="truncate text-xs text-blue-100/80">{{ $companySettings['company_alias'] ?? 'Panel administrativo' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    data-sidebar-toggle
                    class="hidden h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-white transition hover:bg-white/15 md:flex"
                    aria-label="Expandir o contraer sidebar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" data-sidebar-toggle-icon class="h-5 w-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 6.75l4.5 5-4.5 5" />
                    </svg>
                </button>
                <button
                    type="button"
                    data-sidebar-close
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-white lg:hidden"
                    aria-label="Cerrar menu"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div data-sidebar-profile class="mt-6 rounded-[1.75rem] border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-sm font-bold text-white">
                    @if ($adminUser?->persona?->foto_url)
                        <img src="{{ $adminUser->persona->foto_url }}" alt="Foto perfil" class="h-11 w-11 rounded-2xl object-cover">
                    @else
                        {{ strtoupper(substr($adminUser?->name ?? 'A', 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0" data-sidebar-label data-sidebar-persistent>
                    <p class="truncate text-sm font-semibold text-white">{{ $adminUser?->name }}</p>
                    <p class="truncate text-xs text-blue-100/80">{{ $adminUser?->email }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex-1 overflow-y-auto overflow-x-hidden pr-1" data-sidebar-nav>
            <nav class="space-y-2">
                @foreach ($adminNav as $item)
                    @php
                        $active = request()->routeIs(...$item['match']);
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="{{ $active ? 'bg-white text-blue-800 shadow-[0_14px_26px_rgba(255,255,255,0.16)]' : 'text-blue-100/90 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-2xl px-3 py-3 transition"
                        data-sidebar-item
                        title="{{ $item['label'] }}"
                    >
                        <span class="{{ $active ? 'bg-blue-100 text-blue-700' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }} flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                {!! $iconPaths[$item['icon']] !!}
                            </svg>
                        </span>
                        <span class="truncate text-sm font-medium" data-sidebar-label>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

        </div>

        <div data-sidebar-footer class="mt-6 border-t border-white/10 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="group flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-blue-100/90 transition hover:bg-white/10 hover:text-white"
                    data-sidebar-item
                    title="Cerrar sesion"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white/90 group-hover:bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            {!! $iconPaths['logout'] !!}
                        </svg>
                    </span>
                    <span class="truncate text-sm font-medium" data-sidebar-label>Cerrar sesion</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<nav class="tech-mobile-dock admin-mobile-dock lg:hidden" aria-label="Navegacion administrativa movil">
    <div class="tech-mobile-dock__shell">
        <div class="tech-mobile-dock__curve"></div>
        @foreach ($adminMobileNav as $index => $item)
            @php
                $isActive = !empty($item['route']) && request()->routeIs(...($item['match'] ?? [$item['route']]));
                $isCenter = $index === 2;
            @endphp

            @if (($item['action'] ?? null) === 'open')
                <button
                    type="button"
                    data-sidebar-open
                    class="tech-mobile-dock__item {{ $isCenter ? 'tech-mobile-dock__item--center' : '' }}"
                    aria-label="Abrir menu administrativo"
                >
                    <span class="tech-mobile-dock__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5M4.75 12h14.5M4.75 17.25h14.5" />
                        </svg>
                    </span>
                    <span class="tech-mobile-dock__label">{{ $item['label'] }}</span>
                </button>
            @else
                <a
                    href="{{ route($item['route']) }}"
                    class="tech-mobile-dock__item {{ $isCenter ? 'tech-mobile-dock__item--center' : '' }} {{ $isActive ? 'tech-mobile-dock__item--active' : '' }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                >
                    <span class="tech-mobile-dock__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            {!! $iconPaths[$item['icon']] !!}
                        </svg>
                    </span>
                    <span class="tech-mobile-dock__label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
