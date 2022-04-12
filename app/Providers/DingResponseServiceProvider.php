<?php

namespace App\Providers;

use App\Services\DingResponseService;
use Illuminate\Support\ServiceProvider;

class DingResponseServiceProvider extends ServiceProvider
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
        $this->app->singleton('DingResponseService',function(){
            return DingResponseService::class;
        });
    }
}
