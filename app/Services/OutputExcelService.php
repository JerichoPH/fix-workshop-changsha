<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\TextHelper;

class OutputExcelService
{
    final public function do_q01()
    {
        $entireInstances = DB::table('entire_instances')
            ->select([
                'entire_instances.serial_number',
                'entire_instances.factory_name',
                'entire_instances.identity_code',
                'entire_instances.maintain_station_name',
                'entire_instances.maintain_location_code',
                'entire_instances.last_installed_time',
                'entire_instances.is_main',
                'entire_instances.status',
                'entire_instances.category_name',
                'entire_instances.entire_model_unique_code',
                'entire_instances.in_warehouse',
                'entireModels.name as entire_model_name',
            ])
            ->join('entireModels', 'entireModels.unique_code', '=', 'entire_instances.entire_model_unique_code')
            ->where('entire_instances.deleted_at', null)
            ->limit(500)
            ->get();
        Storage::disk('local')->put('继电器统计.json', TextHelper::toJson($entireInstances->toArray()));
    }
}
