@extends('layouts.app')

@section('title', 'Panel Técnico - EPSAS')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar Tecnico -->
    @include('slideboard.sidebartec')

    <!-- Main Content -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6">
                <h1 class="text-2xl font-bold text-gray-900">Panel Técnico</h1>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 block w-2 h-2 bg-blue-500 rounded-full"></span>
                    </button>
                    <div class="w-1 h-8 bg-gray-200"></div>
                    <div class="flex items-center">
                        <img class="w-10 h-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff" alt="Avatar">
                        <span class="ml-3 text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @if (session('success'))
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-medium">¡Éxito!</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Welcome Section -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900">¡Bienvenido, {{ Auth::user()->name }}!</h2>
                <p class="text-gray-600 mt-2">Panel de operaciones técnicas y registro de lecturas</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Medidores Asignados -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Medidores Asignados</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\Medidor::count() }}</p>
                            <p class="text-xs text-gray-500 mt-2">Equipos bajo tu responsabilidad</p>
                        </div>
                        <div class="text-4xl text-blue-100">📏</div>
                    </div>
                </div>

                <!-- Lecturas Este Mes -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-cyan-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Lecturas Registradas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">--</p>
                            <p class="text-xs text-gray-500 mt-2">Este mes</p>
                        </div>
                        <div class="text-4xl text-cyan-100">📖</div>
                    </div>
                </div>

                <!-- Lecturas Pendientes -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Lecturas Pendientes</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">--</p>
                            <p class="text-xs text-gray-500 mt-2">Por registrar</p>
                        </div>
                        <div class="text-4xl text-yellow-100">⏰</div>
                    </div>
                </div>

                <!-- Mantenimiento Programado -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Mantenimientos</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">--</p>
                            <p class="text-xs text-gray-500 mt-2">Próximos trabajos</p>
                        </div>
                        <div class="text-4xl text-purple-100">🔧</div>
                    </div>
                </div>
            </div>

            <!-- Management Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Medidores Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📏 Gestión de Medidores</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Ver medidores asignados
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Información técnica
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Estado de instalación
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Historial técnico
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Medidores
                        </button>
                    </div>
                </div>

                <!-- Lecturas Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📖 Registro de Lecturas</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-cyan-500 mr-3">✓</span>
                                Registrar nuevas lecturas
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-cyan-500 mr-3">✓</span>
                                Validar lecturas
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-cyan-500 mr-3">✓</span>
                                Historial de consumo
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-cyan-500 mr-3">✓</span>
                                Detectar anomalías
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Lecturas
                        </button>
                    </div>
                </div>

                <!-- Mantenimiento Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">🔧 Mantenimiento</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Programar mantenimiento
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Registrar intervenciones
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Reporte de reparaciones
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Plan de preventivo
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Mantenimiento
                        </button>
                    </div>
                </div>

                <!-- Reportes Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📊 Reportes Técnicos</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Análisis de consumo
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Rendimiento de medidores
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Estadísticas mensuales
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-purple-500 mr-3">✓</span>
                                Exportar datos
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded-lg transition">
                            Ver Reportes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center text-gray-500 text-sm border-t border-gray-200 pt-6">
                <p>© 2026 EPSAS - Sistema de Gestión de Agua. Todos los derechos reservados.</p>
            </div>
        </main>
    </div>
</div>
@endsection
