<?php

namespace Tests\Unit;

use App\Services\BillingIntegrityAudit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingIntegrityAuditTest extends TestCase
{
    public function test_clean_billing_data_passes_the_integrity_audit(): void
    {
        $this->createTables();
        DB::table('facturas')->insert([
            'id_factura' => 1,
            'id_lectura' => 1,
            'numero_factura' => 'FAC-00001',
            'consumo_m3' => 10,
            'monto_consumo' => 20,
            'cargo_fijo' => 5,
            'recargo_mora' => 0,
            'descuentos' => 0,
            'total' => 25,
            'estado' => 'pagada',
        ]);
        DB::table('cobros')->insert([
            'id_factura' => 1,
            'monto_pagado' => 25,
            'estado' => 'completado',
        ]);

        $audit = app(BillingIntegrityAudit::class);
        $checks = $audit->run();

        $this->assertTrue($audit->isClean($checks));
    }

    public function test_overpayments_and_state_mismatches_fail_the_integrity_audit(): void
    {
        $this->createTables();
        DB::table('facturas')->insert([
            'id_factura' => 1,
            'id_lectura' => 1,
            'numero_factura' => 'FAC-00001',
            'consumo_m3' => 10,
            'monto_consumo' => 20,
            'cargo_fijo' => 5,
            'recargo_mora' => 0,
            'descuentos' => 0,
            'total' => 25,
            'estado' => 'pagada',
        ]);
        DB::table('cobros')->insert([
            'id_factura' => 1,
            'monto_pagado' => 30,
            'estado' => 'completado',
        ]);

        $audit = app(BillingIntegrityAudit::class);
        $checks = $audit->run();

        $this->assertFalse($audit->isClean($checks));
        $this->assertSame(1, $checks['overpaid_invoices']);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('facturas');

        Schema::create('facturas', function (Blueprint $table): void {
            $table->id('id_factura');
            $table->unsignedBigInteger('id_lectura')->nullable();
            $table->string('numero_factura');
            $table->decimal('consumo_m3', 12, 2);
            $table->decimal('monto_consumo', 12, 2);
            $table->decimal('cargo_fijo', 12, 2);
            $table->decimal('recargo_mora', 12, 2);
            $table->decimal('descuentos', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('estado');
        });
        Schema::create('cobros', function (Blueprint $table): void {
            $table->id('id_cobro');
            $table->unsignedBigInteger('id_factura');
            $table->decimal('monto_pagado', 12, 2);
            $table->string('estado');
        });
    }
}
