<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\OperationalCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class OperationalMapController extends Controller
{
    public function __invoke(): View
    {
        $payload = OperationalCache::remember('mapa-operativo:markers', function (): array {
            try {
                return $this->buildPayload();
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'markers' => [],
                    'stats' => [],
                    'center' => ['lat' => -21.5355, 'lng' => -64.7296],
                    'warning' => 'No se pudo leer la base de datos para cargar el mapa operativo.',
                ];
            }
        }, now()->addMinutes(15));

        return view('mapa-operativo.index', $payload);
    }

    public function updateOffice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'gps_latitude' => ['required', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['required', 'numeric', 'between:-180,180'],
            'map_label' => ['nullable', 'string', 'max:120'],
        ], [
            'address.required' => 'La direccion de la oficina es obligatoria.',
            'gps_latitude.required' => 'Marca la latitud de la oficina en el mapa.',
            'gps_longitude.required' => 'Marca la longitud de la oficina en el mapa.',
            'gps_latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'gps_longitude.between' => 'La longitud debe estar entre -180 y 180.',
        ]);

        $general = SystemSetting::getValue('general', []);
        $general = array_replace([
            'company_name' => 'EPSAS',
            'company_alias' => 'Servicio de agua potable',
            'address' => null,
            'gps_latitude' => -21.5355,
            'gps_longitude' => -64.7296,
            'map_label' => 'Oficina central EPSAS',
            'map_icon' => 'water',
        ], is_array($general) ? $general : []);

        SystemSetting::putValue('general', array_replace($general, [
            'address' => $data['address'],
            'gps_latitude' => round((float) $data['gps_latitude'], 7),
            'gps_longitude' => round((float) $data['gps_longitude'], 7),
            'map_label' => $data['map_label'] ?: ($general['company_name'] ?? 'Oficina central EPSAS'),
            'map_icon' => $general['map_icon'] ?? 'water',
        ]));

        Cache::forget('shared_company_settings');
        Cache::forget('configuracion:settings-bundle');
        Cache::forget('system_setting:general');
        OperationalCache::forget('mapa-operativo:markers');

        return redirect()
            ->route('mapa-operativo.index')
            ->with('success', 'La ubicacion y direccion de la oficina se actualizaron correctamente.');
    }

    private function buildPayload(): array
    {
        $markers = [];
        $stats = [];
        $company = SystemSetting::getValue('general', []);
        $center = [
            'lat' => $this->coordinate($company['gps_latitude'] ?? null, -21.5355),
            'lng' => $this->coordinate($company['gps_longitude'] ?? null, -64.7296),
        ];

        $markers[] = $this->marker(
            'oficina',
            $center['lat'],
            $center['lng'],
            $company['map_label'] ?? ($company['company_name'] ?? 'EPSAS'),
            $company['address'] ?? 'Oficina principal',
            'Oficina'
        );

        $this->appendSocios($markers, $stats);
        $this->appendMedidores($markers, $stats);
        $this->appendInstalaciones($markers, $stats);
        $this->appendIncidencias($markers, $stats);
        $this->appendLecturas($markers, $stats);
        $this->appendServiceOrders($markers, $stats);

        return [
            'markers' => collect($markers)->filter()->values()->all(),
            'stats' => $stats,
            'center' => $center,
            'company' => $company,
            'warning' => null,
        ];
    }

    private function appendSocios(array &$markers, array &$stats): void
    {
        if (! $this->hasCoordinates('socios')) {
            return;
        }

        $rows = DB::table('socios as s')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->whereNotNull('s.latitud')
            ->whereNotNull('s.longitud')
            ->limit(500)
            ->get([
                's.latitud',
                's.longitud',
                's.numero_socio',
                's.direccion',
                's.estado',
                'sec.nombre as sector_nombre',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
            ]);

        $stats['Socios ubicados'] = $rows->count();

        foreach ($rows as $row) {
            $markers[] = $this->marker(
                'socio',
                $row->latitud,
                $row->longitud,
                ($row->numero_socio ?: 'Socio').' - '.($row->socio_nombre ?: 'Sin nombre'),
                trim(($row->sector_nombre ?: 'Sin zona').' | '.($row->direccion ?: 'Sin direccion')),
                'Socio '.$row->estado
            );
        }
    }

    private function appendMedidores(array &$markers, array &$stats): void
    {
        if (! $this->hasCoordinates('medidores')) {
            return;
        }

        $rows = DB::table('medidores as m')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->whereNotNull('m.latitud')
            ->whereNotNull('m.longitud')
            ->limit(500)
            ->get([
                'm.latitud',
                'm.longitud',
                'm.numero_serie',
                'm.estado',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
            ]);

        $stats['Medidores ubicados'] = $rows->count();

        foreach ($rows as $row) {
            $markers[] = $this->marker(
                'medidor',
                $row->latitud,
                $row->longitud,
                'Medidor '.$row->numero_serie,
                trim(($row->numero_socio ?: 'Sin socio').' | '.($row->socio_nombre ?: 'Sin nombre')),
                'Medidor '.$row->estado
            );
        }
    }

    private function appendInstalaciones(array &$markers, array &$stats): void
    {
        $rows = $this->technicalOrders('instalacion', 300);
        $stats['Instalaciones'] = $rows->count();

        foreach ($rows as $row) {
            $markers[] = $this->marker(
                'instalacion',
                $row->coord_x,
                $row->coord_y,
                $row->referencia ?: 'Instalacion nueva',
                trim(($row->zona ?: 'Sin zona').' | '.($row->socio_nombre ?: 'Sin socio')),
                'Instalacion '.$row->estado
            );
        }
    }

    private function appendIncidencias(array &$markers, array &$stats): void
    {
        if (! $this->hasTechnicalCoordinates('incidencias_tecnicas')) {
            return;
        }

        $rows = DB::table('incidencias_tecnicas')
            ->whereNotNull('coord_x')
            ->whereNotNull('coord_y')
            ->orderByDesc('fecha_reporte')
            ->limit(300)
            ->get(['coord_x', 'coord_y', 'tipo', 'prioridad', 'estado', 'zona', 'descripcion']);

        $stats['Incidencias'] = $rows->count();

        foreach ($rows as $row) {
            $markers[] = $this->marker(
                'incidencia',
                $row->coord_x,
                $row->coord_y,
                ucfirst(str_replace('_', ' ', $row->tipo)),
                trim(($row->zona ?: 'Sin zona').' | '.str($row->descripcion)->limit(90)),
                'Incidencia '.$row->estado.' | '.$row->prioridad
            );
        }
    }

    private function appendLecturas(array &$markers, array &$stats): void
    {
        if (! $this->hasCoordinates('lecturas')) {
            return;
        }

        $rows = DB::table('lecturas as l')
            ->leftJoin('medidores as m', 'm.id_medidor', '=', 'l.id_medidor')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->whereNotNull('l.latitud')
            ->whereNotNull('l.longitud')
            ->orderByDesc('l.fecha_lectura')
            ->orderByDesc('l.id_lectura')
            ->limit(300)
            ->get(['l.latitud', 'l.longitud', 'l.fecha_lectura', 'l.consumo_m3', 'm.numero_serie', 's.numero_socio']);

        $stats['Lecturas GPS'] = $rows->count();

        foreach ($rows as $row) {
            $markers[] = $this->marker(
                'lectura',
                $row->latitud,
                $row->longitud,
                'Lectura '.$row->fecha_lectura,
                trim(($row->numero_serie ?: 'Sin medidor').' | '.($row->numero_socio ?: 'Sin socio').' | '.number_format((float) $row->consumo_m3, 2).' m3'),
                'Lectura tecnica'
            );
        }
    }

    private function appendServiceOrders(array &$markers, array &$stats): void
    {
        foreach (['corte' => 'corte', 'reconexion' => 'reconexion'] as $type => $markerType) {
            $rows = $this->technicalOrders($type, 300);
            $stats[ucfirst($type).'s'] = $rows->count();

            foreach ($rows as $row) {
                $markers[] = $this->marker(
                    $markerType,
                    $row->coord_x,
                    $row->coord_y,
                    $row->referencia ?: ucfirst($type).' de servicio',
                    trim(($row->zona ?: 'Sin zona').' | '.($row->socio_nombre ?: 'Sin socio')),
                    ucfirst($type).' '.$row->estado
                );
            }
        }
    }

    private function technicalOrders(string $type, int $limit)
    {
        if (! $this->hasTechnicalCoordinates('ordenes_tecnicas')) {
            return collect();
        }

        return DB::table('ordenes_tecnicas as ot')
            ->leftJoin('socios as s', 's.id_socio', '=', 'ot.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->where('ot.tipo', $type)
            ->whereNotNull('ot.coord_x')
            ->whereNotNull('ot.coord_y')
            ->orderByDesc('ot.fecha_programada')
            ->limit($limit)
            ->get([
                'ot.coord_x',
                'ot.coord_y',
                'ot.estado',
                'ot.zona',
                'ot.referencia',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
            ]);
    }

    private function hasCoordinates(string $table): bool
    {
        return Schema::hasColumn($table, 'latitud') && Schema::hasColumn($table, 'longitud');
    }

    private function hasTechnicalCoordinates(string $table): bool
    {
        return Schema::hasColumn($table, 'coord_x') && Schema::hasColumn($table, 'coord_y');
    }

    private function marker(string $type, mixed $lat, mixed $lng, string $title, string $description, string $category): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'type' => $type,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'title' => $title,
            'description' => $description,
            'category' => $category,
        ];
    }

    private function coordinate(mixed $value, float $fallback): float
    {
        return is_numeric($value) ? (float) $value : $fallback;
    }
}
