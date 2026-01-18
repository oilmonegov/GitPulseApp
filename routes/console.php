<?php

use App\Jobs\SendWeeklyDigestJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Send weekly digest emails every Monday at 9:00 AM
Schedule::job(new SendWeeklyDigestJob)
    ->weeklyOn(1, '09:00')
    ->name('send-weekly-digest')
    ->withoutOverlapping()
    ->onOneServer();
