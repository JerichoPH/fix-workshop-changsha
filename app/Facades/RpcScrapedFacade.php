<?php

namespace App\Facades;

use App\Services\RpcScrapedService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcScarpedFacade
 * @package App\Facades
 * @method static init()
 * @method static dynamic()
 * @method static allCategory()
 * @method static category(string $categoryUniqueCode)
 * @method static entireModel(string $entireModelUniqueCode)
 */
class RpcScrapedFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RpcScrapedService::class;
    }
}
