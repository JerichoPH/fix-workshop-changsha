<?php

namespace App\Facades;


use App\Services\TemporaryService;
use Illuminate\Support\Facades\Facade;

/**
 * Class TemporaryFacade
 * @package App\Facades
 * @method static fun1()
 * @method static fun2()
 * @method static fun3()
 * @method static fun4(string $identityCode)
 * @method static fun5(string $rfidEpcCode)
 */
class TemporaryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TemporaryService::class;
    }
}