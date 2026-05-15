<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Codigo de recuperacion EPSAS</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#2563eb;font-weight:700;">EPSAS</p>
        <h1 style="margin:0 0 20px;font-size:24px;line-height:1.3;">Codigo de recuperacion</h1>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
            Hola {{ $user->name }}, recibimos una solicitud para restablecer tu contrasena.
        </p>

        <div style="margin:20px 0;padding:18px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;text-align:center;">
            <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:0.08em;color:#1d4ed8;">Codigo</p>
            <p style="margin:0;font-size:32px;font-weight:700;letter-spacing:0.18em;">{{ $code }}</p>
        </div>

        <p style="margin:0 0 12px;font-size:15px;line-height:1.7;">
            Este codigo vence en 10 minutos. Si no solicitaste este cambio, ignora este mensaje.
        </p>
    </div>
</body>
</html>
