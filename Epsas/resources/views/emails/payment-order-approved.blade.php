@php
    $cliente = $orden->socio?->persona?->nombre_completo ?? 'Cliente EPSAS';
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pago aprobado</title>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px;background:#fff7ed;border-bottom:1px solid #fed7aa;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:800;letter-spacing:3px;color:#ea580c;text-transform:uppercase;">EPSAS</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;color:#0f172a;">Pago aprobado</h1>
                            <p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#475569;">Hola {{ $cliente }}, tu orden {{ $orden->codigo }} fue verificada correctamente.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding:16px;border:1px solid #e2e8f0;border-radius:18px;">
                                        <p style="margin:0;font-size:13px;color:#64748b;">Total aprobado</p>
                                        <p style="margin:8px 0 0;font-size:26px;font-weight:900;color:#ea580c;">Bs {{ number_format((float) $orden->total, 2) }}</p>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:24px 0 12px;font-size:18px;color:#0f172a;">Conceptos pagados</h2>
                            @foreach ($orden->detalles as $detalle)
                                <div style="margin-bottom:10px;padding:14px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;">
                                    <p style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">{{ $detalle->descripcion }}</p>
                                    <p style="margin:6px 0 0;font-size:13px;color:#475569;">Monto: Bs {{ number_format((float) $detalle->monto, 2) }}</p>
                                </div>
                            @endforeach

                            <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#475569;">
                                La factura fiscal electronica debe generarse despues de integrar el servicio del SIN. Este correo confirma la aprobacion operativa del pago.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
