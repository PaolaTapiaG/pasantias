<?php

namespace App\Services;

use App\Support\OperationalCache;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;

final class PaymentQrService
{
    public function cachedSvg(array $socio, string $concepto, float $monto): ?string
    {
        if ($monto <= 0) {
            return null;
        }

        $cacheKey = 'cobros:qr:v'.OperationalCache::version().':'.md5(implode('|', [
            $socio['id_socio'] ?? '',
            $concepto,
            number_format($monto, 2, '.', ''),
            today()->toDateString(),
        ]));

        return Cache::remember(
            $cacheKey,
            now()->addHours(2),
            fn () => $this->render($this->payload($socio, $concepto, $monto))
        );
    }

    private function payload(array $socio, string $concepto, float $monto): string
    {
        return implode('|', [
            'EPSAS',
            'SOCIO:'.$socio['codigo_display'],
            'NOMBRE:'.$socio['nombre_completo'],
            'CONCEPTO:'.$concepto,
            'MONTO:'.number_format($monto, 2, '.', ''),
            'FECHA:'.now()->format('Y-m-d'),
        ]);
    }

    private function render(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
