@php
    $showAdminNotifications = ! (bool) (auth()->user()?->must_change_password ?? false);
@endphp

@if ($showAdminNotifications)
<div
    data-admin-notifications
    data-notifications-url="{{ route('api.admin.notifications', [], false) }}"
    class="epsas-notifications"
>
    <style>
        .epsas-notifications{position:fixed;right:22px;top:104px;z-index:9999;font-family:inherit}
        .epsas-notifications__button{position:relative;display:inline-flex;height:52px;width:52px;align-items:center;justify-content:center;border-radius:18px;border:1px solid #fed7aa;background:#fff7ed;color:#ea580c;box-shadow:0 20px 45px rgba(15,23,42,.22);transition:.2s ease}
        .epsas-notifications__button svg{display:block;width:22px;height:22px}
        .epsas-notifications__button:hover{transform:translateY(-1px);background:#ffedd5}
        .epsas-notifications__badge{position:absolute;right:-6px;top:-6px;min-width:22px;border-radius:999px;background:#ef4444;padding:2px 6px;text-align:center;font-size:11px;font-weight:900;color:white}
        .epsas-notifications__badge.is-hidden{display:none}
        .epsas-notifications__panel{position:absolute;right:0;margin-top:12px;width:min(92vw,390px);overflow:hidden;border-radius:26px;border:1px solid #e2e8f0;background:white;box-shadow:0 30px 75px rgba(15,23,42,.28)}
        .epsas-notifications__panel.is-hidden{display:none}
        .epsas-notifications__head{border-bottom:1px solid #ffedd5;background:#fff7ed;padding:18px 20px}
        .epsas-notifications__kicker{margin:0;color:#ea580c;font-size:11px;font-weight:900;letter-spacing:.22em;text-transform:uppercase}
        .epsas-notifications__title{margin:4px 0 0;color:#0f172a;font-size:18px;font-weight:900}
        .epsas-notifications__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:16px;text-align:center}
        .epsas-notifications__stat{border-radius:18px;background:#f8fafc;padding:12px 8px}
        .epsas-notifications__stat strong{display:block;font-size:24px;color:#ea580c}
        .epsas-notifications__stat span{display:block;margin-top:3px;color:#64748b;font-size:11px;font-weight:700}
        .epsas-notifications__body{max-height:420px;overflow-y:auto;padding:0 16px 16px}
        .epsas-notifications__section{margin-top:14px}
        .epsas-notifications__section-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
        .epsas-notifications__section-head h3{margin:0;color:#0f172a;font-size:14px;font-weight:900}
        .epsas-notifications__section-head a{color:#ea580c;font-size:12px;font-weight:800;text-decoration:none}
        .epsas-notifications__list{margin-top:10px;display:grid;gap:8px}
        .epsas-notifications__item{display:block;border-radius:18px;border:1px solid #e2e8f0;background:#f8fafc;padding:12px;text-decoration:none;transition:.2s ease}
        .epsas-notifications__item:hover{border-color:#fdba74;background:#fff7ed}
        .epsas-notifications__item strong{display:block;color:#0f172a;font-size:14px}
        .epsas-notifications__item span{display:block;margin-top:4px;color:#64748b;font-size:12px;font-weight:650}
        .epsas-notifications__item small{display:block;margin-top:8px;color:#ea580c;font-size:11px;font-weight:800}
        .epsas-notifications__empty{border-radius:18px;border:1px dashed #cbd5e1;background:#f8fafc;padding:18px;text-align:center;color:#64748b;font-size:12px;font-weight:700}
        @media (max-width: 640px){.epsas-notifications{right:14px;top:86px}.epsas-notifications__button{height:46px;width:46px}.epsas-notifications__panel{position:fixed;left:.75rem;right:.75rem;top:5.75rem;width:auto;max-height:calc(100vh - 7rem);border-radius:22px}.epsas-notifications__stats{gap:6px;padding:10px}.epsas-notifications__stat{padding:8px 5px}.epsas-notifications__stat strong{font-size:18px}.epsas-notifications__stat span{font-size:9px}.epsas-notifications__body{max-height:calc(100vh - 19rem)}}
    </style>
    <button
        type="button"
        data-notifications-toggle
        class="epsas-notifications__button"
        aria-label="Abrir notificaciones"
        aria-expanded="false"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0" />
        </svg>
        <span data-notifications-badge class="epsas-notifications__badge is-hidden">0</span>
    </button>

    <section data-notifications-panel class="epsas-notifications__panel is-hidden">
        <div class="epsas-notifications__head">
            <p class="epsas-notifications__kicker">Notificaciones</p>
            <h2 class="epsas-notifications__title">Centro operativo</h2>
        </div>

        <div class="epsas-notifications__stats">
            <div class="epsas-notifications__stat">
                <strong data-count-qr>0</strong>
                <span>QR por aprobar</span>
            </div>
            <div class="epsas-notifications__stat">
                <strong data-count-approved>0</strong>
                <span>Aprobados hoy</span>
            </div>
            <div class="epsas-notifications__stat">
                <strong data-count-readings>0</strong>
                <span>Lecturas hoy</span>
            </div>
        </div>

        <div class="epsas-notifications__body">
            <div class="epsas-notifications__section">
                <div class="epsas-notifications__section-head">
                    <h3>Pagos QR por aprobar</h3>
                    <a href="{{ route('secretaria.ordenes-pago.index', ['estado' => 'en_revision'], false) }}">Ver todo</a>
                </div>
                <div data-list-qr class="epsas-notifications__list"></div>
            </div>

            <div class="epsas-notifications__section">
                <div class="epsas-notifications__section-head">
                    <h3>Lecturaciones recibidas</h3>
                    <a href="{{ route('tecnico.lecturas.index', [], false) }}">Ver lecturas</a>
                </div>
                <div data-list-readings class="epsas-notifications__list"></div>
            </div>
        </div>
    </section>
</div>
@endif

@once
    @push('scripts')
        <script>
            (() => {
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
                const empty = (text) => `<div class="epsas-notifications__empty">${escapeHtml(text)}</div>`;
                const item = (entry) => `
                    <a href="${escapeHtml(entry.url)}" class="epsas-notifications__item">
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
                        console.warn('No se pudieron cargar notificaciones admin.', error);
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
                        console.warn('No se pudieron cargar notificaciones admin.', error);
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
        </script>
    @endpush
@endonce
