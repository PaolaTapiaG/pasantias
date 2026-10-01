@php
    $authUser = $sharedAuthUser ?? auth()->user();
    $profilePhoto = $profilePhoto ?? null;
    $profilePhoto = $profilePhoto ?? ($authUser?->persona?->foto_url);
    $userName = $userName ?? ($authUser?->name ?? '');
    $userEmail = $userEmail ?? ($authUser?->email ?? '');
    $headerTitle = $headerTitle ?? 'Panel de control';
    $headerRole = $headerRole ?? 'Usuario';
    $companyName = $companyName ?? (($sharedCompanySettings['company_name'] ?? null) ?: 'EPSAS');
    $isSecretaryHeader = $authUser?->hasRole('secretaria');
    $showHeaderNotifications = ($showHeaderNotifications ?? true) && ! (bool) ($authUser?->must_change_password ?? false);
    $headerAccentClass = $headerAccentClass ?? ($isSecretaryHeader ? 'text-emerald-700 dark:text-emerald-300' : 'text-blue-700 dark:text-blue-300');
    $avatarAccentClass = $avatarAccentClass ?? ($isSecretaryHeader ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200');
    $readingsNotificationUrl = $isSecretaryHeader && !$authUser?->hasRole('administrador')
        ? route('secretaria.facturas.index', [], false)
        : route('tecnico.lecturas.index', [], false);
    $readingsNotificationLabel = $isSecretaryHeader && !$authUser?->hasRole('administrador')
        ? 'Ver facturacion'
        : 'Ver lecturas';
@endphp

<!-- Header con Notificaciones y Modo Oscuro/Claro -->
<header class="sticky top-0 z-[80] border-b border-slate-200/80 bg-white/95 backdrop-blur-xl print:hidden dark:border-slate-700/70 dark:bg-slate-950/95">
    <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-4 px-4 py-4 sm:flex-row sm:items-center sm:px-6 lg:px-8">
        <!-- Sección izquierda: Botón toggle + Título y rol -->
        <div data-header-title class="flex min-w-0 items-center gap-3">
            <div>
                <p data-header-role-accent="{{ $isSecretaryHeader ? 'secretaria' : 'default' }}" class="text-xs font-semibold uppercase tracking-[0.28em] {{ $headerAccentClass }}">{{ $headerRole }}</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $headerTitle }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $companyName }}</p>
            </div>
        </div>

        <!-- Sección derecha: Notificaciones, Modo oscuro y Usuario -->
        <div data-header-actions class="flex w-full min-w-0 items-center justify-end gap-3 sm:w-auto">
            <!-- Notificaciones -->
            @if ($showHeaderNotifications)
            <div
                data-admin-notifications 
                data-notifications-url="{{ route('api.admin.notifications', [], false) }}"
                class="relative"
            >
                <style>
                    .header-notifications{display:inline-flex;position:relative}
                    .header-notifications__button{position:relative;display:inline-flex;height:44px;width:44px;align-items:center;justify-content:center;border-radius:12px;border:1px solid #e2e8f0;background:white;color:#64748b;box-shadow:none;transition:.2s ease;dark\:border-slate-700;dark\:bg-slate-950}
                    .dark .header-notifications__button{border-color:#334155;background:#0f172a}
                    .header-notifications__button svg{display:block;width:20px;height:20px}
                    .header-notifications__button:hover{background:#f8fafc;color:#0f172a;dark\:background:#1e293b;dark\:color:#e2e8f0}
                    .dark .header-notifications__button:hover{background:#1e293b;color:#e2e8f0}
                    .header-notifications__badge{position:absolute;right:-6px;top:-6px;min-width:20px;border-radius:999px;background:#ef4444;padding:2px 5px;text-align:center;font-size:10px;font-weight:900;color:white;border:2px solid white}
                    .dark .header-notifications__badge{border-color:#0f172a}
                    .header-notifications__badge.is-hidden{display:none}
                    .header-notifications__panel{position:absolute;right:0;top:calc(100% + 8px);width:min(calc(100vw - 24px),420px);overflow:hidden;border-radius:18px;border:1px solid #e2e8f0;background:white;box-shadow:0 20px 50px rgba(15,23,42,.18);z-index:50;dark\:border-slate-800;dark\:background:#1e293b}
                    .dark .header-notifications__panel{border-color:#334155;background:#1e293b}
                    .header-notifications__panel.is-hidden{display:none}
                    .header-notifications__head{border-bottom:1px solid #e2e8f0;background:#f8fafc;padding:16px 18px;dark\:border-slate-800;dark\:bg-slate-950}
                    .dark .header-notifications__head{border-color:#334155;background:#0f172a}
                    .header-notifications__kicker{margin:0;color:#0f172a;font-size:11px;font-weight:900;letter-spacing:.22em;text-transform:uppercase;dark\:color:#e2e8f0}
                    .dark .header-notifications__kicker{color:#e2e8f0}
                    .header-notifications__title{margin:4px 0 0;color:#0f172a;font-size:16px;font-weight:900;dark\:color:#e2e8f0}
                    .dark .header-notifications__title{color:#e2e8f0}
                    .header-notifications__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:14px;text-align:center}
                    .header-notifications__stat{border-radius:12px;background:white;padding:10px 8px;border:1px solid #e2e8f0;dark\:bg-slate-950;dark\:border-slate-800}
                    .dark .header-notifications__stat{background:#0f172a;border-color:#334155}
                    .header-notifications__stat strong{display:block;font-size:20px;color:#0f172a;dark\:color:#3b82f6}
                    .dark .header-notifications__stat strong{color:#3b82f6}
                    .header-notifications__stat span{display:block;margin-top:3px;color:#64748b;font-size:10px;font-weight:700;dark\:color:#94a3b8}
                    .dark .header-notifications__stat span{color:#94a3b8}
                    .header-notifications__body{max-height:340px;overflow-y:auto;padding:12px 14px 14px}
                    .header-notifications__section{margin-top:12px}
                    .header-notifications__section:first-child{margin-top:0}
                    .header-notifications__section-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}
                    .header-notifications__section-head h3{margin:0;color:#0f172a;font-size:13px;font-weight:900;dark\:color:#e2e8f0}
                    .dark .header-notifications__section-head h3{color:#e2e8f0}
                    .header-notifications__section-head a{color:#3b82f6;font-size:11px;font-weight:800;text-decoration:none;dark\:color:#60a5fa}
                    .dark .header-notifications__section-head a{color:#60a5fa}
                    .header-notifications__list{display:grid;gap:6px}
                    .header-notifications__item{display:block;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;padding:10px;text-decoration:none;transition:.2s ease;dark\:bg-slate-950;dark\:border-slate-800}
                    .dark .header-notifications__item{background:#0f172a;border-color:#334155}
                    .header-notifications__item:hover{border-color:#3b82f6;background:white;dark\:border-slate-600;dark\:bg-slate-900}
                    .dark .header-notifications__item:hover{border-color:#3b82f6;background:#0f172a}
                    .header-notifications__item strong{display:block;color:#0f172a;font-size:13px;dark\:color:#e2e8f0}
                    .dark .header-notifications__item strong{color:#e2e8f0}
                    .header-notifications__item span{display:block;margin-top:3px;color:#64748b;font-size:11px;font-weight:650;dark\:color:#94a3b8}
                    .dark .header-notifications__item span{color:#94a3b8}
                    .header-notifications__item small{display:block;margin-top:6px;color:#3b82f6;font-size:10px;font-weight:800;dark\:color:#60a5fa}
                    .dark .header-notifications__item small{color:#60a5fa}
                    .header-notifications__empty{border-radius:12px;border:1px dashed #cbd5e1;background:#f8fafc;padding:16px;text-align:center;color:#64748b;font-size:11px;font-weight:700;dark\:border-slate-700;dark\:bg-slate-950;dark\:text-slate-400}
                    .dark .header-notifications__empty{border-color:#475569;background:#0f172a;color:#94a3b8}
                    @media (max-width: 640px){
                        .header-notifications__panel{position:fixed;left:.75rem;right:.75rem;top:5.75rem;width:auto;max-height:calc(100vh - 7rem);border-radius:22px}
                        .header-notifications__stats{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;padding:10px}
                        .header-notifications__stat{padding:8px 5px}
                        .header-notifications__stat strong{font-size:18px}
                        .header-notifications__stat span{font-size:9px}
                        .header-notifications__body{max-height:calc(100vh - 19rem)}
                    }
                </style>

                <button
                    type="button"
                    data-notifications-toggle
                    class="header-notifications__button"
                    aria-label="Abrir notificaciones"
                    aria-expanded="false"
                    title="Notificaciones"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0" />
                    </svg>
                    <span data-notifications-badge class="header-notifications__badge is-hidden">0</span>
                </button>

                <section data-notifications-panel class="header-notifications__panel is-hidden">
                    <div class="header-notifications__head">
                        <p class="header-notifications__kicker">Notificaciones</p>
                        <h2 class="header-notifications__title">Centro operativo</h2>
                    </div>

                    <div class="header-notifications__stats">
                        <div class="header-notifications__stat">
                            <strong data-count-qr>0</strong>
                            <span>QR por aprobar</span>
                        </div>
                        <div class="header-notifications__stat">
                            <strong data-count-approved>0</strong>
                            <span>Aprobados hoy</span>
                        </div>
                        <div class="header-notifications__stat">
                            <strong data-count-readings>0</strong>
                            <span>Lecturas hoy</span>
                        </div>
                    </div>

                    <div class="header-notifications__body">
                        <div class="header-notifications__section">
                            <div class="header-notifications__section-head">
                                <h3>Pagos QR por aprobar</h3>
                                <a href="{{ route('secretaria.ordenes-pago.index', ['estado' => 'en_revision'], false) }}">Ver todo</a>
                            </div>
                            <div data-list-qr class="header-notifications__list"></div>
                        </div>

                        <div class="header-notifications__section">
                            <div class="header-notifications__section-head">
                                <h3>Lecturaciones recibidas</h3>
                                <a href="{{ $readingsNotificationUrl }}">{{ $readingsNotificationLabel }}</a>
                            </div>
                            <div data-list-readings class="header-notifications__list"></div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Botón Modo Oscuro/Claro - Solo ícono -->
            @endif

            <button 
                type="button" 
                data-theme-toggle-header
                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-900"
                aria-label="Cambiar tema"
                title="Cambiar tema"
            >
                <svg xmlns="http://www.w3.org/2000/svg" data-theme-toggle-icon-header class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3c-.01.26-.02.52-.02.79A9 9 0 0021 12.79z" />
                </svg>
            </button>

            <!-- Separador visual -->
            <div class="hidden h-8 w-px bg-slate-200 dark:bg-slate-700 sm:block"></div>

            <!-- Info del usuario -->
            <div data-header-user-card class="flex min-w-0 items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-slate-950 sm:w-auto sm:justify-start">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $userName }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $userEmail }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl text-sm font-bold {{ $avatarAccentClass }}">
                    @if ($profilePhoto)
                        <img src="{{ $profilePhoto }}" alt="Foto usuario" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($userName, 0, 1)) }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

@once
    @push('scripts')
        <script>
            (() => {
                // Script para notificaciones en header
                const root = document.querySelector('[data-admin-notifications]');
                if (!root) {
                    return;
                }

                const url = root.dataset.notificationsUrl;
                const toggle = root.querySelector('[data-notifications-toggle]');
                const panel = root.querySelector('[data-notifications-panel]');
                const badge = root.querySelector('[data-notifications-badge]');
                const countQr = root.querySelector('[data-count-qr]');
                const countApproved = root.querySelector('[data-count-approved]');
                const countReadings = root.querySelector('[data-count-readings]');
                const listQr = root.querySelector('[data-list-qr]');
                const listReadings = root.querySelector('[data-list-readings]');

                const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                })[char]);
                
                const empty = (text) => `<div class="header-notifications__empty">${escapeHtml(text)}</div>`;
                const item = (entry) => `
                    <a href="${escapeHtml(entry.url)}" class="header-notifications__item">
                        <strong>${escapeHtml(entry.title)}</strong>
                        <span>${escapeHtml(entry.detail)}</span>
                        <small>${escapeHtml(entry.time || 'Reciente')}</small>
                    </a>
                `;

                let intervalId = null;
                let detailLoadedAt = 0;

                const withDetail = () => `${url}${url.includes('?') ? '&' : '?'}detail=1`;

                const renderCounts = (data) => {
                    const counts = data.counts || {};
                    const qrPending = Number(counts.qr_pendientes || 0);
                    const readings = Number(counts.lecturas_hoy || 0);
                    const total = qrPending + readings;

                    countQr.textContent = qrPending;
                    countApproved.textContent = Number(counts.qr_aprobadas_hoy || 0);
                    countReadings.textContent = readings;

                    badge.textContent = total > 99 ? '99+' : total;
                    badge.classList.toggle('is-hidden', total <= 0);
                };

                const renderLists = (data) => {
                    const qrItems = data.items?.qr || [];
                    const readingItems = data.items?.lecturas || [];
                    listQr.innerHTML = qrItems.length ? qrItems.map(item).join('') : empty('No hay pagos QR pendientes.');
                    listReadings.innerHTML = readingItems.length ? readingItems.map(item).join('') : empty('No hay lecturas cargadas hoy.');
                };

                const requestNotifications = async (detail = false) => {
                    const response = await fetch(detail ? withDetail() : url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                        return null;
                    }

                    return response.json();
                };

                const loadCounts = async () => {
                    if (document.hidden) {
                        return;
                    }

                    try {
                        const data = await requestNotifications(false);

                        if (data) {
                            renderCounts(data);
                        }
                    } catch (error) {
                        console.warn('No se pudieron cargar notificaciones.', error);
                        stopPolling();
                        window.setTimeout(startPolling, 120000);
                    }
                };

                const loadDetails = async () => {
                    try {
                        const data = await requestNotifications(true);

                        if (data) {
                            renderCounts(data);
                            renderLists(data);
                            detailLoadedAt = Date.now();
                        }
                    } catch (error) {
                        console.warn('No se pudieron cargar notificaciones.', error);
                    }
                };

                const startPolling = () => {
                    if (intervalId) {
                        return;
                    }

                    loadCounts();
                    intervalId = window.setInterval(loadCounts, 120000);
                };

                const stopPolling = () => {
                    if (intervalId) {
                        window.clearInterval(intervalId);
                        intervalId = null;
                    }
                };

                toggle.addEventListener('click', () => {
                    const isHidden = panel.classList.toggle('is-hidden');
                    toggle.setAttribute('aria-expanded', String(!isHidden));
                    if (!isHidden && Date.now() - detailLoadedAt > 60000) {
                        loadDetails();
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!root.contains(event.target)) {
                        panel.classList.add('is-hidden');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        stopPolling();
                    } else {
                        startPolling();
                    }
                });

                const scheduleInitialPolling = () => {
                    if ('requestIdleCallback' in window) {
                        window.requestIdleCallback(startPolling, { timeout: 2500 });
                        return;
                    }

                    window.setTimeout(startPolling, 1500);
                };

                scheduleInitialPolling();
            })();

            // Script para el botón de tema en header
            (() => {
                const toggleButton = document.querySelector('[data-theme-toggle-header]');
                const icon = document.querySelector('[data-theme-toggle-icon-header]');
                
                if (!toggleButton || !icon) return;

                const updateIcon = () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    if (isDark) {
                        // Mostrar icono de sol cuando está en modo oscuro
                        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12a9 9 0 11-18 0 9 9 0 0118 0m0 5.25v2.25m-6.364.386l1.591 1.591M5.25 19.5H3m16.5 0h-2.25" />';
                    } else {
                        // Mostrar icono de luna cuando está en modo claro
                        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3c-.01.26-.02.52-.02.79A9 9 0 0021 12.79z" />';
                    }
                };

                toggleButton.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    const newTheme = isDark ? 'light' : 'dark';
                    
                    document.documentElement.classList.toggle('dark', newTheme === 'dark');
                    localStorage.setItem('epsas-theme', newTheme);
                    updateIcon();
                });

                updateIcon();
            })();
        </script>
    @endpush
@endonce
