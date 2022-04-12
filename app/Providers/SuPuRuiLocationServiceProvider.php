<?php

namespace App\Providers;

use App\Services\SuPuRuiLocationService;
use Illuminate\Support\ServiceProvider;

class SuPuRuiLocationServiceProvider extends ServiceProvider
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
        $this->app->singleton('SuPuRuiLocationService', function () {
            return new SuPuRuiLocationService();
        });
    }
}
