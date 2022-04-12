<?php

namespace App\Facades;

use App\Services\CommonService;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Class CommonFacade
 * @package App\Facades
 * @method static ddExceptionWithAppDebug(Throwable $e, array $extra = [])
 * @method static bd02_to_gcj02(...$points): array
 * @method static gcj02_to_bd09(...$points): array
 */
class CommonFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommonService::class;
    }
}
