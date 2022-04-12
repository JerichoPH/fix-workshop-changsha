<?php

namespace App\Providers;

use App\Services\CycleFixService;
use Illuminate\Support\ServiceProvider;

class CycleFixServiceProvider extends ServiceProvider
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
        $this->app->singleton('CycleFixService', function () {
            return new CycleFixService();
        });
    }
}
