<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EPSAS')</title>
    @php
        $themeDefault = ($sharedCompanySettings['theme_preference'] ?? null) ?: 'light';
    @endphp
    <script>
        (() => {
            const fallbackTheme = @json($themeDefault);
            const savedTheme = localStorage.getItem('epsas-theme');
            const theme = savedTheme || fallbackTheme;
            document.documentElement.classList.toggle('dark', theme === 'dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen app-shell" data-theme-default="{{ $themeDefault }}">
    <button type="button" data-theme-toggle class="fixed right-5 top-5 z-[70] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
        Modo oscuro
    </button>
    @yield('content')
    @stack('scripts')
</body>
</html>
