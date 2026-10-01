<?php

namespace App\Console\Commands;

use App\Services\BillingIntegrityAudit;
use App\Models\SystemSetting;
use App\Support\InvoiceBranding;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'app:production-readiness';

    protected $description = 'Verifica bloqueadores tecnicos antes de desplegar EPSAS en produccion';

    public function handle(BillingIntegrityAudit $billingAudit): int
    {
        $generalSettings = [];
        $settingsError = null;

        try {
            $generalSettings = SystemSetting::getValue('general', []);
            $mailSettings = SystemSetting::getValue('mail', [
                'mailer' => filled(config('services.brevo.api_key')) ? 'brevo_api' : 'log',
                'api_key' => config('services.brevo.api_key'),
                'from_address' => config('mail.from.address'),
            ]);
        } catch (\Throwable $exception) {
            $settingsError = $exception->getMessage();
            $mailSettings = [];
        }

        $monitoringReady = config('production.monitoring_enabled')
            && strlen((string) config('production.health_token')) >= 32
            && config('production.external_monitor_configured')
            && $this->schedulerHeartbeatIsCurrent();
        $checks = [
            $this->check('Configuracion desde BD', $settingsError === null, $settingsError ?? 'Configuracion disponible'),
            $this->check('Entorno de produccion', app()->isProduction(), 'APP_ENV debe ser production'),
            $this->check('Depuracion desactivada', ! config('app.debug'), 'APP_DEBUG debe ser false'),
            $this->check('URL con HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL debe comenzar con https://'),
            $this->check('HTTPS obligatorio', (bool) config('production.enforce_https'), 'ENFORCE_HTTPS=true'),
            $this->check('Clave de aplicacion', filled(config('app.key')), 'APP_KEY es obligatoria'),
            $this->check('Zona horaria', config('app.timezone') === 'America/La_Paz', 'Usar America/La_Paz'),
            $this->check('Extension PHP intl', extension_loaded('intl'), 'Instalar y habilitar ext-intl en CLI y PHP-FPM'),
            $this->check('Cookies seguras', (bool) config('session.secure'), 'SESSION_SECURE_COOKIE=true'),
            $this->check('Sesion cifrada', (bool) config('session.encrypt'), 'SESSION_ENCRYPT=true'),
            $this->check('Cookie HttpOnly', (bool) config('session.http_only'), 'SESSION_HTTP_ONLY=true'),
            $this->check('Cookie host-only', str_starts_with((string) config('session.cookie'), '__Host-') && blank(config('session.domain')) && config('session.path') === '/', 'Usar SESSION_COOKIE=__Host-epsas-session, SESSION_DOMAIN vacio y SESSION_PATH=/'),
            $this->check('Sesion Redis', config('session.driver') === 'redis', 'SESSION_DRIVER=redis'),
            $this->check('Cache Redis', config('cache.default') === 'redis', 'CACHE_STORE=redis'),
            $this->check('Colas Redis', config('queue.default') === 'redis', 'QUEUE_CONNECTION=redis'),
            $this->check(
                'Assets de produccion',
                ! File::exists(public_path('hot')) && File::exists(public_path('build/manifest.json')),
                'Eliminar public/hot y ejecutar npm run build'
            ),
            $this->check('Logo de factura', filled(InvoiceBranding::approvedLogoPath($generalSettings)), 'Cargar un logo institucional horizontal valido para facturas'),
            $this->check(
                'Correo Brevo API',
                ($mailSettings['mailer'] ?? null) === 'brevo_api'
                    && filled($mailSettings['api_key'] ?? null)
                    && filter_var($mailSettings['from_address'] ?? null, FILTER_VALIDATE_EMAIL),
                'Configurar mailer brevo_api, clave API y remitente valido en la configuracion del sistema'
            ),
            $this->check('Respaldo restaurado', $this->hasRecentBackupVerification(), 'Ejecutar y registrar una restauracion probada'),
            $this->check(
                'Monitoreo habilitado',
                $monitoringReady,
                'Habilitar monitoreo, usar token de 32+ caracteres, confirmar monitor externo y mantener heartbeat del scheduler'
            ),
            $this->check(
                'QR oficial certificado',
                filled($generalSettings['payment_static_qr_payload'] ?? null),
                'Configurar el identificador QR oficial entregado por la entidad financiera'
            ),
        ];

        try {
            $checks[] = $this->check('Base PostgreSQL', DB::connection()->getDriverName() === 'pgsql', 'Produccion requiere PostgreSQL');
            $checks[] = $this->check('Conexion BD no persistente', ! $this->usesPersistentDatabaseConnection(), 'DB_PERSISTENT debe ser false para evitar conexiones persistentes fuera del pool controlado');
            $checks[] = $this->check('Credenciales BD de aplicacion', $this->hasApplicationDatabaseCredentials(), 'Configurar DB_USERNAME distinto de root y un DB_PASSWORD no vacio');
            $role = DB::selectOne('select current_user, rolsuper, rolcreatedb, rolcreaterole, rolreplication, rolbypassrls from pg_roles where rolname = current_user');
            $leastPrivilege = ! $role->rolsuper && ! $role->rolcreatedb && ! $role->rolcreaterole && ! $role->rolreplication && ! $role->rolbypassrls;
            $checks[] = $this->check('Usuario BD limitado', $leastPrivilege, 'El usuario actual tiene privilegios elevados: '.$role->current_user);
            $checks[] = $this->check('Migraciones al dia', $this->pendingMigrations() === [], 'Pendientes: '.implode(', ', $this->pendingMigrations()));
            $billingChecks = $billingAudit->run();
            $checks[] = $this->check('Integridad financiera', $billingAudit->isClean($billingChecks), 'Inconsistencias: '.json_encode($billingChecks));
        } catch (\Throwable $exception) {
            $checks[] = $this->check('Conexion y permisos BD', false, $exception->getMessage());
        }

        $this->table(['Estado', 'Control', 'Detalle'], array_map(fn (array $check) => [
            $check['ok'] ? 'OK' : 'BLOQUEADOR',
            $check['name'],
            $check['detail'],
        ], $checks));

        $failures = collect($checks)->where('ok', false)->count();
        $this->newLine();
        $failures === 0
            ? $this->info('No se detectaron bloqueadores tecnicos de produccion.')
            : $this->error("Produccion bloqueada: {$failures} control(es) pendientes.");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $name, bool $ok, string $failureDetail): array
    {
        return [
            'name' => $name,
            'ok' => $ok,
            'detail' => $ok ? 'Correcto' : $failureDetail,
        ];
    }

    private function pendingMigrations(): array
    {
        $ran = DB::table('migrations')->pluck('migration')->all();
        $files = collect(File::files(database_path('migrations')))
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->all();

        return array_values(array_diff($files, $ran));
    }

    private function hasRecentBackupVerification(): bool
    {
        $path = config('production.backup_verification_file');

        if (! File::exists($path)) {
            return false;
        }

        $evidence = json_decode(File::get($path), true);

        if (! is_array($evidence) || json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $verifiedAt = $evidence['verified_at'] ?? null;

        try {
            return ($evidence['valid'] ?? false) === true
                && filled($evidence['source_backup_sha256'] ?? null)
                && filled($verifiedAt)
                && Carbon::parse($verifiedAt)
                    ->greaterThanOrEqualTo(now()->subDays(config('production.backup_max_age_days')));
        } catch (\Throwable) {
            return false;
        }
    }

    private function schedulerHeartbeatIsCurrent(): bool
    {
        $heartbeat = cache()->get(config('production.scheduler_heartbeat_key'));

        return filled($heartbeat)
            && Carbon::parse($heartbeat)->greaterThanOrEqualTo(now()->subMinutes(config('production.scheduler_max_age_minutes')));
    }

    private function usesPersistentDatabaseConnection(): bool
    {
        return (bool) data_get(config('database.connections.pgsql.options', []), PDO::ATTR_PERSISTENT, false);
    }

    private function hasApplicationDatabaseCredentials(): bool
    {
        $connection = config('database.connections.pgsql', []);

        return filled($connection['username'] ?? null)
            && strtolower((string) $connection['username']) !== 'root'
            && filled($connection['password'] ?? null);
    }

}
