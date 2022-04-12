<?php

namespace App\Facades;

use App\Services\SuPuRuiTestService;
use Illuminate\Support\Facades\Facade;

/**
 * Class SuPuRuiTest
 * @package App\Facades
 * @method static api1()
 * @method static api3()
 * @method static api4()
 * @method static api5()
 * @method static api6()
 * @method static api7()
 * @method static api8()
 * @method static api9()
 * @method static api10()
 * @method static api11()
 * @method static api12()
 * @method static api13()
 * @method static api14()
 * @method static api15()
 * @method static api16()
 */
class SuPuRuiTest extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SuPuRuiTestService::class;
    }
}
