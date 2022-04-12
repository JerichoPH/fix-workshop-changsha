<?php

namespace App\Providers;

use App\Services\QrCodeService;
use Illuminate\Support\ServiceProvider;

class QrCodeServiceProvider extends ServiceProvider
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
        $this->app->singleton('QrCodeService', function () {
            return new QrCodeService();
        });
    }
}
