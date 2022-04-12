<?php

namespace App\Facades;

use App\Services\SuPuRuiLocationService;
use Illuminate\Support\Facades\Facade;

/**
 * Class SuPuRuiLocationFacade
 * @package App\Facades
 *
 */
class SuPuRuiLocationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SuPuRuiLocationService::class;
    }
}
