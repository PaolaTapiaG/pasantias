<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\HistorialPago;
use App\Models\MetodoPago;
use App\Models\Socio;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CobroController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('buscar', ''));

        $socios = Socio::query()
            ->with(['persona', 'facturasPendientes.cobros'])
            ->where('estado', '!=', 'inactivo')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('numero_socio', 'ilike', "%{$search}%")
                        ->orWhereHas('persona', function ($personaQuery) use ($search) {
                            $personaQuery->where('nombres', 'ilike', "%{$search}%")
                                ->orWhere('apellidos', 'ilike', "%{$search}%")
                                ->orWhere('cedula_identidad', 'ilike', "%{$search}%");
                        });
                });
            })
            ->get()
            ->map(fn (Socio $socio) => $this->mapSocioCobroData($socio))
            ->filter(fn (array $socio) => count($socio['facturas_pendientes']) > 0)
            ->sortBy('nombre_completo')
            ->values();

        $resumen = [
            'socios_con_pendientes' => $socios->count(),
            'multas_pendientes' => round($socios->sum('recargos_pendientes'), 2),
            'monto_total_pendiente' => round($socios->sum('total_pendiente'), 2),
        ];

        return view('cobros.index', [
            'socios' => $socios,
            'search' => $search,
            'resumen' => $resumen,
        ]);
    }

    public function show(Request $request, Socio $socio)
    {
        $selectedDate = $request->query('fecha', now()->toDateString());
        $socio->load(['persona', 'facturasPendientes.periodo', 'facturasPendientes.cobros']);

        $selectedSocio = $this->mapSocioCobroData($socio);

        $metodosPago = MetodoPago::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $cobrosSocio = Cobro::query()
            ->with(['factura', 'metodoPago'])
            ->whereHas('factura', fn ($query) => $query->where('id_socio', $socio->id_socio))
            ->orderByDesc('fecha_cobro')
            ->orderByDesc('id_cobro')
            ->limit(10)
            ->get();

        $qrCuotaMonto = round($selectedSocio['subtotal_pendiente'], 2);
        $qrMoraMonto = round($selectedSocio['recargos_pendientes'], 2);

        return view('cobros.create', [
            'selectedSocio' => $selectedSocio,
            'metodosPago' => $metodosPago,
            'selectedDate' => $selectedDate,
            'cobrosSocio' => $cobrosSocio,
            'qrCuotaSvg' => $qrCuotaMonto > 0 ? $this->makeQrSvg($this->buildQrPayload($selectedSocio, 'CUOTA', $qrCuotaMonto)) : null,
            'qrMoraSvg' => $qrMoraMonto > 0 ? $this->makeQrSvg($this->buildQrPayload($selectedSocio, 'MORA', $qrMoraMonto)) : null,
            'qrCuotaMonto' => $qrCuotaMonto,
            'qrMoraMonto' => $qrMoraMonto,
        ]);
    }

    public function store(Request $request, Socio $socio)
    {
        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id_metodo_pago'],
            'cantidad_pagada' => ['required', 'numeric', 'min:0.01'],
            'factura_ids' => ['required', 'array', 'min:1'],
            'factura_ids.*' => ['integer', 'exists:facturas,id_factura'],
            'comprobante' => ['nullable', 'string', 'max:100'],
        ]);

        $empleado = $this->resolveEmpleado();

        try {
            $resultado = DB::transaction(function () use ($data, $empleado, $socio) {
                $facturas = Factura::query()
                    ->with(['cobros'])
                    ->where('id_socio', $socio->id_socio)
                    ->whereIn('id_factura', $data['factura_ids'])
                    ->lockForUpdate()
                    ->get();

                if ($facturas->count() !== count($data['factura_ids'])) {
                    throw new \RuntimeException('No se pudieron cargar todas las facturas seleccionadas para este socio.');
                }

                $facturasPendientes = $facturas
                    ->map(function (Factura $factura) {
                        $pendiente = $this->pendingAmount($factura);

                        return [
                            'factura' => $factura,
                            'pendiente' => $pendiente,
                        ];
                    })
                    ->filter(fn (array $item) => $item['pendiente'] > 0);

                if ($facturasPendientes->isEmpty()) {
                    throw new \RuntimeException('Las facturas seleccionadas ya no tienen saldo pendiente.');
                }

                $totalSeleccionado = round($facturasPendientes->sum('pendiente'), 2);
                $cantidadPagada = round((float) $data['cantidad_pagada'], 2);

                if ($cantidadPagada < $totalSeleccionado) {
                    throw new \RuntimeException('La cantidad pagada no cubre el total seleccionado.');
                }

                $cobros = collect();

                foreach ($facturasPendientes as $item) {
                    $factura = $item['factura'];
                    $montoPendiente = $item['pendiente'];

                    $cobro = Cobro::create([
                        'fecha_cobro' => $data['fecha_pago'],
                        'monto_pagado' => $montoPendiente,
                        'monto_pendiente' => 0,
                        'estado' => 'completado',
                        'comprobante' => $data['comprobante'] ?: $this->buildComprobante($factura),
                        'id_factura' => $factura->id_factura,
                        'id_metodo_pago' => $data['id_metodo_pago'],
                        'id_empleado' => $empleado->id_empleado,
                    ]);

                    $factura->update([
                        'estado' => 'pagada',
                        'fecha_pago' => $data['fecha_pago'],
                    ]);

                    HistorialPago::create([
                        'fecha_evento' => now(),
                        'tipo_evento' => 'pago_completo',
                        'descripcion' => 'Pago registrado desde la vista de cobros. Factura cancelada en su totalidad.',
                        'monto' => $montoPendiente,
                        'id_socio' => $factura->id_socio,
                        'id_factura' => $factura->id_factura,
                        'id_cobro' => $cobro->id_cobro,
                        'id_empleado' => $empleado->id_empleado,
                    ]);

                    $cobros->push($cobro);
                }

                return $cobros;
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', $exception->getMessage() ?: 'No se pudo registrar el pago.');
        }

        Cache::forget('facturas.totales');

        return redirect()
            ->route('secretaria.cobros.show', $socio)
            ->with('success', 'Pago registrado correctamente. Se guardaron ' . $resultado->count() . ' cobro(s) en facturacion.');
    }

    private function mapSocioCobroData(Socio $socio): array
    {
        $facturas = $socio->facturasPendientes
            ->sortBy('fecha_emision')
            ->map(function (Factura $factura) {
                $pagado = (float) $factura->cobros
                    ->where('estado', '!=', 'anulado')
                    ->sum('monto_pagado');
                $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
                $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

                return [
                    'id_factura' => $factura->id_factura,
                    'numero_factura' => $factura->numero_factura,
                    'periodo' => $factura->periodo?->nombre ?? 'Sin periodo',
                    'fecha_emision' => optional($factura->fecha_emision)->format('d/m/Y'),
                    'estado' => $factura->estado,
                    'subtotal' => $subtotal,
                    'recargo_mora' => (float) $factura->recargo_mora,
                    'total' => (float) $factura->total,
                    'pagado' => $pagado,
                    'pendiente' => $pendiente,
                    'descripcion' => $this->facturaDescription($factura),
                ];
            })
            ->filter(fn (array $factura) => $factura['pendiente'] > 0)
            ->values();

        return [
            'id_socio' => $socio->id_socio,
            'nombre_completo' => $socio->persona?->nombre_completo ?? 'Sin socio',
            'codigo_display' => $socio->codigo_display,
            'cedula_identidad' => $socio->persona?->cedula_identidad,
            'telefono' => $socio->persona?->telefono,
            'email' => $socio->persona?->email,
            'facturas_pendientes' => $facturas->all(),
            'subtotal_pendiente' => round($facturas->sum('subtotal'), 2),
            'recargos_pendientes' => round($facturas->sum('recargo_mora'), 2),
            'total_pendiente' => round($facturas->sum('pendiente'), 2),
        ];
    }

    private function facturaDescription(Factura $factura): string
    {
        $partes = ['Consumo de agua'];

        if ((float) $factura->recargo_mora > 0) {
            $partes[] = 'incluye mora';
        }

        if ($factura->estado === 'vencida') {
            $partes[] = 'pago retrasado';
        }

        return ucfirst(implode(', ', $partes));
    }

    private function pendingAmount(Factura $factura): float
    {
        $pagado = (float) $factura->cobros()
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');

        return round(max(0, (float) $factura->total - $pagado), 2);
    }

    private function buildComprobante(Factura $factura): string
    {
        return 'COB-' . $factura->numero_factura . '-' . now()->format('YmdHis');
    }

    private function resolveEmpleado(): Empleado
    {
        $user = Auth::user();

        if ($user?->email) {
            $empleado = Empleado::query()
                ->where('estado', 'activo')
                ->whereHas('persona', function ($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->first();

            if ($empleado) {
                return $empleado;
            }
        }

        return Empleado::query()
            ->where('estado', 'activo')
            ->orderBy('id_empleado')
            ->firstOrFail();
    }

    private function buildQrPayload(array $socio, string $concepto, float $monto): string
    {
        return implode('|', [
            'EPSAS',
            'SOCIO:' . $socio['codigo_display'],
            'NOMBRE:' . $socio['nombre_completo'],
            'CONCEPTO:' . $concepto,
            'MONTO:' . number_format($monto, 2, '.', ''),
            'FECHA:' . now()->format('Y-m-d'),
        ]);
    }

    private function makeQrSvg(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
