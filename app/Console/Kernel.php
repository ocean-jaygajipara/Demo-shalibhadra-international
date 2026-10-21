<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Register your custom command here
        \App\Console\Commands\TinkerWithPlanData::class,
        \App\Console\Commands\TinkerExpense::class,
        \App\Console\Commands\ProductTinker::class,
        \App\Console\Commands\MasterTinker::class,

        \App\Console\Commands\CompanySubscriptionPlanExpired::class,
        \App\Console\Commands\IndiaMartFetchLeads::class,
        \App\Console\Commands\FetchFacebookLeads::class,
        \App\Console\Commands\SendFollowupFirebaseReminders::class,
        \App\Console\Commands\AutoPunchOut::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        Log::warning('✅ schedule:run test triggered: ' . now());
        $schedule->call(function () {
            try {
                Log::warning('✅ schedule:run test triggered: ' . now());
            } catch (\Throwable $e) {
                file_put_contents(storage_path('logs/closure-error.log'), $e->getMessage() . "\n", FILE_APPEND);
            }
        })->everySecond();

        // $schedule->call(function () {
        //     Log::warning('cron run start: ' . now());
        // })->everyMinute();

        // $schedule->command('import:india-mart-inquiry')->cron('*/6 * * * *')->withoutOverlapping();

        $schedule->call(function () {
            Log::warning('cron run end: ' . now());
        })->everyTwoSeconds();

        // $schedule->command('import:india-mart-inquiry')->everyMinute()->withoutOverlapping()->evenInMaintenanceMode();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
