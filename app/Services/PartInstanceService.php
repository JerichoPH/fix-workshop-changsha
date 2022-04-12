<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Facades\ExcelReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartInstanceService
{
    /**
     * 通过Excel批量生成部件
     * @param Request $request
     * @param array $factoryUniqueCodes
     * @return array
     */
    public function batchFromExcel(Request $request, array $factoryUniqueCodes): array
    {
        $partModelUniqueCodes = DB::table('part_models')->pluck('name', 'unique_code')->toArray();

        $excelPart = ExcelReader::init($request, 'file')->readSheetByName('部件', 2, 0, function ($row) use (
            &$partFactoryDeviceCodeNotExist,
            &$lastEntireInstanceFactoryDeviceCode,
            &$lastEntireInstance,
            &$partModelNotExist,
            $partModelUniqueCodes,
            $factoryUniqueCodes,
            &$partRepeat
        ) {
            list($entireInstanceFactoryDeviceCode, $partInstanceFactoryDeviceCode, $factoryName, $partModelName) = $row;
            # 如果不存在厂家编号则自动生成一个
            if ($partInstanceFactoryDeviceCode === '无') $partInstanceFactoryDeviceCode = time() . rand(1000, 9999);

            # 如果关键字段为空则跳过（出厂编号）
            if ($partInstanceFactoryDeviceCode == null) {
                $partFactoryDeviceCodeNotExist[] = $row;
                return null;
            }

            # 如果出厂编号重复则跳过
            if (DB::table('part_instances')->where('factory_device_code', $partInstanceFactoryDeviceCode)->first()) {
                $partRepeat[] = $row;
                return null;
            }

            if ($entireInstanceFactoryDeviceCode == $lastEntireInstanceFactoryDeviceCode) {
                # 本次插入部件和上一次的整件相同
                $currentEntireInstance = $lastEntireInstance;
            } else {
                # 本次插入部件和上一次的整件不同
                $currentEntireInstanceFactoryDeviceCode = $lastEntireInstanceFactoryDeviceCode = $entireInstanceFactoryDeviceCode;
                # 如果不存在整件，则跳过该部件
                $currentEntireInstance = $lastEntireInstance = DB::table('entire_instances')->where('factory_device_code', $currentEntireInstanceFactoryDeviceCode)->first();
                if (!$currentEntireInstance) {
                    $entireInstanceNotExist[] = $row;
                };
            }

            $partModel = DB::table('part_models')->where('name', $partModelName)->first(['unique_code']);
            if (!$partModel) {
                $partModelNotExist[] = $row;
                return null;
            }

            return [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'part_model_unique_code' => $partModel->unique_code,
                'part_model_name' => $partModelUniqueCodes[$partModel->unique_code],
                'entire_instance_identity_code' => $currentEntireInstance->identity_code,
                'status' => 'FIXING',
                'factory_name' => $factoryName,
                'factory_device_code' => $partInstanceFactoryDeviceCode,
                'identity_code' => CodeFacade::makePartInstanceIdentityCode($partModel->unique_code),
                'category_unique_code' => $currentEntireInstance->category_unique_code,
                'entire_model_unique_code' => $currentEntireInstance->entire_model_unique_code,
            ];
        });

        return $excelPart;
    }

    /**
     * 批量新入所
     * @param Request $request
     * @param string $filename
     * @return array
     */
    final public function batchFromExcelWithNew(Request $request, string $filename): array
    {
        $excel = ExcelReader::init($request, $filename)->readSheetByName('部件', 2, 0, function ($row) {
            list($entireInstanceIdentityCode, $factoryDeviceCode, $factoryName, $partModelName) = $row;

            if ($entireInstanceIdentityCode == null && $factoryDeviceCode == null && $factoryName == null && $partModelName) return null;

            return ['entire_instance_identity_code' => $entireInstanceIdentityCode, 'factory_name' => $factoryName, 'factory_device_code' => $factoryDeviceCode, 'part_model_name' => $partModelName];
        });

        # Excel文件内，通过厂编号去重

        # 获取数据库中已经已存在的整件编号
        $entireInstanceIdentityCodes = collect($excel['success'])->pluck('entire_instance_identity_code')->unique()->values()->toArray();
        $entireInstanceExists = DB::table('entire_instances')->whereIn('identity_code', $entireInstanceIdentityCodes)->pluck('identity_code')->toArray();

        $tmp = [];
        foreach ($excel['success'] as $success) {
            $tmp[$success['factory_device_code']] = $success;
        }
        $excel = collect($tmp);
        unset($tmp);

        $partInstanceExists = DB::table('entire_instances')
            ->whereIn('factory_device_code', $excel->keys()->toArray())
            ->pluck('factory_device_code')
            ->toArray();
        $partInstanceNoExists = $excel->diff($partInstanceExists);
        $partInstancesWithNew = $partInstanceNoExists->reject(function ($value, $key) use ($entireInstanceExists) {
            return !in_array($value['entire_instance_identity_code'], $entireInstanceExists);
        })
            ->toArray();

        $partModelUniqueCodes = DB::table('part_models')->pluck('name', 'unique_code')->toArray();
        $currentTime = date('Y-m-d H:i:s');

        $partInstances = [];
        $i = 0;
        foreach ($partInstancesWithNew as $partInstanceWithNew) {
            $partModelUniqueCode = array_unique($partModelUniqueCodes)[$partInstancesWithNew['part_model_name']];
            $partInstances[] = [
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
                'part_model_unique_code' => $partModelUniqueCode,
                'part_model_name' => $partInstanceWithNew['part_model_name'],
                'entire_instance_identity_code' => $partInstanceWithNew['entire_instance_identity_code'],
                'status' => 'BUY_IN',
                'factory_name' => $partInstanceWithNew['factory_name'],
                'factory_device_code' => $partInstanceWithNew['factory_device_code'],
                'identity_code' => strval(time() * 1000) . strval(++$i),
                'entire_instance_serial_number' => null,
                'category_unique_code' => substr($partModelUniqueCode, 0, 3),
                'entire_model_unique_code' => substr($partModelUniqueCode, 0, 5),
            ];
        }

        return $partInstances;
    }
}
