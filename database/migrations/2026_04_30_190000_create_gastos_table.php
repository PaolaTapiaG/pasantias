<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id('id_gasto');
            $table->date('fecha_gasto');
            $table->string('concepto', 150);
            $table->string('categoria', 80);
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 12, 2);
            $table->foreignId('id_empleado')->nullable()->constrained('empleados', 'id_empleado')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
