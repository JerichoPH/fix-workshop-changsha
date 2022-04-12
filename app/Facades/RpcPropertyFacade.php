<?php

namespace App\Facades;

use App\Services\RpcPropertyService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcPropertyFacade
 * @package App\Facades
 * @method static init()
 * @method static categoryNames()
 * @method static allCategoryAsFactory()
 * @method static allCategory()
 * @method static withCategory(string $categoryUniqueCode)
 * @method static withSubModel(string $subModelUniqueCode)
 */
class RpcPropertyFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RpcPropertyService::class;
    }
}
