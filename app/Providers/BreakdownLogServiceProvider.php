<?php

namespace App\Providers;

use App\Services\BreakdownLogService;
use Illuminate\Support\ServiceProvider;

class BreakdownLogServiceProvider extends ServiceProvider
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
        $this->app->singleton('BreakdownLogService', function () {
            return new BreakdownLogService();
        });
    }
}
