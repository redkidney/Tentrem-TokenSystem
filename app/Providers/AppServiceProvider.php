<?php

namespace App\Providers;

// use App\Jobs\MqttSubscribeJob;
use Illuminate\Support\ServiceProvider;

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
    public function boot()
    {
        // MqttSubscribeJob::dispatch()
        //     ->onQueue('long_running');
    }
}
