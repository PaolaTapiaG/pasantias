<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Socio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SocioService
{
    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Socio::with(['persona', 'sector', 'tarifa'])
            ->orderBy('numero_socio');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_sector'])) {
            $query->where('id_sector', $filtros['id_sector']);
        }

        return $query->get();
    }

    public function buscar(string $termino): Collection
    {
        return Socio::with(['persona', 'sector'])
            ->where('numero_socio', 'ilike', "%{$termino}%")
            ->orWhereHas('persona', function ($q) use ($termino) {
                $q->where('nombres', 'ilike', "%{$termino}%")
                  ->orWhere('apellidos', 'ilike', "%{$termino}%")
                  ->orWhere('cedula_identidad', 'ilike', "%{$termino}%");
            })
            ->get();
    }

    public function obtener(int $id): Socio
    {
        return Socio::with([
            'persona',
            'sector',
            'tarifa',
            'medidorActivo',
            'facturasPendientes',
        ])->findOrFail($id);
    }

    // ──────────────────────────────────────────
    //  Mutaciones
    // ──────────────────────────────────────────

    /**
     * Crea la Persona (si no existe) y luego el Socio dentro de una transacción.
     *
     * @param array{
     *   nombres: string,
     *   apellidos: string,
     *   cedula_identidad: string,
     *   telefono?: string,
     *   email?: string,
     *   direccion?: string,
     *   id_sector: int,
     *   id_tarifa: int
     * } $data
     */
    public function crear(array $data): Socio
    {
        return DB::transaction(function () use ($data) {
            $persona = Persona::firstOrCreate(
                ['cedula_identidad' => $data['cedula_identidad']],
                [
                    'nombres'   => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'telefono'  => $data['telefono'] ?? null,
                    'email'     => $data['email']    ?? null,
                ]
            );

            $socio = Socio::create([
                'numero_socio'   => $this->generarNumeroSocio(),
                'direccion'      => $data['direccion'] ?? null,
                'estado'         => 'activo',
                'id_persona'     => $persona->id_persona,
                'id_sector'      => $data['id_sector'],
                'id_tarifa'      => $data['id_tarifa'],
            ]);

            return $socio->load(['persona', 'sector', 'tarifa']);
        });
    }

    /**
     * Actualiza datos del socio y/o de su persona.
     *
     * @param array{
     *   nombres?: string,
     *   apellidos?: string,
     *   telefono?: string,
     *   email?: string,
     *   direccion?: string,
     *   id_sector?: int,
     *   id_tarifa?: int,
     *   estado?: string
     * } $data
     */
    public function actualizar(int $id, array $data): Socio
    {
        return DB::transaction(function () use ($id, $data) {
            $socio = Socio::with('persona')->findOrFail($id);

            $camposPersona = ['nombres', 'apellidos', 'telefono', 'email'];
            $datosPersona  = array_intersect_key($data, array_flip($camposPersona));
            if (!empty($datosPersona)) {
                $socio->persona->update($datosPersona);
            }

            $camposSocio = ['direccion', 'id_sector', 'id_tarifa', 'estado'];
            $datosSocio  = array_intersect_key($data, array_flip($camposSocio));
            if (!empty($datosSocio)) {
                $socio->update($datosSocio);
            }

            return $socio->fresh(['persona', 'sector', 'tarifa']);
        });
    }

    public function cambiarEstado(int $id, string $nuevoEstado): Socio
    {
        $estadosValidos = ['activo', 'inactivo', 'suspendido', 'cortado'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '{$nuevoEstado}' no válido.");
        }

        $socio = Socio::findOrFail($id);
        $socio->update(['estado' => $nuevoEstado]);

        return $socio->fresh();
    }

    public function eliminar(int $id): void
    {
        $socio = Socio::findOrFail($id);

        if ($socio->facturas()->exists()) {
            // Soft-delete lógico: no se borra físicamente si tiene facturas
            $socio->update(['estado' => 'inactivo']);
            return;
        }

        DB::transaction(function () use ($socio) {
            $socio->persona()->delete();
            $socio->delete();
        });
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    private function generarNumeroSocio(): string
    {
        $ultimo    = Socio::lockForUpdate()->max('numero_socio') ?? 'SOC-0000';
        $secuencia = (int) substr($ultimo, 4) + 1;

        return 'SOC-' . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }
}