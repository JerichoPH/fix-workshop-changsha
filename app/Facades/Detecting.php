<?php

namespace App\Facades;

use App\Services\DetectingService;
use Illuminate\Support\Facades\Facade;

/**
 * Class Detecting
 * @package App\Facades
 * @method static ALX_B051(array $data,array $config)
 */
class Detecting extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DetectingService::class;
    }
}
