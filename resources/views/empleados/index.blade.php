@extends('layouts.app')

@section('title', 'Empleados - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Empleados</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Gestion de empleados</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.empleados.export', ['format' => 'excel'] + request()->query()) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">Exportar Excel</a>
                    <a href="{{ route('admin.empleados.export', ['format' => 'pdf'] + request()->query()) }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Exportar PDF</a>
                    <a href="{{ route('admin.empleados.create') }}" class="inline-flex items-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Registrar empleado</a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Activos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['activos'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Inactivos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['inactivos'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Tecnicos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['tecnicos'] }}</p>
                </article>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1.4fr_0.8fr_0.8fr_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, CI o rol..." class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <select name="estado" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los estados</option>
                        @foreach (['activo', 'inactivo', 'suspendido'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    <select name="rol" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected((string) request('rol') === (string) $rol->id_rol)>{{ ucfirst($rol->nombre) }}</option>
                        @endforeach
                    </select>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filtrar
                    </button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Empleado</th>
                                <th class="px-5 py-4">Contacto</th>
                                <th class="px-5 py-4">Rol</th>
                                <th class="px-5 py-4">Ingreso</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($empleados as $empleado)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($empleado->persona?->foto_url)
                                                <img src="{{ $empleado->persona->foto_url }}" alt="Foto" class="h-11 w-11 rounded-2xl object-cover ring-1 ring-slate-200">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-500">
                                                    {{ strtoupper(substr($empleado->persona?->nombres ?? 'E', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-semibold text-slate-900">{{ $empleado->persona?->nombre_completo }}</div>
                                                <div class="mt-1 text-xs text-slate-500">CI {{ $empleado->persona?->cedula_identidad }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div>{{ $empleado->persona?->telefono ?: 'Sin telefono' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $empleado->persona?->email ?: 'Sin correo' }}</div>
                                        <div class="mt-1 text-xs text-blue-700">Usuario: {{ $empleado->user?->username ?: 'Sin usuario' }}</div>
                                    </td>
                                    <td class="px-5 py-4">{{ ucfirst($empleado->rol?->nombre ?? 'Sin rol') }}</td>
                                    <td class="px-5 py-4">{{ optional($empleado->fecha_ingreso)->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $empleado->estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : ($empleado->estado === 'suspendido' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                            {{ ucfirst($empleado->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.empleados.show', $empleado) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('admin.empleados.edit', $empleado) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron empleados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $empleados->links() }}
            </div>
        </main>
    </div>
</div>
@endsection
