<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('fecha_nacimiento');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_persona')->nullable()->unique()->after('email')->constrained('personas', 'id_persona')->nullOnDelete();
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_persona');
            $table->dropColumn('must_change_password');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};
