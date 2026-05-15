<?php

namespace App\Services;

use App\Models\Medidor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MedidorService
{
    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Medidor::with(['socio.persona', 'empleadoInstalador.persona'])
            ->orderBy('numero_serie');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_socio'])) {
            $query->where('id_socio', $filtros['id_socio']);
        }

        return $query->get();
    }

    public function obtener(int $id): Medidor
    {
        return Medidor::with([
            'socio.persona',
            'empleadoInstalador.persona',
            'ultimaLectura',
        ])->findOrFail($id);
    }

    public function porSocio(int $idSocio): Collection
    {
        return Medidor::with(['empleadoInstalador.persona'])
            ->where('id_socio', $idSocio)
            ->orderByDesc('fecha_instalacion')
            ->get();
    }

    // ──────────────────────────────────────────
    //  Mutaciones
    // ──────────────────────────────────────────

    /**
     * Instala un nuevo medidor para un socio.
     * Valida que el socio no tenga ya un medidor activo.
     *
     * @param array{
     *   numero_serie: string,
     *   marca?: string,
     *   modelo?: string,
     *   fecha_instalacion: string,
     *   id_socio: int,
     *   id_empleado_instalador?: int
     * } $data
     */
    public function instalar(array $data): Medidor
    {
        $this->validarSinMedidorActivo($data['id_socio']);

        $medidor = Medidor::create([
            'numero_serie'           => $data['numero_serie'],
            'marca'                  => $data['marca']                  ?? null,
            'modelo'                 => $data['modelo']                 ?? null,
            'fecha_instalacion'      => $data['fecha_instalacion'],
            'estado'                 => 'activo',
            'id_socio'               => $data['id_socio'],
            'id_empleado_instalador' => $data['id_empleado_instalador'] ?? null,
        ]);

        return $medidor->load(['socio.persona', 'empleadoInstalador.persona']);
    }

    /**
     * Reemplaza el medidor activo de un socio por uno nuevo.
     * El medidor antiguo queda marcado como 'reemplazado'.
     *
     * @param array{
     *   numero_serie: string,
     *   marca?: string,
     *   modelo?: string,
     *   fecha_instalacion: string,
     *   id_empleado_instalador?: int
     * } $datosNuevo
     * @return array{antiguo: Medidor, nuevo: Medidor}
     */
    public function reemplazar(int $idMedidorAntiguo, array $datosNuevo): array
    {
        return DB::transaction(function () use ($idMedidorAntiguo, $datosNuevo) {
            $antiguo = Medidor::findOrFail($idMedidorAntiguo);

            if ($antiguo->estado !== 'activo') {
                throw new \RuntimeException(
                    "Solo se puede reemplazar un medidor activo. Estado actual: '{$antiguo->estado}'."
                );
            }

            $antiguo->update(['estado' => 'reemplazado']);

            $nuevo = Medidor::create([
                'numero_serie'           => $datosNuevo['numero_serie'],
                'marca'                  => $datosNuevo['marca']                  ?? null,
                'modelo'                 => $datosNuevo['modelo']                 ?? null,
                'fecha_instalacion'      => $datosNuevo['fecha_instalacion'],
                'estado'                 => 'activo',
                'id_socio'               => $antiguo->id_socio,
                'id_empleado_instalador' => $datosNuevo['id_empleado_instalador'] ?? null,
            ]);

            return [
                'antiguo' => $antiguo->fresh(),
                'nuevo'   => $nuevo->load(['socio.persona', 'empleadoInstalador.persona']),
            ];
        });
    }

    public function cambiarEstado(int $id, string $nuevoEstado): Medidor
    {
        $estadosValidos = ['activo', 'inactivo', 'dañado', 'reemplazado'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '{$nuevoEstado}' no válido.");
        }

        // Si se activa uno, verificar que el socio no tenga ya otro activo
        if ($nuevoEstado === 'activo') {
            $medidor = Medidor::findOrFail($id);
            $this->validarSinMedidorActivo($medidor->id_socio, $id);
        }

        $medidor = Medidor::findOrFail($id);
        $medidor->update(['estado' => $nuevoEstado]);

        return $medidor->fresh();
    }

    public function eliminar(int $id): void
    {
        $medidor = Medidor::findOrFail($id);

        if ($medidor->lecturas()->exists()) {
            throw new \RuntimeException(
                "No se puede eliminar: el medidor #{$medidor->numero_serie} tiene lecturas registradas."
            );
        }

        $medidor->delete();
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    /**
     * Lanza excepción si el socio ya tiene un medidor activo distinto al excluido.
     */
    private function validarSinMedidorActivo(int $idSocio, ?int $excluirId = null): void
    {
        $query = Medidor::where('id_socio', $idSocio)->where('estado', 'activo');

        if ($excluirId) {
            $query->where('id_medidor', '!=', $excluirId);
        }

        $activo = $query->first();

        if ($activo) {
            throw new \RuntimeException(
                "El socio ya tiene el medidor activo #{$activo->numero_serie}. Reemplácelo primero."
            );
        }
    }
}