<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EPSAS')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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
    @vite(['resources/css/app.css'])
    @hasSection('disable_app_js')
    @else
        @vite(['resources/js/app.js'])
    @endif
    @stack('head')
</head>
<body class="min-h-screen app-shell" data-theme-default="{{ $themeDefault }}">
    @yield('content')
    @stack('scripts')
</body>
</html>
