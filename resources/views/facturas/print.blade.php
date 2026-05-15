<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $factura->numero_factura }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #123057; font-size: 12px; margin: 30px; }
        .header { margin-bottom: 28px; }
        .brand-line { height: 5px; width: 240px; background: #2f69b7; margin-bottom: 10px; }
        .title { font-size: 38px; letter-spacing: 0.1em; color: #2f69b7; text-transform: lowercase; margin: 0; }
        .muted { color: #5c78a2; font-size: 11px; }
        .top-table, .summary-table, .reading-table { width: 100%; border-collapse: collapse; }
        .top-table td { vertical-align: top; padding: 4px 0; }
        .summary-table th, .summary-table td, .reading-table th, .reading-table td {
            border: 1px solid #7aa2d8;
            padding: 9px 10px;
        }
        .summary-table th, .reading-table th {
            color: #2f69b7; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; background: #f4f8ff;
        }
        .text-right { text-align: right; }
        .section-title { color: #2f69b7; font-size: 22px; margin: 0 0 12px; }
        .totals { width: 280px; margin-left: auto; margin-top: 20px; }
        .totals td { padding: 6px 0; }
        .total-box { margin-top: 16px; border: 2px solid #2f69b7; padding: 10px 14px; font-size: 18px; font-weight: bold; color: #2f69b7; }
        .logo-box { width: 82px; height: 82px; border-radius: 50%; border: 1px solid #a9bbd6; text-align: center; line-height: 82px; overflow: hidden; margin-left: auto; }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; }
        .spacer { height: 16px; }
        @media print { .print-hidden { display: none !important; } body { margin: 18px; } }
    </style>
</head>
<body onload="window.print()">
    @php
        $logoUrl = !empty($company['company_logo']) ? asset($company['company_logo']) : null;
    @endphp
    <div class="print-hidden" style="margin-bottom:16px;">
        <button onclick="window.print()">Imprimir</button>
    </div>
    <div class="header">
        <table class="top-table">
            <tr>
                <td style="width: 65%;">
                    <div class="brand-line"></div>
                    <h1 class="title">recibo</h1>
                    <p class="muted">{{ $company['company_name'] ?? 'EPSAS' }} | Recibo electronico emitido el {{ optional($factura->fecha_emision)->format('d/m/Y') }}</p>
                </td>
                <td style="width: 35%;">
                    <div class="logo-box">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo">
                        @else
                            LOGO
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="top-table">
        <tr>
            <td style="width: 52%; padding-right: 16px;">
                <p class="section-title">De</p>
                <div>{{ $company['company_name'] ?? 'EPSAS' }}</div>
                <div>{{ $company['address'] ?? 'Direccion pendiente' }}</div>
                <div>{{ $company['company_phone'] ?: 'Telefono pendiente' }}</div>
                <div>{{ $company['company_email'] ?: 'Correo pendiente' }}</div>
            </td>
            <td style="width: 48%;">
                <table style="width: 100%;">
                    <tr><td style="color:#2f69b7; text-transform:uppercase; letter-spacing:0.08em;">N° de recibo</td><td class="text-right">{{ $factura->numero_factura }}</td></tr>
                    <tr><td style="color:#2f69b7; text-transform:uppercase; letter-spacing:0.08em;">Fecha</td><td class="text-right">{{ optional($factura->fecha_emision)->format('d/m/Y') }}</td></tr>
                    <tr><td style="color:#2f69b7; text-transform:uppercase; letter-spacing:0.08em;">Codigo usuario</td><td class="text-right">{{ $billingBreakdown['codigo_usuario'] }}</td></tr>
                    <tr><td style="color:#2f69b7; text-transform:uppercase; letter-spacing:0.08em;">Periodo</td><td class="text-right">{{ $factura->periodo?->nombre }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="spacer"></div>
    <table class="top-table">
        <tr>
            <td style="width: 50%; padding-right: 16px;">
                <p class="section-title">Facturar a</p>
                <div>{{ $factura->socio?->persona?->nombre_completo }}</div>
                <div>{{ $factura->socio?->direccion ?: 'Direccion pendiente' }}</div>
                <div>{{ $factura->socio?->sector?->nombre ?: 'Sin sector' }}</div>
            </td>
            <td style="width: 50%;">
                <p class="section-title">Enviar a</p>
                <div>{{ $factura->socio?->persona?->email ?: 'Correo no registrado' }}</div>
                <div>{{ $factura->socio?->persona?->telefono ?: 'Telefono no registrado' }}</div>
                <div>Medidor: {{ $factura->lectura?->medidor?->numero_serie ?: 'Sin medidor' }}</div>
            </td>
        </tr>
    </table>

    <div class="spacer"></div>
    <table class="reading-table">
        <thead>
            <tr>
                <th>Lectura anterior</th>
                <th>Lectura actual</th>
                <th>Consumo m3</th>
                <th>M3 excedente</th>
                <th>Tarifa excedente</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format((float) $billingBreakdown['previous_reading'], 2) }}</td>
                <td>{{ number_format((float) $billingBreakdown['current_reading'], 2) }}</td>
                <td>{{ number_format((float) $billingBreakdown['consumed_m3'], 2) }}</td>
                <td>{{ number_format((float) $billingBreakdown['excess_m3'], 2) }}</td>
                <td class="text-right">Bs {{ number_format((float) $billingBreakdown['excess_rate'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="spacer"></div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Descripcion</th>
                <th class="text-right">Precio unitario</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>1</td><td>Cargo fijo agua (0 a {{ number_format((float) $billingBreakdown['included_m3'], 0) }} m3)</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['fixed_charge'], 2) }}</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['fixed_charge'], 2) }}</td></tr>
            <tr><td>{{ number_format((float) $billingBreakdown['excess_m3'], 2) }}</td><td>Excedente de consumo</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['excess_rate'], 2) }}</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['excess_charge'], 2) }}</td></tr>
            <tr><td>1</td><td>Cargo fijo alcantarillado</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['sewer_fixed_charge'], 2) }}</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['sewer_fixed_charge'], 2) }}</td></tr>
            <tr><td>1</td><td>Mora por saldo anterior</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['mora_saldo_anterior'], 2) }}</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['mora_saldo_anterior'], 2) }}</td></tr>
            <tr><td>1</td><td>Multa corte / reconexion</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['cutoff_penalty'], 2) }}</td><td class="text-right">Bs {{ number_format((float) $billingBreakdown['cutoff_penalty'], 2) }}</td></tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">Bs {{ number_format((float) $resumenCobro['subtotal'], 2) }}</td></tr>
        <tr><td>Total pagado</td><td class="text-right">Bs {{ number_format((float) $resumenCobro['pagado'], 2) }}</td></tr>
        <tr><td>Saldo pendiente</td><td class="text-right">Bs {{ number_format((float) $resumenCobro['pendiente'], 2) }}</td></tr>
    </table>

    <div class="total-box">TOTAL: <span style="float:right;">Bs {{ number_format((float) $factura->total, 2) }}</span></div>
</body>
</html>
