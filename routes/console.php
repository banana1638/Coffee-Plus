<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune expired Sanctum tokens daily
Schedule::command('sanctum:prune-expired --hours=24')->daily();

Schedule::command('telescope:prune', [
    '--hours' => max(24, (int) config('telescope.prune_hours', 168)),
])->dailyAt('02:30');

Schedule::command('orders:send-pickup-reminders')
    ->everyMinute()
    ->withoutOverlapping();
