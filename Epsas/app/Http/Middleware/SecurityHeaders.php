<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        $scriptSources = ["'self'"];
        $styleSources = ["'self'", "'unsafe-inline'"];
        $imageSources = ["'self'", 'data:', 'blob:', 'https:', 'https://tile.openstreetmap.org', 'https://*.tile.openstreetmap.org'];
        $fontSources = ["'self'", 'data:'];
        $connectSources = ["'self'"];

        $scriptSources[] = "'unsafe-inline'";
        $connectSources[] = 'https://router.project-osrm.org';

        $reverbOptions = config('broadcasting.connections.reverb.options', []);
        $reverbHost = trim((string) ($reverbOptions['host'] ?? ''));
        $reverbScheme = ($reverbOptions['scheme'] ?? 'https') === 'https' ? 'wss' : 'ws';
        $reverbPort = (int) ($reverbOptions['port'] ?? ($reverbScheme === 'wss' ? 443 : 80));

        if ($reverbHost !== '' && preg_match('/^[A-Za-z0-9.-]+$/', $reverbHost) === 1 && $reverbPort > 0) {
            $connectSources[] = sprintf('%s://%s:%d', $reverbScheme, $reverbHost, $reverbPort);
        }

        if (app()->environment('local')) {
            $viteSources = $this->viteDevSources();
            $scriptSources = array_merge($scriptSources, $viteSources['http']);
            $styleSources = array_merge($styleSources, $viteSources['http']);
            $fontSources = array_merge($fontSources, $viteSources['http']);
            $connectSources = array_merge($connectSources, $viteSources['http'], $viteSources['ws']);
        }

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                'script-src ' . implode(' ', array_unique($scriptSources)),
                'style-src ' . implode(' ', array_unique($styleSources)),
                'img-src ' . implode(' ', array_unique($imageSources)),
                'font-src ' . implode(' ', array_unique($fontSources)),
                "media-src 'self'",
                'connect-src ' . implode(' ', array_unique($connectSources)),
            ])
        );

        if (config('production.enforce_https') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function viteDevSources(): array
    {
        $httpSources = ['http://localhost:5173', 'http://127.0.0.1:5173'];
        $hotPath = public_path('hot');

        if (is_file($hotPath)) {
            $hotUrl = trim((string) file_get_contents($hotPath));

            if (filter_var($hotUrl, FILTER_VALIDATE_URL) && ! str_contains($hotUrl, '[')) {
                $httpSources[] = rtrim($hotUrl, '/');
            }
        }

        return [
            'http' => array_values(array_unique($httpSources)),
            'ws' => ['ws://localhost:5173', 'ws://127.0.0.1:5173'],
        ];
    }
}
