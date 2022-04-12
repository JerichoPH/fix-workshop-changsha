<?php

namespace App\Providers;

use App\Services\RpcQualityService;
use Illuminate\Support\ServiceProvider;

class RpcQualityServiceProvider extends ServiceProvider
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
        $this->app->singleton('RpcQualityService',function(){
            return new RpcQualityService();
        });
    }
}
