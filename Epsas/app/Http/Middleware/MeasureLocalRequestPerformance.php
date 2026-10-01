<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MeasureLocalRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Performance-Debug') !== '1' || ! in_array($request->ip(), ['127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryTime = 0.0;
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTime, &$queries, $request) {
            $queryCount++;
            $queryTime += $query->time;

            if ($request->header('X-Performance-Sql') === '1' && count($queries) < 8) {
                $queries[] = preg_replace('/\s+/', ' ', $query->sql);
            }
        });

        $response = $next($request);
        $appTime = (hrtime(true) - $startedAt) / 1_000_000;

        $response->headers->set('X-Performance-App-Ms', number_format($appTime, 2, '.', ''));
        $response->headers->set('X-Performance-Database-Ms', number_format($queryTime, 2, '.', ''));
        $response->headers->set('X-Performance-Database-Queries', (string) $queryCount);

        if ($request->header('X-Performance-Sql') === '1') {
            $response->headers->set('X-Performance-Database-Sql', implode(' | ', $queries));
        }

        return $response;
    }
}
