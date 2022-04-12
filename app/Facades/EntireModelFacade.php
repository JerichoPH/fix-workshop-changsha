<?php

namespace App\Facades;

use App\Services\EntireModelService;
use Illuminate\Support\Facades\Facade;

/**
 * Class EntireModel
 * @package App\Facades
 * @method static isExistsByName(array $entireModelNames): array
 * @method static isExistByName($entireModelName): bool
 */
class EntireModelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return EntireModelService::class;
    }
}
