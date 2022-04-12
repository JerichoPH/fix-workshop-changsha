<?php

namespace App\Providers;

use App\Services\EntireModelService;
use Illuminate\Support\ServiceProvider;

class EntireModelServiceProvider extends ServiceProvider
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
        $this->app->singleton('entireModel-service',function(){
            return new EntireModelService();
        });
    }
}
