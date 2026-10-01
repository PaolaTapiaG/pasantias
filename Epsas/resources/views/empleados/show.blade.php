@extends('layouts.app')

@section('title', 'Detalle de empleado - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Empleados</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $empleado->persona?->nombre_completo }}</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.empleados.edit', $empleado) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Editar</a>
                    <a href="{{ route('admin.empleados.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Volver</a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('sms_preview'))
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 shadow-sm">
                    Entorno local: la contrasena temporal enviada por SMS es <span class="font-semibold">{{ session('sms_preview') }}</span>.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700 shadow-sm">
                El empleado puede iniciar sesion con usuario <span class="font-semibold">{{ $empleado->user?->username ?: 'sin usuario' }}</span> o con correo <span class="font-semibold">{{ $empleado->user?->email ?: 'sin correo' }}</span>.
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Datos personales</h2>
                    <div class="mt-5 flex items-center gap-4">
                        @if ($empleado->persona?->foto_url)
                            <img src="{{ $empleado->persona->foto_url }}" alt="Foto del empleado" class="h-20 w-20 rounded-[1.5rem] object-cover ring-1 ring-slate-200">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-slate-100 text-2xl font-bold text-slate-500">
                                {{ strtoupper(substr($empleado->persona?->nombres ?? 'E', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ $empleado->persona?->nombre_completo }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $empleado->user?->email ?: $empleado->persona?->email }}</p>
                        </div>
                    </div>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">CI</dt><dd class="mt-1 text-sm text-slate-800">{{ $empleado->persona?->cedula_identidad }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Rol</dt><dd class="mt-1 text-sm text-slate-800">{{ ucfirst($empleado->rol?->nombre ?? 'Sin rol') }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Telefono</dt><dd class="mt-1 text-sm text-slate-800">{{ $empleado->persona?->telefono ?: 'Sin telefono' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Correo</dt><dd class="mt-1 text-sm text-slate-800">{{ $empleado->persona?->email ?: 'Sin correo' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ingreso</dt><dd class="mt-1 text-sm text-slate-800">{{ optional($empleado->fecha_ingreso)->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estado</dt><dd class="mt-1 text-sm text-slate-800">{{ ucfirst($empleado->estado) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Usuario de acceso</dt><dd class="mt-1 text-sm text-slate-800">{{ $empleado->user?->username ?: 'No creado' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cambio de contrasena</dt><dd class="mt-1 text-sm text-slate-800">{{ $empleado->user?->must_change_password ? 'Pendiente' : 'Actualizada' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Actividad</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cobros</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $empleado->cobros_count }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Lecturas</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $empleado->lecturas_count }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Instalaciones</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $empleado->medidores_instalados_count }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Egresos laborales</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Salario, bonos y aguinaldo</h2>
                        <p class="mt-1 text-sm text-slate-500">Al registrar uno de estos pagos se agrega automaticamente al historial de egresos.</p>
                    </div>
                    <a href="{{ route('admin.gastos.index', ['categoria' => 'Pago de salarios']) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Ver egresos
                    </a>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    @foreach ([
                        ['tipo' => 'salario', 'label' => 'Pago de salario', 'monto' => (float) $empleado->salario_base, 'help' => 'Salario base mensual del rol actual.'],
                        ['tipo' => 'bono', 'label' => 'Bono laboral', 'monto' => (float) $empleado->bono_mensual, 'help' => 'Bono o incentivo asociado al empleado.'],
                        ['tipo' => 'aguinaldo', 'label' => 'Aguinaldo', 'monto' => (float) $empleado->aguinaldo_anual, 'help' => 'Pago anual de aguinaldo configurado.'],
                    ] as $item)
                        <form method="POST" action="{{ route('admin.empleados.egreso-laboral', $empleado) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            <input type="hidden" name="tipo_egreso" value="{{ $item['tipo'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item['help'] }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700">Bs {{ number_format($item['monto'], 2) }}</span>
                            </div>
                            <div class="mt-4 grid gap-3">
                                <input type="date" name="fecha_gasto" value="{{ now()->toDateString() }}" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                <input type="number" name="monto" min="0.01" step="0.01" value="{{ number_format($item['monto'], 2, '.', '') }}" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                <input type="text" name="descripcion" placeholder="Observacion opcional" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Registrar egreso
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
