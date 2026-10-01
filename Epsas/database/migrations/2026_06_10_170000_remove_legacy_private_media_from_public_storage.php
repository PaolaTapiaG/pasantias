<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PRIVATE_DIRECTORIES = [
        'anomalias',
        'comprobantes_qr',
        'empleados',
        'incidencias',
        'perfiles',
        'socios',
        'uploads',
    ];

    public function up(): void
    {
        $this->moveReferencedLegacyUploads();

        foreach (self::PRIVATE_DIRECTORIES as $directory) {
            foreach (Storage::disk('public')->allFiles($directory) as $path) {
                if (! Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                }

                Storage::disk('public')->delete($path);
            }
        }
    }

    public function down(): void
    {
        // Personal and financial evidence remains private intentionally.
    }

    private function moveReferencedLegacyUploads(): void
    {
        DB::table('personas')
            ->where('foto_path', 'like', 'uploads/%')
            ->orderBy('id_persona')
            ->each(function (object $persona): void {
                $path = ltrim(Str::replace('\\', '/', (string) $persona->foto_path), '/');
                $source = public_path($path);

                if (! File::isFile($source) || Storage::disk('local')->exists($path)) {
                    return;
                }

                Storage::disk('local')->put($path, File::get($source));
                File::delete($source);
            });
    }
};
