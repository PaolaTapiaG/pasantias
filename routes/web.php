<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MedidorController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SmsGatewayController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\FacturaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/recuperar-cuenta', [AuthController::class, 'showRecoveryRequest'])->name('password.request');
    Route::post('/recuperar-cuenta', [AuthController::class, 'sendRecoveryCode'])->name('password.email');
    Route::get('/recuperar-cuenta/codigo', [AuthController::class, 'showRecoveryReset'])->name('password.reset.code');
    Route::post('/recuperar-cuenta/codigo', [AuthController::class, 'resetWithRecoveryCode'])->name('password.reset.sms');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', fn() => 'Modulo de Usuarios (En desarrollo)')->name('usuarios.index');
        Route::get('/permisos', fn() => 'Modulo de Permisos (En desarrollo)')->name('permisos.index');
        Route::get('/configuracion', [SystemSettingController::class, 'index'])->name('configuracion.index');
        Route::put('/configuracion', [SystemSettingController::class, 'update'])->name('configuracion.update');
        Route::put('/configuracion/perfil', [AdminProfileController::class, 'update'])->name('configuracion.profile.update');
        Route::get('/configuracion/sms-gateway', [SmsGatewayController::class, 'index'])->name('configuracion.sms-gateway');
        Route::post('/configuracion/sms-gateway', [SmsGatewayController::class, 'store'])->name('configuracion.sms-gateway.store');
        Route::get('/auditoria', fn() => 'Modulo de Auditoria (En desarrollo)')->name('auditoria.index');
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
        Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
        Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::get('/socios', [SocioController::class, 'index'])->name('socios.index');
        Route::get('/socios/export/{format}', [ExportController::class, 'socios'])->name('socios.export');
        Route::get('/socios/crear', [SocioController::class, 'create'])->name('socios.create');
        Route::post('/socios', [SocioController::class, 'store'])->name('socios.store');
        Route::get('/socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
        Route::get('/socios/{socio}/editar', [SocioController::class, 'edit'])->name('socios.edit');
        Route::put('/socios/{socio}', [SocioController::class, 'update'])->name('socios.update');
        Route::patch('/socios/{socio}/activar', [SocioController::class, 'activate'])->name('socios.activate');
        Route::patch('/socios/{socio}/desactivar', [SocioController::class, 'deactivate'])->name('socios.deactivate');
        Route::patch('/socios/{socio}/ocultar', [SocioController::class, 'hide'])->name('socios.hide');
        Route::patch('/socios/{socio}/restaurar', [SocioController::class, 'unhide'])->name('socios.unhide');
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
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/perfil', [DashboardController::class, 'perfil'])->name('perfil');
        Route::put('/perfil', [DashboardController::class, 'perfilUpdate'])->name('perfil.update');
    });

    Route::middleware('role:administrador,tecnico')->prefix('admin')->name('tecnico.')->group(function () {
        Route::get('/medidores', [MedidorController::class, 'index'])->name('medidores.index');
        Route::get('/medidores/crear', [MedidorController::class, 'create'])->name('medidores.create');
        Route::get('/medidores/export/{format}', [ExportController::class, 'medidores'])->name('medidores.export');
        Route::post('/medidores', [MedidorController::class, 'store'])->name('medidores.store');
        Route::get('/medidores/{medidor}/editar', [MedidorController::class, 'edit'])->name('medidores.edit');
        Route::put('/medidores/{medidor}', [MedidorController::class, 'update'])->name('medidores.update');
        Route::get('/lecturas', [LecturaController::class, 'index'])->name('lecturas.index');
        Route::get('/lecturas/crear', [LecturaController::class, 'create'])->name('lecturas.create');
        Route::post('/lecturas', [LecturaController::class, 'store'])->name('lecturas.store');
        Route::get('/mantenimiento', fn() => 'Modulo de Mantenimiento (En desarrollo)')->name('mantenimiento.index');
        Route::get('/reportes-tecnicos', fn() => 'Reportes Tecnicos (En desarrollo)')->name('reportes-tecnicos.index');
    });
});
