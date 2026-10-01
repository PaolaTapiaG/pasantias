<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LocalDatabaseWakeup
{
    private const CACHE_KEY = 'local:database-wakeup:last-ok';

    public static function shouldRun(): bool
    {
        if (! app()->environment('local') || ! (bool) config('database.local_wakeup.enabled')) {
            return false;
        }

        if (config('database.default') !== 'pgsql') {
            return false;
        }

        $lastWakeup = (float) Cache::get(self::CACHE_KEY, 0);
        $interval = max(30, (int) config('database.local_wakeup.interval_seconds', 120));

        return $lastWakeup <= 0 || microtime(true) - $lastWakeup >= $interval;
    }

    public static function run(bool $purge = false): float
    {
        $startedAt = hrtime(true);

        if ($purge) {
            DB::purge();
        }

        DB::connection()->getPdo();
        DB::select('select 1');
        self::warmCommonLocalCaches();

        Cache::put(self::CACHE_KEY, microtime(true), now()->addHours(6));

        return (hrtime(true) - $startedAt) / 1_000_000;
    }

    public static function markSuccessfulPulse(): void
    {
        Cache::put(self::CACHE_KEY, microtime(true), now()->addHours(6));
    }

    private static function warmCommonLocalCaches(): void
    {
        SystemSetting::getValue('general', []);
    }
}
