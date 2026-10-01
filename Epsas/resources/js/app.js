import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import './echo.js';

/**
 * ==========================================
 * DARK MODE TOGGLE
 * ==========================================
 */
const setupTheme = () => {
    const root = document.documentElement;
    const toggle = document.getElementById('themeToggle');
    const storageKey = 'epsas-theme';
    
    // Determine initial theme
    const savedTheme = localStorage.getItem(storageKey);
    const fallbackTheme = document.body?.dataset.themeDefault || 'light';
    const initialTheme = savedTheme || fallbackTheme;
    
    // Apply initial theme
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        localStorage.setItem(storageKey, theme);
    };
    
    applyTheme(initialTheme);
    
    // Toggle theme on button click
    if (toggle) {
        toggle.addEventListener('click', () => {
            const isDark = root.classList.contains('dark');
            applyTheme(isDark ? 'light' : 'dark');
        });
    }
    
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey && event.newValue) {
            applyTheme(event.newValue);
        }
    });
};

/**
 * ==========================================
 * NEWS CAROUSEL (SWIPER)
 * ==========================================
 */
const setupNewsCarousel = async () => {
    const newsCarousel = document.querySelector('.newsSwiper');
    if (!newsCarousel) return;

    const slidesCount = newsCarousel.querySelectorAll('.swiper-slide').length;
    const [{ default: Swiper }, { Navigation, Pagination, Autoplay }] = await Promise.all([
        import('swiper'),
        import('swiper/modules'),
    ]);

    const swiper = new Swiper(newsCarousel, {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1.2,
        spaceBetween: 30,
        watchOverflow: true,
        resizeObserver: true,
        autoplay: slidesCount > 1 ? {
            delay: 5000,
            disableOnInteraction: false,
        } : false,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 1.5,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 2.2,
                spaceBetween: 30,
            },
            1280: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });

    window.addEventListener('pagehide', () => swiper.destroy(true, true), { once: true });
};

/**
 * ==========================================
 * MOBILE MENU TOGGLE
 * ==========================================
 */
const setupMobileMenu = () => {
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    
    if (!toggle || !menu) return;
    
    toggle.addEventListener('click', () => {
        menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
    });
    
    // Close menu when a link is clicked
    const links = menu.querySelectorAll('a');
    links.forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
};

const setupSequentialPayment = () => {
    const form = document.querySelector('[data-sequential-payment-form]');
    if (!form) return;

    const target = form.querySelector('[data-payment-target]');
    const totalNode = form.querySelector('[data-sequential-total]');
    const countNode = form.querySelector('[data-sequential-count]');
    const rows = [...form.querySelectorAll('[data-payment-row]')];
    const checks = [...form.querySelectorAll('[data-payment-check]')];
    const formatter = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let selectedIndex = 0;

    const refresh = (nextIndex = selectedIndex) => {
        selectedIndex = Math.max(0, Math.min(nextIndex, checks.length - 1));
        const selected = checks[selectedIndex];

        if (totalNode) totalNode.textContent = formatter.format(Number(selected?.dataset.total || 0));
        if (countNode) countNode.textContent = selected?.dataset.count || '0';
        if (target && selected) target.value = selected.value;

        rows.forEach((row, index) => {
            const included = index <= selectedIndex;
            const box = row.querySelector('[data-payment-box]');
            const status = row.querySelector('[data-payment-status]');
            row.setAttribute('aria-pressed', included ? 'true' : 'false');
            if (checks[index]) checks[index].checked = included;
            row.classList.toggle('border-orange-300', included);
            row.classList.toggle('bg-orange-50', included);
            row.classList.toggle('shadow-[0_16px_35px_rgba(249,115,22,.14)]', index === selectedIndex);
            box?.classList.toggle('border-orange-500', included);
            box?.classList.toggle('bg-orange-500', included);
            box?.classList.toggle('text-white', included);
            box?.classList.toggle('border-slate-300', !included);
            box?.classList.toggle('bg-white', !included);
            if (status) {
                status.textContent = included
                    ? (index === selectedIndex ? 'Limite elegido: se pagara hasta esta factura.' : 'Incluida automaticamente por deuda anterior.')
                    : 'Selecciona este mes para incluirlo junto con los anteriores.';
                status.classList.toggle('text-orange-600', included);
                status.classList.toggle('text-slate-400', !included);
            }
        });
    };

    checks.forEach((check, index) => {
        const row = check.closest('[data-payment-row]');
        row?.addEventListener('click', (event) => {
            event.preventDefault();
            refresh(index);
        });
        row?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            refresh(index);
        });
    });

    refresh(0);
};

const setupProfilePasswordForms = () => {
    document.querySelectorAll('[data-profile-password-form]').forEach((form) => {
        const currentPassword = form.querySelector('[name="current_password"]');
        const newPassword = form.querySelector('[name="new_password"]');
        const confirmation = form.querySelector('[name="new_password_confirmation"]');
        const message = form.querySelector('[data-password-match-message]');

        if (!newPassword || !confirmation) {
            return;
        }

        const sync = () => {
            const hasNewPassword = newPassword.value.length > 0 || confirmation.value.length > 0;
            const mismatch = hasNewPassword && newPassword.value !== confirmation.value;

            confirmation.setCustomValidity(mismatch ? 'La nueva contrasena y la confirmacion deben ser iguales.' : '');
            message?.classList.toggle('hidden', !mismatch);

            if (currentPassword && hasNewPassword) {
                currentPassword.required = true;
            }
        };

        newPassword.addEventListener('input', sync);
        confirmation.addEventListener('input', sync);
        form.addEventListener('submit', sync);
        sync();
    });
};

const setupPasswordVisibilityToggles = () => {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.dataset.passwordToggleReady === '1') return;
        input.dataset.passwordToggleReady = '1';

        const wrapper = input.parentElement;
        if (!wrapper) return;

        const currentPosition = window.getComputedStyle(wrapper).position;
        if (currentPosition === 'static') {
            wrapper.style.position = 'relative';
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'absolute inset-y-0 right-0 flex items-center px-4 text-slate-500 transition hover:text-slate-900 focus:outline-none';
        button.setAttribute('aria-label', 'Mostrar contrasena');
        button.innerHTML = `
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;

        input.classList.add('pr-12');
        wrapper.appendChild(button);

        button.addEventListener('click', () => {
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.setAttribute('aria-label', hidden ? 'Ocultar contrasena' : 'Mostrar contrasena');
        });
    });
};

const setupGeoMaps = () => {
    const maps = document.querySelectorAll('[data-geo-map]');
    if (!maps.length) return;
    window.epsasGeoMaps = window.epsasGeoMaps || {};

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char]);

    const readNumber = (value, fallback) => {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const distanceKm = (from, to) => {
        const radius = 6371;
        const toRad = (degrees) => degrees * Math.PI / 180;
        const deltaLat = toRad(to.lat - from.lat);
        const deltaLng = toRad(to.lng - from.lng);
        const a = Math.sin(deltaLat / 2) ** 2
            + Math.cos(toRad(from.lat)) * Math.cos(toRad(to.lat)) * Math.sin(deltaLng / 2) ** 2;

        return radius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    const routeUrl = (from, to) => (
        `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson&steps=false`
    );

    const externalRouteUrl = (from, to) => (
        `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${from.lat}%2C${from.lng}%3B${to.lat}%2C${to.lng}`
    );

    const formatDistance = (meters, fallbackKm = null) => {
        if (Number.isFinite(meters)) {
            return meters >= 1000 ? `${(meters / 1000).toFixed(1)} km` : `${Math.round(meters)} m`;
        }

        return Number.isFinite(fallbackKm) ? `${fallbackKm.toFixed(1)} km aprox.` : 'Distancia no disponible';
    };

    const formatDuration = (seconds) => {
        if (!Number.isFinite(seconds)) return 'Tiempo no disponible';
        const minutes = Math.max(1, Math.round(seconds / 60));

        return minutes >= 60 ? `${Math.floor(minutes / 60)} h ${minutes % 60} min` : `${minutes} min`;
    };

    const markerColors = {
        oficina: '#2563eb',
        socio: '#16a34a',
        medidor: '#0891b2',
        instalacion: '#f97316',
        incidencia: '#dc2626',
        lectura: '#7c3aed',
        corte: '#111827',
        reconexion: '#059669',
        default: '#334155',
    };

    const markerIcon = (type = 'default') => L.divIcon({
        className: 'geo-map-marker',
        html: `<span style="--marker-color:${markerColors[type] || markerColors.default}"></span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        popupAnchor: [0, -28],
    });

    maps.forEach((node) => {
        if (node.dataset.geoReady === '1') return;
        node.dataset.geoReady = '1';

        let markers = [];
        try {
            markers = JSON.parse(node.dataset.markers || '[]');
        } catch {
            markers = [];
        }

        const defaultLat = readNumber(node.dataset.lat, -21.5355);
        const defaultLng = readNumber(node.dataset.lng, -64.7296);
        const picker = node.dataset.picker === 'true';
        const latInput = node.dataset.latInput ? document.querySelector(node.dataset.latInput) : null;
        const lngInput = node.dataset.lngInput ? document.querySelector(node.dataset.lngInput) : null;
        const initialLat = readNumber(latInput?.value, defaultLat);
        const initialLng = readNumber(lngInput?.value, defaultLng);
        const map = L.map(node, {
            scrollWheelZoom: false,
            tap: true,
        }).setView([initialLat, initialLng], Number(node.dataset.zoom || 14));

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        const bounds = [];
        const visibleMarkers = markers.filter((marker) => Number.isFinite(Number(marker.lat)) && Number.isFinite(Number(marker.lng)));
        let routeLayer = null;

        const clearRoute = () => {
            if (!routeLayer) return;
            map.removeLayer(routeLayer);
            routeLayer = null;
        };

        const drawRouteLine = (latLngs, fallback = false) => {
            clearRoute();
            routeLayer = L.polyline(latLngs, {
                color: fallback ? '#f97316' : '#2563eb',
                weight: 5,
                opacity: 0.88,
                dashArray: fallback ? '10 10' : null,
                lineJoin: 'round',
            }).addTo(map);
            map.fitBounds(routeLayer.getBounds(), { padding: [38, 38], maxZoom: 16 });
        };

        const routeTo = async (marker, origin) => {
            const from = {
                lat: readNumber(origin?.lat, Number.NaN),
                lng: readNumber(origin?.lng, Number.NaN),
            };
            const to = {
                lat: readNumber(marker?.lat, Number.NaN),
                lng: readNumber(marker?.lng, Number.NaN),
            };

            if (!Number.isFinite(from.lat) || !Number.isFinite(from.lng) || !Number.isFinite(to.lat) || !Number.isFinite(to.lng)) {
                throw new Error('Coordenadas incompletas para trazar la ruta.');
            }

            try {
                const response = await fetch(routeUrl(from, to), { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('No se pudo calcular la ruta por calles.');

                const payload = await response.json();
                const route = payload?.routes?.[0];
                const coordinates = route?.geometry?.coordinates;

                if (!Array.isArray(coordinates) || coordinates.length < 2) {
                    throw new Error('La ruta devuelta no contiene trazado valido.');
                }

                drawRouteLine(coordinates.map(([lng, lat]) => [lat, lng]), false);

                return {
                    fallback: false,
                    distanceText: formatDistance(Number(route.distance)),
                    durationText: formatDuration(Number(route.duration)),
                    href: externalRouteUrl(from, to),
                };
            } catch (error) {
                const fallbackKm = distanceKm(from, to);
                drawRouteLine([[from.lat, from.lng], [to.lat, to.lng]], true);

                return {
                    fallback: true,
                    distanceText: formatDistance(Number.NaN, fallbackKm),
                    durationText: 'Ruta por calles no disponible',
                    href: externalRouteUrl(from, to),
                };
            }
        };

        visibleMarkers.forEach((marker) => {
            const lat = Number(marker.lat);
            const lng = Number(marker.lng);
            bounds.push([lat, lng]);
            L.marker([lat, lng], { icon: markerIcon(marker.type) })
                .bindPopup(`
                    <strong>${escapeHtml(marker.title || 'Ubicacion')}</strong>
                    <span>${escapeHtml(marker.description || '')}</span>
                    <small>${escapeHtml(marker.category || marker.type || '')}</small>
                `)
                .addTo(map);
        });

        if (picker && latInput && lngInput) {
            const pin = L.marker([initialLat, initialLng], {
                draggable: true,
                icon: markerIcon('default'),
            }).addTo(map);

            const write = (latLng) => {
                latInput.value = Number(latLng.lat).toFixed(7);
                lngInput.value = Number(latLng.lng).toFixed(7);
            };

            const movePicker = (lat, lng, shouldWrite = true) => {
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                const latLng = L.latLng(lat, lng);
                pin.setLatLng(latLng);
                map.setView(latLng, Math.max(map.getZoom(), 15));

                if (shouldWrite) {
                    write(latLng);
                }
            };

            pin.on('dragend', () => write(pin.getLatLng()));
            map.on('click', (event) => {
                pin.setLatLng(event.latlng);
                write(event.latlng);
            });

            latInput.addEventListener('change', () => movePicker(readNumber(latInput.value, initialLat), readNumber(lngInput.value, initialLng), false));
            lngInput.addEventListener('change', () => movePicker(readNumber(latInput.value, initialLat), readNumber(lngInput.value, initialLng), false));
            node.addEventListener('epsas:geo:set', (event) => {
                const lat = readNumber(event.detail?.lat, Number.NaN);
                const lng = readNumber(event.detail?.lng, Number.NaN);
                movePicker(lat, lng, event.detail?.write !== false);
            });
            bounds.push([initialLat, initialLng]);
        }

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [28, 28], maxZoom: 16 });
        } else if (bounds.length === 1 && !picker) {
            map.setView(bounds[0], Number(node.dataset.zoom || 15));
        }

        window.epsasGeoMaps[node.id] = {
            clearRoute,
            externalRouteUrl,
            map,
            markers: visibleMarkers,
            routeTo,
        };
        document.dispatchEvent(new CustomEvent('epsas:geo-map-ready', { detail: { id: node.id } }));
        window.setTimeout(() => map.invalidateSize(), 120);
    });
};

const setupReadingCapture = () => {
    document.querySelectorAll('[data-geo-current]').forEach((button) => {
        if (button.dataset.geoCurrentReady === '1') return;
        button.dataset.geoCurrentReady = '1';

        const status = document.querySelector(button.dataset.geoStatusTarget);
        const setStatus = (message, isError = false) => {
            if (!status) return;
            status.textContent = message;
            status.classList.toggle('text-rose-600', isError);
            status.classList.toggle('text-slate-500', !isError);
            status.classList.toggle('dark:text-rose-300', isError);
        };

        button.addEventListener('click', () => {
            const hostname = window.location.hostname;
            const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(hostname);

            if (!window.isSecureContext && !isLocalhost) {
                setStatus('El GPS del navegador requiere HTTPS en el celular.', true);
                return;
            }

            if (!navigator.geolocation) {
                setStatus('Este navegador no permite obtener la ubicacion.', true);
                return;
            }

            const mapNode = document.querySelector(button.dataset.geoMapTarget);
            if (!mapNode) {
                setStatus('No se encontro el mapa de esta lectura.', true);
                return;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            setStatus('Obteniendo ubicacion del dispositivo...');

            navigator.geolocation.getCurrentPosition(({ coords }) => {
                mapNode.dispatchEvent(new CustomEvent('epsas:geo:set', {
                    detail: { lat: coords.latitude, lng: coords.longitude },
                }));
                setStatus(`Ubicacion actualizada (precision aproximada: ${Math.round(coords.accuracy)} m).`);
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }, (error) => {
                const message = error.code === error.PERMISSION_DENIED
                    ? 'Permite el acceso a la ubicacion en el navegador y vuelve a intentar.'
                    : error.code === error.TIMEOUT
                        ? 'No se obtuvo una ubicacion a tiempo. Intenta nuevamente al aire libre.'
                        : 'No se pudo obtener la ubicacion actual del dispositivo.';
                setStatus(message, true);
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 });
        });
    });

    document.querySelectorAll('[data-reading-evidence]').forEach((input) => {
        const preview = document.querySelector(input.dataset.previewTarget);
        if (!preview) return;

        input.addEventListener('change', () => {
            if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);

            const file = input.files?.[0];
            if (!file) {
                preview.removeAttribute('src');
                preview.classList.add('hidden');
                return;
            }

            preview.dataset.objectUrl = URL.createObjectURL(file);
            preview.src = preview.dataset.objectUrl;
            preview.classList.remove('hidden');
        });
    });
};

const setupGeoRoutePanels = () => {
    document.querySelectorAll('[data-geo-route-panel]').forEach((panel) => {
        if (panel.dataset.routePanelReady === '1') return;
        panel.dataset.routePanelReady = '1';

        const mapId = panel.dataset.mapId;
        const select = panel.querySelector('[data-route-target]');
        const drawButton = panel.querySelector('[data-route-draw]');
        const clearButton = panel.querySelector('[data-route-clear]');
        const openLink = panel.querySelector('[data-route-open]');
        const status = panel.querySelector('[data-route-status]');
        let routeableMarkers = [];

        const origin = () => ({
            lat: Number.parseFloat(panel.dataset.originLat),
            lng: Number.parseFloat(panel.dataset.originLng),
        });

        const selectedMarker = () => routeableMarkers[Number.parseInt(select?.value ?? '-1', 10)];

        const setStatus = (message, type = 'info') => {
            if (!status) return;
            status.textContent = message;
            status.dataset.state = type;
        };

        const syncOpenLink = () => {
            const instance = window.epsasGeoMaps?.[mapId];
            const marker = selectedMarker();
            const from = origin();

            if (!openLink || !instance || !marker || !Number.isFinite(from.lat) || !Number.isFinite(from.lng)) {
                openLink?.setAttribute('aria-disabled', 'true');
                openLink?.removeAttribute('href');
                return;
            }

            openLink.href = instance.externalRouteUrl(from, { lat: Number(marker.lat), lng: Number(marker.lng) });
            openLink.setAttribute('aria-disabled', 'false');
        };

        const bind = () => {
            const instance = window.epsasGeoMaps?.[mapId];
            if (!instance || !select) return false;

            routeableMarkers = instance.markers
                .filter((marker) => marker.type !== 'oficina')
                .sort((a, b) => String(a.title || '').localeCompare(String(b.title || ''), 'es'));

            select.replaceChildren();

            if (routeableMarkers.length) {
                routeableMarkers.forEach((marker, index) => {
                    const option = document.createElement('option');
                    option.value = String(index);
                    option.textContent = [marker.category || marker.type, marker.title, marker.description]
                        .filter(Boolean)
                        .join(' - ');
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No hay destinos con GPS';
                select.appendChild(option);
            }

            select.disabled = routeableMarkers.length === 0;
            drawButton?.toggleAttribute('disabled', routeableMarkers.length === 0);
            clearButton?.toggleAttribute('disabled', routeableMarkers.length === 0);

            setStatus(routeableMarkers.length
                ? 'Elige un destino y traza la ruta desde la oficina.'
                : 'Primero registra coordenadas en socios, medidores u ordenes tecnicas.'
            );
            syncOpenLink();

            return true;
        };

        select?.addEventListener('change', syncOpenLink);
        drawButton?.addEventListener('click', async () => {
            const instance = window.epsasGeoMaps?.[mapId];
            const marker = selectedMarker();

            if (!instance || !marker) {
                setStatus('Selecciona un destino con coordenadas.', 'error');
                return;
            }

            drawButton.disabled = true;
            setStatus('Calculando ruta...', 'loading');

            try {
                const result = await instance.routeTo(marker, origin());
                if (result.href && openLink) {
                    openLink.href = result.href;
                    openLink.setAttribute('aria-disabled', 'false');
                }
                setStatus(
                    result.fallback
                        ? `Se dibujo una referencia directa: ${result.distanceText}. ${result.durationText}.`
                        : `Ruta trazada: ${result.distanceText}, tiempo estimado ${result.durationText}.`,
                    result.fallback ? 'warning' : 'success'
                );
            } catch (error) {
                setStatus(error?.message || 'No se pudo trazar la ruta.', 'error');
            } finally {
                drawButton.disabled = false;
            }
        });

        clearButton?.addEventListener('click', () => {
            window.epsasGeoMaps?.[mapId]?.clearRoute();
            setStatus('Ruta limpia. Puedes elegir otro destino.', 'info');
        });

        if (!bind()) {
            document.addEventListener('epsas:geo-map-ready', (event) => {
                if (event.detail?.id === mapId) {
                    bind();
                }
            });
        }
    });
};

const setupEpsasCharts = () => {
    const nodes = [...document.querySelectorAll('[data-epsas-chart]')];
    if (!nodes.length) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let chartLoader = null;
    const instances = new WeakMap();

    const loadChart = () => {
        chartLoader = chartLoader || import('chart.js/auto').then((module) => module.default || module.Chart);

        return chartLoader;
    };

    const parseConfig = (node) => {
        try {
            return JSON.parse(node.dataset.epsasChart || '{}');
        } catch {
            return {};
        }
    };

    const palette = {
        blue: ['#2563eb', '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#64748b'],
        emerald: ['#059669', '#10b981', '#84cc16', '#f59e0b', '#ef4444', '#0ea5e9'],
        orange: ['#f97316', '#fb923c', '#0ea5e9', '#22c55e', '#ef4444', '#475569'],
        slate: ['#475569', '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'],
    };

    const numberFormatter = new Intl.NumberFormat('es-BO', {
        maximumFractionDigits: 0,
    });

    const moneyFormatter = new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const formatValue = (value, mode = 'number') => {
        const numeric = Number(value);
        const safeValue = Number.isFinite(numeric) ? numeric : 0;

        if (mode === 'currency') {
            return `Bs ${moneyFormatter.format(safeValue)}`;
        }

        if (mode === 'percent') {
            return `${moneyFormatter.format(safeValue)}%`;
        }

        return numberFormatter.format(safeValue);
    };

    const decorateDatasets = (config) => {
        const colors = palette[config.theme] || palette.blue;
        const type = config.type || 'bar';

        return (config.datasets || config.data?.datasets || []).map((dataset, index) => {
            const color = colors[index % colors.length];

            if (type === 'doughnut' || type === 'pie') {
                return {
                    borderColor: '#ffffff',
                    borderRadius: 8,
                    borderWidth: 3,
                    backgroundColor: dataset.backgroundColor || colors,
                    ...dataset,
                };
            }

            if (type === 'line') {
                return {
                    borderColor: dataset.borderColor || color,
                    backgroundColor: dataset.backgroundColor || `${color}26`,
                    borderWidth: 3,
                    fill: dataset.fill ?? true,
                    pointRadius: 3,
                    tension: 0.35,
                    ...dataset,
                };
            }

            return {
                borderColor: dataset.borderColor || color,
                backgroundColor: dataset.backgroundColor || color,
                borderRadius: 12,
                borderSkipped: false,
                maxBarThickness: 34,
                ...dataset,
            };
        });
    };

    const buildOptions = (config) => {
        const type = config.type || 'bar';
        const valueMode = config.value || 'number';
        const showLegend = config.legend ?? (type === 'doughnut' || type === 'pie');
        const hasScales = type !== 'doughnut' && type !== 'pie';

        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: prefersReducedMotion ? 0 : 420,
            },
            plugins: {
                legend: {
                    display: showLegend,
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        color: '#475569',
                        font: { size: 12, weight: '600' },
                        padding: 14,
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                    borderColor: 'rgba(226, 232, 240, 0.18)',
                    borderWidth: 1,
                    displayColors: true,
                    padding: 12,
                    callbacks: {
                        label: (context) => {
                            const label = context.dataset?.label || context.label || 'Valor';
                            const raw = context.parsed?.y ?? context.parsed ?? context.raw ?? 0;

                            return `${label}: ${formatValue(raw, valueMode)}`;
                        },
                    },
                },
            },
            scales: hasScales ? {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, weight: '600' },
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.14)',
                    },
                    ticks: {
                        color: '#94a3b8',
                        callback: (value) => formatValue(value, valueMode),
                    },
                },
            } : undefined,
            ...config.options,
        };
    };

    const render = async (node) => {
        if (!node.isConnected) return;

        const config = parseConfig(node);
        const labels = config.labels || config.data?.labels || [];
        const datasets = decorateDatasets(config);

        if (!labels.length || !datasets.length) {
            return;
        }

        const Chart = await loadChart();
        const previous = instances.get(node);
        if (previous) {
            previous.destroy();
        }

        instances.set(node, new Chart(node, {
            type: config.type || 'bar',
            data: {
                labels,
                datasets,
            },
            options: buildOptions(config),
        }));
    };

    const refresh = () => {
        nodes.forEach((node) => {
            if (node.dataset.chartRendered === '1') {
                void render(node);
            }
        });
    };

    window.EpsasCharts = {
        refresh,
        render,
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.dataset.chartRendered = '1';
                void render(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '120px' });

        nodes.forEach((node) => observer.observe(node));
    } else {
        nodes.forEach((node) => {
            node.dataset.chartRendered = '1';
            void render(node);
        });
    }

    document.addEventListener('epsas:charts-refresh', refresh);
};

const setupDashboardMetrics = () => {
    document.querySelectorAll('[data-dashboard-metrics-endpoint]').forEach((container) => {
        if (container.dataset.metricsReady === '1') return;
        container.dataset.metricsReady = '1';

        const endpoint = container.dataset.dashboardMetricsEndpoint;
        if (!endpoint) return;

        fetch(endpoint, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((data) => {
                Object.entries(data || {}).forEach(([key, value]) => {
                    container.querySelectorAll(`[data-dashboard-metric="${key}"], [data-tecnico-metric="${key}"]`)
                        .forEach((node) => {
                            node.textContent = value ?? '--';
                        });
                });

                container.querySelectorAll('[data-chart-from-metrics="tecnico-summary"]').forEach((chart) => {
                    chart.dataset.epsasChart = JSON.stringify({
                        type: 'bar',
                        theme: 'orange',
                        value: 'number',
                        legend: false,
                        labels: ['Registrados', 'Activos', 'Lecturas', 'Pendientes'],
                        datasets: [{
                            label: 'Tecnico',
                            data: [
                                Number(data.medidores_registrados || 0),
                                Number(data.medidores_activos || 0),
                                Number(data.lecturas_cargadas || 0),
                                Number(data.pendientes_tecnicos || 0),
                            ],
                        }],
                    });
                });

                document.dispatchEvent(new CustomEvent('epsas:charts-refresh'));
            })
            .catch(() => {});
    });
};

/**
 * ==========================================
 * SCROLL ANIMATIONS
 * ==========================================
 */
const setupScrollAnimations = () => {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('[data-animate]').forEach(el => {
        observer.observe(el);
    });
};

/**
 * ==========================================
 * INITIALIZE ALL
 * ==========================================
 */
const setupSidebar = () => {
    const sidebar = document.querySelector('[data-admin-sidebar], [data-tech-sidebar], [data-secretaria-sidebar]');
    const main = document.querySelector('[data-sidebar-main], [data-admin-main], [data-tech-main]');
    const labels = sidebar?.querySelectorAll('[data-sidebar-label]') ?? [];
    const desktopToggles = document.querySelectorAll('[data-sidebar-toggle]');
    const openers = document.querySelectorAll('[data-sidebar-open]');
    const closers = document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]');
    const icons = document.querySelectorAll('[data-sidebar-toggle-icon]');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (!sidebar) {
        return;
    }

    const collapsedWidth = 'lg:w-28';
    const expandedWidth = 'lg:w-72';
    const collapsedPadding = 'lg:pl-28';
    const expandedPadding = 'lg:pl-72';
    const storageKey = sidebar.hasAttribute('data-tech-sidebar')
        ? 'epsas-tech-sidebar-collapsed'
        : sidebar.hasAttribute('data-secretaria-sidebar')
            ? 'epsas-secretaria-sidebar-collapsed'
        : 'epsas-admin-sidebar-collapsed';

    const applyDesktopState = (collapsed) => {
        if (window.innerWidth < 1024) {
            return;
        }

        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
        sidebar.classList.toggle(collapsedWidth, collapsed);
        sidebar.classList.toggle(expandedWidth, !collapsed);

        if (main) {
            main.classList.toggle(collapsedPadding, collapsed);
            main.classList.toggle(expandedPadding, !collapsed);
        }

        labels.forEach((label) => {
            const persistent = label.hasAttribute('data-sidebar-persistent');
            label.dataset.collapsed = collapsed ? 'true' : 'false';
            label.classList.toggle('hidden', collapsed && !persistent);
        });

        icons.forEach((icon) => {
            icon.classList.toggle('rotate-180', collapsed);
        });

        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    };

    const openMobileSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeMobileSidebar = () => {
        if (window.innerWidth >= 1024) {
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            return;
        }

        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const syncByViewport = () => {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('-translate-x-full');
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            applyDesktopState(window.localStorage.getItem(storageKey) === '1');
        } else {
            sidebar.classList.add('-translate-x-full');
            labels.forEach((label) => {
                label.classList.remove('hidden');
                label.dataset.collapsed = 'false';
            });
            sidebar.dataset.collapsed = 'false';
        }
    };

    applyDesktopState(window.localStorage.getItem(storageKey) === '1');
    syncByViewport();

    desktopToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                openMobileSidebar();
                return;
            }

            const nextCollapsed = sidebar.dataset.collapsed !== 'true';
            applyDesktopState(nextCollapsed);
        });
    });

    openers.forEach((opener) => opener.addEventListener('click', openMobileSidebar));
    closers.forEach((closer) => closer.addEventListener('click', closeMobileSidebar));
    window.addEventListener('resize', syncByViewport, { passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
};

/**
 * ==========================================
 * DOCUMENT READY
 * ==========================================
 */
const setupApp = () => {
    setupTheme();
    setupMobileMenu();
    void setupNewsCarousel();
    setupScrollAnimations();
    setupSidebar();
    setupSequentialPayment();
    setupProfilePasswordForms();
    setupPasswordVisibilityToggles();
    setupGeoMaps();
    setupReadingCapture();
    setupGeoRoutePanels();
    setupEpsasCharts();
    setupDashboardMetrics();
    setupRealtimeReadings();
};

const setupRealtimeReadings = () => {
    const panel = document.querySelector('[data-reading-live]');
    if (!panel) return;

    const connectionLabel = panel.querySelector('[data-reading-connection-label]');
    const connectionDot = panel.querySelector('[data-reading-connection-dot]');
    const notice = panel.querySelector('[data-reading-notice]');
    const noticeDetail = panel.querySelector('[data-reading-notice-detail]');

    const updateConnection = (label, connected = false) => {
        if (connectionLabel) connectionLabel.textContent = label;
        connectionDot?.classList.toggle('bg-emerald-500', connected);
        connectionDot?.classList.toggle('bg-amber-500', !connected);
    };

    if (!window.Echo) {
        updateConnection('No disponible');
        return;
    }

    const formatNumber = (value) => new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

    const formatDate = (value) => value?.split('-').reverse().join('/') || '';
    const textElement = (tag, className, text) => {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;
        return element;
    };

    const addReadingCard = (reading) => {
        const list = document.querySelector('[data-reading-cards]');
        if (!list) return;

        list.querySelector('[data-reading-empty]')?.remove();
        const card = textElement('article', 'mobile-finance-card rounded-[1.65rem] p-4 shadow-sm ring-2 ring-emerald-200', '');
        card.append(
            textElement('p', 'text-base font-semibold text-slate-950', reading.socio_nombre || 'Sin socio'),
            textElement('p', 'mt-1 text-sm text-slate-500', `${formatDate(reading.fecha_lectura)} · ${reading.numero_serie || 'Sin medidor'}`),
            textElement('p', 'mt-3 text-sm font-semibold text-orange-600', `Consumo ${formatNumber(reading.consumo_m3)} m³`),
            textElement('p', 'mt-1 text-xs text-slate-500', `Anterior ${formatNumber(reading.lectura_anterior)} · Actual ${formatNumber(reading.lectura_actual)} · ${reading.lector_nombre || 'Sin lector'}`),
        );
        if (reading.evidencia_url) {
            const evidenceLink = document.createElement('a');
            evidenceLink.href = reading.evidencia_url;
            evidenceLink.target = '_blank';
            evidenceLink.rel = 'noopener noreferrer';
            evidenceLink.className = 'mt-3 inline-flex items-center gap-2 text-sm font-semibold text-blue-700';
            const evidenceImage = document.createElement('img');
            evidenceImage.src = reading.evidencia_url;
            evidenceImage.alt = 'Evidencia de lectura';
            evidenceImage.className = 'h-12 w-12 rounded-lg border border-slate-200 object-cover';
            evidenceLink.append(evidenceImage, document.createTextNode('Ver foto del medidor'));
            card.append(evidenceLink);
        }
        list.prepend(card);
    };

    const addReadingRow = (reading) => {
        const list = document.querySelector('[data-reading-rows]');
        if (!list) return;

        list.querySelector('[data-reading-empty]')?.remove();
        const row = document.createElement('tr');
        row.className = 'bg-emerald-50/70';
        [
            formatDate(reading.fecha_lectura),
            reading.numero_serie || 'Sin medidor',
            `${reading.socio_nombre || 'Sin socio'} · ${reading.socio_codigo || '-'}`,
            formatNumber(reading.lectura_anterior),
            formatNumber(reading.lectura_actual),
            `${formatNumber(reading.consumo_m3)} m³`,
            reading.lector_nombre || 'Sin lector',
        ].forEach((value) => row.append(textElement('td', 'px-5 py-4', value)));
        const evidenceCell = textElement('td', 'px-5 py-4', 'Sin foto');
        if (reading.evidencia_url) {
            evidenceCell.replaceChildren();
            const link = document.createElement('a');
            link.href = reading.evidencia_url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            const image = document.createElement('img');
            image.src = reading.evidencia_url;
            image.alt = 'Foto del medidor';
            image.className = 'h-12 w-12 rounded-lg border border-slate-200 object-cover';
            link.append(image);
            evidenceCell.append(link);
        }
        row.append(evidenceCell);
        list.prepend(row);
    };

    const incrementCounter = (attribute) => {
        document.querySelectorAll(`[${attribute}]`).forEach((counter) => {
            const value = Number(counter.getAttribute(attribute) || 0) + 1;
            counter.setAttribute(attribute, String(value));
            counter.textContent = new Intl.NumberFormat('es-BO').format(value);
        });
    };

    const connection = window.Echo.connector?.pusher?.connection;
    connection?.bind('state_change', ({ current }) => {
        updateConnection(current === 'connected' ? 'En vivo' : 'Reconectando');
    });

    window.Echo.private('lecturas')
        .subscribed(() => updateConnection('En vivo', true))
        .error(() => updateConnection('Sin autorización'))
        .listen('.lectura.registrada', (reading) => {
            updateConnection('En vivo', true);
            if (notice && noticeDetail) {
                noticeDetail.textContent = `${reading.socio_nombre || 'Socio'} · ${reading.numero_serie || 'Sin medidor'} · ${formatNumber(reading.consumo_m3)} m³`;
                notice.hidden = false;
                notice.classList.remove('hidden');
            }

            if (window.location.search === '') {
                addReadingCard(reading);
                addReadingRow(reading);
                incrementCounter('data-reading-total');

                const now = new Date();
                const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
                if (reading.fecha_lectura?.startsWith(currentMonth)) {
                    incrementCounter('data-reading-month-total');
                }
            }
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupApp, { once: true });
} else {
    setupApp();
}

