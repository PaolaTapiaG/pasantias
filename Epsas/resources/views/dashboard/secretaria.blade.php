@extends('layouts.app')

@section('title', 'Panel Secretaria - EPSAS')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar Secretaria -->
    @include('slideboard.sidebarsec')

    <!-- Main Content -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6">
                <h1 class="text-2xl font-bold text-gray-900">Panel de Secretaria</h1>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 block w-2 h-2 bg-green-500 rounded-full"></span>
                    </button>
                    <div class="w-1 h-8 bg-gray-200"></div>
                    <div class="flex items-center">
                        <img class="w-10 h-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10B981&color=fff" alt="Avatar">
                        <span class="ml-3 text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
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
                <h2 class="text-3xl font-bold text-gray-900">¡Bienvenida, {{ Auth::user()->name }}!</h2>
                <p class="text-gray-600 mt-2">Panel de gestión administrativa de facturas y cobros</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Socios -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Socios Registrados</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ \App\Models\Socio::count() }}</p>
                            <p class="text-xs text-gray-500 mt-2">Clientes activos</p>
                        </div>
                        <div class="text-4xl text-green-100">🤝</div>
                    </div>
                </div>

                <!-- Facturas Pendientes -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Facturas Pendientes</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">--</p>
                            <p class="text-xs text-gray-500 mt-2">Facturas sin emitir</p>
                        </div>
                        <div class="text-4xl text-yellow-100">📄</div>
                    </div>
                </div>

                <!-- Cobros Pendientes -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Cobros Pendientes</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">--</p>
                            <p class="text-xs text-gray-500 mt-2">Pagos por recibir</p>
                        </div>
                        <div class="text-4xl text-red-100">💰</div>
                    </div>
                </div>

                <!-- Ingresos -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Ingresos Mensuales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">Bs. --</p>
                            <p class="text-xs text-gray-500 mt-2">Este mes</p>
                        </div>
                        <div class="text-4xl text-blue-100">💵</div>
                    </div>
                </div>
            </div>

            <!-- Management Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Socios Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">🤝 Gestión de Socios</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Registrar nuevos socios
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Ver datos de clientes
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Actualizar información
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-green-500 mr-3">✓</span>
                                Gestionar servicios
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Socios
                        </button>
                    </div>
                </div>

                <!-- Facturas Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📄 Facturas</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Crear nuevas facturas
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Ver historial de emisiones
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Enviar por correo
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-yellow-500 mr-3">✓</span>
                                Generar reportes
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Facturas
                        </button>
                    </div>
                </div>

                <!-- Cobros Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">💰 Cobros</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Registrar nuevos cobros
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Ver historial de pagos
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Cobros por método
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-red-500 mr-3">✓</span>
                                Generar comprobantes
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg transition">
                            Ir a Cobros
                        </button>
                    </div>
                </div>

                <!-- Reportes Panel -->
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">📊 Reportes</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Reportes de ingresos
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Reportes de facturación
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Análisis de cobros
                            </li>
                            <li class="flex items-center text-gray-700">
                                <span class="text-blue-500 mr-3">✓</span>
                                Exportar datos
                            </li>
                        </ul>
                        <button class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
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
