<?php

namespace App\Providers;

use App\Services\RpcTestService;
use Illuminate\Support\ServiceProvider;

class RpcTestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('RpcTestService', function () {
            return new RpcTestService();
        });
    }
}
