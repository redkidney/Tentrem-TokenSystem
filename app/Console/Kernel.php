<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\MqttSubscribeJob;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\HandleExpiredTokens::class,
    ];
    
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('tokens:clean')->daily();
        $schedule->job(new MqttSubscribeJob(), 'long_running')
            ->everyMinute()
            ->withoutOverlapping();
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
