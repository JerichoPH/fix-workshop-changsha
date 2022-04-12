<?php

namespace App\Providers;

use App\Services\SuPuRuiSdkService;
use Illuminate\Support\ServiceProvider;

class SuPuRuiSdkServiceProvider extends ServiceProvider
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
        $this->app->singleton('su-pu-rui_sdk_service', function () {
            return new SuPuRuiSdkService;
        });
    }
}
