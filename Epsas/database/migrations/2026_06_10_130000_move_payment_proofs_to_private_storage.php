<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ordenes_pago')
            ->whereNotNull('comprobante_path')
            ->where('comprobante_path', 'like', 'storage/%')
            ->orderBy('id_orden_pago')
            ->each(function (object $orden): void {
                $path = ltrim(str_replace('storage/', '', $orden->comprobante_path), '/');

                if (Storage::disk('public')->exists($path) && ! Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                    Storage::disk('public')->delete($path);
                }

                DB::table('ordenes_pago')
                    ->where('id_orden_pago', $orden->id_orden_pago)
                    ->update(['comprobante_path' => $path]);
            });
    }

    public function down(): void
    {
        // Payment proofs remain private intentionally.
    }
};
