<?php

namespace App\Services;

use App\Facades\CodeFacade;
use Illuminate\Support\Facades\DB;

class MeasurementService
{

    /**
     * 批量导入测试标准值
     * @param array $measurements
     * @return mixed
     */
    public function batch(array $measurements)
    {
        $currentDatetime = date('Y-m-d H:i:s');
        foreach ($measurements as &$measurement) {
            $newMeasurementIdentityCode = CodeFacade::makeMeasurementIdentityCode($measurement['entire_model_unique_code'], $measurement['part_model_unique_code']);
            $measurement = array_merge($measurement, ['created_at' => $currentDatetime, 'updated_at' => $currentDatetime, 'identity_code' => $newMeasurementIdentityCode]);
        }
        return DB::table('measurements')->insert($measurements);
    }
}
