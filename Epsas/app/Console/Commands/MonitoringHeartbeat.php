<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitoringHeartbeat extends Command
{
    protected $signature = 'app:monitoring-heartbeat';

    protected $description = 'Registra que el scheduler de EPSAS continua ejecutandose';

    public function handle(): int
    {
        Cache::put(config('production.scheduler_heartbeat_key'), now()->toIso8601String(), now()->addMinutes(10));
        $this->info('Heartbeat registrado.');

        return self::SUCCESS;
    }
}
