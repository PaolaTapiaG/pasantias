<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionOperationsSecurityTest extends TestCase
{
    public function test_readiness_health_endpoint_is_hidden_without_token(): void
    {
        config()->set('production.health_token', 'health-secret');

        $this->getJson('/health/ready')->assertNotFound();
    }

    public function test_readiness_health_endpoint_checks_dependencies_with_valid_token(): void
    {
        config()->set('production.health_token', 'health-secret');

        $this->getJson('/health/ready', ['X-Health-Token' => 'health-secret'])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true)
            ->assertJsonPath('checks.private_storage', true);
    }

    public function test_backup_verification_requires_explicit_restored_database_confirmation(): void
    {
        $this->artisan('app:verify-restored-backup')->assertExitCode(1);
    }

    public function test_https_enforcement_redirects_insecure_requests(): void
    {
        config()->set('production.enforce_https', true);

        $this->get('/login')
            ->assertStatus(307)
            ->assertRedirect('https://localhost/login');
    }

    public function test_monitoring_heartbeat_is_recorded(): void
    {
        $this->artisan('app:monitoring-heartbeat')->assertExitCode(0);

        $this->assertNotNull(Cache::get(config('production.scheduler_heartbeat_key')));
    }

    public function test_enabled_monitoring_reports_degraded_without_operational_evidence(): void
    {
        config()->set('production.health_token', 'health-secret');
        config()->set('production.monitoring_enabled', true);
        Cache::forget(config('production.scheduler_heartbeat_key'));

        $this->getJson('/health/ready', ['X-Health-Token' => 'health-secret'])
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.scheduler', false)
            ->assertJsonPath('checks.backup_verification', false);
    }

    public function test_backup_verification_rejects_the_primary_database(): void
    {
        $backup = tempnam(sys_get_temp_dir(), 'epsas-backup-');
        file_put_contents($backup, 'backup-test');
        config()->set('production.primary_database_name', DB::connection()->getDatabaseName());

        try {
            $this->artisan('app:verify-restored-backup', [
                '--restored-database' => true,
                '--source-backup' => $backup,
                '--acknowledge' => true,
            ])->assertExitCode(1);
        } finally {
            @unlink($backup);
        }
    }
}
