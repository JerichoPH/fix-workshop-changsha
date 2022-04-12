<?php

namespace App\Providers;

use App\Services\PartInstanceService;
use Illuminate\Support\ServiceProvider;

class PartInstanceServiceProvider extends ServiceProvider
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
        $this->app->singleton('part_instance-service', function () {
            return new PartInstanceService();
        });
    }
}
