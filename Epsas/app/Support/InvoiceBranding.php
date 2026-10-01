<?php

namespace App\Support;

use Illuminate\Support\Str;

class InvoiceBranding
{
    private const DEFAULT_LOGO_PATH = 'portal/epsas-invoice-logo.svg';

    public static function approvedLogoPath(array $company): ?string
    {
        $candidates = [
            (string) ($company['company_logo'] ?? ''),
            self::DEFAULT_LOGO_PATH,
        ];

        foreach ($candidates as $path) {
            $path = trim($path);

            if ($path !== '' && self::isApproved($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function dataUri(array $company): ?string
    {
        $path = self::approvedLogoPath($company);

        if ($path === null) {
            return null;
        }

        $resolved = self::resolvePath($path);

        if ($resolved === null) {
            return null;
        }

        $contents = @file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        return 'data:'.self::mimeType($resolved).';base64,'.base64_encode($contents);
    }

    private static function isApproved(string $path): bool
    {
        $resolved = self::resolvePath($path);

        if ($resolved === null) {
            return false;
        }

        $ratio = self::logoRatio($resolved);

        return $ratio !== null && $ratio >= 1.6 && $ratio <= 6;
    }

    private static function resolvePath(string $path): ?string
    {
        $resolved = Str::startsWith($path, 'storage/')
            ? storage_path('app/public/'.Str::after($path, 'storage/'))
            : public_path(ltrim($path, '/'));

        return is_file($resolved) && is_readable($resolved) ? $resolved : null;
    }

    private static function logoRatio(string $resolved): ?float
    {
        if (strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) === 'svg') {
            return self::svgRatio($resolved);
        }

        $dimensions = @getimagesize($resolved);

        if (! is_array($dimensions) || ($dimensions[1] ?? 0) <= 0) {
            return null;
        }

        return $dimensions[0] / $dimensions[1];
    }

    private static function svgRatio(string $resolved): ?float
    {
        $contents = @file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        if (preg_match('/viewBox=["\']\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)\s*["\']/i', $contents, $matches)) {
            $width = (float) $matches[1];
            $height = (float) $matches[2];

            return $height > 0 ? $width / $height : null;
        }

        preg_match('/\swidth=["\']([^"\']+)["\']/i', $contents, $widthMatch);
        preg_match('/\sheight=["\']([^"\']+)["\']/i', $contents, $heightMatch);

        $width = self::numericDimension($widthMatch[1] ?? null);
        $height = self::numericDimension($heightMatch[1] ?? null);

        return $width !== null && $height !== null && $height > 0 ? $width / $height : null;
    }

    private static function numericDimension(?string $value): ?float
    {
        if ($value === null || ! preg_match('/([\d.]+)/', $value, $matches)) {
            return null;
        }

        return (float) $matches[1];
    }

    private static function mimeType(string $resolved): string
    {
        if (strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) === 'svg') {
            return 'image/svg+xml';
        }

        return mime_content_type($resolved) ?: 'image/png';
    }
}
