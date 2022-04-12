<?php

namespace App\Providers;

use App\Services\MaintainLocationService;
use Illuminate\Support\ServiceProvider;

class MaintainLocationServiceProvider extends ServiceProvider
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
        $this->app->singleton('MaintainLocationService', function () {
            return new MaintainLocationService();
        });
    }
}
