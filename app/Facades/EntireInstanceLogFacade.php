<?php

namespace App\Facades;

use App\Services\EntireInstanceLogService;
use Illuminate\Support\Facades\Facade;

/**
 * Class EntireInstanceLog
 * @package App\Facades
 * @method static makeOne(string $name, string $entireInstanceIdentityCode, int $type = 0, string $url = '', string $description = '', string $materialType = 'ENTIRE'): bool
 * @method static makeBatchUseEntireInstances(string $name, array $entireInstances, int $type = 0, string $url = '', string $description = ''): bool
 * @method static makeBatchUseEntireInstanceIdentityCodes(string $name, array $entireInstanceIdentityCode, int $type = 0, string $url = '', string $description = ''): bool
 * @method static makeBatchUseArray(array $data): bool
 */
class EntireInstanceLogFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return EntireInstanceLogService::class;
    }
}
