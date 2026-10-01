<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('metodos_pago')) {
            Schema::table('metodos_pago', function (Blueprint $table) {
                if (! Schema::hasColumn('metodos_pago', 'requiere_caja_abierta')) {
                    $table->boolean('requiere_caja_abierta')->default(true);
                }

                if (! Schema::hasColumn('metodos_pago', 'es_online')) {
                    $table->boolean('es_online')->default(false);
                }

                if (! Schema::hasColumn('metodos_pago', 'requiere_conciliacion')) {
                    $table->boolean('requiere_conciliacion')->default(false);
                }

                if (! Schema::hasColumn('metodos_pago', 'origen_predeterminado')) {
                    $table->string('origen_predeterminado', 30)->default('caja');
                }
            });

            DB::table('metodos_pago')
                ->whereRaw('LOWER(nombre) = ?', ['qr'])
                ->update([
                    'nombre' => 'QR oficina',
                    'descripcion' => 'Pago QR asistido y verificado en oficina.',
                    'requiere_referencia' => true,
                    'requiere_caja_abierta' => true,
                    'es_online' => false,
                    'requiere_conciliacion' => true,
                    'origen_predeterminado' => 'qr_oficina',
                    'estado' => 'activo',
                ]);

            DB::table('metodos_pago')->updateOrInsert(
                ['nombre' => 'Efectivo'],
                [
                    'descripcion' => 'Pago en efectivo registrado en caja presencial.',
                    'requiere_referencia' => false,
                    'requiere_caja_abierta' => true,
                    'es_online' => false,
                    'requiere_conciliacion' => false,
                    'origen_predeterminado' => 'caja',
                    'estado' => 'activo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('metodos_pago')->updateOrInsert(
                ['nombre' => 'Transferencia'],
                [
                    'descripcion' => 'Transferencia bancaria validada por oficina.',
                    'requiere_referencia' => true,
                    'requiere_caja_abierta' => true,
                    'es_online' => false,
                    'requiere_conciliacion' => true,
                    'origen_predeterminado' => 'transferencia_oficina',
                    'estado' => 'activo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('metodos_pago')->updateOrInsert(
                ['nombre' => 'QR oficina'],
                [
                    'descripcion' => 'Pago QR asistido y verificado en oficina.',
                    'requiere_referencia' => true,
                    'requiere_caja_abierta' => true,
                    'es_online' => false,
                    'requiere_conciliacion' => true,
                    'origen_predeterminado' => 'qr_oficina',
                    'estado' => 'activo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('metodos_pago')->updateOrInsert(
                ['nombre' => 'QR online'],
                [
                    'descripcion' => 'Pago QR realizado desde el portal ciudadano o pasarela bancaria.',
                    'requiere_referencia' => true,
                    'requiere_caja_abierta' => false,
                    'es_online' => true,
                    'requiere_conciliacion' => true,
                    'origen_predeterminado' => 'online',
                    'estado' => 'activo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('cobros')) {
            Schema::table('cobros', function (Blueprint $table) {
                if (! Schema::hasColumn('cobros', 'origen_pago')) {
                    $table->string('origen_pago', 30)->default('caja');
                }

                if (! Schema::hasColumn('cobros', 'id_cierre_caja')) {
                    $table->foreignId('id_cierre_caja')->nullable()->constrained('cierres_caja', 'id_cierre_caja')->nullOnDelete();
                }

                if (! Schema::hasColumn('cobros', 'referencia_externa')) {
                    $table->string('referencia_externa', 120)->nullable();
                }

                if (! Schema::hasColumn('cobros', 'estado_conciliacion')) {
                    $table->string('estado_conciliacion', 30)->default('no_aplica');
                }

                if (! Schema::hasColumn('cobros', 'confirmado_por')) {
                    $table->string('confirmado_por', 40)->nullable();
                }

                if (! Schema::hasColumn('cobros', 'confirmado_en')) {
                    $table->timestamp('confirmado_en')->nullable();
                }
            });

            DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_origen_fecha ON cobros (origen_pago, fecha_cobro)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_conciliacion ON cobros (estado_conciliacion)');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cobros')) {
            DB::statement('DROP INDEX IF EXISTS idx_cobros_conciliacion');
            DB::statement('DROP INDEX IF EXISTS idx_cobros_origen_fecha');

            Schema::table('cobros', function (Blueprint $table) {
                foreach (['confirmado_en', 'confirmado_por', 'estado_conciliacion', 'referencia_externa', 'id_cierre_caja', 'origen_pago'] as $column) {
                    if (Schema::hasColumn('cobros', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('metodos_pago')) {
            Schema::table('metodos_pago', function (Blueprint $table) {
                foreach (['origen_predeterminado', 'requiere_conciliacion', 'es_online', 'requiere_caja_abierta'] as $column) {
                    if (Schema::hasColumn('metodos_pago', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
