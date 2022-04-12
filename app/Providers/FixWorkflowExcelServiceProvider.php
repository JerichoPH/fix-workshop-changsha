<?php

namespace App\Providers;

use App\Services\FixWorkflowExcelService;
use Illuminate\Support\ServiceProvider;

class FixWorkflowExcelServiceProvider extends ServiceProvider
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
        $this->app->singleton('FixWorkflowExcelService', function () {
            return new FixWorkflowExcelService();
        });
    }
}
