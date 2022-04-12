<?php

namespace App\Facades;

use App\Services\PartInstanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPExcel_Worksheet;

/**
 * Class PartInstance
 * @method static batchFromExcel(Request $request, array $factoryUniqueCodes): array
 * @method static batchFromExcelWithNew(Request $request, string $filename): array
 * @package App\Facades
 */
class PartInstanceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PartInstanceService::class;
    }
}
