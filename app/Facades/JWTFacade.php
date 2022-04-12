<?php

namespace App\Facades;

use App\Services\JWTService;
use Illuminate\Support\Facades\Facade;

/**
 * Class JWTFacade
 * @package App\Facades
 * @method static generate($payload):string
 */
class JWTFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return JWTService::class;
    }
}
