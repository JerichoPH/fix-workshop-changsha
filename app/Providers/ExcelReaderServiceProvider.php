<?php

namespace App\Providers;

use App\Services\ExcelReaderService;
use Illuminate\Support\ServiceProvider;

class ExcelReaderServiceProvider extends ServiceProvider
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
        $this->app->singleton('excel-reader-service',function(){
            return new ExcelReaderService;
        });
    }
}
