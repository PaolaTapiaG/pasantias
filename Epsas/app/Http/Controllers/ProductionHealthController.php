<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class ProductionHealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('production.health_token');
        $provided = (string) $request->header('X-Health-Token');
        abort_unless($expected !== '' && hash_equals($expected, $provided), 404);

        $checks = [
            'database' => fn () => (int) (DB::selectOne('select 1 as ok')?->ok ?? 0) === 1,
            'cache' => function (): bool {
                $key = 'health:'.str()->uuid();
                Cache::put($key, 'ok', 10);
                $ok = Cache::get($key) === 'ok';
                Cache::forget($key);

                return $ok;
            },
            'private_storage' => function (): bool {
                $path = 'health/'.str()->uuid().'.txt';
                Storage::disk('local')->put($path, 'ok');
                $ok = Storage::disk('local')->get($path) === 'ok';
                Storage::disk('local')->delete($path);

                return $ok;
            },
            'disk_space' => fn (): bool => ((int) disk_free_space(storage_path())) >= config('production.minimum_free_disk_mb') * 1024 * 1024,
            'scheduler' => fn (): bool => ! config('production.monitoring_enabled') || $this->schedulerIsCurrent(),
            'queue_backlog' => fn (): bool => ! config('production.monitoring_enabled') || Queue::size() <= config('production.queue_backlog_limit'),
            'backup_verification' => fn (): bool => ! config('production.monitoring_enabled') || $this->backupVerificationIsCurrent(),
        ];

        $result = collect($checks)->map(function (callable $check): bool {
            try {
                return $check();
            } catch (\Throwable) {
                return false;
            }
        });
        $healthy = $result->every(fn (bool $ok) => $ok);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $result,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503)->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function schedulerIsCurrent(): bool
    {
        $heartbeat = Cache::get(config('production.scheduler_heartbeat_key'));

        return filled($heartbeat)
            && Carbon::parse($heartbeat)->greaterThanOrEqualTo(now()->subMinutes(config('production.scheduler_max_age_minutes')));
    }

    private function backupVerificationIsCurrent(): bool
    {
        $path = config('production.backup_verification_file');

        if (! File::exists($path)) {
            return false;
        }

        $evidence = json_decode(File::get($path), true);
        $verifiedAt = $evidence['verified_at'] ?? null;

        return ($evidence['valid'] ?? false) === true
            && filled($evidence['source_backup_sha256'] ?? null)
            && filled($verifiedAt)
            && Carbon::parse($verifiedAt)->greaterThanOrEqualTo(now()->subDays(config('production.backup_max_age_days')));
    }
}
