<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:sync-current-invoices')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('app:media-retention --execute')
    ->monthlyOn(1, '02:30')
    ->withoutOverlapping();

Schedule::command('app:monitoring-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();
