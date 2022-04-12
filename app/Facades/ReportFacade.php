<?php

namespace App\Facades;

use App\Services\ReportService;
use Illuminate\Support\Facades\Facade;

/**
 * Class ReportFacade
 * @package App\Facades
 * @method static cycleFixWithCategory(string $fileDir, string $year, string $currentCycleFixDate, string $categoryUniqueCode = null): array
 * @method static handleDateWithType(string $Type, string $date): string
 */
class ReportFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ReportService::class;
    }
}
