<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{
    JobCreateCommand,
    JobRunPendingCommand,
    JobStatusCommand,
    JobCancelCommand,
    JobRetryCommand
};

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        JobCreateCommand::class,
        JobRunPendingCommand::class,
        JobStatusCommand::class,
        JobCancelCommand::class,
        JobRetryCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run pending jobs every minute with logging
        $schedule->command('job:run-pending')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/scheduler.log'))
            ->before(function () {
                \Log::info('Starting job:run-pending command');
            })
            ->after(function () {
                \Log::info('Finished job:run-pending command');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 