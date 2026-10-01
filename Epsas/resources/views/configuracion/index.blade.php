@extends('layouts.app')

@section('title', 'Configuracion del sistema - EPSAS')

@section('content')
@php
    $carnet = $carnet ?? [
        'fee' => $system['carnet_fee'] ?? 10,
        'back_text' => $system['carnet_back_text'] ?? '',
    ];
    $section = $section ?? 'empresa';
    $sectionMeta = [
        'empresa' => [
            'role' => 'Empresa',
            'title' => 'Empresa y branding',
            'description' => 'Datos publicos, logo, contacto, ubicacion y apariencia institucional.',
            'submit' => 'Guardar empresa y branding',
        ],
        'carnet' => [
            'role' => 'Carnetizacion',
            'title' => 'Configuracion de carnets',
            'description' => 'Monto de emision y texto editable para el reverso del carnet.',
            'submit' => 'Guardar carnetizacion',
        ],
    ][$section] ?? [
        'role' => 'Empresa',
        'title' => 'Empresa y branding',
        'description' => 'Datos publicos, logo, contacto, ubicacion y apariencia institucional.',
        'submit' => 'Guardar empresa y branding',
    ];
@endphp

<div class="page-background min-h-screen transition-colors">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => $sectionMeta['role'],
            'headerTitle' => $sectionMeta['title'],
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">{{ $sectionMeta['role'] }}</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-slate-100">{{ $sectionMeta['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $sectionMeta['description'] }}</p>
            </section>

            <div class="mx-auto max-w-5xl">
                <form method="POST" action="{{ route('admin.configuracion.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="config_section" value="{{ $section }}">

                    @if ($section === 'empresa')
                    <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="theme-text text-xl font-semibold text-slate-900">Empresa y branding</h2>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Datos publicos, logo, contacto y ubicacion institucional.</p>
                            </div>
                            <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">EPSAS</span>
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
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Correo de soporte</label>
                                <input name="support_email" type="email" value="{{ old('support_email', $system['support_email'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Telefono de soporte</label>
                                <input name="support_phone" value="{{ old('support_phone', $system['support_phone'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Logo de la empresa</label>
                                <input name="company_logo" type="file" accept="image/jpeg,image/png,image/webp" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <p class="mt-2 text-xs text-slate-500">Debe ser un logo horizontal institucional. Se utiliza a color en facturas, PDF y documentos.</p>
                                @if (!empty($system['company_logo']))
                                    <img src="{{ asset($system['company_logo']) }}" alt="Logo empresa" class="mt-4 h-20 rounded-2xl border border-slate-200 bg-white p-2">
                                @endif
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Descripcion institucional</label>
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
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Icono de senalizacion</label>
                                        <select id="map-icon" name="map_icon" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                            <option value="water" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'water')>Agua</option>
                                            <option value="office" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'office')>Oficina</option>
                                            <option value="pin" @selected(old('map_icon', $system['map_icon'] ?? 'water') === 'pin')>Pin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="rounded-[1.75rem] border border-slate-200 p-3 dark:border-slate-700">
                                    <x-geo-map
                                        id="company-map-leaflet"
                                        :lat="(float) old('gps_latitude', $system['gps_latitude'] ?? -21.5355)"
                                        :lng="(float) old('gps_longitude', $system['gps_longitude'] ?? -64.7296)"
                                        :zoom="15"
                                        height="320px"
                                        :picker="true"
                                        lat-input="#gps-latitude"
                                        lng-input="#gps-longitude"
                                        :markers="[[
                                            'type' => 'oficina',
                                            'lat' => (float) old('gps_latitude', $system['gps_latitude'] ?? -21.5355),
                                            'lng' => (float) old('gps_longitude', $system['gps_longitude'] ?? -64.7296),
                                            'title' => old('map_label', $system['map_label'] ?? 'Ubicacion de EPSAS'),
                                            'description' => $system['address'] ?? 'Oficina principal',
                                            'category' => 'Oficina',
                                        ]]"
                                    />
                                    <p class="mt-3 rounded-2xl bg-slate-950 px-4 py-3 text-xs font-semibold text-white">
                                        Haz clic en el mapa o arrastra el marcador para ajustar el punto GPS institucional.
                                    </p>
                                    <div
                                        id="company-map"
                                        data-coordinate-picker
                                        class="hidden relative h-[320px] overflow-hidden rounded-[1.25rem] border border-sky-100 bg-[linear-gradient(135deg,#dff5ff_0%,#effdf6_52%,#eaf1ff_100%)]"
                                    >
                                        <div class="absolute inset-0 opacity-70 [background-image:linear-gradient(rgba(14,116,144,.12)_1px,transparent_1px),linear-gradient(90deg,rgba(14,116,144,.12)_1px,transparent_1px)] [background-size:34px_34px]"></div>
                                        <div class="absolute left-[8%] top-[18%] h-24 w-44 rounded-full bg-sky-200/45 blur-2xl"></div>
                                        <div class="absolute bottom-[12%] right-[10%] h-28 w-52 rounded-full bg-emerald-200/45 blur-2xl"></div>
                                        <button
                                            type="button"
                                            data-map-marker
                                            class="absolute z-10 flex h-12 w-12 -translate-x-1/2 -translate-y-full items-center justify-center rounded-full bg-blue-600 text-white shadow-[0_18px_35px_rgba(37,99,235,.32)] ring-4 ring-white"
                                            aria-label="Punto de ubicacion"
                                        >
                                            <span class="text-xl">•</span>
                                        </button>
                                        <div data-map-popup class="absolute left-4 top-4 z-20 max-w-[min(90%,360px)] rounded-2xl border border-white/80 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur">
                                            Ubicacion de EPSAS
                                        </div>
                                        <p class="absolute bottom-4 left-4 right-4 z-20 rounded-2xl bg-slate-950/70 px-4 py-3 text-xs font-semibold text-white backdrop-blur">
                                            Haz clic dentro del recuadro para ajustar el punto GPS. No se cargan mapas externos.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    @endif

                    @if ($section === 'carnet')
                    <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="theme-text text-xl font-semibold text-slate-900">Carnetizacion</h2>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Define el monto de emision y el texto que aparece al reverso del carnet.</p>
                            </div>
                            <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Carnet</span>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-[0.45fr_1fr]">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Monto por carnet</label>
                                <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <span class="inline-flex items-center border-r border-slate-200 px-4 text-sm font-bold text-slate-500">Bs</span>
                                    <input name="carnet_fee" type="number" step="0.01" min="0" value="{{ old('carnet_fee', $carnet['fee'] ?? 10) }}" class="theme-soft h-11 w-full bg-transparent px-4 text-sm outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Texto del reverso</label>
                                <textarea name="carnet_back_text" rows="4" maxlength="700" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('carnet_back_text', $carnet['back_text'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </section>
                    @endif

                    @if (false)
                    <section class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="theme-text text-xl font-semibold text-slate-900">Portal ciudadano</h2>
                                <p class="theme-muted mt-2 text-sm text-slate-500">Edita inicio, comunicados, horarios, textos, imagenes y video publico.</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre de marca publica</label>
                                    <input name="portal_brand_name" value="{{ old('portal_brand_name', $portal['brand_name'] ?? 'EPSAS') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Subtitulo de marca publica</label>
                                    <input name="portal_brand_subtitle" value="{{ old('portal_brand_subtitle', $portal['brand_subtitle'] ?? 'El Portillo') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Logo exclusivo del portal publico</label>
                                <input name="portal_logo" type="file" accept="image/jpeg,image/png,image/webp" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <p class="mt-2 text-xs text-slate-500">Si no se carga, se utiliza una marca tipografica limpia sin afectar el logo interno.</p>
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Etiqueta del inicio</label>
                                    <input name="portal_home_kicker" value="{{ old('portal_home_kicker', $portal['home_kicker'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Titulo destacado</label>
                                    <input name="portal_home_title" value="{{ old('portal_home_title', $portal['home_title'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Imagen principal del portal</label>
                                <input name="portal_hero_image" type="file" accept="image/jpeg,image/png,image/webp" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <p class="mt-2 text-xs text-slate-500">Se muestra en la portada y reemplaza la imagen institucional predeterminada.</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Texto principal del inicio</label>
                                <textarea name="portal_home_intro" rows="3" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('portal_home_intro', $portal['home_intro'] ?? '') }}</textarea>
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">URL de video publico</label>
                                    <input name="portal_home_video_url" value="{{ old('portal_home_video_url', $portal['home_video_url'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Subir video del portal</label>
                                    <input name="portal_home_video_file" type="file" accept="video/mp4,video/webm,video/ogg,video/quicktime" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    @if (!empty($portal['home_video_path']))
                                        <p class="mt-2 text-xs text-slate-500">Video cargado: {{ basename($portal['home_video_path']) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="grid gap-5 md:grid-cols-3">
                                @foreach ([1, 2, 3] as $imageIndex)
                                    @php
                                        $imageKey = 'home_image_' . $imageIndex;
                                        $imageUrl = !empty($portal[$imageKey]) ? asset($portal[$imageKey]) : $portalFallbackImages[$imageIndex];
                                    @endphp
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Imagen {{ $imageIndex }}</label>
                                        <img src="{{ $imageUrl }}" alt="Imagen portal {{ $imageIndex }}" class="mb-3 h-28 w-full rounded-2xl border border-slate-200 object-cover">
                                        <input name="portal_home_image_{{ $imageIndex }}" type="file" accept="image/*" class="theme-soft block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    </div>
                                @endforeach
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Titulo de contenido destacado</label>
                                    <input name="portal_featured_title" value="{{ old('portal_featured_title', $portal['featured_title'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Horario visible en inicio</label>
                                    <input name="portal_schedule_summary" value="{{ old('portal_schedule_summary', $portal['schedule_summary'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Descripcion de contenido destacado</label>
                                <textarea name="portal_featured_text" rows="3" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('portal_featured_text', $portal['featured_text'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Nota visible sobre pagos QR</label>
                                <textarea name="portal_payment_note" rows="2" class="theme-soft w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none">{{ old('portal_payment_note', $portal['payment_note'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Identificador QR oficial de la empresa</label>
                                <input name="payment_static_qr_payload" value="{{ old('payment_static_qr_payload', $system['payment_static_qr_payload'] ?? '') }}" autocomplete="off" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                                <p class="mt-2 text-xs text-slate-500">Debe ser entregado y certificado por la entidad financiera. Cada orden agrega monto, referencia y vencimiento.</p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-4 border-t border-slate-200 pt-6 dark:border-slate-700">
                            <div>
                                <h3 class="theme-text text-lg font-semibold text-slate-900">Paginas, comunicados y horarios</h3>
                                <p class="theme-muted mt-1 text-sm text-slate-500">En tarjetas usa una linea por item con el formato: Titulo | Texto. En puntos clave usa una linea por punto.</p>
                            </div>
                            @foreach ($portalPages as $pageKey => $page)
                                <details class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40" {{ in_array($pageKey, ['comunicados', 'horarios'], true) ? 'open' : '' }}>
                                    <summary class="cursor-pointer text-sm font-bold text-slate-900 dark:text-slate-100">{{ $page['kicker'] ?? $pageKey }}</summary>
                                    <div class="mt-4 grid gap-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Etiqueta</label>
                                                <input name="portal_pages[{{ $pageKey }}][kicker]" value="{{ old('portal_pages.' . $pageKey . '.kicker', $page['kicker'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Modulo / hero</label>
                                                <input name="portal_pages[{{ $pageKey }}][hero]" value="{{ old('portal_pages.' . $pageKey . '.hero', $page['hero'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Titulo</label>
                                            <input name="portal_pages[{{ $pageKey }}][title]" value="{{ old('portal_pages.' . $pageKey . '.title', $page['title'] ?? '') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Texto introductorio</label>
                                            <textarea name="portal_pages[{{ $pageKey }}][intro]" rows="3" class="theme-soft w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none">{{ old('portal_pages.' . $pageKey . '.intro', $page['intro'] ?? '') }}</textarea>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Imagen de portada de esta pagina</label>
                                            <input name="portal_page_images[{{ $pageKey }}]" type="file" accept="image/jpeg,image/png,image/webp" class="theme-soft block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                        </div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Tarjetas / comunicados</label>
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Puntos clave / horarios</label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_35px_rgba(37,99,235,0.25)] transition hover:bg-blue-700">
                        {{ $sectionMeta['submit'] }}
                    </button>
                </form>
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
    const mapNode = document.querySelector('[data-coordinate-picker]');
    const markerNode = document.querySelector('[data-map-marker]');
    const popupNode = document.querySelector('[data-map-popup]');

    if (latInput && lngInput && mapNode && markerNode && popupNode) {
        const iconMap = {
            water: 'Agua',
            office: 'Oficina',
            pin: 'Ubicacion'
        };
        const bounds = {
            minLat: -23.0,
            maxLat: -9.0,
            minLng: -70.0,
            maxLng: -57.0,
        };
        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const readLat = () => clamp(parseFloat(latInput.value || '-16.5'), bounds.minLat, bounds.maxLat);
        const readLng = () => clamp(parseFloat(lngInput.value || '-68.15'), bounds.minLng, bounds.maxLng);
        const latToY = (lat) => ((bounds.maxLat - lat) / (bounds.maxLat - bounds.minLat)) * 100;
        const lngToX = (lng) => ((lng - bounds.minLng) / (bounds.maxLng - bounds.minLng)) * 100;
        const yToLat = (y) => bounds.maxLat - (y / 100) * (bounds.maxLat - bounds.minLat);
        const xToLng = (x) => bounds.minLng + (x / 100) * (bounds.maxLng - bounds.minLng);

        const syncMarker = () => {
            const lat = readLat();
            const lng = readLng();
            markerNode.style.left = `${clamp(lngToX(lng), 4, 96)}%`;
            markerNode.style.top = `${clamp(latToY(lat), 12, 96)}%`;
            popupNode.textContent = `${iconMap[iconSelect?.value || 'pin']} - ${labelInput?.value || 'Ubicacion de EPSAS'} (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
        };

        mapNode.addEventListener('click', (event) => {
            if (event.target.closest('[data-map-popup]')) {
                return;
            }

            const rect = mapNode.getBoundingClientRect();
            const x = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 100);
            const y = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 100);
            latInput.value = yToLat(y).toFixed(6);
            lngInput.value = xToLng(x).toFixed(6);
            syncMarker();
        });

        [latInput, lngInput, labelInput, iconSelect].forEach((input) => input?.addEventListener('change', syncMarker));
        syncMarker();
    }
</script>
@endpush
