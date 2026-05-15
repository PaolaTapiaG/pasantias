<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
            $monthEarnings = \App\Models\Cobro::whereMonth('fecha_cobro', now()->month)
                ->whereYear('fecha_cobro', now()->year)
                ->where('estado', '!=', 'anulado')
                ->sum('monto_pagado');

            $newSocios = \App\Models\Socio::with(['persona', 'facturas' => function($query) {
                    $query->orderByDesc('fecha_emision')->take(1);
                }])
                ->orderByDesc('created_at')
                ->take(8)
                ->get();

            // Datos para el gráfico de ingresos y socios (últimos 6 meses)
            $chartData = collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                return [
                    'month' => $date->translatedFormat('F'),
                    'earnings' => \App\Models\Cobro::whereMonth('fecha_cobro', $date->month)
                        ->whereYear('fecha_cobro', $date->year)
                        ->where('estado', '!=', 'anulado')
                        ->sum('monto_pagado'),
                    'socios' => \App\Models\Socio::whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->count(),
                ];
            });

            $dashboardStats = \Illuminate\Support\Facades\Cache::remember('dashboard.secretaria.stats', now()->addMinutes(10), function () {
                return [
                    'socios' => \App\Models\Socio::count(),
                    'facturas' => \App\Models\Factura::count(),
                    'cobros' => \App\Models\Cobro::count(),
                ];
            });

            $stats = [
                [
                    'label' => 'Socios',
                    'value' => $dashboardStats['socios'],
                    'detail' => 'Clientes registrados',
                ],
                [
                    'label' => 'Facturas',
                    'value' => $dashboardStats['facturas'],
                    'detail' => 'Facturas emitidas',
                ],
                [
                    'label' => 'Cobros',
                    'value' => $dashboardStats['cobros'],
                    'detail' => 'Pagos registrados',
                ],
                [
                    'label' => 'Estado',
                    'value' => 'En linea',
                    'detail' => 'Sistema operativo correctamente',
                ],
            ];

            return view('dashboard.secretaria', compact('monthEarnings', 'newSocios', 'chartData', 'stats'));
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

    public function perfil()
    {
        return view('dashboard.perfil_secretaria');
    }

    public function perfilUpdate(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password' => 'nullable|confirmed|min:8',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'La contraseña actual no es correcta.');
            }
            $user->password = Hash::make($request->password);
        }

        // Asegurar que el usuario tenga una persona asociada
        if (!$user->id_persona) {
            $persona = \App\Models\Persona::create([
                'nombres' => $user->name,
                'email' => $user->email,
            ]);
            $user->id_persona = $persona->id_persona;
        } else {
            $persona = $user->persona;
        }

        if ($persona) {
            $persona->telefono = $request->telefono;
            if ($request->hasFile('foto')) {
                // Eliminar foto anterior si existe
                if ($persona->foto_path) {
                    $oldPath = public_path('storage/' . $persona->foto_path);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                
                // Generar nombre único
                $file = $request->file('foto');
                $filename = 'perfil_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Asegurar que el directorio existe en public/storage/perfiles
                $destinationPath = public_path('storage/perfiles');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Mover el archivo directamente a public/storage/perfiles
                $file->move($destinationPath, $filename);
                
                // Guardar la ruta relativa para el accessor
                $persona->foto_path = 'perfiles/' . $filename;
            }
            $persona->save();
        }

        $user->save();

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
