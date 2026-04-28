<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $factura->numero_factura }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; margin: 32px; }
        .header, .section, .summary { margin-bottom: 24px; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 8px; }
        .muted { color: #64748b; font-size: 11px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 8px 0; vertical-align: top; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; }
        .summary-table, .payments-table { width: 100%; border-collapse: collapse; }
        .summary-table td, .summary-table th, .payments-table td, .payments-table th {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }
        .summary-table th, .payments-table th { background: #eff6ff; }
        .text-right { text-align: right; }
        .mb-8 { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Factura {{ $factura->numero_factura }}</div>
        <div class="muted">EPSAS | Emitida el {{ optional($factura->fecha_emision)->format('d/m/Y') }}</div>
    </div>

    <table class="grid section">
        <tr>
            <td style="width: 50%; padding-right: 12px;">
                <div class="card">
                    <strong>Datos del socio</strong>
                    <div class="mb-8"></div>
                    <div>Nombre: {{ $factura->socio?->persona?->nombre_completo }}</div>
                    <div>Codigo: {{ $factura->socio?->codigo_display }}</div>
                    <div>Sector: {{ $factura->socio?->sector?->nombre ?: 'Sin sector' }}</div>
                    <div>Telefono: {{ $factura->socio?->persona?->telefono ?: 'Sin registro' }}</div>
                    <div>Correo: {{ $factura->socio?->persona?->email ?: 'Sin registro' }}</div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 12px;">
                <div class="card">
                    <strong>Datos de facturacion</strong>
                    <div class="mb-8"></div>
                    <div>Periodo: {{ $factura->periodo?->nombre }}</div>
                    <div>Estado: {{ ucfirst($factura->estado) }}</div>
                    <div>Fecha de pago: {{ optional($factura->fecha_pago)->format('d/m/Y') ?: 'Pendiente' }}</div>
                    <div>Consumo: {{ number_format((float) $factura->consumo_m3, 2) }} m3</div>
                    <div>Medidor: {{ $factura->lectura?->medidor?->numero_serie ?: 'Sin medidor' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="summary">
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Consumo</td>
                    <td class="text-right">Bs {{ number_format((float) $factura->monto_consumo, 2) }}</td>
                </tr>
                <tr>
                    <td>Cargo fijo</td>
                    <td class="text-right">Bs {{ number_format((float) $factura->cargo_fijo, 2) }}</td>
                </tr>
                <tr>
                    <td>Recargo mora</td>
                    <td class="text-right">Bs {{ number_format((float) $factura->recargo_mora, 2) }}</td>
                </tr>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">Bs {{ number_format((float) $resumenCobro['subtotal'], 2) }}</td>
                </tr>
                <tr>
                    <td>Total facturado</td>
                    <td class="text-right">Bs {{ number_format((float) $factura->total, 2) }}</td>
                </tr>
                <tr>
                    <td>Total pagado</td>
                    <td class="text-right">Bs {{ number_format((float) $resumenCobro['pagado'], 2) }}</td>
                </tr>
                <tr>
                    <td>Saldo pendiente</td>
                    <td class="text-right">Bs {{ number_format((float) $resumenCobro['pendiente'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Metodo</th>
                    <th>Referencia</th>
                    <th>Estado</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($factura->cobros as $cobro)
                    <tr>
                        <td>{{ optional($cobro->fecha_cobro)->format('d/m/Y') }}</td>
                        <td>{{ $cobro->metodoPago?->nombre ?: 'Sin metodo' }}</td>
                        <td>{{ $cobro->comprobante ?: 'Sin referencia' }}</td>
                        <td>{{ ucfirst($cobro->estado) }}</td>
                        <td class="text-right">Bs {{ number_format((float) $cobro->monto_pagado, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No existen cobros registrados para esta factura.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
