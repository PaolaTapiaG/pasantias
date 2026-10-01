<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id('id_cierre_caja');
            $table->date('fecha_caja');
            $table->string('estado', 20)->default('abierta');
            $table->unsignedInteger('cantidad_cobros')->default(0);
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_qr', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_general', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('abierta_en')->nullable();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamp('revisada_en')->nullable();
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->foreignId('revisado_por')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id_empleado', 'fecha_caja']);
            $table->index(['fecha_caja', 'estado']);
        });

        Schema::create('comunicados', function (Blueprint $table) {
            $table->id('id_comunicado');
            $table->string('titulo', 180);
            $table->text('contenido');
            $table->string('estado', 30)->default('borrador');
            $table->boolean('importante')->default(false);
            $table->timestamp('publicar_desde')->nullable();
            $table->timestamp('publicar_hasta')->nullable();
            $table->foreignId('id_empleado')->constrained('empleados', 'id_empleado');
            $table->foreignId('aprobado_por')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'publicar_desde']);
            $table->index(['importante', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicados');
        Schema::dropIfExists('cierres_caja');
    }
};
