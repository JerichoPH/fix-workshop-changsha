<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\WarehouseReportFacade;
use App\Model\EntireInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\TextHelper;

class DeviceInput_Q01Service
{
    /**
     * 导入衡阳数据
     * @param string $name
     * @return array|null
     * @throws \Exception
     */
    final public function b051_1(string $name)
    {
        # 调用python辅助函数将Excel转JSON
        $pythonPath = __DIR__ . '/../../public';
        $pathinfo = pathinfo(storage_path("deviceInputExcel/{$name}"));
        $pythonShellName = env('PYTHON_SHELL_NAME', 'python36');
        $shell = "{$pythonShellName} {$pythonPath}/excel2json.py " . storage_path("deviceInputExcel/{$pathinfo['filename']}");
        $shellRet = trim(shell_exec($shell));
        if ($shellRet) throw new \Exception($shellRet);
        $json = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$pathinfo['filename']}.json"));

        /*# 找到所编号和型号一样的
        $repeat1_lineCode = [];
        $repeat1_content = [];
        foreach ($json as $sheetName => $sheet) {
            $a = [];
            foreach ($sheet as $lineCode => $row) {
                list($buyInTime, $scrapingAt, $categoryUniqueCode, $entireModelName, $serialNumber, $statusName, $inWarehouse, $maintainStationName,
                    $maintainLocationCode, $isMain, $factoryName, $factoryDeviceCode, $entireInstanceIdentityCode, $lastSerialNumber, $nextGoingToFixingDate,
                    $fixCycleValue, $cycleFixCount, $unCycleFixCount, $madeAt, $residueUseYear, $installedTime, $purpose, $warehouseName, $warehouseLocation,
                    $railWayName, $stationName, $baseName) = $row;
                if (!$factoryDeviceCode) {
                    $factoryNoExists[] = $row;
                    continue;
                }
                $a[$lineCode] = $lastSerialNumber . $entireModelName;
            }

            $b = [];
            foreach ($a as $lineCode => $item) {
                $b[$item][] = $lineCode;
            }

            foreach ($b as $item) {
                if (count($item) > 1) {
                    foreach ($item as $value) {
                        $repeat1_lineCode[$sheetName][] = $value;
                    }
                }
            }

            foreach ($repeat1_lineCode as $sn => $lineCodes) {
                foreach ($lineCodes as $lineCode) {
                    $repeat1_content[$sn][] = $json[$sn][$lineCode];
                }
            }
        }
        foreach ($repeat1_lineCode as $sheetName => $lineCodes) {
            foreach ($lineCodes as $lineCode) {
                unset($json[$sheetName][$lineCode]);
            }
        }
        Storage::disk('deviceInputExcel')->put($pathinfo['filename'] . '/repeat1.json', TextHelper::toJson($repeat1_content));

        # 找到厂编号和位置编码一样的
        $repeat2_lineCode = [];
        $repeat2_content = [];
        foreach ($json as $sheetName => $sheet) {
            $a = [];
            foreach ($sheet as $lineCode => $row) {
                list($buyInTime, $scrapingAt, $categoryUniqueCode, $entireModelName, $serialNumber, $statusName, $inWarehouse, $maintainStationName,
                    $maintainLocationCode, $isMain, $factoryName, $factoryDeviceCode, $entireInstanceIdentityCode, $lastSerialNumber, $nextGoingToFixingDate,
                    $fixCycleValue, $cycleFixCount, $unCycleFixCount, $madeAt, $residueUseYear, $installedTime, $purpose, $warehouseName, $warehouseLocation,
                    $railWayName, $stationName, $baseName) = $row;
                if (!$factoryDeviceCode) {
                    $factoryNoExists[] = $row;
                    continue;
                }
                $a[$lineCode] = $lastSerialNumber . $entireModelName;
            }

            $b = [];
            foreach ($a as $lineCode => $item) {
                $b[$item][] = $lineCode;
            }

            foreach ($b as $item) {
                if (count($item) > 1) {
                    foreach ($item as $value) {
                        $repeat2_lineCode[$sheetName][] = $value;
                    }
                }
            }

            foreach ($repeat2_lineCode as $sn => $lineCodes) {
                foreach ($lineCodes as $lineCode) {
                    $repeat2_content[$sn][] = $json[$sn][$lineCode];
                }
            }
        }
        foreach ($repeat2_lineCode as $sheetName => $lineCodes) {
            foreach ($lineCodes as $lineCode) {
                unset($json[$sheetName][$lineCode]);
            }
        }
        Storage::disk('deviceInputExcel')->put($pathinfo['filename'] . '/repeat2.json', TextHelper::toJson($repeat2_content));*/

        $factoryNoExists = [];
        $entireModelNoExists = [];
        $input = [];

        foreach ($json as $sheetName => $sheet) {
            $i = 0;
            foreach ($sheet as $row) {
                $i++;
                list($buyInTime, $scrapingAt, $categoryUniqueCode, $entireModelName, $serialNumber, $statusName, $inWarehouse, $maintainStationName,
                    $maintainLocationCode, $isMain, $factoryName, $factoryDeviceCode, $entireInstanceIdentityCode, $lastSerialNumber, $nextGoingToFixingDate,
                    $fixCycleValue, $cycleFixCount, $unCycleFixCount, $madeAt, $residueUseYear, $installedTime, $purpose, $warehouseName, $warehouseLocation,
                    $railWayName, $stationName, $baseName) = $row;
//                if (!$factoryDeviceCode) {
//                    $factoryNoExists[] = $row;
//                    continue;
//                }

                $entireModel = DB::table('entire_models')
                    ->select([
                        'entireModels.unique_code as unique_code',
                        'entireModels.category_unique_code as category_unique_code',
                        'categories.name as category_name'
                    ])
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->first();
                if (!$entireModel) {
                    $entireModelNoExists[] = $row;
                    continue;
                }

                $time = date('Y-m-d H:i:s');
                $categoryUniqueCode = $entireModel->category_unique_code;  # 种类代码
                $serialNumber = $lastSerialNumber;  # 流水号等于最后流水号
                $status = 'INSTALLED';  # 已安装
                $inWarehouse = 0;  # 库外
                $isMain = $isMain == '主用' ? 1 : 0;  # 主备用状态
                $entireInstanceIdentityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->unique_code);  # 获取设备唯一标识
                $railWayName = '广铁集团';  # 路局名称
                $stationName = $maintainStationName;  # 站名
                $fixCycleValue = intval($fixCycleValue);  # 合法化周期修（值）

                if ($installedTime !== null) {
                    $installedTime = str_replace('.', '-', $installedTime);
                    list($installedTimeYear, $installedTimeMonth) = explode('-', $installedTime);
                    $installedTime = Carbon::create($installedTimeYear, $installedTimeMonth, 1)->getTimestamp();
                }

                $scarpingAt = null;
                if ($madeAt !== null) {
                    $madeAt = str_replace('.', '-', $madeAt);
                    list($madeAtYear, $madeAtMonth) = explode('-', $madeAt);
                    $madeAt = Carbon::create($madeAtYear, $madeAtMonth, 1)->format('Y-m-d');
                    $scrapingAt = Carbon::create($madeAtYear, $madeAtMonth, 1)->addYear($fixCycleValue)->format('Y-m-d');
                }

                $input[$factoryDeviceCode] = [
                    'created_at' => $time,
                    'updated_at' => $time,
                    'entire_model_unique_code' => $entireModel->unique_code,
                    'serial_number' => $serialNumber,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'is_main' => $isMain,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'identity_code' => $entireInstanceIdentityCode,
                    'last_installed_time' => $installedTime,
                    'in_warehouse' => $inWarehouse,
                    'category_unique_code' => $categoryUniqueCode,
                    'category_name' => $entireModel->category_name,
                    'fix_cycle_value' => $fixCycleValue,
                    'cycle_fix_count' => $cycleFixCount,
                    'un_cycle_fix_count' => $unCycleFixCount != '' ? $unCycleFixCount : null,
                    'made_at' => $madeAt,
                    'scarping_at' => $scrapingAt,
                    'residue_use_year' => $residueUseYear,
                    'purpose' => $purpose,
                    'railway_name' => $railWayName,
                    'section_name' => $stationName,
                    'base_name' => $baseName
                ];
            }
        }
        Storage::disk('deviceInputExcel')->put("{$pathinfo['filename']}/success.json", TextHelper::toJson($input));
        Storage::disk('deviceInputExcel')->put("{$pathinfo['filename']}/factoryNoExists.json", TextHelper::toJson($factoryNoExists));
        Storage::disk('deviceInputExcel')->put("{$pathinfo['filename']}/entireModelNoExists.json", TextHelper::toJson($entireModelNoExists));

        foreach ($input as $key => $value) {
            try {
                DB::table('entire_instances')->insert($value);
            } catch (\Exception $exception) {
                Storage::disk('deviceInputExcel')->put("{$pathinfo['filename']}/error.json", TextHelper::toJson([$key => $value]));
            }
        }

        # 刷新衡阳数据下次检修时间
        EntireInstance::all()
            ->each(function ($item) {
                $item->fill(EntireInstanceFacade::nextFixingTime($item))->save();
            });
    }

    /**
     * 导入株洲数据（38列格式，所内设备）
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_38_in_workshop()
    {
        # 读取转换后的JSON文件
        if (is_file(storage_path("deviceInputExcel/B049/在车间设备.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("B049/在车间设备.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                list($serialNumber, $factoryDeviceCode, $entireModelName, $statusName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $purpose, $fixCycleValue, $fixCount, $factoryName,
                    $xSpotTesterName, $xSpotTestedAt, $xCheckAgainAt, $madeAt, $createdAt, $lastInstalledAt, $updatedAt, $xCheckedAt,
                    $xScrapingAt, $scarpingAt, $xFixerName, $xCheckerName, $beforeFixerName, $xCheckerName, $note, $xMeasurementId,
                    $xInputWarehouseAt, $xInputWorkshopAt, $xFixReason, $oldNumber, $xTemporaryFixerName, $xTemporaryCheckerName,
                    $xOutWorkshopType, $beforeFixedAt, $xBreakdownFixerName, $xBreakdownFixedAt, $outOrganizationName) = $row;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                $status = 'FIXED';

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'purpose' => $purpose,
                    'fix_cycle_value' => $fixCycleValue,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'note' => $note,
                    'old_number' => $oldNumber,
                    'before_fixed_at' => $beforeFixedAt,
                    'before_fixer_name' => $beforeFixerName,
                    'un_cycle_fix_count' => $fixCount != '' ? $fixCount : null,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("B049/在车间设备-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("B049/在车间设备-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("B049/在车间设备-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("B049/在车间设备-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/B049/在车间设备.json"));
        }
    }

    /**
     * 导入株洲数据（38列格式）
     * @param string $name
     * @param string|null $dir
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_38(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName, $statusName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $purpose, $fixCycleValue, $fixCount, $factoryName,
                    $xChecker, $xSpotTestAt, $xCheckAgainAt, $madeAt, $createdAt, $lastInstalledAt, $updatedAt,
                    $checkedAt, $xScrapingAt, $scarpingAt, $xFixerName, $xCheckerName, $beforeFixerName,
                    $xCheckAgainPersonName, $note, $xMeasurementId, $xInputWarehouseAt, $xInputWorkshopAt,
                    $xFixReason, $oldNumber, $xTemporaryFixerName, $xTemporaryCheckerName,
                    $xOutWorkshopType, $beforeFixedAt, $xBreakdownFixerName, $xBreakdownFixedAt) = $row;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if ($statusName == '入所') {
                    $status = $createdAt != null ? 'FIXED' : 'FIXING';
                } elseif ($statusName == '出所') {
                    $status = $maintainLocationCode != null ? 'INSTALLED' : 'INSTALLING';
                } else {
                    $status = 'BUY_IN';
                }

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'purpose' => $purpose,
                    'fix_cycle_value' => $fixCycleValue,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'note' => $note,
                    'old_number' => $oldNumber,
                    'before_fixed_at' => $beforeFixedAt,
                    'before_fixer_name' => $beforeFixerName,
                    'un_cycle_fix_count' => $fixCount != '' ? $fixCount : null,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }

    /**
     * 导入株洲数据（23列格式）
     * @param string $name
     * @param string|null $dir
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_23(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName, $statusName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $purpose, $fixCycleValue, $fixCount, $factoryName,
                    $madeAt, $createdAt, $lastInstalledAt, $updatedAt, $checkedAt, $xScrapingAt, $scarpingAt,
                    $xFixerName, $xCheckerName, $beforeFixerName, $xCheckAgainPersonName, $note) = $row;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if ($statusName == '入所') {
                    $status = $createdAt != null ? 'FIXED' : 'FIXING';
                } elseif ($statusName == '出所') {
                    $status = $maintainLocationCode != null ? 'INSTALLED' : 'INSTALLING';
                } else {
                    $status = 'BUY_IN';
                }

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'purpose' => $purpose,
                    'fix_cycle_value' => $fixCycleValue,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'note' => $note,
                    'before_fixer_name' => $beforeFixerName,
                    'un_cycle_fix_count' => $fixCount != '' ? $fixCount : null,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }

    /**
     * 导入株洲数据（19列格式）
     * @param string $name
     * @param string|null $dir
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_19(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $purpose, $fixCycleValue, $fixCount,
                    $factoryName, $madeAt, $createdAt, $lastInstalledAt, $updatedAt, $checkedAt,
                    $xScrapingAt, $scarpingAt, $xFixerName, $xCheckerName) = $row;

                # 合理化时间
                if ($createdAt == '') $createdAt = null;
                if ($updatedAt == '') $updatedAt = null;
                if ($madeAt == '') $madeAt = null;
                if ($fixCycleValue == '') $fixCycleValue = 0;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if (null != $maintainLocationCode) {
                    $status = '备品' != $maintainLocationCode ? 'INSTALLED' : 'FIXED';
                } else {
                    $status = 'BUY_IN';
                }

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'purpose' => $purpose,
                    'fix_cycle_value' => $fixCycleValue,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'un_cycle_fix_count' => $fixCount != '' ? $fixCount : null,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                '锐恩威批量入所',
                '13522178057',
                'BATCH_WITH_OLD'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }

    /**
     * 导入株洲数据（15列格式）
     * @param string $name
     * @param string|null $dir
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_15(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $fixCycleValue, $factoryName, $madeAt,
                    $createdAt, $lastInstalledAt, $updatedAt, $checkedAt, $xScrapingAt, $scarpingAt) = $row;

                # 合法化出厂时间
                if ($madeAt == '') $madeAt = null;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if (null != $maintainLocationCode) {
                    $status = '备品' != $maintainLocationCode ? 'INSTALLED' : 'FIXED';
                } else {
                    $status = 'BUY_IN';
                }

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'fix_cycle_value' => $fixCycleValue != '' ? $fixCycleValue : 0,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }

    /**
     * 导入株洲数据（16列格式）
     * @param string $name
     * @param string|null $dir
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function b049_16(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName, $maintainStationName,
                    $maintainLocationCode, $xUseToPlace, $fixCycleValue, $fixCount, $factoryName, $madeAt,
                    $createdAt, $lastInstalledAt, $updatedAt, $checkedAt, $xScrapingAt, $scarpingAt) = $row;

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if (null != $maintainLocationCode) {
                    $status = '备品' != $maintainLocationCode ? 'INSTALLED' : 'FIXED';
                } else {
                    $status = 'BUY_IN';
                }

                # 获取安装时间
                if ($lastInstalledAt != null) $lastInstalledAt = Carbon::createFromFormat('Y-m-d H:i:s', $lastInstalledAt)->timestamp;

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $entireInstances[] = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'fix_cycle_value' => $fixCycleValue != '' ? $fixCycleValue : 0,
                    'un_cycle_fix_count' => $fixCount,
                    'made_at' => $madeAt,
                    'last_installed_time' => $lastInstalledAt,
                    'scarping_at' => $scarpingAt,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }

    final public function b049_20(string $name, string $dir = null)
    {
        # 读取转换后的JSON文件
        $filePath = "{$dir}/{$name}";
        if (is_file(storage_path("deviceInputExcel/{$filePath}.json"))) {
            $excel = TextHelper::parseJson(Storage::disk('deviceInputExcel')->get("{$filePath}.json"));

            $sheet = $excel["Sheet1"];
            $entireModelNotExists = [];
            $factoryNameNotExists = [];
            $repeatSerialNumber = [];
            $entireInstances = [];

            foreach ($sheet as $row) {
                # waiting: 等李明现场反馈
                # “使用处所”是什么意思，其中的数字什么意思
                # 周期为：“0”是什么意思
                # 厂家名称没有登记
                # 位置代码重复
                # 所编号重复
                # todo: 特殊情况处理
                # 没有厂家的记录在案
                # 编号重复的跳过，记录在案

                list($serialNumber, $factoryDeviceCode, $entireModelName,
                    $maintainStationName, $maintainLocationCode, $xUseToPlace,
                    $purpose, $fixCycleValue, $fixCount, $factoryName, $madeAt,
                    $createdAt, $updatedAt, $updatedAt, $nextFixingTime, $scarpingAt,
                    $xFixerName, $xCheckerName, $beforeFixerName) = $row;

                if ($madeAt == '') $madeAt = null;

                # 计算下次检修时间
                $hasNextFixingTime = false;
                if ($nextFixingTime != null && $nextFixingTime != '' && $fixCycleValue != 0) {
                    $hasNextFixingTime = true;
                    $nextFixingTime = Carbon::createFromFormat('Y-m-d H:i:s', $nextFixingTime)->timestamp;
                    $nextAutoMakingFixWorkflowTime = Carbon::createFromTimestamp($nextFixingTime)->addYear($fixCycleValue)->timestamp;
                    $nextAutoMakingFixWorkflowAt = Carbon::createFromTimestamp($nextFixingTime)->addYear($fixCycleValue)->format('Y-m-d');
                    $nextFixingMonth = Carbon::createFromTimestamp($nextFixingTime)->addYear($fixCycleValue)->format('Y-m') . '-01';
                    $nextFixingDay = Carbon::createFromTimestamp($nextFixingTime)->addYear($fixCycleValue)->format('Y-m-d');
                }

                # 型号不存在
                $entireModel = DB::table('entire_models')
                    ->select([
                        'categories.unique_code as category_unique_code',
                        'categories.name as category_name',
                        'entireModels.unique_code as entire_model_unique_code'
                    ])
                    ->join('categories', 'categories.unique_code', '=', 'entireModels.category_unique_code')
                    ->where('entireModels.deleted_at', null)
                    ->where('entireModels.name', $entireModelName)
                    ->first();
                if ($entireModel == null) {
                    $entireModelNotExists[$entireModelName][] = $row;
                    continue;
                }

                # 重复所编号
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('serial_number', $serialNumber)->first();
                if ($entireInstance != null) {
                    $repeatSerialNumber[$serialNumber][] = $row;
                    continue;
                }

                # 获取状态
                if (null != $maintainLocationCode) {
                    $status = preg_match('/备品/', $maintainLocationCode) ? 'FIXED' : 'INSTALLED';
                } else {
                    $status = 'BUY_IN';
                }

                # 厂商不存在
                if ($factoryName == null) $factoryName = '沈阳信号厂';

                # 唯一编号
                $identityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModel->entire_model_unique_code);

                $tmp = [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'serial_number' => $serialNumber,
                    'factory_name' => $factoryName,
                    'factory_device_code' => $factoryDeviceCode,
                    'status' => $status,
                    'maintain_station_name' => $maintainStationName,
                    'maintain_location_code' => $maintainLocationCode,
                    'purpose' => $purpose,
                    'fix_cycle_value' => $fixCycleValue,
                    'made_at' => $madeAt,
                    'last_installed_time' => $updatedAt,
                    'scarping_at' => $scarpingAt,
                    'before_fixer_name' => $beforeFixerName,
                    'un_cycle_fix_count' => $fixCount != '' ? $fixCount : null,
                    'entire_model_unique_code' => $entireModel->entire_model_unique_code,
                    'identity_code' => $identityCode,
                    'category_unique_code' => $entireModel->category_unique_code,
                    'category_name' => $entireModel->category_name,
                ];

                # 如果存在下次检修时间
                if ($hasNextFixingTime) {
                    $tmp['next_fixing_time'] = $nextFixingTime;
                    $tmp['next_auto_making_fix_workflow_time'] = $nextAutoMakingFixWorkflowTime;
                    $tmp['next_auto_making_fix_workflow_at'] = $nextAutoMakingFixWorkflowAt;
                    $tmp['next_fixing_month'] = $nextFixingMonth;
                    $tmp['next_fixing_day'] = $nextFixingDay;
                }

                $entireInstances[] = $tmp;
            }

            Storage::disk('deviceInputExcel')->put("{$filePath}-型号不存在.json", TextHelper::toJson($entireModelNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-供应商未填写.json", TextHelper::toJson($factoryNameNotExists));
            Storage::disk('deviceInputExcel')->put("{$filePath}-所编号重复.json", TextHelper::toJson($repeatSerialNumber));
            Storage::disk('deviceInputExcel')->put("{$filePath}-整件.json", TextHelper::toJson($entireInstances));

            DB::beginTransaction();
            $ret = DB::table('entire_instances')->insert($entireInstances);
            if ($ret) WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                array_pluck($entireInstances, 'identity_code'),
                1,
                Carbon::create()->format('Y-m-d'),
                'BATCH_WITH_OLD',
                '锐恩威批量入所',
                '13522178057'
            );
            DB::commit();
            return $ret;
        } else {
            throw new \Exception('文件不存在：' . storage_path("deviceInputExcel/{$filePath}.json"));
        }
    }
}
