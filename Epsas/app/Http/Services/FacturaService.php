<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\HistorialPago;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FacturaService
{
    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Factura::with(['socio.persona', 'periodo'])
            ->orderByDesc('fecha_emision');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_socio'])) {
            $query->where('id_socio', $filtros['id_socio']);
        }
        if (!empty($filtros['id_periodo'])) {
            $query->where('id_periodo', $filtros['id_periodo']);
        }

        return $query->get();
    }

    public function obtener(int $id): Factura
    {
        return Factura::with([
            'socio.persona',
            'socio.sector',
            'lectura.medidor',
            'periodo',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ])->findOrFail($id);
    }

    public function resumenPorPeriodo(int $idPeriodo): array
    {
        $facturas = Factura::where('id_periodo', $idPeriodo)->get();

        return [
            'total_facturas'   => $facturas->count(),
            'pendientes'       => $facturas->where('estado', 'pendiente')->count(),
            'pagadas'          => $facturas->where('estado', 'pagada')->count(),
            'vencidas'         => $facturas->where('estado', 'vencida')->count(),
            'parciales'        => $facturas->where('estado', 'parcial')->count(),
            'anuladas'         => $facturas->where('estado', 'anulada')->count(),
            'monto_total'      => $facturas->whereNotIn('estado', ['anulada'])->sum('total'),
            'monto_cobrado'    => $facturas->where('estado', 'pagada')->sum('total'),
            'monto_pendiente'  => $facturas->whereIn('estado', ['pendiente', 'vencida', 'parcial'])->sum('total'),
        ];
    }

    // ──────────────────────────────────────────
    //  Mutaciones
    // ──────────────────────────────────────────

    /**
     * Genera una factura a partir de una lectura.
     * Calcula automáticamente el monto según la tarifa del socio.
     *
     * @param array{
     *   id_lectura: int,
     *   id_periodo: int,
     *   cargo_fijo?: float,
     *   recargo_mora?: float,
     *   descuentos?: float
     * } $data
     */
    public function generar(array $data): Factura
    {
        return DB::transaction(function () use ($data) {
            $lectura = Lectura::with(['medidor.socio.tarifa'])->findOrFail($data['id_lectura']);

            // Validar período abierto
            $periodo = PeriodoFacturacion::findOrFail($data['id_periodo']);
            if ($periodo->cerrado) {
                throw new \RuntimeException(
                    "El período '{$periodo->nombre}' está cerrado. No se pueden generar facturas."
                );
            }

            // Validar que la lectura no tenga ya factura
            if ($lectura->factura()->exists()) {
                throw new \RuntimeException(
                    "La lectura #{$lectura->id_lectura} ya tiene una factura generada."
                );
            }

            $socio  = $lectura->medidor->socio;
            $tarifa = $socio->tarifa;

            // Calcular montos
            $consumoM3    = (float) $lectura->consumo_m3;
            $montoConsumo = $tarifa->calcularMonto($consumoM3);
            $cargoFijo    = (float) ($data['cargo_fijo']    ?? $tarifa->cargo_fijo);
            $recagoMora   = (float) ($data['recargo_mora']  ?? 0);
            $descuentos   = (float) ($data['descuentos']    ?? 0);
            $total        = round(($montoConsumo + $cargoFijo + $recagoMora) - $descuentos, 2);

            $factura = Factura::create([
                'numero_factura' => $this->generarNumeroFactura(),
                'fecha_emision'  => now()->toDateString(),
                'consumo_m3'     => $consumoM3,
                'monto_consumo'  => $montoConsumo,
                'cargo_fijo'     => $cargoFijo,
                'recargo_mora'   => $recagoMora,
                'descuentos'     => $descuentos,
                'total'          => $total,
                'estado'         => 'pendiente',
                'id_socio'       => $socio->id_socio,
                'id_lectura'     => $lectura->id_lectura,
                'id_periodo'     => $periodo->id_periodo,
            ]);

            // Registrar en historial
            HistorialPago::create([
                'tipo_evento' => 'emision_factura',
                'descripcion' => "Factura #{$factura->numero_factura} emitida. Consumo: {$consumoM3} m³.",
                'monto'       => $total,
                'id_socio'    => $socio->id_socio,
                'id_factura'  => $factura->id_factura,
            ]);

            return $factura->load(['socio.persona', 'periodo', 'lectura']);
        });
    }

    /**
     * Genera facturas en lote para todas las lecturas sin factura de un período.
     * Retorna un resumen de facturas creadas y errores.
     */
    public function generarLote(int $idPeriodo): array
    {
        $lecturasService = app(LecturaService::class);
        $lecturasPendientes = $lecturasService->sinFactura();

        $creadas = 0;
        $errores = [];

        foreach ($lecturasPendientes as $lectura) {
            try {
                $this->generar([
                    'id_lectura' => $lectura->id_lectura,
                    'id_periodo' => $idPeriodo,
                ]);
                $creadas++;
            } catch (\Exception $e) {
                $errores[] = [
                    'id_lectura' => $lectura->id_lectura,
                    'motivo'     => $e->getMessage(),
                ];
            }
        }

        return [
            'creadas' => $creadas,
            'errores' => $errores,
            'total_procesadas' => $creadas + count($errores),
        ];
    }

    /**
     * Actualiza recargo o descuento de una factura no pagada/anulada.
     *
     * @param array{
     *   cargo_fijo?: float,
     *   recargo_mora?: float,
     *   descuentos?: float
     * } $data
     */
    public function actualizar(int $id, array $data): Factura
    {
        $factura = Factura::findOrFail($id);

        if (in_array($factura->estado, ['pagada', 'anulada'])) {
            throw new \RuntimeException(
                "No se puede modificar una factura en estado '{$factura->estado}'."
            );
        }

        $camposPermitidos = ['cargo_fijo', 'recargo_mora', 'descuentos'];
        $factura->fill(array_intersect_key($data, array_flip($camposPermitidos)));

        // Recalcular total
        $factura->total = round(
            ((float) $factura->monto_consumo + (float) $factura->cargo_fijo
             + (float) $factura->recargo_mora) - (float) $factura->descuentos,
            2
        );

        $factura->save();

        return $factura->fresh(['socio.persona', 'periodo']);
    }

    public function anular(int $id): Factura
    {
        $factura = Factura::findOrFail($id);

        if ($factura->estado === 'pagada') {
            throw new \RuntimeException(
                "No se puede anular la factura #{$factura->numero_factura}: ya está pagada."
            );
        }
        if ($factura->estado === 'anulada') {
            throw new \RuntimeException("La factura ya está anulada.");
        }

        $factura->update(['estado' => 'anulada']);

        return $factura->fresh();
    }

    // ──────────────────────────────────────────
    //  Procesos batch
    // ──────────────────────────────────────────

    /**
     * Marca como 'vencida' todas las facturas pendientes cuya fecha_pago
     * ya pasó. Se recomienda ejecutar desde un Comando/Scheduler diario.
     */
    public function marcarVencidas(): int
    {
        return Factura::where('estado', 'pendiente')
            ->whereNotNull('fecha_pago')
            ->where('fecha_pago', '<', now()->toDateString())
            ->update(['estado' => 'vencida']);
    }

    /**
     * Aplica un recargo de mora porcentual a todas las facturas vencidas
     * de un período dado.
     */
    public function aplicarMoraMasiva(int $idPeriodo, float $porcentaje): array
    {
        if ($porcentaje <= 0 || $porcentaje > 100) {
            throw new \InvalidArgumentException("El porcentaje de mora debe estar entre 0 y 100.");
        }

        $facturas = Factura::where('id_periodo', $idPeriodo)
            ->where('estado', 'vencida')
            ->get();

        $procesadas = 0;

        DB::transaction(function () use ($facturas, $porcentaje, &$procesadas) {
            foreach ($facturas as $factura) {
                $mora = round((float) $factura->total * ($porcentaje / 100), 2);
                $factura->recargo_mora = (float) $factura->recargo_mora + $mora;
                $factura->total        = (float) $factura->total + $mora;
                $factura->save();
                $procesadas++;
            }
        });

        return [
            'mensaje'    => "Mora del {$porcentaje}% aplicada a {$procesadas} factura(s) vencidas.",
            'procesadas' => $procesadas,
        ];
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    private function generarNumeroFactura(): string
    {
        $ultimo    = Factura::lockForUpdate()->max('numero_factura') ?? 'FAC-000000';
        $secuencia = (int) substr($ultimo, 4) + 1;

        return 'FAC-' . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
    }
}