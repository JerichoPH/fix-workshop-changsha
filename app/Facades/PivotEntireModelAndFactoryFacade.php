<?php

namespace App\Facades;

use App\Services\PivotEntireModelAndFactoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class PivotEntireModelAndFactory
 * @package App\Facades
 * @method static batch(Request $request, string $filename): array
 */
class PivotEntireModelAndFactoryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PivotEntireModelAndFactoryService::class;
    }
}
