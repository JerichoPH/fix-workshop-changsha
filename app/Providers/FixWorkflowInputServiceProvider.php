<?php

namespace App\Providers;

use App\Services\FixWorkflowInputService;
use Illuminate\Support\ServiceProvider;

class FixWorkflowInputServiceProvider extends ServiceProvider
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
        $this->app->singleton('FixWorkflowInputService', function () {
            return new FixWorkflowInputService();
        });
    }
}
