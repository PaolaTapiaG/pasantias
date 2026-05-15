<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SocioController;
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
        Route::get('/configuracion', fn() => redirect()->route('admin.tarifas.index'))->name('configuracion.index');
        Route::get('/auditoria', fn() => 'Modulo de Auditoria (En desarrollo)')->name('auditoria.index');
        Route::get('/tarifas', [TarifaController::class, 'index'])->name('tarifas.index');
        Route::get('/tarifas/crear', [TarifaController::class, 'create'])->name('tarifas.create');
        Route::post('/tarifas', [TarifaController::class, 'store'])->name('tarifas.store');
        Route::get('/tarifas/{tarifa}/editar', [TarifaController::class, 'edit'])->name('tarifas.edit');
        Route::put('/tarifas/{tarifa}', [TarifaController::class, 'update'])->name('tarifas.update');
        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::get('/empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
        Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::get('/socios', [SocioController::class, 'index'])->name('socios.index');
        Route::get('/socios/crear', [SocioController::class, 'create'])->name('socios.create');
        Route::post('/socios', [SocioController::class, 'store'])->name('socios.store');
        Route::get('/socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
        Route::get('/socios/{socio}/editar', [SocioController::class, 'edit'])->name('socios.edit');
        Route::put('/socios/{socio}', [SocioController::class, 'update'])->name('socios.update');
        Route::patch('/socios/{socio}/activar', [SocioController::class, 'activate'])->name('socios.activate');
        Route::patch('/socios/{socio}/desactivar', [SocioController::class, 'deactivate'])->name('socios.deactivate');
        Route::patch('/socios/{socio}/ocultar', [SocioController::class, 'hide'])->name('socios.hide');
        Route::patch('/socios/{socio}/restaurar', [SocioController::class, 'unhide'])->name('socios.unhide');
    });

    Route::middleware('role:administrador,secretaria')->prefix('admin')->name('secretaria.')->group(function () {
        Route::get('/facturas', [FacturaController::class, 'index'])->name('facturas.index');
        Route::post('/facturas/generar', [FacturaController::class, 'store'])->name('facturas.store');
        Route::get('/facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');
        Route::get('/cobros', [CobroController::class, 'index'])->name('cobros.index');
        Route::get('/cobros/{socio}', [CobroController::class, 'show'])->name('cobros.show');
        Route::post('/cobros/{socio}', [CobroController::class, 'store'])->name('cobros.store');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });

    Route::middleware('role:tecnico')->prefix('admin')->name('tecnico.')->group(function () {
        Route::get('/medidores', fn() => 'Modulo de Medidores (En desarrollo)')->name('medidores.index');
        Route::post('/medidores', fn() => 'Crear Medidor (En desarrollo)')->name('medidores.store');
        Route::get('/lecturas', fn() => 'Modulo de Lecturas (En desarrollo)')->name('lecturas.index');
        Route::post('/lecturas', fn() => 'Registrar Lectura (En desarrollo)')->name('lecturas.store');
        Route::get('/mantenimiento', fn() => 'Modulo de Mantenimiento (En desarrollo)')->name('mantenimiento.index');
        Route::get('/reportes-tecnicos', fn() => 'Reportes Tecnicos (En desarrollo)')->name('reportes-tecnicos.index');
    });

    Route::middleware('role:administrador,tecnico')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/medidores-compartido', fn() => 'Modulo de Medidores')->name('medidores.shared');
    });
});
