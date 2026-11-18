<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\MakeSubscriptionsExpired;
use App\Models\Config as ModelsConfig;
use App\Notifications\Dynamic\DynamicNotificationsManager;
use App\Support\DropletManager;
use App\Support\FaviconManager;
use App\Support\FileOwnership;
use App\Support\System\Traits\WriteLogs;
use Throwable;

class Kernel extends ConsoleKernel
{
    use WriteLogs;

    const CRON_LAST_RUN_CONFIG_KEY = 'cron.last_run';

    private static $schedules = [];

    public static function addSchedule($callback)
    {
        static::$schedules[] = $callback;
    }

    private function runSchedules(Schedule $schedule)
    {
        foreach ($this::$schedules as $callback) {
            try {
                $callback($schedule);
            } catch (Throwable $th) {
                $this->logError('Error running schedule. ' . $th->getMessage());
            }
        }
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(app(MakeSubscriptionsExpired::class))->twiceDaily();

        $schedule->call(function () {
            // Fix storage file permission issue in docarized platform
            FileOwnership::setStorageOwnership();
        })->everyTenMinutes();

        $schedule->call(function () {
            ModelsConfig::set(static::CRON_LAST_RUN_CONFIG_KEY, time());
        })->everyMinute();

        $schedule->call(function () {
            FaviconManager::publish();
        })->twiceDaily();

        $schedule->call(function () {
            $dropletManager = new DropletManager;
            try {
                $dropletManager->verify();
            } catch (Throwable $th) {
                //
            }
        })->daily();

        $schedule->call(function () {
            DynamicNotificationsManager::broadcast();
        })->twiceDaily();

        $this->runSchedules($schedule);
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
