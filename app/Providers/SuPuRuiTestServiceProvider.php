<?php

namespace App\Providers;

use App\Services\SuPuRuiTestService;
use Illuminate\Support\ServiceProvider;

class SuPuRuiTestServiceProvider extends ServiceProvider
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
        $this->app->singleton('SuPuRuiTest-service', function () {
            return new SuPuRuiTestService;
        });
    }
}
