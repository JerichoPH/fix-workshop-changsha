<?php

namespace App\Facades;

use App\Services\RpcDeviceStatusService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcDeviceFacade
 * @package App\Facades
 * @method static init()
 * @method static dynamic(string $categoryUniqueCode = null)
 */
class RpcDeviceStatusFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RpcDeviceStatusService::class;
    }
}
