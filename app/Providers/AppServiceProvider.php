<?php

namespace App\Providers;
use Illuminate\Support\Facades\Blade;

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
        Blade::component('layouts.admin', 'admin-layout');
        Blade::component('components.sidebar', 'sidebar');
        Blade::component('components.top-navigation', 'top-navigation');
    }
}
