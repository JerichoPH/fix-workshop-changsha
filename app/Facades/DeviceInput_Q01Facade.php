<?php

namespace App\Facades;

use App\Services\DeviceInput_Q01Service;
use Illuminate\Support\Facades\Facade;
use phpDocumentor\Reflection\Types\Null_;

/**
 * 继电器设备导入
 * Class DeviceInputFacade
 * @package App\Facades
 * @method static b051_1(string $name)
 * @method static b049_38(string $name, string $dir = null)
 * @method static b049_23(string $name, string $dir = null)
 * @method static b049_19(string $name, string $dir = null)
 * @method static b049_15(string $name, string $dir = null)
 * @method static b049_16(string $name, string $dir = null)
 * @method static b049_20(string $name, string $dir = null)
 * @method static b049_38_in_workshop()
 */
class DeviceInput_Q01Facade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DeviceInput_Q01Service::class;
    }
}
