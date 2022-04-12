<?php

namespace App\Facades;

use App\Services\FixWorkflowExcelService;
use Illuminate\Support\Facades\Facade;

/**
 * Class FixWorkflowExcelFacade
 * @package App\Facades
 * @method static init(int $year, int $month): self
 * @method static onlyOnceFixedToExcel()
 * @method static fixWorkflowCycleToExcel()
 */
class FixWorkflowExcelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FixWorkflowExcelService::class;
    }
}

