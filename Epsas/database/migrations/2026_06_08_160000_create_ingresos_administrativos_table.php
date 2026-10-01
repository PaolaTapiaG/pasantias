<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos_administrativos', function (Blueprint $table) {
            $table->id('id_ingreso');
            $table->date('fecha_ingreso');
            $table->string('concepto', 150);
            $table->string('categoria', 80);
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 12, 2);
            $table->foreignId('id_socio')->nullable()->constrained('socios', 'id_socio')->nullOnDelete();
            $table->foreignId('id_empleado')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha_ingreso', 'categoria']);
            $table->index(['id_socio', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos_administrativos');
    }
};
