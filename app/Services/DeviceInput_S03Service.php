<?php

namespace App\Services;

use App\Facades\CodeFacade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelReadHelper;
use Jericho\TextHelper;

class DeviceInput_S03Service
{
    /**
     * 导入株洲转辙机
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    final public function b049_1()
    {
        $entire_instances = TextHelper::parseJson(Storage::disk("deviceInputExcel")->get("转辙机/B049/entire_instances.json"));
        $part_instances = collect(TextHelper::parseJson(Storage::disk("deviceInputExcel")->get("转辙机/B049/part_instances.json")));
        return true;
    }

    /**
     * excel 转换 json
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function excelToJson()
    {
        $json = TextHelper::parseJson(Storage::disk("deviceInputExcel")->get("转辙机/B049/转辙机台账.json"));
        $entire_instances = [];
        $part_instances = [];
        $entire_model_not_exists = [];
        foreach ($json as $row) {
            list($serial_number, $entire_model_name, $category_name, $fix_cycle_value, $factory_name, $factory_device_code,
                $status, $to_direction, $crossroad_number, $traction, $made_at, $updated_at, $checked_at, $last_installed_at,
                $scarping_at, $line_name, $open_direction, $said_rod, $fixer_name, $checker_name, $tmp1, $tmp2, $tmp3,
                $part_instance_serial_number, $part_model_name, $part_factory_name, $part_factory_device_code,
                $tmp4, $tmp5, $tmp6, $tmp7, $tmp8, $tmp9, $tmp10, $tmp11, $tmp12, $tmp13, $tmp14, $tmp15, $tmp16, $tmp17, $tmp18, $tmp19) = $row;
            if ($serial_number == null) continue;

            if ($updated_at == '') $updated_at = null;
            if ($made_at == '') $made_at = null;
            if ($scarping_at == '') $scarping_at = null;

            # 获取整件类型代码，获取部件型号代码
            $part_model = DB::table('part_models')
                ->select([
                    'part_models.entire_model_unique_code',
                    'part_models.unique_code',
                    'part_models.category_unique_code',
                    'categories.name as category_name'
                ])
                ->join('categories', 'categories.unique_code', '=', 'part_models.category_unique_code')
                ->where('part_models.deleted_at', null)
                ->where('part_models.name', $entire_model_name)
                ->first();
            if ($part_model == null) {
                $entire_model_not_exists[] = $row;
                continue;
            }
            $entire_model_unique_code = $part_model->entire_model_unique_code;  # 获取整件类型代码
            $part_model_unique_code = $part_model->unique_code;  # 获取部件型号代码
            $category_name = $part_model->category_name;  # 获取种类名称
            $category_unique_code = $part_model->category_unique_code;  # 获取种类代码

            if ($status == '上道使用') {
                $status = 'INSTALLED';
            } elseif ($status == '成品') {
                $status = 'FIXED';
            } else {
                $status = 'FIXING';
            }

            $entire_instances[] = [
                'created_at' => $made_at,
                'updated_at' => $updated_at,
                'serial_number' => $serial_number,
                'identity_code' => $new_entire_instance_identity_code = CodeFacade::makeEntireInstanceIdentityCode($entire_model_unique_code),
                'entire_model_unique_code' => $entire_model_unique_code,
                'fix_cycle_value' => $fix_cycle_value,
                'factory_name' => $factory_name,
                'factory_device_code' => $factory_device_code,
                'status' => $status,
                'to_direction' => $to_direction,
                'crossroad_number' => $crossroad_number,
                'traction' => $traction,
                'made_at' => $made_at,
                'last_installed_time' => $last_installed_at ? Carbon::createFromFormat('Y-m-d', $last_installed_at)->timestamp : null,
                'scarping_at' => $scarping_at,
                'line_name' => $line_name,
                'open_direction' => $open_direction,
                'said_rod' => $said_rod,
                'category_name' => $category_name,
                'category_unique_code' => $category_unique_code,
            ];

            $part_instances [] = [
                'created_at' => $made_at,
                'updated_at' => $updated_at,
                'part_model_unique_code' => $part_model_unique_code,
                'part_model_name' => $part_model_name,
                'entire_instance_identity_code' => $new_entire_instance_identity_code,
                'status' => $status,
                'factory_name' => $part_factory_name,
                'factory_device_code' => $part_factory_device_code,
                'identity_code' => $new_part_instance_identity_code = CodeFacade::makePartInstanceIdentityCode($part_model_unique_code),
                'category_unique_code' => $category_unique_code,
                'entire_model_unique_code' => $entire_model_unique_code,
            ];
        }
        Storage::disk("deviceInputExcel")->put("转辙机/B049/entire_instances.json", TextHelper::toJson($entire_instances));
        Storage::disk("deviceInputExcel")->put("转辙机/B049/part_instances.json", TextHelper::toJson($part_instances));
        Storage::disk("deviceInputExcel")->put("转辙机/B049/entire_model_not_exists.json", TextHelper::toJson($entire_model_not_exists));
    }
}
