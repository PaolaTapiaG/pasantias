<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PrivateMedia
{
    public static function storeImage(
        UploadedFile $file,
        string $directory,
        string $prefix,
        ?string $currentPath = null
    ): ?string {
        if (! $file->isValid()) {
            return $currentPath;
        }

        self::delete($currentPath);

        $extension = strtolower($file->extension() ?: 'jpg');
        $filename = Str::slug($prefix).'_'.now()->format('YmdHis').'_'.Str::random(8).'.'.$extension;

        return $file->storeAs($directory, $filename, 'local') ?: $currentPath;
    }

    public static function delete(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $relative = self::relativePath($path);
        Storage::disk('local')->delete($relative);
        Storage::disk('public')->delete($relative);
    }

    public static function response(string $path): Response
    {
        $relative = self::relativePath($path);

        if (Storage::disk('local')->exists($relative)) {
            return Storage::disk('local')->response($relative, null, [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless(Storage::disk('public')->exists($relative), 404);

        return Storage::disk('public')->response($relative, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function relativePath(string $path): string
    {
        return ltrim(Str::after($path, 'storage/'), '/');
    }
}
