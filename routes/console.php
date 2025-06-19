<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/* ======  SCHEDULER REMINDER  ====== */
Schedule::command('assessment:reminder')
        ->dailyAt('08:00');
Schedule::command('assessment:reminder-lecturer')
        ->dailyAt('08:00');
        