<?php

namespace App\Facades;

use App\Services\EveryMonthExcelService;
use Illuminate\Support\Facades\Facade;

/**
 * Class EveryMonthExcelFacade
 * @package App\Facades
 * @method static init(int $year, int $month): self
 * @method static getBasicInfo(int $year, int $month)
 * @method static fileRead(string $filename): array
 * @method static getEntireInstanceIdentityCodesWithCategory(): array
 * @method static getEntireInstanceIdentityCodesWithEntireModel()
 * @method static getEntireInstanceIdentityCodesWithSub()
 */
class EveryMonthExcelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return EveryMonthExcelService::class;
    }
}
