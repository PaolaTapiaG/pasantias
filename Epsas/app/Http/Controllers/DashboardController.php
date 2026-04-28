<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $roles = $user->cachedRoleNames();

        if ($roles->contains('administrador')) {
            return view('dashboard.admin', ['user' => $user]);
        }

        if ($roles->contains('secretaria')) {
            return view('dashboard.secretaria');
        }

        if ($roles->contains('tecnico')) {
            return view('dashboard.tecnico');
        }

        return view('dashboard.index', [
            'user' => $user,
            'roleLabel' => 'Usuario',
            'modules' => [
                'Panel principal',
                'Consulta de informacion',
                'Acceso a modulos asignados',
            ],
        ]);
    }
}
