@php
    $persona = $socio->persona;
    $medidor = $socio->medidorActivo;
    $zona = trim(($socio->sector?->nombre ?? 'Sin zona') . (($socio->sector?->zona ?? null) ? ' - ' . $socio->sector->zona : ''));
    $inicial = strtoupper(substr($persona?->nombres ?? 'S', 0, 1));
    $companyName = $carnetSettings['company_name'] ?? 'EPSAS';
    $companyAlias = $carnetSettings['company_alias'] ?? 'Servicio de agua potable';
    $logoUrl = !empty($carnetSettings['company_logo']) ? asset($carnetSettings['company_logo']) : null;
    $backText = $carnetSettings['carnet_back_text'] ?? 'Este carnet identifica al socio registrado en EPSAS.';
    $defaultFee = number_format((float) ($carnetSettings['carnet_fee'] ?? 10), 2, '.', '');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnet {{ $socio->codigo_display }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #e2e8f0;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            padding: 28px;
        }
        .toolbar {
            position: fixed;
            top: 18px;
            right: 18px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .toolbar a,
        .toolbar button,
        .income-panel button {
            border: 0;
            border-radius: 12px;
            background: #0f172a;
            color: #fff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }
        .workspace {
            display: grid;
            grid-template-columns: minmax(0, auto) 320px;
            gap: 28px;
            align-items: start;
            justify-content: center;
            margin-top: 54px;
        }
        .carnet-layout {
            display: grid;
            grid-template-columns: repeat(2, 88mm);
            gap: 18px;
            align-items: start;
        }
        .side-label {
            margin: 0 0 8px;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .card {
            width: 88mm;
            min-height: 56mm;
            overflow: hidden;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
            border: 1px solid #cbd5e1;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: linear-gradient(135deg, #0f766e, #2563eb);
            color: #fff;
            padding: 12px 14px;
        }
        .brand {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .08em;
            line-height: 1.05;
            text-transform: uppercase;
        }
        .subtitle {
            margin-top: 3px;
            color: #dbeafe;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .code {
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            padding: 6px 9px;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }
        .card-body {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 12px;
            padding: 14px;
        }
        .photo {
            display: grid;
            height: 34mm;
            width: 28mm;
            place-items: center;
            overflow: hidden;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 28px;
            font-weight: 900;
        }
        .photo img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }
        .name {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .grid {
            display: grid;
            gap: 6px;
        }
        .field {
            display: grid;
            grid-template-columns: 24mm 1fr;
            gap: 6px;
            align-items: baseline;
            font-size: 10px;
        }
        .label {
            color: #64748b;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .value {
            color: #0f172a;
            font-weight: 800;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 14px 12px;
            font-size: 9px;
            font-weight: 700;
        }
        .back-card {
            display: grid;
            min-height: 56mm;
            grid-template-rows: auto 1fr auto;
        }
        .back-main {
            display: grid;
            place-items: center;
            gap: 10px;
            padding: 14px 18px;
            text-align: center;
        }
        .logo-box {
            display: grid;
            height: 22mm;
            min-width: 36mm;
            place-items: center;
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .08em;
            padding: 8px;
        }
        .logo-box img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .back-text {
            margin: 0;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.45;
            white-space: pre-line;
        }
        .signature-line {
            margin: 0 auto;
            width: 48mm;
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            color: #64748b;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .income-panel {
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .16);
            padding: 18px;
        }
        .income-panel h2 {
            margin: 0;
            font-size: 18px;
        }
        .income-panel p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
        }
        .alert {
            margin-bottom: 14px;
            border-radius: 12px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 12px;
        }
        .income-panel label {
            display: block;
            margin: 14px 0 7px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .income-panel input,
        .income-panel textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            font: inherit;
            font-size: 14px;
            outline: none;
            padding: 10px 12px;
        }
        .income-panel textarea {
            min-height: 82px;
            resize: vertical;
        }
        .income-panel button {
            margin-top: 14px;
            width: 100%;
            background: #047857;
        }
        .income-list {
            margin-top: 16px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
        }
        .income-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .income-item + .income-item {
            margin-top: 8px;
        }
        .error {
            margin-top: 6px;
            color: #be123c;
            font-size: 12px;
            font-weight: 700;
        }
        @media (max-width: 980px) {
            .workspace {
                grid-template-columns: 1fr;
                justify-items: center;
            }
            .carnet-layout {
                grid-template-columns: 88mm;
            }
            .income-panel {
                width: min(100%, 88mm);
            }
        }
        @media print {
            @page { margin: 8mm; size: auto; }
            body {
                background: #fff;
                min-height: auto;
                padding: 0;
            }
            .toolbar,
            .income-panel,
            .side-label {
                display: none;
            }
            .workspace {
                display: block;
                margin: 0;
            }
            .carnet-layout {
                display: grid;
                grid-template-columns: repeat(2, 88mm);
                gap: 10mm;
            }
            .card {
                box-shadow: none;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('admin.socios.show', $socio) }}">Volver</a>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="workspace">
        <section class="carnet-layout" aria-label="Carnet de socio">
            <article>
                <p class="side-label">Anverso</p>
                <div class="card">
                    <header class="card-header">
                        <div>
                            <div class="brand">{{ $companyName }}</div>
                            <div class="subtitle">Carnet de socio</div>
                        </div>
                        <div class="code">{{ $socio->codigo_display }}</div>
                    </header>

                    <section class="card-body">
                        <div class="photo">
                            @if ($persona?->foto_url)
                                <img src="{{ $persona->foto_url }}" alt="Foto de {{ $persona->nombre_completo }}">
                            @else
                                {{ $inicial }}
                            @endif
                        </div>

                        <div>
                            <h1 class="name">{{ $persona?->nombre_completo ?? 'Socio sin nombre' }}</h1>
                            <div class="grid">
                                <div class="field">
                                    <span class="label">CI</span>
                                    <span class="value">{{ $persona?->cedula_identidad ?: 'No registrado' }}</span>
                                </div>
                                <div class="field">
                                    <span class="label">ID socio</span>
                                    <span class="value">#{{ $socio->id_socio }}</span>
                                </div>
                                <div class="field">
                                    <span class="label">Medidor</span>
                                    <span class="value">{{ $medidor?->numero_serie ?: 'Sin medidor' }}</span>
                                </div>
                                <div class="field">
                                    <span class="label">Zona</span>
                                    <span class="value">{{ $zona }}</span>
                                </div>
                                <div class="field">
                                    <span class="label">Estado</span>
                                    <span class="value">{{ ucfirst($socio->estado) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <footer class="footer">
                        <span>Documento interno de identificacion</span>
                        <span>{{ now()->format('d/m/Y') }}</span>
                    </footer>
                </div>
            </article>

            <article>
                <p class="side-label">Reverso</p>
                <div class="card back-card">
                    <header class="card-header">
                        <div>
                            <div class="brand">{{ $companyName }}</div>
                            <div class="subtitle">{{ $companyAlias }}</div>
                        </div>
                        <div class="code">ID #{{ $socio->id_socio }}</div>
                    </header>

                    <section class="back-main">
                        <div class="logo-box">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo {{ $companyName }}">
                            @else
                                {{ \Illuminate\Support\Str::limit($companyName, 10, '') }}
                            @endif
                        </div>
                        <p class="back-text">{{ $backText }}</p>
                        <div class="signature-line">Firma autorizada</div>
                    </section>

                    <footer class="footer">
                        <span>{{ $socio->codigo_display }}</span>
                        <span>Emitido {{ now()->format('d/m/Y') }}</span>
                    </footer>
                </div>
            </article>
        </section>

        <aside class="income-panel">
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            <h2>Ingreso por carnet</h2>
            <p>Registra el cobro de emision para que se sume a ingresos.</p>

            <form method="POST" action="{{ route('admin.socios.carnet.ingreso', $socio) }}">
                @csrf
                <label for="monto">Monto</label>
                <input id="monto" name="monto" type="number" step="0.01" min="0.01" value="{{ old('monto', $defaultFee) }}">
                @error('monto')
                    <div class="error">{{ $message }}</div>
                @enderror

                <label for="descripcion">Detalle</label>
                <textarea id="descripcion" name="descripcion">{{ old('descripcion', 'Carnetizacion de ' . ($persona?->nombre_completo ?? $socio->codigo_display)) }}</textarea>
                @error('descripcion')
                    <div class="error">{{ $message }}</div>
                @enderror

                <button type="submit">Registrar ingreso</button>
            </form>

            @if ($ingresosCarnet->isNotEmpty())
                <div class="income-list">
                    <p>Ultimos registros</p>
                    @foreach ($ingresosCarnet as $ingreso)
                        <div class="income-item">
                            <span>{{ $ingreso->fecha_ingreso?->format('d/m/Y') }}</span>
                            <span>Bs {{ number_format((float) $ingreso->monto, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </main>
</body>
</html>
