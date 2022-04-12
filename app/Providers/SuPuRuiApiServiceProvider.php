<?php

namespace App\Providers;

use App\Services\SuPuRuiApiService;
use Illuminate\Support\ServiceProvider;

class SuPuRuiApiServiceProvider extends ServiceProvider
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
        $this->app->singleton('su-pu-rui_api-service', function () {
            return new SuPuRuiApiService();
        });
    }
}
