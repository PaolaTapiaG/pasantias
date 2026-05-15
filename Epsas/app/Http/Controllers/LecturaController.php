<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Medidor;
use App\Models\Factura;

class LecturaController
{
    // ─────────────────────────────────────────
    //  CRUD Principal
    // ─────────────────────────────────────────

    /**
     * Listar lecturas (con filtros opcionales por medidor o empleado).
     * GET /lecturas?id_medidor=&id_empleado=
     */
    public function index(array $filtros = []): array
    {
        $query = Lectura::with(['medidor.socio.persona', 'empleado.persona'])
            ->orderByDesc('fecha_lectura');

        if (!empty($filtros['id_medidor'])) {
            $query->where('id_medidor', $filtros['id_medidor']);
        }
        if (!empty($filtros['id_empleado'])) {
            $query->where('id_empleado', $filtros['id_empleado']);
        }

        return $query->get()->toArray();
    }

    /**
     * Registrar una nueva lectura de medidor.
     * POST /lecturas
     *
     * Body esperado:
     * {
     *   "id_medidor": 3,
     *   "id_empleado": 2,
     *   "lectura_actual": 1520.00,
     *   "fecha_lectura": "2024-07-01",   // opcional, default hoy
     *   "observaciones": "Sin novedad"   // opcional
     * }
     */
    public function store(array $data): array
    {
        $medidor = Medidor::findOrFail($data['id_medidor']);

        if ($medidor->estado !== 'activo') {
            throw new \RuntimeException(
                "El medidor #{$medidor->numero_serie} no está activo."
            );
        }

        // Obtener la última lectura para calcular lectura_anterior
        $ultimaLectura = Lectura::where('id_medidor', $data['id_medidor'])
            ->orderByDesc('fecha_lectura')
            ->first();

        $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;
        $lecturaActual   = $data['lectura_actual'];

        if ($lecturaActual < $lecturaAnterior) {
            throw new \InvalidArgumentException(
                "La lectura actual ($lecturaActual) no puede ser menor a la anterior ($lecturaAnterior)."
            );
        }

        $lectura = Lectura::create([
            'fecha_lectura'   => $data['fecha_lectura'] ?? now()->toDateString(),
            'lectura_anterior' => $lecturaAnterior,
            'lectura_actual'   => $lecturaActual,
            'consumo_m3'       => $lecturaActual - $lecturaAnterior,
            'observaciones'    => $data['observaciones'] ?? null,
            'id_medidor'       => $data['id_medidor'],
            'id_empleado'      => $data['id_empleado'],
        ]);

        return $lectura->load(['medidor.socio.persona', 'empleado.persona'])->toArray();
    }

    /**
     * Ver detalle de una lectura.
     * GET /lecturas/{id}
     */
    public function show(int $id): array
    {
        return Lectura::with([
            'medidor.socio.persona',
            'empleado.persona',
            'facturas',
        ])->findOrFail($id)->toArray();
    }

    /**
     * Actualizar observaciones de una lectura (solo si no tiene factura generada).
     * PUT /lecturas/{id}
     */
    public function update(int $id, array $data): array
    {
        $lectura = Lectura::findOrFail($id);

        if ($lectura->facturas()->exists()) {
            throw new \RuntimeException(
                "No se puede modificar: ya existe una factura generada para esta lectura."
            );
        }

        // Solo se permiten corregir lectura_actual y observaciones
        if (isset($data['lectura_actual'])) {
            if ($data['lectura_actual'] < $lectura->lectura_anterior) {
                throw new \InvalidArgumentException(
                    "La lectura actual no puede ser menor a la anterior ({$lectura->lectura_anterior})."
                );
            }
            $lectura->lectura_actual = $data['lectura_actual'];
            $lectura->consumo_m3     = $data['lectura_actual'] - $lectura->lectura_anterior;
        }

        if (isset($data['observaciones'])) {
            $lectura->observaciones = $data['observaciones'];
        }

        $lectura->save();

        return $lectura->fresh(['medidor.socio.persona', 'empleado.persona'])->toArray();
    }

    /**
     * Eliminar una lectura (solo si no tiene factura).
     * DELETE /lecturas/{id}
     */
    public function destroy(int $id): array
    {
        $lectura = Lectura::findOrFail($id);

        if ($lectura->facturas()->exists()) {
            throw new \RuntimeException(
                "No se puede eliminar: la lectura ya tiene factura asociada."
            );
        }

        $lectura->delete();
        return ['mensaje' => 'Lectura eliminada correctamente.'];
    }

    // ─────────────────────────────────────────
    //  Acciones de negocio
    // ─────────────────────────────────────────

    /**
     * Obtener la última lectura de un medidor.
     * GET /lecturas/medidor/{idMedidor}/ultima
     */
    public function ultimaPorMedidor(int $idMedidor): array
    {
        $lectura = Lectura::with(['empleado.persona'])
            ->where('id_medidor', $idMedidor)
            ->orderByDesc('fecha_lectura')
            ->firstOrFail();

        return $lectura->toArray();
    }

    /**
     * Obtener lecturas de un medidor en un rango de fechas.
     * GET /lecturas/medidor/{idMedidor}?desde=&hasta=
     */
    public function porMedidorYFecha(int $idMedidor, string $desde, string $hasta): array
    {
        return Lectura::with(['empleado.persona'])
            ->where('id_medidor', $idMedidor)
            ->whereBetween('fecha_lectura', [$desde, $hasta])
            ->orderBy('fecha_lectura')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todas las lecturas pendientes de facturación (sin factura asociada).
     * GET /lecturas/sin-factura
     */
    public function sinFactura(): array
    {
        return Lectura::with(['medidor.socio.persona'])
            ->whereDoesntHave('facturas')
            ->orderBy('fecha_lectura')
            ->get()
            ->toArray();
    }
}