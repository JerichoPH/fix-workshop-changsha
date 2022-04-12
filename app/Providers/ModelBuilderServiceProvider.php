<?php

namespace App\Providers;

use App\Services\ModelBuilderService;
use Illuminate\Support\ServiceProvider;

class ModelBuilderServiceProvider extends ServiceProvider
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
        $this->app->singleton('SqlService',function(){
            return new ModelBuilderService();
        });
    }
}
