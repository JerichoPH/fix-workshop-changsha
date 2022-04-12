<?php

namespace App\Providers;

use App\Services\PivotEntireModelAndFactoryService;
use Illuminate\Support\ServiceProvider;

class PivotEntireModelAndFactoryServiceProvider extends ServiceProvider
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
        $this->app->singleton('pivot-entire_model_and_factory-service', function () {
            return new PivotEntireModelAndFactoryService();
        });
    }
}
