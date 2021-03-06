<?php

namespace App\Providers;

use App\Services\NewStationService;
use Illuminate\Support\ServiceProvider;

class NewStationServiceProvider extends ServiceProvider
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
        $this->app->singleton('NewStationService', function () {
            return new NewStationService();
        });
    }
}
