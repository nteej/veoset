<?php

namespace App\Providers;

use App\Events\AssetStatusChanged;
use App\Listeners\SendMaintenanceNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            AssetStatusChanged::class,
            SendMaintenanceNotification::class,
        );

        // Set up scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Collect sensor data every 15 minutes for operational assets
            $schedule->command('sensor:collect')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            // Comprehensive data collection every hour for all active assets
            $schedule->command('sensor:collect --all')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();

            // Daily health report generation at 6 AM
            $schedule->command('sensor:collect --all')
                ->dailyAt('06:00')
                ->withoutOverlapping();

            // Monitor scheduled tasks every 10 minutes
            $schedule->command('monitor:scheduled-tasks')
                ->everyTenMinutes()
                ->withoutOverlapping();

            // Generate detailed monitoring report daily at 7 AM
            $schedule->command('monitor:scheduled-tasks --report')
                ->dailyAt('07:00')
                ->withoutOverlapping();

            // Check for critical health scores every 30 minutes
            $schedule->command('alert:critical-health --threshold=30')
                ->everyThirtyMinutes()
                ->withoutOverlapping();

            // Emergency health check every 5 minutes for extremely low scores
            $schedule->command('alert:critical-health --threshold=15 --hours=1')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        });
    }
}
