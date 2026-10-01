<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $factura->numero_factura }} - {{ $company['company_name'] ?? 'EPSAS' }}</title>
    <style>
        @page { margin: 12mm 13mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #20252b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .invoice-sheet { border-top: 0; }
        .institution-header { display: table; width: 100%; border-bottom: 1px solid #8d9b92; padding-bottom: 8px; }
        .institution-header > div { display: table-cell; vertical-align: middle; }
        .institution-mark { width: 82px; text-align: center; }
        .institution-mark img { display: block; max-width: 75px; max-height: 52px; margin: auto; }
        .institution-mark strong { color: #1f5d42; font-size: 14px; }
        .institution-copy { padding: 0 8px; color: #4d555c; line-height: 1.45; }
        .institution-title { color: #1f5d42; font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .institution-subtitle { color: #20252b; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .document-meta { width: 165px; border-left: 1px solid #cbd5d1; padding-left: 8px; line-height: 1.55; }
        .document-label { color: #1f5d42; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .identity-grid { display: table; width: 100%; margin: 9px 0; border: 1px solid #9aa9a1; }
        .identity-grid > div { display: table-cell; width: 17%; padding: 4px 5px; border-right: 1px solid #d3dbd6; vertical-align: top; }
        .identity-grid > div:last-child { border-right: 0; }
        .identity-grid .identity-wide { width: 25%; }
        .identity-grid span { display: block; color: #637269; font-size: 6.5px; font-weight: 700; text-transform: uppercase; }
        .identity-grid b { display: block; margin-top: 2px; font-size: 8px; }
        .boleta-table { width: 100%; border-collapse: collapse; }
        .boleta-table th { background: #e5eee9; color: #1f5d42; font-size: 7px; text-transform: uppercase; }
        .boleta-table th, .boleta-table td { border: 1px solid #8d9b92; padding: 5px 6px; }
        .reading-table td { height: 37px; text-align: center; }
        .observation { border: 1px solid #8d9b92; border-top: 0; min-height: 22px; padding: 5px 6px; }
        .charges-section { margin-top: 10px; }
        .amount { text-align: right; white-space: nowrap; }
        .current-total, .debt-total { display: table; width: 205px; margin: 5px 0 0 auto; border: 1.5px solid #1f5d42; padding: 5px 7px; color: #1f5d42; font-size: 9px; }
        .current-total span, .current-total b, .debt-total span, .debt-total b { display: table-cell; }
        .current-total b, .debt-total b { text-align: right; }
        .debt-section { margin-top: 11px; }
        .debt-section h2 { margin: 0 0 4px; color: #1f5d42; font-size: 10px; text-transform: uppercase; }
        .empty-debt { text-align: center; color: #637269; }
        .invoice-footer { margin-top: 11px; border-top: 1px solid #9aa9a1; padding-top: 6px; color: #637269; font-size: 7px; line-height: 1.45; }
    </style>
</head>
<body>
    @include('facturas.partials.boleta-epsas')
</body>
</html>
