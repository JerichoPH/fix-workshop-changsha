<?php
namespace App\Facades;
use App\Services\RpcTestService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcTestFacade
 * @package App\Facades
 * @method static init()
 * @method static test()
 * @method static params(string $params)
 */
class RpcTestFacade extends Facade{
    protected static function getFacadeAccessor()
    {
        return RpcTestService::class;
    }
}
