<!-- Tecnico Sidebar -->
<div class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 bg-gray-900 text-white">
    <!-- Logo -->
    <div class="flex items-center h-16 px-6 bg-gray-800 border-b border-gray-700">
        <h1 class="text-2xl font-bold">EPSAS</h1>
    </div>

    <!-- User Info -->
    <div class="px-6 py-4 border-b border-gray-700">
        <p class="text-sm text-gray-300">Técnico</p>
        <p class="text-white font-semibold truncate">{{ Auth::user()->name }}</p>
        <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/>
                <path d="M3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"/>
                <path d="M14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
            <span class="group-hover:text-white">Dashboard</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Operaciones</p>
        </div>

        <!-- Medidores -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/>
                <path d="M3 10a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"/>
            </svg>
            <span class="group-hover:text-white">Gestión de Medidores</span>
        </a>

        <!-- Lecturas -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/>
            </svg>
            <span class="group-hover:text-white">Registro de Lecturas</span>
        </a>

        <!-- Historial de Lecturas -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2 4 4 0 00-4 4v10a4 4 0 004 4h12a4 4 0 004-4V5a4 4 0 00-4-4 1 1 0 000 2 2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
            </svg>
            <span class="group-hover:text-white">Historial de Lecturas</span>
        </a>

        <!-- Mantenimiento -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
            </svg>
            <span class="group-hover:text-white">Mantenimiento</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Reportes</p>
        </div>

        <!-- Reportes Técnicos -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2 9a1 1 0 011-1h16a1 1 0 011 1v8a2 2 0 01-2 2H4a2 2 0 01-2-2V9zm13-4a1 1 0 11-2 0 1 1 0 012 0zM9 5a1 1 0 11-2 0 1 1 0 012 0zm6 0a1 1 0 11-2 0 1 1 0 012 0zM9 1a1 1 0 11-2 0 1 1 0 012 0zm6 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
            </svg>
            <span class="group-hover:text-white">Reportes Técnicos</span>
        </a>

        <!-- Análisis de Consumo -->
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            <span class="group-hover:text-white">Análisis de Consumo</span>
        </a>

        <!-- Mi Perfil -->
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Usuario</p>
        </div>

        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition group">
            <svg class="w-5 h-5 mr-3 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
            <span class="group-hover:text-white">Mi Perfil</span>
        </a>
    </nav>

    <!-- Logout -->
    <div class="px-4 py-4 border-t border-gray-700">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center px-4 py-3 text-gray-300 hover:bg-red-900 hover:text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                </svg>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</div>
