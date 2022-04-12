<?php

namespace App\Facades;

use App\Services\FactoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class Factory
 * @package App\Facades
 * @method static batch(Request $request, string $filename): array
 */
class FactoryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FactoryService::class;
    }
}
