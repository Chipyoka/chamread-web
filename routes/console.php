<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('gps:process-exceptions')
    ->twiceDaily(0, 12)
    ->withoutOverlapping();

// Balance sync runs are scheduled to start at 18:00 and end at 06:00, so we dispatch the run every minute to catch any that are ready to start, and close the run at 06:00.
Schedule::command('balance:dispatch-run')->everyMinute()->withoutOverlapping();
Schedule::command('balance:close-run')->dailyAt('06:00')->withoutOverlapping();