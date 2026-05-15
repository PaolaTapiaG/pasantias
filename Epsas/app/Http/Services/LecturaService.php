<?php

namespace App\Services;

use App\Models\Lectura;
use App\Models\Medidor;
use Illuminate\Database\Eloquent\Collection;

class LecturaService
{
    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Lectura::with(['medidor.socio.persona', 'empleado.persona'])
            ->orderByDesc('fecha_lectura');

        if (!empty($filtros['id_medidor'])) {
            $query->where('id_medidor', $filtros['id_medidor']);
        }
        if (!empty($filtros['id_empleado'])) {
            $query->where('id_empleado', $filtros['id_empleado']);
        }
        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $query->whereBetween('fecha_lectura', [$filtros['desde'], $filtros['hasta']]);
        }

        return $query->get();
    }

    public function obtener(int $id): Lectura
    {
        return Lectura::with([
            'medidor.socio.persona',
            'empleado.persona',
            'factura',
        ])->findOrFail($id);
    }

    public function ultimaPorMedidor(int $idMedidor): ?Lectura
    {
        return Lectura::where('id_medidor', $idMedidor)
            ->orderByDesc('fecha_lectura')
            ->first();
    }

    public function sinFactura(): Collection
    {
        return Lectura::with(['medidor.socio.persona', 'empleado.persona'])
            ->sinFactura()
            ->orderBy('fecha_lectura')
            ->get();
    }

    // ──────────────────────────────────────────
    //  Mutaciones
    // ──────────────────────────────────────────

    /**
     * Registra una nueva lectura. Obtiene la lectura_anterior automáticamente
     * y valida que la lectura_actual sea mayor o igual.
     *
     * @param array{
     *   id_medidor: int,
     *   id_empleado: int,
     *   lectura_actual: float,
     *   fecha_lectura?: string,
     *   observaciones?: string
     * } $data
     */
    public function registrar(array $data): Lectura
    {
        $medidor = Medidor::findOrFail($data['id_medidor']);

        if ($medidor->estado !== 'activo') {
            throw new \RuntimeException(
                "El medidor #{$medidor->numero_serie} no está activo (estado: '{$medidor->estado}')."
            );
        }

        $lecturaAnterior = $this->obtenerLecturaAnterior($data['id_medidor']);
        $lecturaActual   = (float) $data['lectura_actual'];

        if ($lecturaActual < $lecturaAnterior) {
            throw new \InvalidArgumentException(
                "La lectura actual ({$lecturaActual}) no puede ser menor a la anterior ({$lecturaAnterior})."
            );
        }

        $lectura = Lectura::create([
            'fecha_lectura'    => $data['fecha_lectura'] ?? now()->toDateString(),
            'lectura_anterior' => $lecturaAnterior,
            'lectura_actual'   => $lecturaActual,
            'consumo_m3'       => round($lecturaActual - $lecturaAnterior, 2),
            'observaciones'    => $data['observaciones'] ?? null,
            'id_medidor'       => $data['id_medidor'],
            'id_empleado'      => $data['id_empleado'],
        ]);

        return $lectura->load(['medidor.socio.persona', 'empleado.persona']);
    }

    /**
     * Corrige una lectura siempre que aún no tenga factura generada.
     *
     * @param array{
     *   lectura_actual?: float,
     *   observaciones?: string
     * } $data
     */
    public function corregir(int $id, array $data): Lectura
    {
        $lectura = Lectura::findOrFail($id);

        if ($lectura->factura()->exists()) {
            throw new \RuntimeException(
                "No se puede corregir: la lectura ya tiene una factura generada."
            );
        }

        if (isset($data['lectura_actual'])) {
            $nueva = (float) $data['lectura_actual'];

            if ($nueva < (float) $lectura->lectura_anterior) {
                throw new \InvalidArgumentException(
                    "La lectura actual no puede ser menor a la anterior ({$lectura->lectura_anterior})."
                );
            }

            $lectura->lectura_actual = $nueva;
            $lectura->consumo_m3    = round($nueva - (float) $lectura->lectura_anterior, 2);
        }

        if (array_key_exists('observaciones', $data)) {
            $lectura->observaciones = $data['observaciones'];
        }

        $lectura->save();

        return $lectura->fresh(['medidor.socio.persona', 'empleado.persona']);
    }

    public function eliminar(int $id): void
    {
        $lectura = Lectura::findOrFail($id);

        if ($lectura->factura()->exists()) {
            throw new \RuntimeException(
                "No se puede eliminar: la lectura ya tiene una factura asociada."
            );
        }

        $lectura->delete();
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    /**
     * Retorna el valor de la última lectura_actual registrada para ese medidor,
     * o 0 si es el primer registro.
     */
    private function obtenerLecturaAnterior(int $idMedidor): float
    {
        $ultima = Lectura::where('id_medidor', $idMedidor)
            ->orderByDesc('fecha_lectura')
            ->value('lectura_actual');

        return (float) ($ultima ?? 0);
    }
}