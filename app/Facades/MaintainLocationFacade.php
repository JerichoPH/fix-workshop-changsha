<?php

namespace App\Facades;

use App\Services\MaintainLocationService;
use Illuminate\Support\Facades\Facade;

/**
 * Class MaintainLocationFacade
 * @package App\Facades
 * @method static fromSceneWorkshopUniqueCode(string $sceneWorkshopUniqueCode): self
 * @method static fromSceneWorkshopName(string $sceneWorkshopName): self
 * @method static fromMaintainStationName(string $stationName): self
 * @method static fromMaintainStationUniqueCode(string $stationUniqueCode): self
 * @method static getStationUniqueCodes()
 * @method static connectionName(string $connectionName): self
 * @method static getCount(): array
 * @method static withCategory(string $currentCategoryUniqueCode = null)
 * @method static withEntireModelByCategoryUniqueCode(string $categoryUniqueCode)
 * @method static withAllCategory()
 */
class MaintainLocationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MaintainLocationService::class;
    }
}
