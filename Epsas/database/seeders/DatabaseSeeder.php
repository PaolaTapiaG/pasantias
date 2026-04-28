<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders de roles y permisos primero
        $this->call(RolesAndPermissionsSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        // Crear usuarios para EPSAS sin duplicarlos
        $adminUser = User::query()->updateOrCreate(
            ['email' => 'carlos.mamani@aguapotable.bo'],
            [
                'name' => 'Carlos Alberto Mamani',
                'password' => Hash::make('Admin2025!'),
            ]
        );
        $adminUser->assignRole('administrador');

        $secretariaUser = User::query()->updateOrCreate(
            ['email' => 'rosa.flores@aguapotable.bo'],
            [
                'name' => 'Rosa Elena Flores',
                'password' => Hash::make('Secret2025!'),
            ]
        );
        $secretariaUser->assignRole('secretaria');

        $tecnicoUser = User::query()->updateOrCreate(
            ['email' => 'pedro.condori@aguapotable.bo'],
            [
                'name' => 'Pedro Luis Condori',
                'password' => Hash::make('Tecnic2025!'),
            ]
        );
        $tecnicoUser->assignRole('tecnico');
    }
}
