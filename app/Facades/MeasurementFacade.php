<?php

namespace App\Facades;

use App\Services\MeasurementService;
use Illuminate\Support\Facades\Facade;

/**
 * Class Measurement
 * @package App\Facades
 * @method static batch(array $measurements)
 */
class MeasurementFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MeasurementService::class;
    }
}
