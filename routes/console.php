<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gym:send-reminders')->hourly()->withoutOverlapping();
Schedule::command('gym:release-event-holds')->everyMinute()->withoutOverlapping();
Schedule::command('gym:complete-started-private-requests')->everyMinute()->withoutOverlapping();
