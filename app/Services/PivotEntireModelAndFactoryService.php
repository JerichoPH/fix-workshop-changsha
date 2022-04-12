<?php

namespace App\Services;

use App\Facades\ExcelReader;
use Illuminate\Http\Request;

class PivotEntireModelAndFactoryService
{
    /**
     * 批量导入整件类型与厂家关系
     * @param Request $request
     * @param string $filename
     * @return array
     */
    public function batch(Request $request, string $filename): array
    {
        return ExcelReader::init($request, $filename)->readSheetByName('整件类型', 2, 0, function ($row) {
            list($categoryName, $categoryUniqueCode, $entireModelName, $entireModelUniqueCode, $subEntireModelName, $fixCycleValue, $subEntireModelUniqueCode, $factoryName) = $row;
            return [
                'entire_model_unique_code' => $entireModelUniqueCode,
                'factory_name' => $factoryName,
            ];
        });
    }
}
