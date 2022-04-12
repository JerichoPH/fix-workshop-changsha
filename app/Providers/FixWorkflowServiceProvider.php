<?php

namespace App\Providers;

use App\Services\FixWorkflowService;
use Illuminate\Support\ServiceProvider;

class FixWorkflowServiceProvider extends ServiceProvider
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
        $this->app->singleton('fixWorkflow-service', function () {
            return new FixWorkflowService;
        });
    }
}
