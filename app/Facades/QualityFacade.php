<?php

namespace App\Facades;

use App\Services\QualityService;
use Illuminate\Support\Facades\Facade;

/**
 * Class QualityFacade
 * @package App\Facades
 * @method static init(int $year, int $month)
 * @method static getBasicInfo(int $year, int $month)
 * @method static fileRead(string $filename): array
 * @method static getCategories()
 * @method static getEntireModels()
 * @method static getSubEntireModels()
 * @method static getPartModels()
 * @method static getDeviceCountWithCategory(bool $isTest = false): array
 * @method static getFixedCountWithoutCycleWithCategory(bool $isTest = false): array
 * @method static getRateWithoutCycleWithCategory(): array
 * @method static getDeviceCountWithEntireModel(bool $isTest = false): array
 * @method static getFixedWithoutCycleWithEntireModel(bool $isTest = false): array
 * @method static getRateWithoutCycleWithEntireModel(): array
 * @method static getDeviceCountWithSub(bool $isTest = false): array
 * @method static getFixedCountWithoutCycleWithSub(bool $isTest = false): array
 * @method static getRateWithoutCycleWithSub(): array
 */
class QualityFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return QualityService::class;
    }
}
