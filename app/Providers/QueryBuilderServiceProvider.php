<?php

namespace App\Providers;

use App\Services\QueryBuilderService;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
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
        $this->app->singleton('QueryBuilderService', function () {
            return new QueryBuilderService();
        });
    }
}
