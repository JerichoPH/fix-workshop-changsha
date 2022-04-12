<?php

namespace App\Facades;

use App\Model\EntireInstance;
use App\Services\BreakdownLogService;
use Illuminate\Support\Facades\Facade;

/**
 * Class BreakdownLogFacade
 * @package App\Facades
 * @method static createWarehouseIn(EntireInstance $entire_instance, string $explain = '', string $submitted_at = '', array $breakdown_type_ids = [], string $submitter_name = ''): array
 * @method static createStation(EntireInstance $entire_instance, string $explain = '', string $submitted_at = '', string $crossroad_number = '', string $submitter_name = ''): bool
 * @method static createStationAsOriginal(string $identityCode, string $explain = '', string $submittedAt = '', string $crossroadNumber = '', string $submitterName = '', string $stationName = '', string $locationCode = ''): bool
 */
class BreakdownLogFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BreakdownLogService::class;
    }
}
