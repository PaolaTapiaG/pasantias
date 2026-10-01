# Auditoria de preproduccion EPSAS

> Documento historico archivado. No representa el estado actual del CRM.
> Para el backlog vigente consultar `AUDITORIA_DEUDA_TECNICA.md` y ejecutar
> `php artisan app:production-readiness`.

Fecha: 10 de junio de 2026

## Dictamen

El sistema esta funcionalmente encaminado y puede continuar a una etapa piloto controlada, pero todavia no debe publicarse como produccion empresarial definitiva.

## Validaciones superadas

- Auditoria responsive automatizada sobre 57 pantallas de administrador, 18 de secretaria y 26 de tecnico.
- Cada pantalla fue verificada en escritorio, tablet y celular.
- Resultado responsive: 0 errores HTTP y 0 desbordes de pagina.
- Prueba realista de 1000 usuarios publicos: 6.471 solicitudes correctas, 0 errores, promedio 34,19 ms y p95 100,62 ms.
- Pantallas principales autenticadas: aproximadamente 29 a 99 ms con cache preparada.
- Inicio de sesion con cache preparada: aproximadamente 460 a 600 ms. Este costo es aceptable porque incluye la validacion segura de la contrasena.
- Pruebas automatizadas: 37 aprobadas, 93 aserciones.
- Auditorias de Composer y npm: 0 vulnerabilidades conocidas.
- Portal publico redisenado y verificado en escritorio, tablet y celular, sin desbordes.
- Todas las paginas publicas informativas y de pagos responden correctamente; las rutas cacheadas se mantienen en decenas de milisegundos.

## Correcciones aplicadas

- Navegacion responsive para tablets, celulares y escritorio.
- Tablas contenidas con desplazamiento horizontal interno.
- Menus laterales adaptados para tablet.
- Correccion del marcador `public/hot` que hacia cargar pantallas sin estilos cuando el servidor de desarrollo no estaba activo.
- Textos largos, formularios y controles ajustados para no salir de pantalla.
- Cache versionada para detalles de facturas, cobros, socios, carnets, empleados y archivos privados; se invalida al modificar datos operativos.
- Bloqueo transaccional al generar facturas para impedir facturacion doble de una lectura.
- Restriccion unica de base de datos entre factura y lectura.
- Correccion del calculo para respetar la tarifa asignada a cada socio.
- Valores de tarifa, consumo minimo, umbral de corte y reconexion guardados en cada factura para conservar el historico.
- Bloqueo y segunda validacion al aprobar o rechazar ordenes de pago.
- Prevencion de ordenes activas duplicadas y comprobantes bancarios reutilizados.
- Restricciones de montos positivos y cobro unico por orden/factura.
- Comprobantes, fotos personales y evidencias tecnicas movidos de almacenamiento publico a privado.
- Claves de correo y SMS configurables cifradas con la clave de la aplicacion.
- Limite de intentos para inicio de sesion y recuperacion de cuenta.
- Cambio obligatorio de contrasenas temporales y de cuentas que aun conservaban claves iniciales conocidas.
- Cuentas de demostracion impedidas fuera de entornos local y testing.
- Cabeceras de seguridad para navegador.
- CSP estricta en el portal publico sin JavaScript insertado, bloqueo de marcos, permisos innecesarios y cache privada para respuestas sensibles.
- Consulta de deuda protegida por numero de socio o medidor mas los ultimos cuatro digitos del CI.
- Acceso temporal firmado para facturas y ordenes, con vencimiento y validacion del socio esperado.
- Orden QR unica con monto, moneda, referencia y vencimiento definidos por el sistema.
- Retornos del navegador impedidos de confirmar o modificar el estado de un pago.
- Identidad, imagen principal, contenido de paginas y comunicados editables desde administracion.
- Auditoria financiera ejecutable con `php artisan app:audit-billing-integrity`.
- Facturacion manual obligada a procesar primero la lectura pendiente mas antigua.
- Numeracion manual y automatica protegida por el mismo bloqueo de concurrencia.
- Cobros presenciales obligados a cancelar primero las facturas mas antiguas, sin saltar periodos.
- Factura tradicional a color conservada; el logo institucional debe ser horizontal y valido para mostrarse en factura y PDF.
- HTTPS obligatorio configurable, cookie host-only, evidencia de restauracion con hash y monitoreo de scheduler, cola, disco y respaldo.
- Dependencias del servidor actualizadas.
- Configuracion de produccion de referencia agregada en `.env.production.example`.
- Eliminacion de servicios antiguos no usados que duplicaban la logica de facturacion y cobros.
- Los controladores de reportes y panel tecnico fueron reducidos a controladores delgados de 18 y 32 lineas; la logica se movio a servicios especializados.
- Confirmacion bancaria automatica mediante webhook firmado, con ventana contra repeticion, monto exacto, referencia unica e idempotencia.
- Politica automatizada de retencion para fotos personales, evidencias tecnicas y comprobantes, con simulacion previa y auditoria de eliminaciones.
- Endpoint privado de salud, verificacion documentada de restauraciones y comando de bloqueo de produccion.
- Plantillas de Nginx, PHP-FPM, servicios supervisados, respaldo PostgreSQL y usuario de base de datos con privilegios limitados.
- Acta de aceptacion contable disponible en `deployment/ACTA_ACEPTACION_CONTABLE.md`.

## Logica de facturacion y cobros

La logica actual es consistente para el flujo implementado:

- La factura se genera desde una lectura pendiente y no puede repetirse para la misma lectura.
- Los pagos bloquean las facturas mientras se procesan.
- La base impide sobrepagos y montos invalidos.
- QR y transferencias exigen monto exacto y referencia.
- La confirmacion bancaria automatica registra proveedor, evento, referencia y empleado de sistema; la revision manual permanece como respaldo.
- Los pagos actualizan factura, historial y orden dentro de una transaccion.

Antes del lanzamiento, la empresa debe aprobar por escrito tarifas, redondeos, mora, fechas de vencimiento, anulaciones y conciliacion diaria.

Las primeras aperturas de algunos detalles todavia pueden tardar entre 0,7 y 2,6 segundos en el staging actual porque la aplicacion y PostgreSQL remoto no estan en la misma infraestructura. Luego de preparar el cache, esos detalles responden entre 45 y 99 ms. La infraestructura final debe ubicar aplicacion y base de datos en la misma region y conservar Redis.

## Bloqueadores de produccion

1. Crear un usuario de base de datos exclusivo para la aplicacion, sin `CREATEDB`, `CREATEROLE`, `REPLICATION` ni `BYPASSRLS`. El usuario actual conserva privilegios elevados.
2. Desplegar en Linux con Nginx o Apache y PHP-FPM, Redis y trabajadores de cola supervisados. Apache de Windows se usa solo para staging local.
3. Configurar HTTPS real, dominio, cookies seguras y rotar todas las credenciales antes de publicar.
4. Configurar respaldos automaticos cifrados y ejecutar una prueba documentada de restauracion.
5. Configurar monitoreo de errores, disponibilidad, uso de recursos, colas y alertas.
6. Ejecutar pruebas de aceptacion contable con datos reales anonimizados y conciliacion firmada.
7. Ampliar pruebas automatizadas de integracion para anulaciones, pagos parciales, cierres de caja y concurrencia.
8. Repetir carga en la infraestructura final. Una rafaga artificial sin pausas logro agotar hilos y conexiones del staging Windows.

El comando `php artisan app:production-readiness` mantiene bloqueado el lanzamiento mientras existan controles pendientes. El control ahora separa HTTPS obligatorio, cookies host-only, logo de factura, evidencia real de restauracion y monitor externo para impedir falsos positivos.

## Riesgos residuales

- La cobertura automatizada aumento, pero debe seguir creciendo con cada cambio financiero.
- La confirmacion QR automatica requiere credenciales, formato y pruebas de certificacion del banco real.
- La politica de retencion esta implementada; la empresa debe aprobar legalmente los plazos configurados.
- Los servicios de reportes y panel tecnico aun son extensos y conviene dividirlos por dominio en futuras iteraciones.
- La aceptacion contable, HTTPS, monitoreo externo, restauracion y usuario limitado deben ejecutarse en la infraestructura final.

## Recomendacion de lanzamiento

Realizar primero un piloto interno con pocos usuarios, datos respaldados y supervision diaria. Autorizar produccion definitiva solo cuando todos los bloqueadores anteriores esten cerrados.
