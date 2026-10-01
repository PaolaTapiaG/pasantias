<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lecturas', 'evidencia_path')) {
            Schema::table('lecturas', function (Blueprint $table) {
                $table->string('evidencia_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lecturas', 'evidencia_path')) {
            Schema::table('lecturas', function (Blueprint $table) {
                $table->dropColumn('evidencia_path');
            });
        }
    }
};