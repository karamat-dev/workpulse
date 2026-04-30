<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workpulse:generate-events', function () {
    $this->call('workpulse:events:birthday');
    $this->call('workpulse:events:anniversary');
    $this->call('workpulse:events:probation');
})->purpose('Generate muSharp automated events');

app(Schedule::class)->command('workpulse:generate-events')->dailyAt('00:10');
app(Schedule::class)->command('workpulse:attendance:remind-missed-clockin')->everyFiveMinutes()->withoutOverlapping();
app(Schedule::class)->command('workpulse:backup')->dailyAt('01:00')->withoutOverlapping();
