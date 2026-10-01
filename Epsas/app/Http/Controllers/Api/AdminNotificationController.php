<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    private const COUNTS_TTL_MINUTES = 5;

    private const PAYLOAD_TTL_MINUTES = 2;

    public function warmCache(): void
    {
        $counts = $this->buildCounts();

        Cache::put(
            $this->countsCacheKey(),
            $counts,
            now()->addMinutes(self::COUNTS_TTL_MINUTES)
        );
        Cache::put(
            $this->lastCountsCacheKey(),
            $counts,
            now()->addDay()
        );

        $payload = $this->buildPayload($counts);

        Cache::put(
            $this->payloadCacheKey(),
            $payload,
            now()->addMinutes(self::PAYLOAD_TTL_MINUTES)
        );
        Cache::put(
            $this->lastPayloadCacheKey(),
            $payload,
            now()->addDay()
        );
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->boolean('detail')) {
            return response()->json(['counts' => $this->cachedCountsFast()]);
        }

        $payload = $this->cachedPayloadFast();

        return response()->json($payload);
    }

    private function cachedCountsFast(): array
    {
        return Cache::get($this->countsCacheKey())
            ?? Cache::get($this->lastCountsCacheKey())
            ?? $this->emptyCounts();
    }

    private function cachedPayloadFast(): array
    {
        return Cache::get($this->payloadCacheKey())
            ?? Cache::get($this->lastPayloadCacheKey())
            ?? [
                'counts' => $this->cachedCountsFast(),
                'items' => [
                    'qr' => [],
                    'lecturas' => [],
                ],
            ];
    }

    private function countsCacheKey(): string
    {
        return 'api.admin.notifications.counts:v'.OperationalCache::version();
    }

    private function payloadCacheKey(): string
    {
        return 'api.admin.notifications:v'.OperationalCache::version();
    }

    private function lastCountsCacheKey(): string
    {
        return 'api.admin.notifications.counts:last';
    }

    private function lastPayloadCacheKey(): string
    {
        return 'api.admin.notifications:last';
    }

    private function emptyCounts(): array
    {
        return [
            'qr_pendientes' => 0,
            'qr_aprobadas_hoy' => 0,
            'lecturas_hoy' => 0,
        ];
    }

    private function buildCounts(): array
    {
        $startedAt = microtime(true);
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $summary = DB::query()
            ->selectRaw("
                (SELECT COUNT(*) FROM ordenes_pago WHERE estado = 'en_revision') as qr_pendientes,
                (SELECT COUNT(*) FROM ordenes_pago WHERE estado = 'aprobada' AND revisado_en BETWEEN ? AND ?) as qr_aprobadas_hoy,
                (SELECT COUNT(*) FROM lecturas WHERE created_at BETWEEN ? AND ?) as lecturas_hoy
            ")
            ->addBinding([$startOfDay, $endOfDay, $startOfDay, $endOfDay], 'select')
            ->first();

        logger()->info('billing.adminNotifications.buildCounts', [
            'ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'qr_pendientes' => (int) ($summary->qr_pendientes ?? 0),
            'qr_aprobadas_hoy' => (int) ($summary->qr_aprobadas_hoy ?? 0),
            'lecturas_hoy' => (int) ($summary->lecturas_hoy ?? 0),
        ]);

        return [
            'qr_pendientes' => (int) ($summary->qr_pendientes ?? 0),
            'qr_aprobadas_hoy' => (int) ($summary->qr_aprobadas_hoy ?? 0),
            'lecturas_hoy' => (int) ($summary->lecturas_hoy ?? 0),
        ];
    }

    private function buildPayload(?array $counts = null): array
    {
        $startedAt = microtime(true);
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $qr = DB::table('ordenes_pago as op')
            ->leftJoin('socios as s', 's.id_socio', '=', 'op.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->where('op.estado', 'en_revision')
            ->orderByDesc('op.updated_at')
            ->limit(5)
            ->get([
                'op.codigo',
                'op.total',
                'op.updated_at',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as cliente"),
            ])
            ->map(fn ($row) => [
                'title' => $row->codigo.' - Bs '.number_format((float) $row->total, 2),
                'detail' => trim(($row->cliente ?: 'Cliente').' / '.($row->numero_socio ?: 'Sin codigo')),
                'url' => route('secretaria.ordenes-pago.show', $row->codigo, false),
                'time' => optional($row->updated_at ? Carbon::parse($row->updated_at) : null)->diffForHumans(),
            ])
            ->values();

        $lecturas = DB::table('lecturas as l')
            ->leftJoin('medidores as m', 'm.id_medidor', '=', 'l.id_medidor')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->whereBetween('l.created_at', [$startOfDay, $endOfDay])
            ->orderByDesc('l.created_at')
            ->limit(5)
            ->get([
                'l.fecha_lectura',
                'l.consumo_m3',
                'l.created_at',
                'm.numero_serie',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as cliente"),
            ])
            ->map(fn ($row) => [
                'title' => ($row->numero_serie ?: 'Medidor').' - '.number_format((float) $row->consumo_m3, 2).' m3',
                'detail' => trim(($row->cliente ?: 'Socio').' / '.($row->numero_socio ?: 'Sin codigo')),
                'url' => route('tecnico.lecturas.index', [], false),
                'time' => optional($row->created_at ? Carbon::parse($row->created_at) : null)->diffForHumans(),
            ])
            ->values();

        logger()->info('billing.adminNotifications.buildPayload', [
            'ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'qr_count' => $qr->count(),
            'lecturas_count' => $lecturas->count(),
        ]);

        return [
            'counts' => $counts ?? $this->cachedCountsFast(),
            'items' => [
                'qr' => $qr,
                'lecturas' => $lecturas,
            ],
        ];
    }
}
