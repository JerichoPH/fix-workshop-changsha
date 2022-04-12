<?php

namespace App\Facades;

use App\Services\LocationService;
use Illuminate\Support\Facades\Facade;

/**
 * Class LocationFacade
 * @package App\Facades
 * @method static makeLocationUniqueCode(string $locationUniqueCode, int $state = 0): string
 */
class LocationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LocationService::class;
    }
}