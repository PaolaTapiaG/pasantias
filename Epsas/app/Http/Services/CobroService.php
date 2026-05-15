<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Factura;
use App\Models\HistorialPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CobroService
{
    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Cobro::with([
            'factura.socio.persona',
            'metodoPago',
            'empleado.persona',
        ])->orderByDesc('fecha_cobro');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_empleado'])) {
            $query->where('id_empleado', $filtros['id_empleado']);
        }
        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->whereBetween('fecha_cobro', [
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
            ]);
        }

        return $query->get();
    }

    public function obtener(int $id): Cobro
    {
        return Cobro::with([
            'factura.socio.persona',
            'metodoPago',
            'empleado.persona',
        ])->findOrFail($id);
    }

    public function porFactura(int $idFactura): Collection
    {
        return Cobro::with(['metodoPago', 'empleado.persona'])
            ->where('id_factura', $idFactura)
            ->orderBy('fecha_cobro')
            ->get();
    }

    /**
     * Resumen de caja para un día y empleado opcional.
     */
    public function resumenCaja(string $fecha, ?int $idEmpleado = null): array
    {
        $query = Cobro::with(['metodoPago', 'empleado.persona'])
            ->where('fecha_cobro', $fecha)
            ->where('estado', '!=', 'anulado');

        if ($idEmpleado) {
            $query->where('id_empleado', $idEmpleado);
        }

        $cobros = $query->get();

        $porMetodo = $cobros
            ->groupBy('id_metodo_pago')
            ->map(fn($grupo) => [
                'metodo'   => $grupo->first()->metodoPago->nombre,
                'cantidad' => $grupo->count(),
                'total'    => round($grupo->sum('monto_pagado'), 2),
            ])
            ->values();

        return [
            'fecha'           => $fecha,
            'total_cobrado'   => round($cobros->sum('monto_pagado'), 2),
            'cantidad_cobros' => $cobros->count(),
            'por_metodo_pago' => $porMetodo,
        ];
    }

    // ──────────────────────────────────────────
    //  Mutaciones
    // ──────────────────────────────────────────

    /**
     * Registra un cobro (total o parcial) sobre una factura.
     * Actualiza el estado de la factura y escribe en historial_pagos.
     *
     * @param array{
     *   id_factura: int,
     *   id_metodo_pago: int,
     *   id_empleado: int,
     *   monto_pagado: float,
     *   comprobante?: string
     * } $data
     */
    public function registrar(array $data): Cobro
    {
        return DB::transaction(function () use ($data) {
            $factura = Factura::with('cobros')->lockForUpdate()->findOrFail($data['id_factura']);

            // Validaciones de estado
            if (in_array($factura->estado, ['pagada', 'anulada'])) {
                throw new \RuntimeException(
                    "La factura #{$factura->numero_factura} ya está en estado '{$factura->estado}'."
                );
            }

            $montoPagado    = round((float) $data['monto_pagado'], 2);
            $montoPendiente = $this->calcularMontoPendiente($factura);

            if ($montoPagado <= 0) {
                throw new \InvalidArgumentException("El monto pagado debe ser mayor a 0.");
            }
            if ($montoPagado > $montoPendiente) {
                throw new \InvalidArgumentException(
                    "El monto pagado ({$montoPagado}) supera el pendiente ({$montoPendiente})."
                );
            }

            $nuevoMontoPendiente = round($montoPendiente - $montoPagado, 2);
            $estadoCobro         = $nuevoMontoPendiente == 0 ? 'completado' : 'parcial';

            // Crear cobro
            $cobro = Cobro::create([
                'fecha_cobro'     => now()->toDateString(),
                'monto_pagado'    => $montoPagado,
                'monto_pendiente' => $nuevoMontoPendiente,
                'estado'          => $estadoCobro,
                'comprobante'     => $data['comprobante'] ?? null,
                'id_factura'      => $factura->id_factura,
                'id_metodo_pago'  => $data['id_metodo_pago'],
                'id_empleado'     => $data['id_empleado'],
            ]);

            // Actualizar estado factura
            $nuevoEstadoFactura = $nuevoMontoPendiente == 0 ? 'pagada' : 'parcial';
            $factura->update([
                'estado'     => $nuevoEstadoFactura,
                'fecha_pago' => $nuevoMontoPendiente == 0 ? now()->toDateString() : null,
            ]);

            // Registrar historial
            $this->registrarHistorial($cobro, $factura, $data['id_empleado']);

            return $cobro->load(['factura.socio.persona', 'metodoPago', 'empleado.persona']);
        });
    }

    /**
     * Anula un cobro y revierte el estado de la factura.
     *
     * @param array{
     *   id_empleado: int,
     *   motivo?: string
     * } $data
     */
    public function anular(int $idCobro, array $data): Cobro
    {
        return DB::transaction(function () use ($idCobro, $data) {
            $cobro   = Cobro::with('factura')->lockForUpdate()->findOrFail($idCobro);
            $factura = $cobro->factura;

            if ($cobro->estado === 'anulado') {
                throw new \RuntimeException("El cobro #{$cobro->id_cobro} ya está anulado.");
            }

            $cobro->update(['estado' => 'anulado']);

            // Recalcular estado de la factura tras anulación
            $montoCobradoRestante = $this->calcularMontoCobrado($factura);
            $nuevoEstado          = match (true) {
                $montoCobradoRestante <= 0                         => 'pendiente',
                $montoCobradoRestante < (float) $factura->total    => 'parcial',
                default                                            => 'pagada',
            };

            $factura->update([
                'estado'     => $nuevoEstado,
                'fecha_pago' => $nuevoEstado === 'pagada' ? $factura->fecha_pago : null,
            ]);

            // Registrar historial
            HistorialPago::create([
                'tipo_evento' => 'anulacion_cobro',
                'descripcion' => $data['motivo'] ?? "Cobro #{$cobro->id_cobro} anulado.",
                'monto'       => $cobro->monto_pagado,
                'id_socio'    => $factura->id_socio,
                'id_factura'  => $factura->id_factura,
                'id_cobro'    => $cobro->id_cobro,
                'id_empleado' => $data['id_empleado'],
            ]);

            return $cobro->fresh(['factura.socio.persona', 'metodoPago', 'empleado.persona']);
        });
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    /**
     * Suma de cobros no anulados de una factura (monto ya cobrado).
     */
    private function calcularMontoCobrado(Factura $factura): float
    {
        return (float) $factura->cobros()
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
    }

    /**
     * Saldo pendiente real = total - lo ya cobrado.
     */
    private function calcularMontoPendiente(Factura $factura): float
    {
        return round((float) $factura->total - $this->calcularMontoCobrado($factura), 2);
    }

    /**
     * Escribe el evento de pago en historial_pagos.
     */
    private function registrarHistorial(Cobro $cobro, Factura $factura, int $idEmpleado): void
    {
        $tipo = $cobro->estado === 'completado' ? 'pago_completo' : 'pago_parcial';

        HistorialPago::create([
            'tipo_evento' => $tipo,
            'descripcion' => "Cobro registrado vía {$cobro->metodoPago->nombre}. "
                           . "Pendiente restante: {$cobro->monto_pendiente}.",
            'monto'       => $cobro->monto_pagado,
            'id_socio'    => $factura->id_socio,
            'id_factura'  => $factura->id_factura,
            'id_cobro'    => $cobro->id_cobro,
            'id_empleado' => $idEmpleado,
        ]);
    }
}