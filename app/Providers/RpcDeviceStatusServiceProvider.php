<?php

namespace App\Providers;

use App\Services\RpcDeviceStatusService;
use Illuminate\Support\ServiceProvider;

class RpcDeviceStatusServiceProvider extends ServiceProvider
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
        $this->app->singleton('RpcDeviceService',function(){
            return new RpcDeviceStatusService();
        });
    }
}
