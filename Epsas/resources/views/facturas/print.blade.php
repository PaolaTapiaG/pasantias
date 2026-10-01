<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $factura->numero_factura }} - {{ $company['company_name'] ?? 'EPSAS' }}</title>
    @php($paper = request()->query('paper') === '58' ? '58' : '80')
    <style>
        @page { size: {{ $paper }}mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f5; color: #20252b; font-family: Arial, sans-serif; font-size: 11px; }
        .print-toolbar { max-width: 820px; margin: 16px auto; display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
        .print-toolbar select, .print-toolbar button { border: 1px solid #1f5d42; background: #fff; color: #1f5d42; padding: 9px 12px; border-radius: 4px; cursor: pointer; }
        .print-toolbar button { background: #1f5d42; color: #fff; }
        .invoice-sheet { width: 100%; max-width: 820px; margin: 0 auto 24px; padding: 22px 26px; background: #fff; border-top: 0; box-shadow: 0 2px 12px rgba(32, 37, 43, .12); }
        .institution-header { display: table; width: 100%; border-bottom: 1px solid #8d9b92; padding-bottom: 12px; }
        .institution-header > div { display: table-cell; vertical-align: middle; }
        .institution-mark { width: 105px; text-align: center; }
        .institution-mark img { display: block; max-width: 98px; max-height: 68px; margin: auto; }
        .institution-mark strong { color: #1f5d42; font-size: 18px; }
        .institution-copy { padding: 0 12px; color: #4d555c; line-height: 1.5; }
        .institution-title { color: #1f5d42; font-size: 19px; font-weight: 700; text-transform: uppercase; }
        .institution-subtitle { color: #20252b; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .document-meta { width: 205px; border-left: 1px solid #cbd5d1; padding-left: 14px; line-height: 1.65; }
        .document-label { color: #1f5d42; font-size: 16px; font-weight: 700; text-transform: uppercase; margin-bottom: 3px; }
        .identity-grid { display: table; width: 100%; margin: 14px 0; border: 1px solid #9aa9a1; }
        .identity-grid > div { display: table-cell; width: 17%; padding: 6px 8px; border-right: 1px solid #d3dbd6; vertical-align: top; }
        .identity-grid > div:last-child { border-right: 0; }
        .identity-grid .identity-wide { width: 25%; }
        .identity-grid span { display: block; color: #637269; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .identity-grid b { display: block; margin-top: 3px; font-size: 10px; }
        .boleta-table { width: 100%; border-collapse: collapse; }
        .boleta-table th { background: #e5eee9; color: #1f5d42; font-size: 9px; text-transform: uppercase; }
        .boleta-table th, .boleta-table td { border: 1px solid #8d9b92; padding: 7px 8px; }
        .reading-table td { height: 48px; text-align: center; }
        .observation { border: 1px solid #8d9b92; border-top: 0; min-height: 28px; padding: 7px 8px; }
        .charges-section { margin-top: 10px; }
        .charges-table th:first-child, .charges-table td:first-child { text-align: left; }
        .amount { text-align: right; white-space: nowrap; }
        .subtotal-line { display: table; width: 55%; margin: 5px 0 0 auto; padding: 4px 6px; border-bottom: 1px solid #8d9b92; }
        .subtotal-line span, .subtotal-line b { display: table-cell; }
        .subtotal-line b { text-align: right; }
        .current-total, .debt-total { display: table; width: 280px; margin: 8px 0 0 auto; border: 2px solid #1f5d42; padding: 8px 10px; color: #1f5d42; font-size: 13px; }
        .current-total span, .current-total b, .debt-total span, .debt-total b { display: table-cell; }
        .current-total b, .debt-total b { text-align: right; }
        .debt-section { margin-top: 10px; }
        .debt-section h2 { margin: 0 0 6px; color: #1f5d42; font-size: 14px; text-transform: uppercase; }
        .debt-summary { margin-top: 5px; width: 55%; margin-left: auto; }
        .debt-summary td, .debt-summary th { padding: 4px 6px; }
        .cutoff-notice { margin-top: 7px; border: 1px solid #1f5d42; padding: 6px; color: #1f5d42; font-size: 9px; }
        .empty-debt { text-align: center; color: #637269; }
        .invoice-footer { margin-top: 10px; border-top: 1px solid #9aa9a1; padding-top: 6px; color: #637269; font-size: 9px; line-height: 1.35; }
        .customer-notice { margin: 5px 0; border: 1px solid #1f5d42; padding: 6px; color: #1f5d42; font-weight: 700; }
        .thermal-58 .institution-header > div, .thermal-58 .identity-grid > div { display: block; width: 100%; border: 0; }
        .thermal-58 .institution-header > div { text-align: center; padding: 4px 0; }
        .thermal-58 .document-meta { border-top: 1px solid #cbd5d1; border-left: 0; }
        .thermal-58 .identity-grid > div { border-bottom: 1px solid #d3dbd6; }
        .thermal-58 .identity-grid > div:last-child { border-bottom: 0; }
        .thermal-58 .boleta-table th, .thermal-58 .boleta-table td { padding: 4px 3px; font-size: 8px; overflow-wrap: anywhere; }
        .thermal-58 .reading-table, .thermal-58 .charges-table, .thermal-58 .debt-table { table-layout: fixed; }
        .thermal-58 .reading-table th, .thermal-58 .reading-table td { font-size: 7px; }
        .thermal-58 .current-total, .thermal-58 .debt-total { width: 100%; font-size: 10px; }
        .thermal-58 .subtotal-line { width: 100%; }
        .thermal-58 .debt-summary { width: 100%; }
        .thermal-58 .cutoff-notice { font-size: 8px; }
        .thermal-58 .debt-table th:nth-child(2), .thermal-58 .debt-table td:nth-child(2) { display: none; }
        .thermal-58 .institution-title { font-size: 15px; }
        .thermal-58 .institution-subtitle, .thermal-58 .institution-copy { font-size: 9px; }
        .thermal-58 .document-label { font-size: 12px; }
        .thermal-80 .invoice-sheet { padding: 12px 10px; }
        .thermal-80 .boleta-table th, .thermal-80 .boleta-table td { padding: 5px 4px; font-size: 9px; }
        .thermal-80 .debt-summary { width: 72%; }
        .thermal-80 .subtotal-line { width: 72%; }
        @media print {
            body { background: #fff; color: #000; }
            .print-toolbar { display: none; }
            .invoice-sheet { margin: 0; max-width: none; padding: 0; border-top: 0; box-shadow: none; }
            .institution-header, .boleta-table th, .boleta-table td, .identity-grid, .identity-grid > div, .observation, .invoice-footer, .customer-notice { border-color: #000; }
            .institution-mark strong, .institution-title, .institution-subtitle, .document-label, .boleta-table th, .debt-section h2, .current-total, .debt-total { color: #000; }
            .boleta-table th { background: #fff; }
            .cutoff-notice { color: #000; }
            .customer-notice { color: #000; }
        }
    </style>
</head>
<body class="thermal-{{ $paper }}">
    <div class="print-toolbar">
        <label for="paper">Ancho</label>
        <select id="paper" onchange="changePaper(this.value)">
            <option value="80" @selected($paper === '80')>80 mm</option>
            <option value="58" @selected($paper === '58')>58 mm</option>
        </select>
        <button type="button" onclick="window.print()">Imprimir termica</button>
    </div>
    @include('facturas.partials.boleta-epsas')
    <script>
        function changePaper(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('paper', value);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
