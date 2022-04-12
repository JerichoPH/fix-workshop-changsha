<?php

namespace App\Services;

use App\Facades\ExcelReader;
use Illuminate\Http\Request;

class FactoryService
{
    /**
     * 批量导入
     * @param Request $request
     * @param string $filename
     * @return array
     */
    public function batch(Request $request, string $filename): array
    {
        $currentDatetime = date('Y-m-d H:i:s');
        return ExcelReader::init($request, $filename)->readSheetByName('Worksheet', 2, 0, function ($row) use ($currentDatetime) {
            return [
                'created_at' => $currentDatetime,
                'updated_at' => $currentDatetime,
                'name' => $row[0],
                'short_name' => $row[1],
                'unique_code' => $row[2],
            ];
        });
    }
}
