<?php

namespace App\Facades;

use App\Model\V250TaskOrder;
use App\Services\NewStationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class NewStationFacade
 * @package App\Facades
 * @method static downloadUploadCreateDeviceExcelTemplate(Request $request)
 * @method static downloadUploadEditDeviceExcelTemplate(Request $request)
 * @method static uploadCreateDevice(Request $request,string $sn,V250TaskOrder $v250_task_order)
 * @method static uploadEditDevice(Request $request, string $sn, V250TaskOrder $v250_task_order)
 */
class NewStationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return NewStationService::class;
    }
}
