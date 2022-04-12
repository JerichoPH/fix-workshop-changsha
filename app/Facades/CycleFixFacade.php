<?php
namespace App\Facades;
use App\Services\CycleFixService;
use Illuminate\Support\Facades\Facade;

/**
 * Class CycleFixFacade
 * @package App\Facades
 * @method static getMissionStatisticsAllCategory(string $dateType = null, string $date = null)
 * @method static getMissionStatisticsWithCategory(string $categoryUniqueCode, string $dateType = null, string $date = null)
 * @method static getMissionStatisticsWithEntireModel(string $uniqueCode, string $dateType = null, string $date = null)
 */
class CycleFixFacade extends Facade{
    protected static function getFacadeAccessor()
    {
        return CycleFixService::class;
    }
}
