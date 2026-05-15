@extends('layouts.app')

@section('title', 'Gateway SMS local - EPSAS')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(96,165,250,0.15),_transparent_20%),linear-gradient(180deg,_#f8fbff_0%,_#eef4fb_100%)]">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Mensajeria</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Gateway SMS</h1>
                    <p class="mt-2 text-sm text-slate-500">Envio de prueba, monitoreo rapido y bandeja local de mensajes procesados.</p>
                </div>
                <a href="{{ route('admin.configuracion.index') }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Volver a configuracion
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Driver</p>
                    <p class="mt-3 text-2xl font-bold text-slate-900">{{ strtoupper($messaging['sms_driver'] ?? 'log') }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Mensajes hoy</p>
                    <p class="mt-3 text-3xl font-bold text-blue-600">{{ $stats['today'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Fallidos</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">{{ $stats['failed'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Ultimo mensaje</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">{{ $stats['last_message_at'] ? \Illuminate\Support\Carbon::parse($stats['last_message_at'])->format('d/m/Y H:i') : 'Sin envios' }}</p>
                </article>
            </section>

            <section class="mt-8 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Enviar prueba</h2>
                            <p class="mt-2 text-sm text-slate-500">Ideal para confirmar que el servidor de mensajeria esta registrando correctamente.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Test</span>
                    </div>

                    <form method="POST" action="{{ route('admin.configuracion.sms-gateway.store') }}" class="mt-6 grid gap-5">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Telefono destino</label>
                            <input name="recipient_phone" value="{{ old('recipient_phone') }}" placeholder="+59170000000" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre destinatario</label>
                            <input name="recipient_name" value="{{ old('recipient_name') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de mensaje</label>
                            <select name="type" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                                <option value="general">General</option>
                                <option value="recordatorio">Recordatorio</option>
                                <option value="alerta">Alerta</option>
                                <option value="cobranza">Cobranza</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Mensaje</label>
                            <textarea name="message" rows="5" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">{{ old('message', 'Mensaje de prueba enviado desde el panel EPSAS.') }}</textarea>
                        </div>
                        <button class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_35px_rgba(37,99,235,0.25)] transition hover:bg-blue-700">
                            Enviar mensaje
                        </button>
                    </form>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Historial</h2>
                            <p class="mt-2 text-sm text-slate-500">Consulta los mensajes procesados por el sistema y su resultado.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Bandeja</span>
                    </div>

                    <form method="GET" class="mt-6 grid gap-4 md:grid-cols-[1fr_auto]">
                        <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por telefono, nombre, tipo o mensaje..." class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Buscar
                        </button>
                    </form>

                    <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50/80">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-4">Fecha</th>
                                        <th class="px-5 py-4">Destino</th>
                                        <th class="px-5 py-4">Tipo</th>
                                        <th class="px-5 py-4">Mensaje</th>
                                        <th class="px-5 py-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                    @forelse ($messages as $message)
                                        <tr>
                                            <td class="px-5 py-4">{{ optional($message->sent_at)->format('d/m/Y H:i') ?: optional($message->created_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                            <td class="px-5 py-4">
                                                <div class="font-semibold text-slate-900">{{ $message->recipient_name ?: 'Sin nombre' }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $message->recipient_phone }}</div>
                                            </td>
                                            <td class="px-5 py-4">{{ str_replace('_', ' ', ucfirst($message->type)) }}</td>
                                            <td class="px-5 py-4 max-w-sm">{{ $message->message }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $message->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ ucfirst($message->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                                No hay SMS enviados todavia.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6">
                        {{ $messages->links() }}
                    </div>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection
