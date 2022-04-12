<?php

namespace App\Providers;

use App\Services\MockDataService;
use Illuminate\Support\ServiceProvider;

class MockDataServiceProvider extends ServiceProvider
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
        $this->app->singleton('MockDataServiceProvider', function () {
            return new MockDataService();
        });
    }
}
