<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('intentos_pago')) {
            return;
        }

        if (DB::table('intentos_pago')->count() === 0) {
            Schema::dropIfExists('intentos_pago');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('intentos_pago')) {
            return;
        }

        Schema::create('intentos_pago', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('numero_socio', 30)->index();
            $table->decimal('monto', 12, 2);
            $table->string('referencia', 100)->unique();
            $table->string('estado', 30)->default('pendiente')->index();
            $table->timestampsTz();
        });
    }
};
