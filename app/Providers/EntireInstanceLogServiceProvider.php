<?php

namespace App\Providers;

use App\Services\EntireInstanceLogService;
use Illuminate\Support\ServiceProvider;

class EntireInstanceLogServiceProvider extends ServiceProvider
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
        $this->app->singleton('entireInstanceLogsService', function () {
            return new EntireInstanceLogService();
        });
    }
}
