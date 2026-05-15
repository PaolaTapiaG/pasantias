<?php

namespace App\Http\Controllers;

use App\Models\Medidor;
use App\Models\Socio;

class MedidorController
{
    // ─────────────────────────────────────────
    //  CRUD Principal
    // ─────────────────────────────────────────

    /**
     * Listar todos los medidores con su socio y empleado instalador.
     * GET /medidores
     */
    public function index(): array
    {
        return Medidor::with(['socio.persona', 'empleadoInstalador.persona'])
            ->orderBy('numero_serie')
            ->get()
            ->toArray();
    }

    /**
     * Registrar un nuevo medidor para un socio.
     * POST /medidores
     *
     * Body esperado:
     * {
     *   "numero_serie": "MED-00123",
     *   "marca": "Actaris",
     *   "modelo": "MTK",
     *   "fecha_instalacion": "2024-01-15",
     *   "id_socio": 5,
     *   "id_empleado_instalador": 2
     * }
     */
    public function store(array $data): array
    {
        // Un socio solo puede tener UN medidor activo a la vez
        $medidorActivo = Medidor::where('id_socio', $data['id_socio'])
            ->where('estado', 'activo')
            ->first();

        if ($medidorActivo) {
            throw new \RuntimeException(
                "El socio ya tiene un medidor activo (#{$medidorActivo->numero_serie}). " .
                "Debe reemplazarlo antes de instalar uno nuevo."
            );
        }

        $medidor = Medidor::create([
            'numero_serie'           => $data['numero_serie'],
            'marca'                  => $data['marca']                  ?? null,
            'modelo'                 => $data['modelo']                 ?? null,
            'fecha_instalacion'      => $data['fecha_instalacion'],
            'estado'                 => 'activo',
            'id_socio'               => $data['id_socio'],
            'id_empleado_instalador' => $data['id_empleado_instalador'] ?? null,
        ]);

        return $medidor->load(['socio.persona', 'empleadoInstalador.persona'])->toArray();
    }

    /**
     * Ver detalle de un medidor con sus lecturas.
     * GET /medidores/{id}
     */
    public function show(int $id): array
    {
        return Medidor::with([
            'socio.persona',
            'empleadoInstalador.persona',
            'lecturas' => fn($q) => $q->orderByDesc('fecha_lectura')->limit(12),
        ])->findOrFail($id)->toArray();
    }

    /**
     * Actualizar datos de un medidor (marca, modelo, estado).
     * PUT /medidores/{id}
     */
    public function update(int $id, array $data): array
    {
        $medidor = Medidor::findOrFail($id);

        $camposPermitidos = ['marca', 'modelo', 'estado', 'fecha_instalacion'];
        $medidor->update(array_intersect_key($data, array_flip($camposPermitidos)));

        return $medidor->fresh(['socio.persona', 'empleadoInstalador.persona'])->toArray();
    }

    /**
     * Eliminar un medidor (solo si no tiene lecturas asociadas).
     * DELETE /medidores/{id}
     */
    public function destroy(int $id): array
    {
        $medidor = Medidor::findOrFail($id);

        if ($medidor->lecturas()->exists()) {
            throw new \RuntimeException(
                "No se puede eliminar: el medidor tiene lecturas registradas."
            );
        }

        $medidor->delete();
        return ['mensaje' => 'Medidor eliminado correctamente.'];
    }

    // ─────────────────────────────────────────
    //  Acciones de negocio
    // ─────────────────────────────────────────

    /**
     * Reemplazar un medidor dañado/antiguo por uno nuevo.
     * POST /medidores/{id}/reemplazar
     *
     * Body esperado:
     * {
     *   "numero_serie": "MED-00999",
     *   "marca": "Zenner",
     *   "modelo": "ETKD",
     *   "fecha_instalacion": "2024-06-01",
     *   "id_empleado_instalador": 3
     * }
     */
    public function reemplazar(int $idAntiguo, array $datosNuevo): array
    {
        $medidorAntiguo = Medidor::findOrFail($idAntiguo);

        // Marcar el antiguo como reemplazado
        $medidorAntiguo->update(['estado' => 'reemplazado']);

        // Instalar el nuevo
        $medidorNuevo = Medidor::create([
            'numero_serie'           => $datosNuevo['numero_serie'],
            'marca'                  => $datosNuevo['marca']                  ?? null,
            'modelo'                 => $datosNuevo['modelo']                 ?? null,
            'fecha_instalacion'      => $datosNuevo['fecha_instalacion'],
            'estado'                 => 'activo',
            'id_socio'               => $medidorAntiguo->id_socio,
            'id_empleado_instalador' => $datosNuevo['id_empleado_instalador'] ?? null,
        ]);

        return [
            'mensaje'          => 'Medidor reemplazado correctamente.',
            'medidor_antiguo'  => $medidorAntiguo->toArray(),
            'medidor_nuevo'    => $medidorNuevo->load(['socio.persona'])->toArray(),
        ];
    }

    /**
     * Cambiar el estado de un medidor (activo, inactivo, dañado, reemplazado).
     * PATCH /medidores/{id}/estado
     */
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        $estadosValidos = ['activo', 'inactivo', 'dañado', 'reemplazado'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '$nuevoEstado' no válido.");
        }

        $medidor = Medidor::findOrFail($id);
        $medidor->update(['estado' => $nuevoEstado]);

        return [
            'mensaje' => "Estado actualizado a '$nuevoEstado'.",
            'medidor' => $medidor->toArray(),
        ];
    }

    /**
     * Obtener las lecturas de un medidor.
     * GET /medidores/{id}/lecturas
     */
    public function lecturas(int $id): array
    {
        $medidor = Medidor::findOrFail($id);

        return $medidor->lecturas()
            ->with(['empleado.persona'])
            ->orderByDesc('fecha_lectura')
            ->get()
            ->toArray();
    }

    /**
     * Listar medidores por socio.
     * GET /medidores/socio/{idSocio}
     */
    public function porSocio(int $idSocio): array
    {
        return Medidor::with(['empleadoInstalador.persona'])
            ->where('id_socio', $idSocio)
            ->orderByDesc('fecha_instalacion')
            ->get()
            ->toArray();
    }
}