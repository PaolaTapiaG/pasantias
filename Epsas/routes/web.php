<?php

use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\DashboardMetricsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankPaymentWebhookController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\MedidorController;
use App\Http\Controllers\OperationalMapController;
use App\Http\Controllers\PaymentOrderController;
use App\Http\Controllers\PrivateMediaController;
use App\Http\Controllers\ProductionHealthController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SecretariaOperacionController;
use App\Http\Controllers\SecretariaProfileController;
use App\Http\Controllers\SmsGatewayController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\TecnicoPanelController;
use App\Http\Controllers\TecnicoProfileController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::post('/webhooks/bank/payments', BankPaymentWebhookController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
    ])
    ->middleware('throttle:120,1')
    ->name('webhooks.bank.payments');

Route::get('/health/ready', ProductionHealthController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
    ])
    ->middleware('throttle:30,1')
    ->name('health.ready');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::get('/recuperar-cuenta', [AuthController::class, 'showRecoveryRequest'])->name('password.request');
    Route::post('/recuperar-cuenta', [AuthController::class, 'sendRecoveryCode'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/recuperar-cuenta/codigo', [AuthController::class, 'showRecoveryReset'])->name('password.reset.code');
    Route::post('/recuperar-cuenta/codigo', [AuthController::class, 'resetWithRecoveryCode'])->middleware('throttle:5,1')->name('password.reset.sms');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::middleware('role:administrador,secretaria,tecnico')->get('/media/personas/{persona}/foto', [PrivateMediaController::class, 'personaPhoto'])->name('private-media.persona-photo');
    Route::middleware('role:administrador,tecnico')->get('/media/anomalias/{anomalia}/evidencia', [PrivateMediaController::class, 'anomalyEvidence'])->name('private-media.anomaly-evidence');
    Route::middleware('role:administrador,tecnico')->get('/media/incidencias/{incidencia}/evidencia', [PrivateMediaController::class, 'incidentEvidence'])->name('private-media.incident-evidence');
    Route::middleware('role:administrador,secretaria,tecnico')->get('/media/lecturas/{lectura}/evidencia', [PrivateMediaController::class, 'readingEvidence'])->name('private-media.reading-evidence');

    Route::prefix('api')->name('api.')->group(function () {
        Route::middleware('role:administrador,secretaria,tecnico')->get('/admin/notifications', AdminNotificationController::class)->name('admin.notifications');
        Route::middleware('role:administrador,secretaria')->get('/dashboard/secretaria-metrics', [DashboardMetricsController::class, 'secretaria'])->name('dashboard.secretaria-metrics');
        Route::middleware('role:administrador,tecnico')->get('/dashboard/tecnico-metrics', [DashboardMetricsController::class, 'tecnico'])->name('dashboard.tecnico-metrics');
        Route::middleware('role:administrador,tecnico')->get('/tecnico/medidores/catalogo', [TecnicoPanelController::class, 'medidorCatalog'])->name('tecnico.medidores.catalogo');
        Route::middleware('role:administrador,secretaria,tecnico')->get('/tecnico/socios/catalogo', [TecnicoPanelController::class, 'socioCatalogSearch'])->name('tecnico.socios.catalogo');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('role:administrador,secretaria,tecnico')
        ->get('/admin/mapa-operativo', OperationalMapController::class)
        ->name('mapa-operativo.index');
    Route::middleware('role:administrador')
        ->patch('/admin/mapa-operativo/oficina', [OperationalMapController::class, 'updateOffice'])
        ->name('mapa-operativo.office.update');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('role:administrador,secretaria')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/socios', [SocioController::class, 'index'])->name('socios.index');
        Route::get('/socios/export/{format}', [ExportController::class, 'socios'])->name('socios.export');
        Route::get('/socios/crear', [SocioController::class, 'create'])->name('socios.create');
        Route::post('/socios', [SocioController::class, 'store'])->name('socios.store');
        Route::get('/socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
        Route::get('/socios/{socio}/carnet', [SocioController::class, 'carnet'])->name('socios.carnet');
        Route::post('/socios/{socio}/carnet/ingreso', [SocioController::class, 'registrarCarnetizacion'])->name('socios.carnet.ingreso');
        Route::get('/socios/{socio}/editar', [SocioController::class, 'edit'])->name('socios.edit');
        Route::put('/socios/{socio}', [SocioController::class, 'update'])->name('socios.update');
        Route::patch('/socios/{socio}/activar', [SocioController::class, 'activate'])->name('socios.activate');
        Route::patch('/socios/{socio}/desactivar', [SocioController::class, 'deactivate'])->name('socios.deactivate');
        Route::patch('/socios/{socio}/ocultar', [SocioController::class, 'hide'])->name('socios.hide');
        Route::patch('/socios/{socio}/restaurar', [SocioController::class, 'unhide'])->name('socios.unhide');
    });

    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [StaticPageController::class, 'usuarios'])->name('usuarios.index');
        Route::get('/permisos', [StaticPageController::class, 'permisos'])->name('permisos.index');
        Route::get('/perfil', [AdminProfileController::class, 'edit'])->name('perfil.index');
        Route::put('/perfil', [AdminProfileController::class, 'update'])->name('perfil.update');
        Route::get('/configuracion', [SystemSettingController::class, 'index'])->name('configuracion.index');
        Route::get('/configuracion/empresa-branding', [SystemSettingController::class, 'company'])->name('configuracion.empresa');
        Route::get('/configuracion/carnetizacion', [SystemSettingController::class, 'carnet'])->name('configuracion.carnet');
        Route::put('/configuracion', [SystemSettingController::class, 'update'])->name('configuracion.update');
        Route::put('/configuracion/perfil', [AdminProfileController::class, 'update'])->name('configuracion.profile.update');
        Route::get('/configuracion/sms-gateway', [SmsGatewayController::class, 'index'])->name('configuracion.sms-gateway');
        Route::post('/configuracion/sms-gateway', [SmsGatewayController::class, 'store'])->name('configuracion.sms-gateway.store');
        Route::get('/auditoria', [StaticPageController::class, 'auditoria'])->name('auditoria.index');
        Route::get('/tarifas', [TarifaController::class, 'index'])->name('tarifas.index');
        Route::get('/tarifas/export/{format}', [ExportController::class, 'tarifas'])->name('tarifas.export');
        Route::get('/tarifas/crear', [TarifaController::class, 'create'])->name('tarifas.create');
        Route::post('/tarifas', [TarifaController::class, 'store'])->name('tarifas.store');
        Route::get('/tarifas/{tarifa}/editar', [TarifaController::class, 'edit'])->name('tarifas.edit');
        Route::put('/tarifas/{tarifa}', [TarifaController::class, 'update'])->name('tarifas.update');
        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::get('/empleados/export/{format}', [ExportController::class, 'empleados'])->name('empleados.export');
        Route::get('/empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::post('/empleados/{empleado}/egreso-laboral', [EmpleadoController::class, 'storeLaborExpense'])->name('empleados.egreso-laboral');
        Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
        Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/export/{format}', [ExportController::class, 'gastos'])->name('gastos.export');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    });

    Route::middleware('role:administrador,secretaria')->prefix('admin')->name('secretaria.')->group(function () {
        Route::get('/facturas', [FacturaController::class, 'index'])->name('facturas.index');
        Route::get('/facturas/export/{format}', [ExportController::class, 'facturas'])->name('facturas.export');
        Route::post('/facturas/generar', [FacturaController::class, 'store'])->name('facturas.store');
        Route::get('/facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');
        Route::get('/facturas/{factura}/imprimir', [FacturaController::class, 'print'])->name('facturas.print');
        Route::get('/cobros', [CobroController::class, 'index'])->name('cobros.index');
        Route::get('/cobros/resultado/finalizado', [CobroController::class, 'result'])->name('cobros.result');
        Route::get('/cobros/{socio}', [CobroController::class, 'show'])->name('cobros.show');
        Route::post('/cobros/{socio}', [CobroController::class, 'store'])->name('cobros.store');
        Route::get('/ordenes-pago', [PaymentOrderController::class, 'index'])->name('ordenes-pago.index');
        Route::get('/ordenes-pago/{ordenPago:codigo}/comprobante', [PaymentOrderController::class, 'proof'])->name('ordenes-pago.proof');
        Route::get('/ordenes-pago/{ordenPago:codigo}', [PaymentOrderController::class, 'show'])->name('ordenes-pago.show');
        Route::patch('/ordenes-pago/{ordenPago:codigo}/aprobar', [PaymentOrderController::class, 'approve'])->name('ordenes-pago.approve');
        Route::patch('/ordenes-pago/{ordenPago:codigo}/rechazar', [PaymentOrderController::class, 'reject'])->name('ordenes-pago.reject');
        Route::post('/ordenes-pago/{ordenPago:codigo}/enviar-facturas-email', [PaymentOrderController::class, 'sendInvoicesEmail'])->name('ordenes-pago.send-invoices-email');
        Route::patch('/reconexiones/{orden}/aprobar', [TecnicoPanelController::class, 'approveReconexion'])->name('reconexiones.approve');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/operaciones-secretaria', [SecretariaOperacionController::class, 'index'])->name('operaciones.index');
        Route::post('/operaciones-secretaria/caja/abrir', [SecretariaOperacionController::class, 'openCash'])->name('operaciones.caja.abrir');
        Route::patch('/operaciones-secretaria/caja/cerrar', [SecretariaOperacionController::class, 'closeCash'])->name('operaciones.caja.cerrar');
        Route::patch('/operaciones-secretaria/caja/{cierreCaja}/revisar', [SecretariaOperacionController::class, 'reviewCash'])->name('operaciones.caja.revisar');
        Route::post('/operaciones-secretaria/solicitudes', [SecretariaOperacionController::class, 'storeRequest'])->name('operaciones.solicitudes.store');
        Route::patch('/operaciones-secretaria/solicitudes/{incidencia}', [SecretariaOperacionController::class, 'updateRequest'])->name('operaciones.solicitudes.update');
        Route::post('/operaciones-secretaria/comunicados', [SecretariaOperacionController::class, 'storeCommunication'])->name('operaciones.comunicados.store');
        Route::patch('/operaciones-secretaria/comunicados/{comunicado}', [SecretariaOperacionController::class, 'updateCommunication'])->name('operaciones.comunicados.update');
        Route::get('/perfil-secretaria', [SecretariaProfileController::class, 'edit'])->name('perfil.index');
        Route::put('/perfil-secretaria', [SecretariaProfileController::class, 'update'])->name('perfil.update');
    });

    Route::middleware('role:administrador,secretaria,tecnico')
        ->get('/admin/lecturas', [LecturaController::class, 'index'])
        ->name('tecnico.lecturas.index');

    Route::middleware('role:administrador,tecnico')->prefix('admin')->name('tecnico.')->group(function () {
        Route::get('/medidores', [MedidorController::class, 'index'])->name('medidores.index');
        Route::get('/medidores/export/{format}', [ExportController::class, 'medidores'])->name('medidores.export');
        Route::get('/lecturas/crear', [LecturaController::class, 'create'])->name('lecturas.create');
        Route::post('/lecturas', [LecturaController::class, 'store'])->name('lecturas.store');
        Route::put('/perfil-tecnico', [TecnicoProfileController::class, 'update'])->name('configuracion.profile.update');
        Route::get('/perfil-tecnico', [TecnicoProfileController::class, 'edit'])->name('configuracion.index');
        Route::get('/anomalias', [TecnicoPanelController::class, 'anomalias'])->name('anomalias.index');
        Route::post('/anomalias', [TecnicoPanelController::class, 'storeAnomalia'])->name('anomalias.store');
        Route::get('/cortes', [TecnicoPanelController::class, 'cortes'])->name('cortes.index');
        Route::post('/cortes', [TecnicoPanelController::class, 'storeCorte'])->name('cortes.store');
        Route::get('/reconexiones', [TecnicoPanelController::class, 'reconexiones'])->name('reconexiones.index');
        Route::post('/reconexiones', [TecnicoPanelController::class, 'storeReconexion'])->name('reconexiones.store');
        Route::get('/instalaciones', [TecnicoPanelController::class, 'instalaciones'])->name('instalaciones.index');
        Route::post('/instalaciones', [TecnicoPanelController::class, 'storeInstalacion'])->name('instalaciones.store');
        Route::get('/mantenimiento', [TecnicoPanelController::class, 'mantenimiento'])->name('mantenimiento.index');
        Route::post('/mantenimiento', [TecnicoPanelController::class, 'storeMantenimiento'])->name('mantenimiento.store');
        Route::get('/operacion', [TecnicoPanelController::class, 'operacion'])->name('operacion.index');
        Route::post('/operacion', [TecnicoPanelController::class, 'storeOperacion'])->name('operacion.store');
        Route::get('/reportes-tecnicos', [TecnicoPanelController::class, 'reportes'])->name('reportes-tecnicos.index');
        Route::post('/reportes-tecnicos', [TecnicoPanelController::class, 'storeReporte'])->name('reportes-tecnicos.store');
        Route::get('/incidencias', [TecnicoPanelController::class, 'incidencias'])->name('incidencias.index');
        Route::post('/incidencias', [TecnicoPanelController::class, 'storeIncidencia'])->name('incidencias.store');
    });

    Route::middleware('role:administrador')->prefix('admin')->name('tecnico.')->group(function () {
        Route::get('/medidores/crear', [MedidorController::class, 'create'])->name('medidores.create');
        Route::post('/medidores', [MedidorController::class, 'store'])->name('medidores.store');
        Route::get('/medidores/{medidor}/editar', [MedidorController::class, 'edit'])->name('medidores.edit');
        Route::put('/medidores/{medidor}', [MedidorController::class, 'update'])->name('medidores.update');
    });

    Route::middleware('role:administrador,tecnico')
        ->get('/admin/consumo', [TecnicoPanelController::class, 'consumo'])
        ->name('tecnico.consumo.legacy');

    Route::middleware('role:tecnico')
        ->get('/tecnico/consumo', [TecnicoPanelController::class, 'consumoTecnico'])
        ->name('tecnico.consumo.index');
});
