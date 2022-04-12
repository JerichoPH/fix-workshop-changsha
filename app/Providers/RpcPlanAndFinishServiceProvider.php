<?php

namespace App\Providers;

use App\Services\RpcPlanAndFinishService;
use Illuminate\Support\ServiceProvider;

class RpcPlanAndFinishServiceProvider extends ServiceProvider
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
        $this->app->singleton('RpcPlanAndFinishService',function(){
            return new RpcPlanAndFinishService();
        });
    }
}
