<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personas')) {
            Schema::create('personas', function (Blueprint $table) {
                $table->id('id_persona');
                $table->string('nombres');
                $table->string('apellidos');
                $table->string('cedula_identidad')->unique();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sectores')) {
            Schema::create('sectores', function (Blueprint $table) {
                $table->id('id_sector');
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->string('zona');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tarifas')) {
            Schema::create('tarifas', function (Blueprint $table) {
                $table->id('id_tarifa');
                $table->string('nombre');
                $table->decimal('precio_m3_base', 10, 2);
                $table->decimal('consumo_minimo_m3', 10, 2)->default(0);
                $table->decimal('cargo_fijo', 10, 2)->default(0);
                $table->date('fecha_vigencia')->nullable();
                $table->string('estado')->default('activa');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('socios')) {
            Schema::create('socios', function (Blueprint $table) {
                $table->id('id_socio');
                $table->string('numero_socio')->unique();
                $table->string('direccion')->nullable();
                $table->date('fecha_registro')->nullable();
                $table->string('estado')->default('activo');
                $table->boolean('oculto')->default(false);
                $table->text('motivo_ocultacion')->nullable();
                $table->timestamp('oculto_en')->nullable();
                $table->foreignId('oculto_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('id_persona')->constrained('personas', 'id_persona')->cascadeOnDelete();
                $table->foreignId('id_sector')->constrained('sectores', 'id_sector');
                $table->foreignId('id_tarifa')->constrained('tarifas', 'id_tarifa');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('medidores')) {
            Schema::create('medidores', function (Blueprint $table) {
                $table->id('id_medidor');
                $table->string('numero_medidor')->unique();
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->date('fecha_instalacion')->nullable();
                $table->string('estado')->default('activo');
                $table->foreignId('id_socio')->nullable()->constrained('socios', 'id_socio')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medidores');
        Schema::dropIfExists('socios');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('sectores');
        Schema::dropIfExists('personas');
    }
};
