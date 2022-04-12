<?php

namespace App\Facades;

use App\Services\RpcPlanAndFinishService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcPlanAndFinishFacade
 * @package App\Facades
 * @method static init()
 * @method static dateList()
 * @method static withMonth(string $date)
 * @method static allCategory(string $date)
 * @method static category(string $categoryUniqueCode, string $date)
 * @method static entireModel(string $entireModelUniqueCode, string $date)
 * @method static subModel(string $subModelUniqueCode)
 */
class RpcPlanAndFinishFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RpcPlanAndFinishService::class;
    }
}
