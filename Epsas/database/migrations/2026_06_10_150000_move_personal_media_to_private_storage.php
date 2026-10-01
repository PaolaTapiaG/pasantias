<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveTableFiles('personas', 'id_persona', 'foto_path');
        $this->moveTableFiles('medidor_anomalias', 'id_anomalia', 'evidencia_path');
        $this->moveTableFiles('incidencias_tecnicas', 'id_incidencia', 'evidencia_path');
    }

    public function down(): void
    {
        // Personal media remains private intentionally.
    }

    private function moveTableFiles(string $table, string $primaryKey, string $pathColumn): void
    {
        DB::table($table)
            ->whereNotNull($pathColumn)
            ->orderBy($primaryKey)
            ->each(function (object $row) use ($table, $primaryKey, $pathColumn): void {
                $path = (string) $row->{$pathColumn};

                if ($path === '' || Str::startsWith($path, ['http://', 'https://', 'uploads/'])) {
                    return;
                }

                $relative = ltrim(Str::after($path, 'storage/'), '/');

                if (Storage::disk('public')->exists($relative) && ! Storage::disk('local')->exists($relative)) {
                    Storage::disk('local')->put($relative, Storage::disk('public')->get($relative));
                    Storage::disk('public')->delete($relative);
                }

                DB::table($table)
                    ->where($primaryKey, $row->{$primaryKey})
                    ->update([$pathColumn => $relative]);
            });
    }
};
