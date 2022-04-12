<?php

namespace App\Providers;

use App\Services\DeviceInput_S03Service;
use Illuminate\Support\ServiceProvider;

class DeviceInput_S03ServiceProvider extends ServiceProvider
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
        $this->app->singleton('DeviceInput_S03Service', function () {
            return new DeviceInput_S03Service();
        });
    }
}
