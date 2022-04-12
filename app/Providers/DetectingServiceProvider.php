<?php

namespace App\Providers;

use App\Services\DetectingService;
use Illuminate\Support\ServiceProvider;

class DetectingServiceProvider extends ServiceProvider
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
        $this->app->singleton('detecting-service', function () {
            return new DetectingService();
        });
    }
}
