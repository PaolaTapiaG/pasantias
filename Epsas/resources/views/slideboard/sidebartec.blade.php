@php
    $companySettings = $sharedCompanySettings ?? [];
    $techUser = $sharedAuthUser ?? Auth::user();

    $techNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'dashboard', 'group' => 'general'],
        ['label' => 'Mi perfil', 'route' => 'tecnico.configuracion.index', 'match' => ['tecnico.configuracion.*'], 'icon' => 'settings', 'group' => 'general'],
        ['label' => 'Lectura de medidores', 'route' => 'tecnico.lecturas.index', 'match' => ['tecnico.lecturas.*'], 'icon' => 'document', 'group' => 'operaciones'],
        ['label' => 'Registrar consumo', 'route' => 'tecnico.consumo.index', 'match' => ['tecnico.consumo.*'], 'icon' => 'gauge', 'group' => 'operaciones'],
        ['label' => 'Reportar anomalias', 'route' => 'tecnico.anomalias.index', 'match' => ['tecnico.anomalias.*'], 'icon' => 'alert', 'group' => 'operaciones'],
        ['label' => 'Cortes de servicio', 'route' => 'tecnico.cortes.index', 'match' => ['tecnico.cortes.*'], 'icon' => 'slash', 'group' => 'servicio'],
        ['label' => 'Reconexiones', 'route' => 'tecnico.reconexiones.index', 'match' => ['tecnico.reconexiones.*'], 'icon' => 'link', 'group' => 'servicio'],
        ['label' => 'Instalaciones nuevas', 'route' => 'tecnico.instalaciones.index', 'match' => ['tecnico.instalaciones.*'], 'icon' => 'spark', 'group' => 'campo'],
        ['label' => 'Medidores', 'route' => 'tecnico.medidores.index', 'match' => ['tecnico.medidores.*'], 'icon' => 'meter', 'group' => 'campo'],
        ['label' => 'Mantenimiento de red', 'route' => 'tecnico.mantenimiento.index', 'match' => ['tecnico.mantenimiento.*'], 'icon' => 'settings', 'group' => 'campo'],
        ['label' => 'Operacion del sistema', 'route' => 'tecnico.operacion.index', 'match' => ['tecnico.operacion.*'], 'icon' => 'power', 'group' => 'sistema'],
        ['label' => 'Mapa operativo', 'route' => 'mapa-operativo.index', 'match' => ['mapa-operativo.*'], 'icon' => 'map', 'group' => 'sistema'],
        ['label' => 'Reportes tecnicos', 'route' => 'tecnico.reportes-tecnicos.index', 'match' => ['tecnico.reportes-tecnicos.*'], 'icon' => 'chart', 'group' => 'sistema'],
        ['label' => 'Gestion de incidencias', 'route' => 'tecnico.incidencias.index', 'match' => ['tecnico.incidencias.*'], 'icon' => 'incident', 'group' => 'sistema'],
    ];

    $groupLabels = [
        'general' => 'Panel',
        'operaciones' => 'Lecturacion',
        'servicio' => 'Servicio',
        'campo' => 'Campo',
        'sistema' => 'Red y alertas',
    ];

    $iconPaths = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75h6.5v5.5h-6.5zm8 0h6.5v8.5h-6.5zm-8 7h6.5v5.5h-6.5zm8 4h6.5v1.5h-6.5z" />',
        'meter' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25h12.5V9.5a6.25 6.25 0 10-12.5 0v8.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l2.5-2.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.75 18.25v-1.5m6.5 1.5v-1.5" />',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.38l3.12 3.12v9.38A1.75 1.75 0 0115 18H8.25A1.75 1.75 0 016.5 16.25V5.5A1.75 1.75 0 018.25 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v3.75h3.75M9 10.5h4.5M9 13.5h4.5" />',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.92c.97-.56 2.03-.56 3 0l.5.28c.3.17.66.2.98.07l.54-.22c1.05-.42 2.24.08 2.66 1.13l.22.54c.13.32.38.58.68.75l.5.28c.97.56 1.5 1.55 1.5 2.67s-.53 2.11-1.5 2.67l-.5.28a1.5 1.5 0 00-.68.75l-.22.54a2 2 0 01-2.66 1.13l-.54-.22a1.5 1.5 0 00-.98.07l-.5.28a3 3 0 01-3 0l-.5-.28a1.5 1.5 0 00-.98-.07l-.54.22a2 2 0 01-2.66-1.13l-.22-.54a1.5 1.5 0 00-.68-.75l-.5-.28A3.07 3.07 0 013 9.55c0-1.12.53-2.11 1.5-2.67l.5-.28c.3-.17.55-.43.68-.75l.22-.54a2 2 0 012.66-1.13l.54.22c.32.13.68.1.98-.07l.5-.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25V10.5m6.25 7.75V5.75M18.25 18.25v-5.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.25h15" />',
        'gauge' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 16a6 6 0 1112 0" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l2.5-2.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 18h7" />',
        'alert' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.75l7 12.5H5l7-12.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.25v3.5M12 15.5h.01" />',
        'slash' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 5l14 14" /><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.5V7A2.25 2.25 0 0015 4.75H9A2.25 2.25 0 006.75 7v10A2.25 2.25 0 009 19.25h6A2.25 2.25 0 0017.25 17v-2.5" />',
        'link' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l4-4" /><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.5l-1 1a3 3 0 104.25 4.25l1-1M16.5 9.5l1-1a3 3 0 10-4.25-4.25l-1 1" />',
        'spark' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75l1.5 4.5L18 9.75l-4.5 1.5L12 15.75l-1.5-4.5L6 9.75l4.5-1.5L12 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 4.5l.5 1.5 1.5.5-1.5.5-.5 1.5-.5-1.5-1.5-.5 1.5-.5.5-1.5z" />',
        'power' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v7" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.5a5.5 5.5 0 107.5 0" />',
        'incident' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h10.5v10.5H6.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.25 9.25h5.5M9.25 12h5.5M9.25 14.75h3.5" />',
        'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75l4.5-1.5 4.5 1.5 3.5-1.25v13.75l-3.5 1.25-4.5-1.5-4.5 1.5V5.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 4.25v13.75M14.75 5.75V19.5" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.75 4.75H7A2.25 2.25 0 004.75 7v10A2.25 2.25 0 007 19.25h3.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 15.25l3.5-3.5-3.5-3.5M17.25 11.75h-8.5" />',
    ];
@endphp

<div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>

<aside
    data-tech-sidebar
    class="fixed inset-y-0 left-0 z-50 flex h-screen w-[min(20rem,calc(100vw-1rem))] -translate-x-full flex-col overflow-hidden border-r border-orange-300/25 bg-[linear-gradient(180deg,#ff8a1d_0%,#f97316_32%,#c2410c_100%)] text-white shadow-[0_30px_70px_rgba(194,65,12,0.35)] transition duration-300 ease-out sm:w-80 lg:z-40 lg:translate-x-0"
>
    <div class="flex h-full w-full flex-col px-4 py-5">
        <div data-sidebar-header class="flex items-center justify-between gap-3 px-2">
            <div class="flex min-w-0 items-center gap-3">
                @if (!empty($companySettings['company_logo']))
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.65rem] bg-white/12 p-1.5 shadow-inner shadow-white/15">
                        <img src="{{ asset($companySettings['company_logo']) }}" alt="Logo empresa" class="h-full w-full object-contain">
                    </div>
                @else
                    <div data-sidebar-brand class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.65rem] bg-white/15 text-lg font-black text-white shadow-inner shadow-white/15">
                        {{ strtoupper(substr($companySettings['company_name'] ?? 'E', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0" data-sidebar-label data-sidebar-persistent>
                    <p class="truncate text-base font-semibold">{{ $companySettings['company_name'] ?? 'EPSAS' }}</p>
                    <p class="truncate text-xs text-orange-100/90">Panel tecnico</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    data-sidebar-toggle
                    class="hidden h-10 w-10 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white transition hover:bg-white/15 md:flex"
                    aria-label="Expandir o contraer sidebar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" data-sidebar-toggle-icon class="h-5 w-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 6.75l4.5 5-4.5 5" />
                    </svg>
                </button>
                <button
                    type="button"
                    data-sidebar-close
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white lg:hidden"
                    aria-label="Cerrar menu"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div data-sidebar-profile class="mt-6 rounded-[1.9rem] border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-950/20 text-sm font-bold text-white shadow-inner shadow-black/10">
                    @if ($techUser?->persona?->foto_url)
                        <img src="{{ $techUser->persona->foto_url }}" alt="Foto tecnico" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($techUser?->name ?? 'T', 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0" data-sidebar-label data-sidebar-persistent>
                    <p class="truncate text-sm font-semibold text-white">{{ $techUser?->name }}</p>
                    <p class="truncate text-xs text-orange-100/85">{{ $techUser?->email }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex-1 overflow-y-auto overflow-x-hidden pr-1" data-sidebar-nav>
            @foreach ($groupLabels as $groupKey => $groupLabel)
                @php
                    $groupItems = collect($techNav)->where('group', $groupKey)->values();
                @endphp
                @if ($groupItems->isNotEmpty())
                    <div class="mb-5">
                        <p class="px-3 text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-orange-100/75" data-sidebar-label>{{ $groupLabel }}</p>
                        <nav class="mt-3 space-y-2">
                            @foreach ($groupItems as $item)
                                @php
                                    $active = request()->routeIs(...$item['match']);
                                @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="{{ $active ? 'bg-white text-orange-700 shadow-[0_18px_36px_rgba(255,255,255,0.18)]' : 'text-orange-50/95 hover:bg-white/10 hover:text-white' }} group flex items-center gap-3 rounded-2xl px-3 py-3 transition"
                                    data-sidebar-item
                                    title="{{ $item['label'] }}"
                                >
                                    <span class="{{ $active ? 'bg-orange-100 text-orange-700' : 'bg-white/10 text-white/95 group-hover:bg-white/15' }} flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            {!! $iconPaths[$item['icon']] !!}
                                        </svg>
                                    </span>
                                    <span class="truncate text-sm font-medium" data-sidebar-label>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                @endif
            @endforeach
        </div>

        <div data-sidebar-footer class="mt-4 border-t border-white/15 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-orange-50/95 transition hover:bg-slate-950/20 hover:text-white" data-sidebar-item>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white/95 group-hover:bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            {!! $iconPaths['logout'] !!}
                        </svg>
                    </span>
                    <span class="text-sm font-medium" data-sidebar-label>Cerrar sesion</span>
                </button>
            </form>
        </div>
    </div>
</aside>

@php
    $mobileDock = [
        ['label' => 'Menu', 'route' => null, 'icon' => 'dashboard', 'action' => 'open'],
        ['label' => 'Inicio', 'route' => route('dashboard'), 'icon' => 'dashboard'],
        ['label' => 'Lecturas', 'route' => route('tecnico.lecturas.index'), 'icon' => 'document', 'active' => request()->routeIs('tecnico.lecturas.*')],
        ['label' => 'Consumo', 'route' => route('tecnico.consumo.index'), 'icon' => 'gauge'],
        ['label' => 'Alertas', 'route' => route('tecnico.incidencias.index'), 'icon' => 'incident'],
    ];
@endphp

<nav class="tech-mobile-dock lg:hidden" aria-label="Navegacion tecnica movil">
    <div class="tech-mobile-dock__shell">
        <div class="tech-mobile-dock__curve"></div>
        @foreach ($mobileDock as $index => $item)
            @php
                $isActive = $item['active'] ?? ($item['route'] ? url()->current() === $item['route'] : false);
                $isCenter = $index === 2;
            @endphp

            @if (($item['action'] ?? null) === 'open')
                <button
                    type="button"
                    data-sidebar-open
                    class="tech-mobile-dock__item {{ $isCenter ? 'tech-mobile-dock__item--center' : '' }}"
                    aria-label="Abrir menu tecnico"
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
                    href="{{ $item['route'] }}"
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
