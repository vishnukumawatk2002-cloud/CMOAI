<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();
Schedule::command('cmo:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
