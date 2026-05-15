<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            if (!Schema::hasColumn('tarifas', 'tipo_uso')) {
                $table->string('tipo_uso', 30)->default('domestico')->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            if (Schema::hasColumn('tarifas', 'tipo_uso')) {
                $table->dropColumn('tipo_uso');
            }
        });
    }
};
