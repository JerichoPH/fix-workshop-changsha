<?php
namespace App\Facades;
use App\Services\StatisticsService;
use Illuminate\Support\Facades\Facade;

/**
 * Class StatisticsFacade
 * @package App\Facades
 * @method static makeStation(bool $is_array = false)
 * @method static makeCategoryAndEntireModel(bool $is_array = false)
 * @method static makeCategories(bool $is_array = false)
 * @method static makeSubModel(bool $is_array = false)
 */
class StatisticsFacade extends Facade{
    protected static function getFacadeAccessor()
    {
        return StatisticsService::class;
    }
}
