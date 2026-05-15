@php
    $techNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'dashboard'],
        ['label' => 'Medidores', 'route' => 'tecnico.medidores.index', 'match' => ['tecnico.medidores.*'], 'icon' => 'meter'],
        ['label' => 'Lecturaciones', 'route' => 'tecnico.lecturas.index', 'match' => ['tecnico.lecturas.*'], 'icon' => 'document'],
        ['label' => 'Mantenimiento', 'route' => 'tecnico.mantenimiento.index', 'match' => ['tecnico.mantenimiento.*'], 'icon' => 'settings'],
        ['label' => 'Reportes tecnicos', 'route' => 'tecnico.reportes-tecnicos.index', 'match' => ['tecnico.reportes-tecnicos.*'], 'icon' => 'chart'],
    ];

    $iconPaths = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.75h6.5v5.5h-6.5zm8 0h6.5v8.5h-6.5zm-8 7h6.5v5.5h-6.5zm8 4h6.5v1.5h-6.5z" />',
        'meter' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25h12.5V9.5a6.25 6.25 0 10-12.5 0v8.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l2.5-2.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.75 18.25v-1.5m6.5 1.5v-1.5" />',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.38l3.12 3.12v9.38A1.75 1.75 0 0115 18H8.25A1.75 1.75 0 016.5 16.25V5.5A1.75 1.75 0 018.25 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v3.75h3.75M9 10.5h4.5M9 13.5h4.5" />',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.92c.97-.56 2.03-.56 3 0l.5.28c.3.17.66.2.98.07l.54-.22c1.05-.42 2.24.08 2.66 1.13l.22.54c.13.32.38.58.68.75l.5.28c.97.56 1.5 1.55 1.5 2.67s-.53 2.11-1.5 2.67l-.5.28a1.5 1.5 0 00-.68.75l-.22.54a2 2 0 01-2.66 1.13l-.54-.22a1.5 1.5 0 00-.98.07l-.5.28a3 3 0 01-3 0l-.5-.28a1.5 1.5 0 00-.98-.07l-.54.22a2 2 0 01-2.66-1.13l-.22-.54a1.5 1.5 0 00-.68-.75l-.5-.28A3.07 3.07 0 013 9.55c0-1.12.53-2.11 1.5-2.67l.5-.28c.3-.17.55-.43.68-.75l.22-.54a2 2 0 012.66-1.13l.54.22c.32.13.68.1.98-.07l.5-.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25V10.5m6.25 7.75V5.75M18.25 18.25v-5.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18.25h15" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.75 4.75H7A2.25 2.25 0 004.75 7v10A2.25 2.25 0 007 19.25h3.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 15.25l3.5-3.5-3.5-3.5M17.25 11.75h-8.5" />',
    ];
@endphp

<div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 backdrop-blur-sm md:hidden"></div>

<button
    type="button"
    data-sidebar-open
    class="fixed bottom-5 right-5 z-50 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-[0_20px_40px_rgba(15,23,42,0.35)] md:hidden"
    aria-label="Abrir menu tecnico"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5M4.75 12h14.5M4.75 17.25h14.5" />
    </svg>
</button>

<aside
    data-tech-sidebar
    class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-950 text-white shadow-2xl transition duration-300 ease-out md:z-40 md:translate-x-0"
>
    <div class="flex items-center justify-between border-b border-slate-800 px-5 py-5">
        <div>
            <p class="text-lg font-bold">EPSAS</p>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Tecnico</p>
        </div>
        <button
            type="button"
            data-sidebar-close
            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900 text-slate-200 md:hidden"
            aria-label="Cerrar menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <div class="border-b border-slate-800 px-5 py-4">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Sesion activa</p>
        <p class="mt-2 truncate text-sm font-semibold">{{ Auth::user()->name }}</p>
        <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>
    </div>

    <nav class="flex-1 space-y-2 overflow-y-auto px-4 py-6">
        @foreach ($techNav as $item)
            @php
                $active = request()->routeIs(...$item['match']);
            @endphp
            <a
                href="{{ route($item['route']) }}"
                class="{{ $active ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} flex items-center gap-3 rounded-2xl px-4 py-3 transition"
            >
                <span class="{{ $active ? 'bg-white/15' : 'bg-slate-800' }} flex h-11 w-11 items-center justify-center rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        {!! $iconPaths[$item['icon']] !!}
                    </svg>
                </span>
                <span class="text-sm font-medium">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-800 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-rose-950 hover:text-white">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        {!! $iconPaths['logout'] !!}
                    </svg>
                </span>
                <span class="text-sm font-medium">Cerrar sesion</span>
            </button>
        </form>
    </div>
</aside>
