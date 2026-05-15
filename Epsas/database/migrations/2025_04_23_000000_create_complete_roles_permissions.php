<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No dropear tablas, solo crear lo que no existe

        // Crear tabla user_roles si no existe (para roles de usuario de la aplicación)
        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Crear tabla user_permissions si no existe
        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Crear tabla role_user si no existe (muchos a muchos)
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('user_roles_id')->constrained('user_roles', 'id')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['user_id', 'user_roles_id']);
            });
        }

        // Crear tabla permission_role si no existe (muchos a muchos)
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_permissions_id')->constrained('user_permissions', 'id')->onDelete('cascade');
                $table->foreignId('user_roles_id')->constrained('user_roles', 'id')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['user_permissions_id', 'user_roles_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
