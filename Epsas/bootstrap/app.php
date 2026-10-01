<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnforceHttps;
use App\Http\Middleware\MeasureLocalRequestPerformance;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\WarmLocalDatabaseConnection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', [
        'middleware' => ['web', 'auth'],
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnforceHttps::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(WarmLocalDatabaseConnection::class);
        $middleware->append(MeasureLocalRequestPerformance::class);
        $middleware->alias([
            'password.changed' => EnsurePasswordChanged::class,
            'role' => CheckRole::class,
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
