# Guia de mensajeria real EPSAS

## Correo real con Brevo

1. Crea una cuenta gratuita en Brevo.
2. Ve a SMTP and API settings.
3. Genera una SMTP key.
4. Autentica tu dominio o usa un remitente valido.
5. En `admin/configuracion` coloca:
   - Mailer: `SMTP real`
   - SMTP host: `smtp-relay.brevo.com`
   - Puerto: `587`
   - Cifrado: `TLS`
   - Usuario SMTP: tu login SMTP de Brevo
   - Contrasena SMTP: tu SMTP key
   - Correo remitente: tu remitente verificado
   - Nombre remitente: `EPSAS`

## SMS real con Android propio

Proveedor recomendado: `SMS Gateway for Android`

Fuentes oficiales:
- https://docs.sms-gate.app/getting-started/
- https://docs.sms-gate.app/integration/api/
- https://docs.sms-gate.app/integration/authentication/

### Opcion simple

1. Instala la app `SMS Gateway for Android` en un telefono Android.
2. Coloca una SIM boliviana con saldo o plan SMS.
3. En la app usa modo cloud o private server.
4. Copia el usuario y contrasena del gateway.
5. En `admin/configuracion` coloca:
   - Driver: `Gateway propio Android`
   - Proveedor de gateway: `SMS Gateway for Android`
   - URL del gateway: `https://api.sms-gate.app/3rdparty/v1/message`
   - Usuario del gateway: usuario que muestra la app
   - Contrasena del gateway: contrasena que muestra la app
   - Device ID: si usas multi dispositivo, el que te entregue la app
   - Codigo de pais: `+591`

### Opcion privada

1. Monta el private server de SMS Gateway.
2. Usa la URL de tu servidor, por ejemplo:
   - `https://tu-dominio/3rdparty/v1/message`
3. Mantén el telefono Android enlazado a ese servidor.
4. Usa las mismas credenciales dentro de EPSAS.

## Pruebas

1. Guarda configuracion.
2. Ve a `admin/configuracion/sms-gateway`.
3. Envia un SMS de prueba a un numero boliviano real.
4. Crea un empleado nuevo con correo y telefono reales.
5. Revisa que llegue:
   - correo con la contrasena temporal
   - SMS con la contrasena temporal

## Nota

El SMS no es gratis a nivel operador. El gateway es gratis/self-hosted, pero la SIM boliviana sigue cobrando o consumiendo tu plan SMS.
