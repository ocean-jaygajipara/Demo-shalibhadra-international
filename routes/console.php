<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



app()->booted(function () {
    $schedule = app(Schedule::class);

    // ✅ Your working test
    // $schedule->call(function () {
    //     \Illuminate\Support\Facades\Log::info('✅ Task executed at: ' . now());
    // })->everyMinute();

    // ✅ Your real commands
    /** Check the Company wise plan expire */
    $schedule->command('app:company-subscription-plan-expired')->dailyAt('00:00');

    /** India mart Inquiry auto fetch */
    $schedule->command('app:india-mart-fetch-leads')->cron('*/10 * * * *')->withoutOverlapping();

    $schedule->command('app:trade-india-fetch-leads')->everyFiveMinutes()->withoutOverlapping();

    $schedule->command('app:fetch-facebook-leads')->everyTwoMinutes()->withoutOverlapping();

    $schedule->command('app:send-followup-firebase-reminders')->everyMinute();

    /** Auto Punch Out  */
    // $schedule->command('app:auto-punch-out')->everySecond();
    $schedule->command('app:auto-punch-out')->everyMinute()->withoutOverlapping();
});
