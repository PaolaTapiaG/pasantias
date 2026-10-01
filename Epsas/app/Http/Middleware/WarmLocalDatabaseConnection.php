<?php

namespace App\Http\Middleware;

use App\Support\LocalDatabaseWakeup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WarmLocalDatabaseConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        $shouldWarm = $request->isMethod('GET') && LocalDatabaseWakeup::shouldRun();
        $queryCount = 0;

        if ($shouldWarm) {
            DB::listen(static function () use (&$queryCount): void {
                $queryCount++;
            });
        }

        $response = $next($request);

        if ($shouldWarm) {
            if ($queryCount > 0) {
                LocalDatabaseWakeup::markSuccessfulPulse();

                return $response;
            }

            LocalDatabaseWakeup::markSuccessfulPulse();

            app()->terminating(static function (): void {
                try {
                    LocalDatabaseWakeup::run();
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        }

        return $response;
    }
}
