<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->forceTestingEnvironment();

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $this->forceTestingConfig();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->forceTestingConfig();
    }

    private function forceTestingEnvironment(): void
    {
        foreach ($this->testingEnvironment() as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function forceTestingConfig(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('session.driver', 'array');

        DB::purge('pgsql');
    }

    private function testingEnvironment(): array
    {
        return [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];
    }
}
