<?php

namespace App\Facades;

use App\Services\FixWorkflowCycleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Class FixWorkflowCycle
 * @method static getBasicInfo(int $year,int $month)
 * @method static getCurrentMonthAllFixCountWithSub(int $year, int $month,bool $isTest = false): array
 * @method static getCurrentMonthAllFixCount(int $year, int $month, bool $isTest = false): array
 * @method static getCurrentMonthAllFixCountWithCategory(int $year, int $month, bool $isTest = false): array
 * @method static getCurrentMonthFixedCountWithSub(int $year, int $month, bool $isTest = false): array
 * @method static getCurrentMonthFixedCount(int $year, int $month, bool $isTest = false): array
 * @method static getCurrentMonthFixedCountWithCategory(int $year, int $month, bool $isTest = false): array
 * @method static getCurrentMonthFixedRateCountWithSub(int $year, int $month): array
 * @method static getCurrentMonthFixedRateCount(int $year, int $month): array
 * @method static getCurrentMonthFixedRateCountWithCategory(int $year, int $month): array
 * @method static getCurrentMonthGoingToFixCount(int $year, int $month)
 * @method static getEntireInstanceIdentityCodesForGoingToAutoMakeFixWorkflow(int $year, int $month)
 * @method static autoMakeFixWorkflow(Collection $entireInstances): array
 * @method static saveFile(int $year, int $month, string $fileName, $content)
 * @method static makeSavePath(int $year, int $month, string $fileName): string
 * @method static dirExist(string $dir): bool
 * @method static fileExist(string $filename): bool
 * @method static readFile(int $year, int $month, string $fileName): array
 * @method static makeReadPath(int $year, int $month, string $fileName): string
 * @package App\Facades
 */
class FixWorkflowCycleFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FixWorkflowCycleService::class;
    }
}
