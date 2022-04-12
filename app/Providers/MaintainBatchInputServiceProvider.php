<?php

namespace App\Providers;

use App\Services\MaintainBatchInputService;
use Illuminate\Support\ServiceProvider;

class MaintainBatchInputServiceProvider extends ServiceProvider
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
        $this->app->singleton('MaintainBatchInputService', function () {
            return new MaintainBatchInputService();
        });
    }
}
