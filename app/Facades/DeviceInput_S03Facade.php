<?php

namespace App\Facades;

use App\Services\DeviceInput_S03Service;
use Overtrue\LaravelWeChat\Facade;

/**
 * Class DeviceInput_S03Facade
 * @package App\Facades
 * @method static b049_1()
 * @method static excelToJson()
 */
class DeviceInput_S03Facade extends Facade
{
    public static function getFacadeAccessor()
    {
        return DeviceInput_S03Service::class;
    }
}