<?php

namespace App\Providers;

use App\Services\QueryConditionService;
use Illuminate\Support\ServiceProvider;

class QueryConditionServiceProvider extends ServiceProvider
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
        $this->app->singleton("QueryService", function () {
            return new QueryConditionService();
        });
    }
}
