<?php
namespace App\Facades;
use App\Services\RpcQualityService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcQualityFacade
 * @package App\Facades
 * @method static init()
 * @method static dateList()
 * @method static withYear(int $year)
 * @method static allFactory(int $year)
 * @method static factory(string $factoryName)
 * @method static withSubModel(string $subModelUniqueCode)
 */
class RpcQualityFacade extends Facade{
    protected static function getFacadeAccessor()
    {
        return RpcQualityService::class;
    }
}
