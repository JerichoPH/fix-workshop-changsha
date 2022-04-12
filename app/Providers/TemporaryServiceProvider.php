<?php

namespace App\Providers;

use App\Services\TemporaryService;
use Illuminate\Support\ServiceProvider;

class TemporaryServiceProvider extends ServiceProvider
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
        $this->app->singleton('TemporaryService',function(){
            return new TemporaryService();
        });
    }
}
