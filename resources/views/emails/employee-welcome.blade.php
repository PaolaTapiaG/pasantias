<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso al sistema EPSAS</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#2563eb;font-weight:700;">EPSAS</p>
        <h1 style="margin:0 0 20px;font-size:24px;line-height:1.3;">Bienvenido al sistema</h1>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
            Hola {{ $user->name }}, tu cuenta fue creada correctamente.
        </p>

        <div style="margin:20px 0;padding:18px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;">
            <p style="margin:0 0 8px;font-size:14px;"><strong>Usuario:</strong> {{ $user->username }}</p>
            <p style="margin:0 0 8px;font-size:14px;"><strong>Correo:</strong> {{ $user->email }}</p>
            <p style="margin:0;font-size:14px;"><strong>Contrasena temporal:</strong> {{ $temporaryPassword }}</p>
        </div>

        <p style="margin:0 0 12px;font-size:15px;line-height:1.7;">
            Por seguridad, cambia tu contrasena cuando ingreses por primera vez.
        </p>

        <p style="margin:24px 0 0;font-size:13px;color:#475569;">
            Si no reconoces este registro, comunicate con el administrador del sistema.
        </p>
    </div>
</body>
</html>
