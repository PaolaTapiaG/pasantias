<?php

namespace App\Console\Commands;

use App\Services\BillingIntegrityAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class VerifyRestoredBackup extends Command
{
    protected $signature = 'app:verify-restored-backup
        {--restored-database : Confirma que esta conexion apunta a una restauracion aislada, no a produccion}
        {--source-backup= : Archivo de respaldo que fue restaurado}
        {--acknowledge : Registra la verificacion satisfactoria}';

    protected $description = 'Valida integridad basica de una base restaurada y registra evidencia operativa';

    public function handle(BillingIntegrityAudit $billingAudit): int
    {
        if (! $this->option('restored-database')) {
            $this->error('Ejecuta este comando solamente contra una base restaurada aislada usando --restored-database.');
            return self::FAILURE;
        }

        $database = DB::connection()->getDatabaseName();
        $primaryDatabase = (string) config('production.primary_database_name');
        $sourceBackup = (string) $this->option('source-backup');

        if ($primaryDatabase !== '' && $database === $primaryDatabase) {
            $this->error('La conexion apunta a la base principal. La restauracion debe validarse en una base aislada distinta.');

            return self::FAILURE;
        }

        if ($sourceBackup === '' || ! File::isFile($sourceBackup) || File::size($sourceBackup) === 0) {
            $this->error('Debes indicar con --source-backup un archivo de respaldo restaurado, existente y no vacio.');

            return self::FAILURE;
        }

        $billingChecks = $billingAudit->run();
        $checks = [
            'database' => $database,
            'driver' => DB::connection()->getDriverName(),
            'migrations' => DB::table('migrations')->count(),
            'users' => DB::table('users')->count(),
            'socios' => DB::table('socios')->count(),
            'facturas' => DB::table('facturas')->count(),
            'cobros' => DB::table('cobros')->count(),
        ] + $billingChecks;

        $valid = $checks['driver'] === 'pgsql'
            && $checks['migrations'] > 0
            && $billingAudit->isClean($billingChecks);

        $this->table(['Control', 'Valor'], collect($checks)->map(fn ($value, $key) => [$key, $value])->values()->all());

        if (! $valid) {
            $this->error('La restauracion no supero los controles de integridad.');
            return self::FAILURE;
        }

        if ($this->option('acknowledge')) {
            $path = config('production.backup_verification_file');
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode([
                'verified_at' => now()->toIso8601String(),
                'database' => $checks['database'],
                'valid' => true,
                'source_backup' => basename($sourceBackup),
                'source_backup_size' => File::size($sourceBackup),
                'source_backup_sha256' => hash_file('sha256', $sourceBackup),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));
            $this->info('Restauracion verificada y evidencia registrada.');
        } else {
            $this->warn('Controles correctos. Repite con --acknowledge para registrar la evidencia.');
        }

        return self::SUCCESS;
    }
}
