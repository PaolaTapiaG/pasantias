@extends('layouts.app')

@section('title', 'Configuracion del sistema - EPSAS')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('content')
<div class="page-background min-h-screen transition-colors">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700 dark:text-blue-300">Configuracion</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Panel general del sistema</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Empresa, perfil administrativo, tema, ubicación y multas.</p>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <form method="POST" action="{{ route('admin.configuracion.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="theme-text text-xl font-semibold text-slate-900">Empresa y branding</h2>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Nombre, logo, contacto, descripción, ubicación y multas.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">EPSAS</span>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre de la empresa</label>
                                <input name="company_name" value="{{ old('company_name', $system['company_name'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Alias o subtitulo</label>
                                <input name="company_alias" value="{{ old('company_alias', $system['company_alias'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Email de la empresa</label>
                                <input name="company_email" type="email" value="{{ old('company_email', $system['company_email'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono de la empresa</label>
                                <input name="company_phone" value="{{ old('company_phone', $system['company_phone'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">NIT de la empresa</label>
                                <input name="company_nit" value="{{ old('company_nit', $system['company_nit'] ?? '') }}" placeholder="Pendiente de habilitacion" disabled class="theme-soft h-11 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm outline-none opacity-70">
                            </div>
                            <div class="flex items-end">
                                <button type="button" disabled class="h-11 w-full cursor-not-allowed rounded-xl border border-dashed border-slate-300 bg-slate-100 px-4 text-sm font-semibold text-slate-500 opacity-80">
                                    Facturacion electronica bloqueada
                                </button>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo de soporte</label>
                                <input name="support_email" type="email" value="{{ old('support_email', $system['support_email'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono de soporte</label>
                                <input name="support_phone" value="{{ old('support_phone', $system['support_phone'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Logo de la empresa</label>
                                <input name="company_logo" type="file" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                @if (!empty($system['company_logo']))
                                    <img src="{{ asset($system['company_logo']) }}" alt="Logo empresa" class="mt-4 h-20 rounded-2xl border border-slate-200 bg-white p-2">
                                @endif
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Descripcion</label>
                                <textarea name="company_description" rows="4" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('company_description', $system['company_description'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Direccion</label>
                                <textarea name="address" rows="3" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('address', $system['address'] ?? '') }}</textarea>
                            </div>
                            <div class="grid gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Tema por defecto</label>
                                    <select name="theme_preference" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                        <option value="light" @selected(old('theme_preference', $system['theme_preference'] ?? 'light') === 'light')>Claro</option>
                                        <option value="dark" @selected(old('theme_preference', $system['theme_preference'] ?? 'light') === 'dark')>Oscuro</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Zona horaria</label>
                                        <input name="timezone" value="{{ old('timezone', $system['timezone'] ?? 'America/La_Paz') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Moneda</label>
                                        <input name="currency" value="{{ old('currency', $system['currency'] ?? 'Bs') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700">
                            <h3 class="theme-text text-lg font-semibold text-slate-900">Ubicacion GPS</h3>
                            <div class="mt-5 grid gap-5 lg:grid-cols-[0.7fr_1.3fr]">
                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Latitud</label>
                                        <input id="gps-latitude" name="gps_latitude" value="{{ old('gps_latitude', $system['gps_latitude'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Longitud</label>
                                        <input id="gps-longitude" name="gps_longitude" value="{{ old('gps_longitude', $system['gps_longitude'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Etiqueta del mapa</label>
                                        <input id="map-label" name="map_label" value="{{ old('map_label', $system['map_label'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Icono de señalizacion</label>
                                        <select id="map-icon" name="map_icon" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                            <option value="water" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'water')>Agua</option>
                                            <option value="office" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'office')>Oficina</option>
                                            <option value="pin" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'pin')>Pin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="rounded-[1.75rem] border border-slate-200 p-3 dark:border-slate-700">
                                    <div id="company-map" class="h-[320px] rounded-[1.25rem]"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700">
                            <h3 class="theme-text text-lg font-semibold text-slate-900">Tarifas y multas</h3>
                            <div class="mt-5 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Cargo fijo agua</label>
                                    <input name="fixed_charge" value="{{ old('fixed_charge', $system['fixed_charge'] ?? 20) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">M3 incluidos</label>
                                    <input name="included_m3" value="{{ old('included_m3', $system['included_m3'] ?? 10) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Excedente por m3</label>
                                    <input name="excess_rate" value="{{ old('excess_rate', $system['excess_rate'] ?? 3) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Umbral de corte</label>
                                    <input name="cutoff_threshold_m3" value="{{ old('cutoff_threshold_m3', $system['cutoff_threshold_m3'] ?? 30) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Corte / reconexion</label>
                                    <input name="reconnection_fee" value="{{ old('reconnection_fee', $system['reconnection_fee'] ?? 30) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Cargo fijo alcantarillado</label>
                                    <input name="sewer_fixed_charge" value="{{ old('sewer_fixed_charge', $system['sewer_fixed_charge'] ?? 0) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Multa por mora</label>
                                    <input name="multa_mora" value="{{ old('multa_mora', $system['multa_mora'] ?? 0) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Multa por reconexion</label>
                                    <input name="multa_reconexion" value="{{ old('multa_reconexion', $system['multa_reconexion'] ?? 0) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Multa por retraso</label>
                                    <input name="multa_retraso" value="{{ old('multa_retraso', $system['multa_retraso'] ?? 0) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                            </div>
                            <p class="theme-muted mt-4 text-xs text-slate-500">Regla actual: de 0 a 10 m3 se cobra el cargo fijo; el excedente se calcula por m3 adicional y despues de 30 m3 se agrega el cobro por corte/reconexion.</p>
                        </div>
                    </section>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_35px_rgba(37,99,235,0.25)] transition hover:bg-blue-700">
                        Guardar configuracion general
                    </button>
                </form>

                <div class="space-y-6">
                    <form method="POST" action="{{ route('admin.configuracion.profile.update') }}" enctype="multipart/form-data" class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        @csrf
                        @method('PUT')
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="theme-text text-xl font-semibold text-slate-900">Perfil del administrador</h2>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Actualiza nombre, correo, foto y contraseña.</p>
                            </div>
                            <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">Perfil</span>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <img src="{{ $adminProfile?->persona?->foto_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($adminProfile?->name ?? 'A') . '&background=DBEAFE&color=1D4ED8' }}" alt="Foto admin" class="h-18 w-18 rounded-3xl border border-slate-200 object-cover">
                            <div class="flex-1">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Foto de perfil</label>
                                <input type="file" name="admin_photo" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            </div>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre</label>
                                <input name="admin_name" value="{{ old('admin_name', $adminProfile?->name) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo</label>
                                <input name="admin_email" type="email" value="{{ old('admin_email', $adminProfile?->email) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono</label>
                                <input name="admin_phone" value="{{ old('admin_phone', $adminProfile?->persona?->telefono) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Descripcion</label>
                                <textarea name="admin_description" rows="3" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('admin_description') }}</textarea>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Contrasena actual</label>
                                    <input name="current_password" type="password" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nueva contrasena</label>
                                    <input name="new_password" type="password" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Confirmar nueva contrasena</label>
                                <input name="new_password_confirmation" type="password" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                        </div>

                        <button type="submit" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Guardar perfil
                        </button>
                    </form>

                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="theme-text text-xl font-semibold text-slate-900">Accesos directos</h2>
                        <div class="mt-5 grid gap-3">
                            <a href="{{ route('admin.gastos.index') }}" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">Registrar y revisar gastos</a>
                            <a href="{{ route('secretaria.reportes.index') }}" class="rounded-2xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">Ver reportes con fechas</a>
                            <a href="{{ route('admin.tarifas.index') }}" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">Ajustar tarifas base</a>
                        </div>
                    </article>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const latInput = document.getElementById('gps-latitude');
    const lngInput = document.getElementById('gps-longitude');
    const labelInput = document.getElementById('map-label');
    const iconSelect = document.getElementById('map-icon');
    const defaultLat = parseFloat(latInput?.value || '-16.5');
    const defaultLng = parseFloat(lngInput?.value || '-68.15');
    const map = L.map('company-map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const iconMap = {
        water: '💧',
        office: '🏢',
        pin: '📍'
    };

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    const syncPopup = () => {
        marker.bindPopup(`${iconMap[iconSelect?.value || 'pin']} ${labelInput?.value || 'Ubicacion de EPSAS'}`).openPopup();
    };

    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        latInput.value = pos.lat.toFixed(6);
        lngInput.value = pos.lng.toFixed(6);
        syncPopup();
    });

    [latInput, lngInput].forEach((input) => input?.addEventListener('change', () => {
        const lat = parseFloat(latInput.value || defaultLat);
        const lng = parseFloat(lngInput.value || defaultLng);
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], map.getZoom());
        syncPopup();
    }));

    [labelInput, iconSelect].forEach((input) => input?.addEventListener('change', syncPopup));
    syncPopup();
</script>
@endpush
