<!-- Secretaria Sidebar -->
<div class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 text-white" style="background-color: #064e3b;">
    <!-- Logo -->
    <div class="flex items-center h-16 px-6 border-b" style="background-color: #065f46; border-color: #047857;">
        <h1 class="text-2xl font-bold">EPSAS</h1>
    </div>

    <!-- User Info -->
    <div class="px-6 py-4 border-b" style="border-color: #047857;">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 overflow-hidden rounded-xl bg-emerald-800 border border-emerald-600">
                @if(Auth::user()->persona?->foto_url)
                    <img src="{{ Auth::user()->persona->foto_url }}" 
                         alt="Foto" 
                         class="h-full w-full object-cover"
                         onerror='this.onerror=null; this.parentElement.innerHTML="<div class=\"flex h-full w-full items-center justify-center text-sm font-bold text-emerald-300\">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>";'>
                @else
                    <div class="flex h-full w-full items-center justify-center text-sm font-bold text-emerald-300">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-semibold truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-emerald-300 uppercase tracking-wider font-bold">Secretaria</p>
            </div>
        </div>
        <p class="text-xs text-emerald-300 truncate">{{ Auth::user()->email }}</p>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Mi Perfil Arriba -->
        <a href="{{ route('secretaria.perfil') }}" 
           class="flex items-center px-4 py-3 mb-2 rounded-lg transition group"
           style="{{ request()->routeIs('secretaria.perfil') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('secretaria.perfil') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('secretaria.perfil') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
            <span>Mi Perfil</span>
        </a>

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="{{ request()->routeIs('dashboard') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('dashboard') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/>
                <path d="M3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"/>
                <path d="M14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-emerald-400 uppercase">Gestión</p>
        </div>

        <!-- Socios -->
        <a href="{{ route('admin.socios.index') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="{{ request()->routeIs('admin.socios.*') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('admin.socios.*') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('admin.socios.*') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM16 15a3 3 0 00-3-3H9a3 3 0 00-3 3v2h10v-2z"/>
            </svg>
            <span>Gestión de Socios</span>
        </a>

        <!-- Facturas -->
        <a href="{{ route('secretaria.facturas.index') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="{{ request()->routeIs('secretaria.facturas.*') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('secretaria.facturas.*') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('secretaria.facturas.*') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
            <span>Facturas</span>
        </a>

        <!-- Cobros -->
        <a href="{{ route('secretaria.cobros.index') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="{{ request()->routeIs('secretaria.cobros.*') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('secretaria.cobros.*') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('secretaria.cobros.*') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.16 2.75a.75.75 0 00-1.32 0l-.83 2.47a.75.75 0 01-.576.548l-2.569.274a.75.75 0 00-.416 1.279l1.867 1.82a.75.75 0 01.215.816l-.441 2.57a.75.75 0 001.088.791l2.298-1.209a.75.75 0 01.659 0l2.298 1.209a.75.75 0 001.088-.79l-.441-2.573a.75.75 0 01.215-.816l1.867-1.82a.75.75 0 00-.416-1.28l-2.57-.274a.75.75 0 01-.575-.548l-.83-2.47z"/>
            </svg>
            <span>Cobros</span>
        </a>

        <!-- Historial de Pagos -->
        <a href="{{ route('secretaria.reportes.index') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="{{ request()->routeIs('secretaria.reportes.*') ? 'background-color: #065f46; color: white;' : 'color: #d1fae5;' }}"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="if(!{{ request()->routeIs('secretaria.reportes.*') ? 'true' : 'false' }}) { this.style.backgroundColor='transparent'; this.style.color='#d1fae5'; }">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('secretaria.reportes.*') ? 'text-white' : 'text-emerald-300 group-hover:text-white' }}" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            <span>Historial de Pagos</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-emerald-400 uppercase">Reportes</p>
        </div>

        <!-- Reportes -->
        <a href="#" 
           class="flex items-center px-4 py-3 rounded-lg transition group"
           style="color: #d1fae5;"
           onmouseover="this.style.backgroundColor='#065f46'; this.style.color='white';"
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='#d1fae5';">
            <svg class="w-5 h-5 mr-3 text-emerald-300 group-hover:text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2 9a1 1 0 011-1h16a1 1 0 011 1v8a2 2 0 01-2 2H4a2 2 0 01-2-2V9zm13-4a1 1 0 11-2 0 1 1 0 012 0zM9 5a1 1 0 11-2 0 1 1 0 012 0zm6 0a1 1 0 11-2 0 1 1 0 012 0zM9 1a1 1 0 11-2 0 1 1 0 012 0zm6 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
            </svg>
            <span>Reportes</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-emerald-400 uppercase">Usuario</p>
        </div>
    </nav>

    <!-- Logout -->
    <div class="px-4 py-4 border-t" style="border-color: #047857;">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center px-4 py-3 text-emerald-100 hover:bg-red-900 hover:text-white rounded-lg transition"
                    onmouseover="this.style.backgroundColor='#7f1d1d'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#d1fae5';">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                </svg>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</div>
