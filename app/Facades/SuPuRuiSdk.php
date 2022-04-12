<?php

namespace App\Facades;

use App\Services\SuPuRuiSdkService;
use Illuminate\Support\Facades\Facade;

/**
 * Class SuPuRuiSdk
 * @package App\Facades
 * @method static makeEntireInstance_S(string $sysId, string $speciesNId, string $productionNumber, int $status, string $railwayId, string $sectionId = null, int $deleteFlag = 0, string $stationId = null, string $intervalId = null, string $coordinate = null, string $locationOne = null, string $locationTwo = null, string $positionId = null, string $factoryId = null, string $productionDate = null, string $useDate = null, int $overhaul = null, int $middleRepair = null, int $largeRepair = null): array
 * @method static makeEntireInstance_Q(string $sysId, string $speciesNId, string $productionNumber, string $exitNumber, int $status, string $railwayId, string $sectionId, int $deleteFlag = 0, string $stationId = null, string $intervalId = null, string $positionId = null, string $location = null, string $equipmentId = null, string $factoryId = null, string $productionDate = null, string $useDate = null): array
 * @method static makeEntireModel(string $sysId, string $pid, string $speciesName, int $speciesLevel = 3, int $deleteFlag = 0): Collection
 * @method static makeFixWorkflow_S(string $sysId, string $equipmentId, string $repairDate, int $repairProcess, int $deleteFlag = 0, string $problem = null, string $overhaulUser = null, string $overhaulTime = null, string $exitNumber = null, string $softwareVersion = null, string $alarmInformation = null, string $disorderSpeciesId = null, string $repairRecords = null): Collection
 * @method static makeFixWorkflow_Q(string $sysId, string $deviceId, int $repairNumber, string $repairDate, int $deleteFlag = 0, string $overhaulUser = null, string $overhaulTime = null, string $checkUser = null, string $checkTime = null, string $recheckUser = null, string $recheckTime = null, string $spotCheckUser = null, string $spotCheckTime = null, string $repairRecords = null): Collection
 */
class SuPuRuiSdk extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SuPuRuiSdkService::class;
    }
}
