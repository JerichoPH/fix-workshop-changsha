<?php

namespace App\Providers;

use App\Services\DeviceInput_Q01Service;
use Illuminate\Support\ServiceProvider;

class DeviceInput_Q01ServiceProvider extends ServiceProvider
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
        $this->app->singleton('DeviceInputService', function () {
            return new DeviceInput_Q01Service();
        });
    }
}
