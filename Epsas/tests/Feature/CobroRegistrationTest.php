<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CobroRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createBillingTables();
    }

    public function test_secretary_without_open_cash_gets_controlled_error(): void
    {
        [$user, $socio, $factura] = $this->seedCobroScenario();

        $this->actingAs($user)
            ->from(route('secretaria.cobros.show', $socio))
            ->post(route('secretaria.cobros.store', $socio), [
                'id_metodo_pago' => 1,
                'cantidad_pagada' => 12.82,
                'factura_ids' => [$factura->id_factura],
            ])
            ->assertRedirect(route('secretaria.cobros.show', $socio))
            ->assertSessionHas('error', 'Debes abrir la caja del dia antes de registrar pagos. Si ya fue cerrada, solicita revision al administrador.');

        $this->assertDatabaseCount('cobros', 0);
        $this->assertSame('pendiente', $factura->fresh()->estado);
    }

    public function test_secretary_with_open_cash_can_register_payment(): void
    {
        [$user, $socio, $factura, $empleado] = $this->seedCobroScenario();

        CierreCaja::query()->create([
            'fecha_caja' => today()->toDateString(),
            'estado' => 'abierta',
            'abierta_en' => now(),
            'id_empleado' => $empleado->id_empleado,
        ]);

        $this->actingAs($user)
            ->from(route('secretaria.cobros.show', $socio))
            ->post(route('secretaria.cobros.store', $socio), [
                'id_metodo_pago' => 1,
                'cantidad_pagada' => 20,
                'factura_ids' => [$factura->id_factura],
            ])
            ->assertRedirect(route('secretaria.cobros.result'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cobros', [
            'id_factura' => $factura->id_factura,
            'id_metodo_pago' => 1,
            'id_empleado' => $empleado->id_empleado,
            'estado' => 'completado',
        ]);
        $this->assertDatabaseHas('historial_pagos', [
            'id_factura' => $factura->id_factura,
            'id_empleado' => $empleado->id_empleado,
            'tipo_evento' => 'pago_completo',
        ]);
        $this->assertSame('pagada', $factura->fresh()->estado);
    }

    private function seedCobroScenario(): array
    {
        DB::table('user_roles')->insert([
            'id' => 1,
            'name' => 'secretaria',
            'description' => 'Secretaria',
        ]);
        DB::table('roles')->insert([
            'id_rol' => 1,
            'nombre' => 'secretaria',
            'descripcion' => 'Secretaria',
        ]);

        DB::table('personas')->insert([
            [
                'id_persona' => 1,
                'nombres' => 'Rosa',
                'apellidos' => 'Flores',
                'cedula_identidad' => '1234567',
                'email' => 'rosa@example.test',
            ],
            [
                'id_persona' => 2,
                'nombres' => 'Cliente',
                'apellidos' => 'Prueba',
                'cedula_identidad' => '7654321',
                'email' => 'cliente@example.test',
            ],
        ]);

        $empleado = Empleado::query()->create([
            'id_empleado' => 1,
            'fecha_ingreso' => today()->toDateString(),
            'estado' => 'activo',
            'id_persona' => 1,
            'id_rol' => 1,
        ]);

        $user = User::query()->create([
            'name' => 'Rosa Flores',
            'email' => 'rosa@example.test',
            'password' => 'password',
            'id_persona' => 1,
            'must_change_password' => false,
        ]);
        DB::table('role_user')->insert([
            'user_id' => $user->id,
            'user_roles_id' => 1,
        ]);

        $socio = Socio::query()->create([
            'id_socio' => 14,
            'numero_socio' => 'SOC-0012',
            'direccion' => 'Zona Central',
            'fecha_registro' => today()->subMonth()->toDateString(),
            'estado' => 'activo',
            'id_persona' => 2,
        ]);

        DB::table('periodos_facturacion')->insert([
            'id_periodo' => 1,
            'nombre' => 'Junio 2026',
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-30',
            'cerrado' => false,
        ]);
        DB::table('metodos_pago')->insert([
            'id_metodo_pago' => 1,
            'nombre' => 'Efectivo',
            'descripcion' => 'Pago en caja',
            'requiere_referencia' => false,
            'estado' => 'activo',
        ]);

        $factura = Factura::query()->create([
            'id_factura' => 160,
            'numero_factura' => 'FAC-00160',
            'fecha_emision' => today()->toDateString(),
            'fecha_inicio_cobro' => '2026-06-01',
            'fecha_fin_cobro' => '2026-06-30',
            'consumo_m3' => 4,
            'monto_consumo' => 10,
            'cargo_fijo' => 2.82,
            'recargo_mora' => 0,
            'descuentos' => 0,
            'total' => 12.82,
            'estado' => 'pendiente',
            'id_socio' => $socio->id_socio,
            'id_periodo' => 1,
        ]);

        return [$user, $socio, $factura, $empleado];
    }

    private function createBillingTables(): void
    {
        foreach ([
            'historial_pagos',
            'cobros',
            'cierres_caja',
            'facturas',
            'metodos_pago',
            'periodos_facturacion',
            'socios',
            'role_user',
            'users',
            'empleados',
            'roles',
            'user_roles',
            'personas',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('personas', function (Blueprint $table): void {
            $table->id('id_persona');
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('cedula_identidad')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id('id_rol');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
        });
        Schema::create('empleados', function (Blueprint $table): void {
            $table->id('id_empleado');
            $table->date('fecha_ingreso')->nullable();
            $table->string('estado')->default('activo');
            $table->foreignId('id_persona');
            $table->foreignId('id_rol')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->foreignId('id_persona')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('user_id');
            $table->foreignId('user_roles_id');
        });
        Schema::create('socios', function (Blueprint $table): void {
            $table->id('id_socio');
            $table->string('numero_socio')->nullable();
            $table->string('direccion')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->string('estado')->default('activo');
            $table->boolean('oculto')->default(false);
            $table->foreignId('id_persona')->nullable();
            $table->foreignId('id_sector')->nullable();
            $table->foreignId('id_tarifa')->nullable();
            $table->timestamps();
        });
        Schema::create('periodos_facturacion', function (Blueprint $table): void {
            $table->id('id_periodo');
            $table->string('nombre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('cerrado')->default(false);
            $table->timestamps();
        });
        Schema::create('metodos_pago', function (Blueprint $table): void {
            $table->id('id_metodo_pago');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_referencia')->default(false);
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
        Schema::create('facturas', function (Blueprint $table): void {
            $table->id('id_factura');
            $table->string('numero_factura')->unique();
            $table->date('fecha_emision');
            $table->date('fecha_pago')->nullable();
            $table->date('fecha_inicio_cobro')->nullable();
            $table->date('fecha_fin_cobro')->nullable();
            $table->decimal('consumo_m3', 10, 2)->default(0);
            $table->decimal('monto_consumo', 12, 2)->default(0);
            $table->decimal('cargo_fijo', 12, 2)->default(0);
            $table->decimal('recargo_mora', 12, 2)->default(0);
            $table->decimal('descuentos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado')->default('pendiente');
            $table->foreignId('id_socio');
            $table->foreignId('id_lectura')->nullable();
            $table->foreignId('id_periodo')->nullable();
            $table->timestamps();
        });
        Schema::create('cierres_caja', function (Blueprint $table): void {
            $table->id('id_cierre_caja');
            $table->date('fecha_caja');
            $table->string('estado')->default('abierta');
            $table->unsignedInteger('cantidad_cobros')->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_qr', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_general', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('abierta_en')->nullable();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamp('revisada_en')->nullable();
            $table->foreignId('id_empleado');
            $table->foreignId('revisado_por')->nullable();
            $table->timestamps();
        });
        Schema::create('cobros', function (Blueprint $table): void {
            $table->id('id_cobro');
            $table->date('fecha_cobro');
            $table->decimal('monto_pagado', 12, 2);
            $table->decimal('monto_pendiente', 12, 2)->default(0);
            $table->string('estado')->default('completado');
            $table->string('comprobante')->nullable();
            $table->foreignId('id_factura');
            $table->foreignId('id_metodo_pago');
            $table->foreignId('id_empleado');
            $table->foreignId('id_orden_pago')->nullable();
        });
        Schema::create('historial_pagos', function (Blueprint $table): void {
            $table->id('id_historial');
            $table->timestamp('fecha_evento')->nullable();
            $table->string('tipo_evento');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->foreignId('id_socio');
            $table->foreignId('id_factura');
            $table->foreignId('id_cobro')->nullable();
            $table->foreignId('id_empleado')->nullable();
        });
    }
}
