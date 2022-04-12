<?php

namespace App\Providers;

use App\Services\StatisticsService;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('StatisticsService', function () {
            return new StatisticsService();
        });
    }
}
