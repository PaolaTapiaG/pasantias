@php
    $inicioCobro = $factura->fecha_inicio_cobro ?? $factura->periodo?->fecha_inicio;
    $finCobro = $factura->fecha_fin_cobro ?? $factura->periodo?->fecha_fin;
    $logoSource = $companyLogoDataUri ?? (!empty($company['company_logo']) ? asset($company['company_logo']) : null);
    $companyName = $company['company_name'] ?? 'EPSAS';
    $companyAlias = 'Serv. de Agua Potable y Alcantarillado Sanitario';
    $debts = collect($facturasAdeudadas ?? []);
    $debtTotal = $debts->sum(fn ($debt) => (float) $debt->saldo_pendiente);
    $debtSubtotal = $debts->sum(fn ($debt) => max(0, (float) $debt->monto_consumo + (float) $debt->cargo_fijo - (float) $debt->descuentos));
    $debtVarious = $debts->sum(fn ($debt) => (float) $debt->recargo_mora);
    $totalWithDebt = round((float) $factura->total + $debtTotal, 2);
    $cutoffRecommended = $debts->count() >= 3;
    $fechaAnterior = $inicioCobro?->copy()->subMonth();
@endphp

<div class="invoice-sheet">
    <header class="institution-header">
        <div class="institution-mark">
            @if ($logoSource)
                <img src="{{ $logoSource }}" alt="Logo {{ $companyName }}">
            @else
                <strong>{{ $companyName }}</strong>
            @endif
        </div>
        <div class="institution-copy">
            <div class="institution-title">{{ $companyName }}</div>
            <div class="institution-subtitle">{{ $companyAlias }}</div>
            <div>{{ $company['address'] ?? 'Direccion institucional' }}</div>
            <div>{{ $company['company_phone'] ?? 'Telefono institucional' }} · {{ $company['company_email'] ?? 'Correo institucional' }}</div>
        </div>
        <div class="document-meta">
            <div class="document-label">Aviso de cobranza</div>
            <div><b>N° factura:</b> {{ $factura->numero_factura }}</div>
            <div><b>Emision:</b> {{ optional($factura->fecha_emision)->format('d/m/Y') }}</div>
            <div><b>Periodo:</b> {{ $factura->periodo?->nombre ?: optional($factura->fecha_emision)->format('m/Y') }}</div>
        </div>
    </header>

    <section class="identity-grid">
        <div><span>Codigo usuario</span><b>{{ $billingBreakdown['codigo_usuario'] }}</b></div>
        <div class="identity-wide"><span>Nombre</span><b>{{ $factura->socio?->persona?->nombre_completo ?: 'Sin nombre registrado' }}</b></div>
        <div class="identity-wide"><span>Direccion</span><b>{{ $factura->socio?->direccion ?: 'Sin direccion registrada' }}</b></div>
        <div><span>Categoria</span><b>{{ $factura->socio?->tarifa?->nombre ?: 'Sin categoria' }}</b></div>
        <div><span>Medidor</span><b>{{ $billingBreakdown['numero_medidor'] }}</b></div>
    </section>

    <section class="reading-section">
        <table class="boleta-table reading-table">
            <thead><tr><th>Fecha anterior</th><th>Fecha actual</th><th>Dias</th><th>Lectura</th><th>Consumo</th></tr></thead>
            <tbody><tr>
                <td>{{ optional($fechaAnterior)->format('d/m/Y') }}</td>
                <td>{{ optional($factura->fecha_emision)->format('d/m/Y') }}</td>
                <td>{{ $inicioCobro && $finCobro ? $inicioCobro->diffInDays($finCobro) + 1 : '-' }} dias</td>
                <td>ANT: {{ number_format((float) $billingBreakdown['previous_reading'], 0) }}<br>ACT: {{ number_format((float) $billingBreakdown['current_reading'], 0) }}</td>
                <td>{{ number_format((float) $billingBreakdown['consumed_m3'], 0) }} m3</td>
            </tr></tbody>
        </table>
    </section>

    <section class="charges-section">
        <table class="boleta-table charges-table">
            <thead><tr><th>Concepto</th><th class="amount">Importe Bs.</th></tr></thead>
            <tbody>
                <tr><td>Cargo fijo agua</td><td class="amount">{{ number_format((float) $billingBreakdown['fixed_charge'], 2) }}</td></tr>
                <tr><td>Cargo consumo ({{ number_format((float) $billingBreakdown['excess_m3'], 0) }} m3 excedente)</td><td class="amount">{{ number_format((float) $billingBreakdown['excess_charge'], 2) }}</td></tr>
                <tr><td>Cargo alcantarillado</td><td class="amount">{{ number_format((float) $billingBreakdown['sewer_fixed_charge'], 2) }}</td></tr>
                <tr><td>Mora por saldo anterior</td><td class="amount">{{ number_format((float) $billingBreakdown['mora_saldo_anterior'], 2) }}</td></tr>
                <tr><td>Multa corte / reconexion</td><td class="amount">{{ number_format((float) $billingBreakdown['cutoff_penalty'], 2) }}</td></tr>
            </tbody>
        </table>
        <div class="subtotal-line"><span>Subtotal servicios</span><b>Bs {{ number_format((float) $billingBreakdown['subtotal'], 2) }}</b></div>
        <div class="current-total"><span>TOTAL FACTURA</span><b>Bs {{ number_format((float) $factura->total, 2) }}</b></div>
    </section>

    <section class="debt-section">
        <h2>Facturas adeudadas</h2>
        <table class="boleta-table debt-table">
            <thead><tr><th>Mes / año</th><th>Factura</th><th>Consumo</th><th>Servicios Bs.</th><th>Varios Bs.</th><th>Saldo Bs.</th></tr></thead>
            <tbody>
                @forelse ($debts as $debt)
                    <tr>
                        <td>{{ $debt->periodo_nombre ?: optional($debt->fecha_emision)->format('m/Y') }}</td>
                        <td>{{ $debt->numero_factura }}</td>
                        <td>{{ number_format((float) $debt->consumo_m3, 0) }} m3</td>
                        <td class="amount">{{ number_format(max(0, (float) $debt->monto_consumo + (float) $debt->cargo_fijo - (float) $debt->descuentos), 2) }}</td>
                        <td class="amount">{{ number_format((float) $debt->recargo_mora, 2) }}</td>
                        <td class="amount">{{ number_format((float) $debt->saldo_pendiente, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-debt">No existen otras facturas pendientes.</td></tr>
                @endforelse
            </tbody>
        </table>
        <table class="boleta-table debt-summary">
            <tr><td>Subtotal servicios pendientes</td><td class="amount">Bs {{ number_format((float) $debtSubtotal, 2) }}</td></tr>
            <tr><td>Varios / mora registrada</td><td class="amount">Bs {{ number_format((float) $debtVarious, 2) }}</td></tr>
            <tr><th>Saldo pendiente total</th><th class="amount">Bs {{ number_format((float) $debtTotal, 2) }}</th></tr>
        </table>
        @if ($cutoffRecommended)
            <div class="cutoff-notice"><b>Aviso de corte:</b> registra {{ $debts->count() }} facturas pendientes. El corte y las multas se aplican solo conforme a las reglas operativas del sistema.</div>
        @endif
        <div class="debt-total"><span>Total a cancelar</span><b>Bs {{ number_format((float) $totalWithDebt, 2) }}</b></div>
    </section>

    <footer class="invoice-footer">
        <div><b>Estado:</b> {{ ucfirst($factura->estado) }} · <b>Saldo de esta factura:</b> Bs {{ number_format((float) $resumenCobro['pendiente'], 2) }}</div>
        <div class="customer-notice">Por cualquier reclamo o consulta pasar por la oficina de EPSA El Portillo o llamar al 64565559. El reclamo por cualquier factura podra ser realizado 30 dias posterior a la emision.</div>
        <div>{{ $companyName }} · Documento personalizado para consulta y cobranza. Conserve este aviso para sus registros.</div>
    </footer>
</div>
