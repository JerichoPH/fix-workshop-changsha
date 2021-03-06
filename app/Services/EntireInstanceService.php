<?php

namespace App\Services;

use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\ExcelReader;
use App\Facades\FixWorkflowFacade;
use App\Facades\SuPuRuiSdk;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireInstanceExcelTaggingIdentityCode;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Maintain;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\PartModel;
use App\Model\V250TaskOrder;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class EntireInstanceService
{
    /**
     * 转换成设备唯一编码
     * @param string $code
     * @return string
     */
    final public function toDecode(string $code): string
    {
        if (substr($code, 0, 4) == '130E' || substr($code, 0, 4) == '130F') {
            // rfid_epc
            $entire_unique_code = CodeFacade::hexToIdentityCode($code);
        } elseif (substr($code, 0, 2) == 'E2' && strlen($code) == 24) {
            // rfid_code
            $tmp = EntireInstance::with([])->where('rfid_code', $code)->first(['identity_code']);
            $entire_unique_code = empty($tmp) ? '' : $tmp->identity_code;
        } else {
            $entire_unique_code = $code;
        }

        return $entire_unique_code;
    }

    /**
     * 自增设备实例总数
     * @param string $entireModelUniqueCode
     * @return int
     */
    final public function incCount(string $entireModelUniqueCode): int
    {
        $entireFixedCountDB = DB::table('entire_instance_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->first(['count']);
        if ($entireFixedCountDB) {
            $entireFixedCount = $entireFixedCountDB ? $entireFixedCountDB->count : 0;
            DB::table('entire_instance_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->update(['count' => $entireFixedCount + 1]);
            return $entireFixedCount + 1;
        } else {
            DB::table('entire_instance_counts')->insert(['entire_model_unique_code' => $entireModelUniqueCode, 'count' => 1]);
            return 1;
        }
    }

    /**
     * 记录设备维修总数
     * @param string $entireModelUniqueCode
     * @return int
     */
    final public function incFixedCount(string $entireModelUniqueCode): int
    {
        $entireFixedCountDB = DB::table('entire_fixed_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->where('year', date('Y'))->first(['count']);
        if ($entireFixedCountDB) {
            $entireFixedCount = $entireFixedCountDB ? $entireFixedCountDB->count : 0;
            DB::table('entire_fixed_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->where('year', date('Y'))->update(['count' => $entireFixedCount + 1]);
            return $entireFixedCount + 1;
        } else {
            DB::table('entire_fixed_counts')->insert(['entire_model_unique_code' => $entireModelUniqueCode, 'year' => date('Y'), 'count' => 1]);
            return 1;
        }
    }

    /**
     * 通过设备唯一标识获取下次检修时间
     * @param string $entireInstanceIdentityCode
     * @return array
     * @throws \Throwable
     */
    final public function nextFixingTimeWithIdentityCode(string $entireInstanceIdentityCode): array
    {
        $entireInstance = EntireInstance::with('EntireModel', 'WarehouseReportByOut', 'FixWorkflow')
            ->where('identity_code', $entireInstanceIdentityCode)
            ->firstOrFail();
        $fillData = $this->nextFixingTime($entireInstance);
        $entireInstance->fill($fillData)->saveOrFail();
        return $fillData;
    }

    /**
     * 计算下一次检修时间
     * @param EntireInstance $entireInstance
     * @return array
     */
    final public function nextFixingTime(EntireInstance $entireInstance): array
    {
        // 修改整件状态和最后一次出所单流水号
        $nextFixingData = null;
        $fixCycleValue = $entireInstance->fix_cycle_value;

        if ($fixCycleValue == 0) {
            // 使用型号的周期修时间
            if ($entireInstance->EntireModel->fix_cycle_value == 0) {
                // 设备为状态修
                $nextFixingTime = 0;
                $nextFixingMonth = null;
                $nextFixingDay = null;
                $nextAutoMakingFixWorkflowTime = 0;
                $nextAutoMakingFixWorkflowAt = null;
            } else {
                // 根据型号的周期进行计算
                $fixCycleUnits = [
                    '年' => 'YEAR',
                    '月' => 'MONTH',
                    '周' => 'WEEK',
                    '日' => 'DAY',
                ];
                $fixCycleUnit = strtolower($fixCycleUnits[$entireInstance->EntireModel->fix_cycle_unit]);
                $fixCycleValue = $entireInstance->EntireModel->fix_cycle_value;
                $next = Carbon::createFromTimestamp(strtotime("+ {$fixCycleValue} {$fixCycleUnit}", time()));
                $nextFixingTime = $next->timestamp;
                $nextFixingDay = $next->toDateString();
                $nextFixingMonth = $next->firstOfMonth()->toDateString();
                $nextAutoMakingFixWorkflowTime = $next->subMonth()->firstOfMonth()->timestamp;
                $nextAutoMakingFixWorkflowAt = $next->firstOfMonth()->toDateString();
            }
        } else {
            // 根据新周期计算
            $fixCycleUnit = strtolower($entireInstance->fix_cycle_unit);
            $next = Carbon::createFromTimestamp(strtotime("+ {$fixCycleValue} {$fixCycleUnit}", time()));
            $nextFixingTime = $next->timestamp;
            $nextFixingDay = $next->toDateString();
            $nextFixingMonth = $next->firstOfMonth()->toDateString();
            $nextAutoMakingFixWorkflowTime = $next->subMonth()->firstOfMonth()->timestamp;
            $nextAutoMakingFixWorkflowAt = $next->firstOfMonth()->toDateString();
        }

        $nextFixingData = [
            'next_auto_making_fix_workflow_time' => $nextAutoMakingFixWorkflowTime,
            'next_fixing_time' => $nextFixingTime,
            'next_auto_making_fix_workflow_at' => $nextAutoMakingFixWorkflowAt,
            'next_fixing_month' => $nextFixingMonth,
            'next_fixing_day' => $nextFixingDay
        ];
        return $nextFixingData;
    }

    /**
     * 计算下一次周期修时间（相对上一次周期修时间）
     * @param EntireInstance $entireInstance
     * @param int $fixCycleValue
     * @param string $fixCycleUnit
     * @return array
     * @throws \Exception
     */
    final public function nextFixingTimeForRelative(EntireInstance $entireInstance, int $fixCycleValue = 0, string $fixCycleUnit = 'YEAR'): array
    {
        // 修改整件状态和最后一次出所单流水号
        $nextFixingData = null;
        $fixCycleValue = $fixCycleValue == 0 ? $entireInstance->EntireModel->fix_cycle_value : $fixCycleValue;  // 如果新的周期修时间为0则使用其设备类型的周期修时间
        if ($fixCycleValue == 0) {
            // 设备为状态修
            return [
                'next_auto_making_fix_workflow_time' => 0,
                'next_fixing_time' => 0,
                'next_auto_making_fix_workflow_at' => null,
                'next_fixing_month' => null,
                'next_fixing_day' => null
            ];
        }

        if (!$entireInstance->next_fixing_day) throw new \Exception('上次周期修时间不存在');
        $lastFixingTime = Carbon::createFromFormat('Y-m-d', explode(' ', $entireInstance->next_fixing_day)[0])->timestamp;
        $originTime = strtotime("- {$entireInstance->fix_cycle_value} {$fixCycleUnit}", $lastFixingTime);  // 按照上次周期修时间恢复起始时间
        $next = Carbon::createFromTimestamp(strtotime("+ {$fixCycleValue} {$fixCycleUnit}", $originTime));  // 重新计算新的下次周期修时间
        $nextFixingTime = $next->timestamp;
        $nextFixingDay = $next->toDateString();
        $nextFixingMonth = $next->firstOfMonth()->toDateString();
        $nextAutoMakingFixWorkflowTime = $next->subMonth()->firstOfMonth()->timestamp;
        $nextAutoMakingFixWorkflowAt = $next->firstOfMonth()->toDateString();

        $nextFixingData = [
            'next_auto_making_fix_workflow_time' => $nextAutoMakingFixWorkflowTime,
            'next_fixing_time' => $nextFixingTime,
            'next_auto_making_fix_workflow_at' => $nextAutoMakingFixWorkflowAt,
            'next_fixing_month' => $nextFixingMonth,
            'next_fixing_day' => $nextFixingDay
        ];
        return $nextFixingData;
    }

    /**
     * 批量新入所
     * @param Request $request
     * @param string $filename
     * @param string $sheetName
     * @return array
     * @throws \Exception
     */
    final public function batchFromExcelWithNew(Request $request, string $filename, string $sheetName = '0'): array
    {
        $currentTime = date('Y-m-d H:i:s');

        $excel = ExcelReadHelper::INS($request, $filename)->withSheetIndex($sheetName, function ($row) {
            // 导入整件
            list(
                $entireModelName,
                $factoryName,
                $entireFactoryDeviceCode,
                $partModelName,
                $partFactoryDeviceCode,
                $madeAt,
                $lifetime
                ) = $row;

            if ($entireModelName == null && $factoryName == null && $entireFactoryDeviceCode == null) return null;

            return [
                'entire_model_name' => $entireModelName,
                'factory_name' => $factoryName,
                'entire_factory_device_code' => $entireFactoryDeviceCode,
                'part_model_name' => $partModelName,
                'part_factory_device_code' => $partFactoryDeviceCode,
                'made_at' => $madeAt,
                'scarping_at' => Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', intval(($madeAt - 25569) * 3600 * 24)))->addYear($lifetime)->toDateString(),
            ];
        });

        // Excel文件内，通过厂编号去重
        $tmp = [];
        foreach ($excel['success'] as $success) {
            $tmp[$success['entire_factory_device_code']] = $success;
        }
        $excel = collect($tmp);
        unset($tmp);
        $exists = DB::table('entire_instances')
            ->whereIn('factory_device_code', $excel->keys()->toArray())
            ->pluck('factory_device_code')
            ->toArray();

        foreach ($excel as $factoryDeviceCode => $item) if (in_array($factoryDeviceCode, $exists)) unset($excel[$factoryDeviceCode]);

        $factoryUniqueCodes = DB::table('factories')->pluck('name', 'unique_code')->toArray();  // 工厂编码
        $factoryUniqueCodesFlip = array_flip($factoryUniqueCodes);
        $categoryUniqueCodes = DB::table('categories')->pluck('name', 'unique_code')->toArray();  // 种类编码
        $categoryUniqueCodesFlip = array_flip($categoryUniqueCodes);
        $entireModelUniqueCodes = DB::table('entire_models')->pluck('name', 'unique_code')->toArray();  // 类型编码
        $entireModelUniqueCodesFlip = array_flip($entireModelUniqueCodes);
        $partModelUniqueCodes = DB::table('part_models')->pluck('name', 'unique_code')->toArray();  // 型号编码
        $partModelUniqueCodesFlip = array_flip($partModelUniqueCodes);

        // 保存到数据库
        $entireInstances = [];
        $partInstances = [];
        $i = 0;
        $suPuRuiDeviceType = null;
        $suPuRuiInputs = ['S' => [], 'Q' => []];
        foreach ($excel as $noExistKey => $noExistValue) {
            // 生成整件编号
            $newEntireInstanceIdentityCode = CodeFacade::makeEntireInstanceIdentityCode(
                $entireModelUniqueCodesFlip[$noExistValue['entire_model_name']],
                $factoryUniqueCodesFlip[$noExistValue['factory_name']]
            );

            // 插入整件
            $entireInstances[] = [
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
                'entire_model_unique_code' => $entireModelUniqueCodesFlip[$noExistValue['entire_model_name']],
                'status' => 'BUY_IN',
                'factory_name' => $noExistValue['factory_name'],
                'factory_device_code' => $noExistValue['entire_factory_device_code'],
                'identity_code' => $newEntireInstanceIdentityCode,
                'category_unique_code' => substr($newEntireInstanceIdentityCode, 0, 3),
                'category_name' => $categoryUniqueCodes[substr($newEntireInstanceIdentityCode, 0, 3)],
            ];

            // 如果存在部件，则插入部件
            if ($noExistValue['part_model_name']) {
                $partModelUniqueCode = $partModelUniqueCodesFlip[$noExistValue['part_model_name']];
                $partInstances[] = [
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                    'part_model_unique_code' => $partModelUniqueCode,
                    'part_model_name' => $noExistValue['part_model_name'],
                    'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                    'status' => 'BUY_IN',
                    'factory_name' => $noExistValue['entire_factory_device_code'],
                    'factory_device_code' => $noExistValue['part_factory_device_code'],
                    'identity_code' => strval(time() * 1000) . strval(++$i),
                    'entire_instance_serial_number' => null,
                    'category_unique_code' => substr($partModelUniqueCode, 0, 3),
                    'entire_model_unique_code' => substr($partModelUniqueCode, 0, 5),
                ];
            }

            // 生成速普瑞接口所需结构数据
            switch (substr($newEntireInstanceIdentityCode, 0, 1)) {
                case 'S':
                default:
                    $suPuRuiInputs['S'][] = SuPuRuiSdk::makeEntireInstance_S(
                        $newEntireInstanceIdentityCode,
                        $partModelUniqueCodesFlip[$noExistValue['part_model_name']],
                        $noExistValue['entire_factory_device_code'],
                        3,
                        'A12',
                        'B048',
                        0,
                        'G00001'
                    );
                    break;
                case 'Q':
                    $suPuRuiInputs['Q'][] = SuPuRuiSdk::makeEntireInstance_Q(
                        $newEntireInstanceIdentityCode,
                        substr($newEntireInstanceIdentityCode, 0, 7),
                        $noExistValue['entire_factory_device_code'],
                        '',
                        3,
                        'A12',
                        'B048'
                    );
                    break;
            }
        }

        return [$entireInstances, $partInstances, $suPuRuiInputs];
    }

    /**
     * 批量检修入所
     * @param Request $request
     * @param string $filename
     * @return array
     */
    final public function batchFromExcelWithFix(Request $request, string $filename): array
    {
        $excel = ExcelReader::init($request, $filename)->readSheetByName('整件', 2, 0, function ($row) {
            // 导入整件
        });
    }

    /**
     * 旧码转换新码
     * @param mixed $entireInstances
     * @return int
     */
    final public function makeNewCode($entireInstances): int
    {
        $successCount = 0;
        DB::transaction(function () use ($entireInstances, &$successCount) {
            foreach ($entireInstances as $entireInstance) {
                $entireModelUniqueCode = $entireInstance->entire_model_unique_code;
                $entireInstanceIdentityCode = $entireInstance->identity_code;
                // 修改整件：entire_instances
                DB::table('entire_instances')->where('identity_code', $entireInstanceIdentityCode)->update([
                    'identity_code' => $newEntireInstanceIdentityCode = CodeFacade::makeEntireInstanceIdentityCode($entireModelUniqueCode, ''),
                    'old_number' => $entireInstanceIdentityCode
                ]);
                // 修改部件：part_instances
                DB::table('part_instances')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                // 修改检修单：fix_workflows
                DB::table('fix_workflows')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                // 修改出入所单：warehouse_report_entire_instances
                DB::table('warehouse_report_entire_instances')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                // 修改非正常检修记录：un_cycle_fix_reports
                DB::table('un_cycle_fix_reports')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                // 修改整件操作日志：entire_instance_logs
                DB::table('entire_instance_logs')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                // 修改整件更换部件记录：entire_instance_change_part_logs
                DB::table('entire_instance_change_part_logs')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->update(['entire_instance_identity_code' => $newEntireInstanceIdentityCode]);
                $successCount += 1;
            }
        });

        return $successCount;
    }

    /**
     * 根据PDA上传code 获取整件唯一编号
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    final public function getEntireInstanceIdentityCodeByCodeForPda(string $code): string
    {
        switch (strlen($code)) {
            case 14:
            case 19:
                $identity_code = $code;
                break;
            case 24:
                if (substr($code, 0, 4) == '130E') {
                    $identity_code = CodeFacade::hexToIdentityCode($code);
                } else {
                    $exist = DB::table('entire_instances as ei')->where('rfid_code', $code)->first(['identity_code']);
                    if (!$exist) throw new \Exception('设备不存在', 404);

                    $identity_code = $exist->identity_code;
                }
                break;
            default:
                throw new \Exception('设备编号格式错误', 403);
                break;
        }
        return $identity_code;
    }

    /**
     * 复制位置信息
     * @param string $from
     * @param string $to
     * @throws \Throwable
     */
    final public function copyLocation(string $from, string $to)
    {
        $old = EntireInstance::with([])->where('identity_code', $from)->first();
        if (!$old) throw new \Exception('旧设备不存在');

        $new = EntireInstance::with([])->where('identity_code', $to)->first();
        if (!$new) throw new \Exception('新设备不存在');

        $new->fill([
            'maintain_station_name' => $old->maintain_station_name,
            'maintain_location_code' => $old->maintain_location_code,
            'said_rod' => $old->said_rod,
            'traction' => $old->traction,
            'source' => $old->source,
            'line_name' => $old->line_name,
            'crossroad_number' => $old->crossroad_number,
            'open_direction' => $old->open_direction,
            'crossroad_type' => $old->crossroad_type,
            'point_switch_group_type' => $old->point_switch_group_type,
            'extrusion_protect' => $old->extrusion_protect,
        ])->saveOrFail();
    }

    /**
     * 清除位置信息
     * @param string $identity_code
     * @throws \Throwable
     */
    final public function clearLocation(string $identity_code)
    {
        $entire_instance = EntireInstance::with([])->where('identity_code', $identity_code)->first();
        if (!$entire_instance) throw new \Exception('设备不存在');
        $entire_instance->fill([
            'maintain_station_name' => '',
            'maintain_location_code' => '',
            'said_rod' => '',
            'traction' => '',
            'source' => '',
            'line_name' => '',
            'crossroad_number' => '',
            'open_direction' => '',
            'crossroad_type' => '',
            'point_switch_group_type' => '',
            'extrusion_protect' => '',
        ])->saveOrFail();
    }

    /**
     * 批量复制位置信息
     * @param array $entire_instances
     * @throws \Throwable
     */
    final public function copyLocations(array $entire_instances)
    {
        foreach ($entire_instances as $from => $to) {
            $old = EntireInstance::with([])->where('identity_code', $from)->first();
            if (!$old) throw new \Exception('旧设备不存在');

            $new = EntireInstance::with([])->where('identity_code', $to)->first();
            if (!$new) throw new \Exception('新设备不存在');

            $new->fill([
                'maintain_station_name' => $new->maintain_station_name,
                'maintain_location_code' => $new->maintain_location_code,
                'traction' => $new->traction,
                'crossroad_number' => $new->crossroad_number,
                'open_direction' => $new->open_direction,
                'crossroad_type' => $new->crossroad_type,
                'point_switch_group_type' => $new->point_switch_group_type,
                'extrusion_protect' => $new->extrusion_protect,
            ])->saveOrFail();
        }
    }

    /**
     * 批量清除位置信息
     * @param array $identity_codes
     */
    final public function clearLocations(array $identity_codes)
    {
        DB::table('entire_instances')->whereIn('identity_code', $identity_codes)->update([
            'updated_at' => date('Y-m-d H:i:s'),
            'maintain_station_name' => '',
            'maintain_location_code' => '',
            'said_rod' => '',
            'traction' => '',
            'source' => '',
            'line_name' => '',
            'crossroad_number' => '',
            'open_direction' => '',
            'crossroad_type' => '',
            'point_switch_group_type' => '',
            'extrusion_protect' => '',
        ]);
    }

    /**
     * 生成上传excel错误报告
     * @param array $excel_errors
     * @param string $filename
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function _makeErrorExcel(array $excel_errors, string $filename)
    {
        ExcelWriteHelper::save(
            function ($excel) use ($excel_errors) {
                $excel_error_row = 2;
                $excel->setActiveSheetIndex(0);
                $current_sheet = $excel->getActiveSheet();

                // 首行数据
                $first_row_data = [
                    ['context' => '位置', 'color' => 'black', 'width' => 20],
                    ['context' => '错误项', 'color' => 'black', 'width' => 20],
                    ['context' => '错误值', 'color' => 'black', 'width' => 20],
                    ['context' => '错误说明', 'color' => 'black', 'width' => 20],
                ];

                // 填充首行数据
                foreach ($first_row_data as $col => $second_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                    $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                foreach ($excel_errors as $excel_error) {
                    foreach ($excel_error as $col => $excel_err) {
                        $current_sheet->setCellValueExplicit("A{$excel_error_row}", "第{$excel_err['row']}行 {$col}列");
                        $current_sheet->setCellValueExplicit("B{$excel_error_row}", $excel_err['name']);
                        $current_sheet->setCellValueExplicit("C{$excel_error_row}", $excel_err['value']);
                        $current_sheet->setCellValueExplicit("D{$excel_error_row}", $excel_err['error_message']);
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(30);
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(30);
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(30);
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(50);
                        $excel_error_row++;
                    }
                }

                return $excel;
            },
            $filename
        );
    }

    /**
     * 下载设备赋码Excel错误报告
     * @param string $sn
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadCreateDeviceErrorExcel(string $sn)
    {
        try {
            $v250_task_order = V250TaskOrder::with([])->where('serial_number', $sn)->firstOrFail();

            $filename = storage_path(request('path'));
            if (!file_exists($filename)) return back()->with('danger', '文件不存在');

            $v250_task_order->fill(['is_upload_create_device_excel_error' => false])->saveOrFail();
            return response()->download($filename, '上传设备赋码错误报告.xls');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 下载上传新站赋码Excel模板
     * @param WorkArea $work_area
     * @return \Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function downloadUploadCreateDeviceExcelTemplate(WorkArea $work_area)
    {
        switch ($work_area->type) {
            case 'pointSwitch':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        // 整机数据 A~Y
                        $current_sheet->setCellValueExplicit('A1', '整机');
                        $current_sheet->getStyle('A1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('A1:Y1');
                        $current_sheet->getStyle('A1:Y1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 电机 Z~AF
                        $current_sheet->setCellValueExplicit('Z1', '电机');
                        $current_sheet->getStyle('Z1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('Z1:AF1');
                        $current_sheet->getStyle('Z1:AF1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(左) AG~AL
                        $current_sheet->setCellValueExplicit('AG1', '移位接触器(左)');
                        $current_sheet->getStyle('AG1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AG1:AK1');
                        $current_sheet->getStyle('AG1:AK1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(右) AL~AR
                        $current_sheet->setCellValueExplicit('AL1', '移位接触器(右)');
                        $current_sheet->getStyle('AL1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AL1:AR1');
                        $current_sheet->getStyle('AL1:AR1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 减速器 AS~AX
                        $current_sheet->setCellValueExplicit('AS1', '减速器');
                        $current_sheet->getStyle('AS1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AS1:AX1');
                        $current_sheet->getStyle('AS1:AX1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 油泵 AY~BD
                        $current_sheet->setCellValueExplicit('AY1', '油泵');
                        $current_sheet->getStyle('AY1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AY1:BD1');
                        $current_sheet->getStyle('AY1:BD1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 自动开闭器 BE~BJ
                        $current_sheet->setCellValueExplicit('BE1', '自动开闭器');
                        $current_sheet->getStyle('BE1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BE1:BJ1');
                        $current_sheet->getStyle('BE1:BJ1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 摩擦连接器 BK~BP
                        $current_sheet->setCellValueExplicit('BK1', '摩擦连接器');
                        $current_sheet->getStyle('BJK')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BK1:BP1');
                        $current_sheet->getStyle('BK1:BP1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

                        // 首行数据2
                        $first_row_data = [
                            // 整机数据 A~Z
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],  // 所编号A
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],  // 种类B
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],  // 类型C
                            ['context' => '型号*', 'color' => 'red', 'width' => 20],  // 型号D
                            ['context' => '状态*(上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],  // 状态E
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 厂编号F
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 厂家G
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 生产日期H
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // 车站I
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // 道岔号G
                            ['context' => '出所日期', 'color' => 'black', 'width' => 20],  // 出所日期K
                            ['context' => '上道日期', 'color' => 'black', 'width' => 20],  // 上道日期L
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],  // 检测/检修人M
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 检测/检修时间N
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],  // 验收人O
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 验收时间P
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],  // 抽验人Q
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 抽验时间R
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 寿命S
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],  // 周期修年T
                            ['context' => '开向(左、右)', 'color' => 'black', 'width' => 20],  // 开向U
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // 线制V
                            ['context' => '表示杆特征', 'color' => 'black', 'width' => 20],  // 表示杆特征W
                            ['context' => '道岔类型', 'color' => 'black', 'width' => 20],  // 道岔类型X
                            ['context' => '防挤压保护罩(是、否)', 'color' => 'black', 'width' => 20],  // 防挤压保护罩Y
                            ['context' => '牵引', 'color' => 'black', 'width' => 20],  // 牵引Z
                            // 电机 AA~AG
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 电机 所编号AA
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 电机 厂家AB
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 电机 厂编号AC
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 电机 型号AD
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 电机 生产日期AE
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 电机 寿命AG
                            // 移位接触器(左) AG~AL
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 所编号AG
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 厂家AH
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 厂编号AI
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 型号AJ
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 生产日期AK
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 移位接触器(左) 寿命AL
                            // 移位接触器(右) AM~AR
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 所编号AM
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 厂家AN
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 厂编号AO
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 型号AP
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 生产日期AQ
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 移位接触器(右) 寿命AR
                            // 减速器 AS~AX
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 减速器 所编号AS
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 减速器 厂家AT
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 减速器 厂编号AU
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 减速器 型号AV
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 减速器 生产日期AW
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 减速器 寿命AX
                            // 油泵 AY~BD
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 油泵 所编号AY
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 油泵 厂家AZ
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 油泵 厂编号BA
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 油泵 型号BB
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 油泵 生产日期BC
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 油泵 寿命BD
                            // 自动开闭器 BE~BJ
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 自动开闭器 所编号BE
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 自动开闭器 厂家BF
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 自动开闭器 厂编号BG
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 自动开闭器 型号BH
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 自动开闭器 生产日期BI
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 自动开闭器 寿命BJ
                            // 摩擦连接器 BK~BP
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // 摩擦连接器 所编号BK
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 摩擦连接器 厂家BL
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 摩擦连接器 厂编号BM
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // 摩擦连接器 型号BN
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 摩擦连接器 生产日期BO
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 摩擦连接器 寿命BP
                        ];
                        // 填充首行数据2
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            // 整机数据 A~Z
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '待修', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // R
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // S
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // T
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // W
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // X
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '', 'color' => 'black', 'width' => 20],  // Z
                            // 电机 AA~AG
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AD
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AF
                            // 移位接触器(左) AG~AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            // 移位接触器(右) AM~AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            // 减速器 AS~AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            // 油泵 AY~BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            // 自动开闭器 BE~BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            // 摩擦连接器 BK~BP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BP
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            // 整机数据 A~Z
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '待修', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // R
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // S
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // T
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // W
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // X
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '', 'color' => 'black', 'width' => 20],  // Z
                            // 电机 AA~AG
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AD
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AF
                            // 移位接触器(左) AG~AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            // 移位接触器(右) AM~AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            // 减速器 AS~AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            // 油泵 AY~BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            // 自动开闭器 BE~BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            // 摩擦连接器 BK~BP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BP
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                            $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第四行数据
                        $fourth_row_data = [
                            // 整机 A~Y
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '待修', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-02-01', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // R
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // S
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // T
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // W
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // X
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '', 'color' => 'black', 'width' => 20],  // Z
                            // 电机 Z~AE
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AD
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AF
                            // 移位接触器(左) AF~AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            // 移位接触器(右) AL~AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            // 减速器 AR~AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            // 油泵 AX~BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            // 自动开闭器 BD~BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            // 摩擦连接器 BJ~BO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BP
                        ];
                        // 填充第四行数据
                        foreach ($fourth_row_data as $col => $fourth_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}5", $context);
                            $current_sheet->getStyle("{$col_for_excel}5")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "赋码模板(转辙机)",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            default:
            case 'relay':
            case 'synthesize':
            case 'powerSupplyPanel':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],  // 所编号A
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],  // 种类B
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],  // 类型C
                            ['context' => '型号(设备不填此项)*', 'color' => 'red', 'width' => 20],  // 型号D
                            ['context' => '状态*(上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],  // 状态E
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // 厂编号F
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // 厂家G
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // 生产日期H
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // 车站I
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],  // 组合位置J
                            ['context' => '出所日期', 'color' => 'black', 'width' => 20],  // 出所日期K
                            ['context' => '上道日期', 'color' => 'black', 'width' => 20],  // 上道日期L
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],  // 检测/检修人M
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 检测/检修时间N
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],  // 验收人O
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 验收时间P
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],  // 抽验人Q
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // 抽验时间R
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // 寿命年S
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],  // 周期修年T
                            ['context' => '用途', 'color' => 'black', 'width' => 20],  // 用途U
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // 道岔号V
                            ['context' => '开向', 'color' => 'black', 'width' => 20],  // 开向W
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // 线制X
                        ];
                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            ['context' => '20210316001', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '继电器', 'color' => 'black', 'width' => 20],  // B
                            ['context' => '无极继电器', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'JWXC-1000', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '待修', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '2021-02-01', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2021-02-01', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // R
                            ['context' => '15', 'color' => 'black', 'width' => 20],  // S
                            ['context' => 0, 'color' => 'black', 'width' => 25],  // T
                            ['context' => '201# J4', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '201#', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // W
                            ['context' => 'J4', 'color' => 'black', 'width' => 20],  // X
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'JM', 'color' => 'black', 'width' => 20],  // C
                            ['context' => '', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '待修', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '2021-02-01', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2021-02-01', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // R
                            ['context' => '15', 'color' => 'black', 'width' => 20],  // S
                            ['context' => 0, 'color' => 'black', 'width' => 25],  // T
                            ['context' => '201# J4', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '201#', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // W
                            ['context' => 'J4', 'color' => 'black', 'width' => 20],  // X
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "赋码模板(继电器、综合、电源屏)",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
        }
        return null;
    }

    /**
     * 下载上传批量修改Excel模板
     * @param WorkArea $work_area
     * @return \Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function downloadUploadEditDeviceExcelTemplate(WorkArea $work_area)
    {
        switch ($work_area->type) {
            case 'pointSwitch':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            // 整机 A~N
                            ['context' => '唯一编号(二选一)*', 'color' => 'red', 'width' => 20],  // A
                            ['context' => '所编号(二选一)*', 'color' => 'red', 'width' => 20],  // B
                            ['context' => '厂编号', 'color' => 'red', 'width' => 30],  // C
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // E
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '出所日期', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '开向(左、右)', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '表示杆特征', 'color' => 'black', 'width' => 20],  // L
                            ['context' => '道岔类型', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '防挤压保护罩(是、否)', 'color' => 'black', 'width' => 20],  // N
                        ];
                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            // 整机 A~P
                            ['context' => 'S0301B04912345', 'color' => 'black', 'width' => 30],  // 唯一编号 A
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // 所编号 B
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // 厂编号 C
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // 厂家 D
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // 生产日期 E
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // 寿命 F
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // 车站 G
                            ['context' => '202#', 'color' => 'black', 'width' => 20],  // 道岔号 H
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // 出所日期 I
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // 开向 G
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // 线制 K
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // 表示干特征 L
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // 道岔类型 M
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // 防挤压保护罩 N
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "编辑模板(转辙机)",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            default:
            case 'relay':
            case 'synthesize':
            case 'powerSupplyPanel':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '唯一编号(二选一)*', 'color' => 'red', 'width' => 20],  // A
                            ['context' => '所编号(二选一)*', 'color' => 'red', 'width' => 30],  // B
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // C
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // E
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '出所日期', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '开向', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // L
                        ];
                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            ['context' => 'Q010203B0491234567H', 'color' => 'black', 'width' => 30],  // A
                            ['context' => '20210301001', 'color' => 'black', 'width' => 20],  // B
                            ['context' => '1234567', 'color' => 'black', 'width' => 20],  // C
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // E
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // F
                            ['context' => 'Q01-02-03-04', 'color' => 'black', 'width' => 20],  // G
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // H
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '201#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // K
                            ['context' => 'J4', 'color' => 'black', 'width' => 20],  // L
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "编辑模板(继电器、综合、电源屏)",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
        }
        return null;
    }

    /**
     * 上传设备赋码Excel
     * @param Request $request
     * @param string $work_area_type
     * @param string $work_area_unique_code
     * @return \Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws ExcelInException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \Throwable
     */
    final public function uploadCreateDevice(Request $request, string $work_area_type, string $work_area_unique_code)
    {
        $excel_errors = [];
        $statuses = [
            '上道' => 'INSTALLED',
            '备品' => 'INSTALLING',
            '成品' => 'FIXED',
            '待修' => 'FIXING',
        ];

        switch ($work_area_type) {
            case 'pointSwitch':
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(3)
                    ->withSheetIndex(0);
                $current_row = 3;

                // 转辙机工区
                // 所编号、种类*、类型*、型号*、状态*(待修、上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、出所日期、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)、开向(左、右)、线制、表示杆特征、道岔类型、防挤压保护罩(是、否)、牵引
                // 电机：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 移位接触器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 油泵：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 自动开闭器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 摩擦减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                $new_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;

                    try {
                        list(
                            $om_serial_number,  // 所编号A
                            $om_category_name,  // 种类B
                            $om_entire_model_name,  // 类型C
                            $om_sub_model_name,  // 型号D
                            $om_status_mame,  // 状态E
                            $o_factory_device_code,  // 厂编号F
                            $om_factory_name,  // 厂家G
                            $o_made_at,  // 生产日期H
                            $o_station_name,  // 车站I
                            $o_maintain_location_code,  // 道岔号G
                            $o_last_out_at,  // 出所日期K
                            $o_last_installed_time,  // 上道日期L
                            $o_fixer_name,  // 检修人M
                            $o_fixed_at,  // 检修时间N
                            $o_checker_name,  // 验收人O
                            $o_checked_at,  // 验收时间P
                            $o_spot_checker_name,  // 抽验人Q
                            $o_spot_checked_at,  // 抽验时间R
                            $o_life_year,  // 寿命S
                            $o_fix_cycle_year,  // 周期修年T
                            $o_open_direction,  // 开向U
                            $o_line_name,  // 线制V
                            $o_said_rod,  // 表示杆特征W
                            $o_crossroad_type,  // 道岔类型X
                            $o_extrusion_protect,  // 防挤压保护罩Y
                            $o_traction,  // 牵引Z
                            $dj_serial_number,  // 电机 所编号AA
                            $dj_factory_name,  // 电机 厂家AB
                            $dj_factory_device_code,  // 电机 厂编号AC
                            $dj_model_name,  // 电机 型号AD
                            $dj_made_at,  // 电机 生产日期AE
                            $dj_life_year,  // 电机 寿命AG
                            $ywjcq_serial_number_l,  // 移位接触器(左) 所编号AG
                            $ywjcq_factory_name_l,  // 移位接触器(左) 厂家AH
                            $ywjcq_factory_device_code_l,  // 移位接触器(左) 厂编号AI
                            $ywjcq_model_name_l,  // 移位接触器(左) 型号AJ
                            $ywjcq_made_at_l,  // 移位接触器(左) 生产日期AK
                            $ywjcq_life_year_l,  // 移位接触器(左) 寿命AL
                            $ywjcq_serial_number_r,  // 移位接触器(右) 所编号AM
                            $ywjcq_factory_name_r,  // 移位接触器(右) 厂家AN
                            $ywjcq_factory_device_code_r,  // 移位接触器(右) 厂编号AO
                            $ywjcq_model_name_r,  // 移位接触器(右) 型号AP
                            $ywjcq_made_at_r,  // 移位接触器(右) 生产日期AQ
                            $ywjcq_life_year_r, // 移位接触器(右) 寿命AR
                            $jsq_serial_number,  // 减速器 所编号AS
                            $jsq_factory_name,  // 减速器 厂家AT
                            $jsq_factory_device_code,  // 减速器 厂编号AU
                            $jsq_model_name,  // 减速器 型号AV
                            $jsq_made_at,  // 减速器 生产日期AW
                            $jsq_life_year,  // 减速器 寿命AX
                            $yb_serial_number,  // 油泵 所编号AY
                            $yb_factory_name,  // 油泵 厂家AZ
                            $yb_factory_device_code,  // 油泵 厂编号BA
                            $yb_model_name,  // 油泵 型号BB
                            $yb_made_at,  // 油泵 生产日期BC
                            $yb_life_year, // 油泵 寿命BD
                            $zdkbq_serial_number,  // 自动开闭器 所编号BE
                            $zdkbq_factory_name,  // 自动开闭器 厂家BF
                            $zdkbq_factory_device_code,  // 自动开闭器 厂编号BG
                            $zdkbq_model_name,  // 自动开闭器 型号BH
                            $zdkbq_made_at,  // 自动开闭器 生产日期BI
                            $zdkbq_life_year,  // 自动开闭器 寿命BJ
                            $mcljq_serial_number,  // 摩擦连接器 所编号BK
                            $mcljq_factory_name,  // 摩擦连接器 厂家BL
                            $mcljq_factory_device_code,  // 摩擦连接器 厂编号BM
                            $mcljq_model_name,  // 摩擦连接器 型号BN
                            $mcljq_made_at,  // 摩擦连接器 生产日期BO
                            $mcljq_life_year  // 摩擦连接器 寿命BP
                            ) = $row_datum;
                    } catch (Exception $e) {
                        $pattern = '/Undefined offset: /';
                        $offset = preg_replace($pattern, '', $e->getMessage());
                        $column_name = ExcelWriteHelper::int2Excel($offset);
                        throw new ExcelInException("读取：{$column_name}列失败。");
                    }

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    // if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                    // 验证型号
                    if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                    $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    if(!$sm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                    // $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    // if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                    // if (!$sm && $pm) $sm = $pm;
                    // 验证厂家
                    $om_factory_name = $om_factory_name ? trim($om_factory_name) : '';
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");

                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：{$om_status_mame}错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d', strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (\Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        // $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证出所日期
                    if ($o_last_out_at) {
                        try {
                            $o_last_out_at = date('Y-m-d', strtotime(ExcelWriteHelper::getExcelDate($o_last_out_at)));
                        } catch (\Exception $e) {
                            $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, $e->getMessage());
                            $o_last_out_at = null;
                        }
                    } else {
                        $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, '没有填写出所日期');
                        $o_last_out_at = null;
                    }

                    // 验证上道日期
                    if ($o_last_installed_time) {
                        try {
                            $o_last_installed_time = strtotime(ExcelWriteHelper::getExcelDate($o_last_out_at));
                        } catch (\Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '上道日期', $o_last_installed_time, $e->getMessage());
                            $o_last_installed_time = null;
                        }
                    } else {
                        $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '上道日期', $o_last_installed_time, '没有填写上道日期');
                        $o_last_installed_time = null;
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (\Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    }

                    // 验证验收时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (\Exception $e) {
                            $excel_error['P'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (\Exception $e) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $o_life_year = 0;
                            $scraping_at = null;
                        } else {
                            $o_life_year = intval($o_life_year);
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $o_life_year = 0.;
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_fix_cycle_year)) {
                        if ($o_fix_cycle_year < 0) {
                            $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_fix_cycle_year = 0;
                        }
                    } else {
                        $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_fix_cycle_year = 0;
                    }

                    // 计算下次周期修时间
                    $next_fixing_time = 0;
                    $next_fixing_day = null;
                    $next_fixing_month = null;
                    if (($em->fix_cycle_value > 0 || $o_fix_cycle_year > 0) && $o_last_out_at) {
                        $next_fixing_time = Carbon::parse($o_last_out_at)->addYears($o_fix_cycle_year > 0 ? $o_fix_cycle_year : $em->fix_cycle_value)->timestamp;
                        $next_fixing_day = date('Y-m-d', $next_fixing_time);
                        $next_fixing_month = date('Y-m-01', $next_fixing_time);
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['U'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右']))
                            $excel_error['U'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向建议填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['V'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }

                    // 验证表示杆特征
                    if (!$o_said_rod) {
                        $excel_error['W'] = ExcelWriteHelper::makeErrorResult($current_row, '表示杆特征', $o_said_rod, '表示杆特征未填写');
                        $o_said_rod = '';
                    }

                    // 验证道岔类型
                    if (!$o_crossroad_type) {
                        $excel_error['X'] = ExcelWriteHelper::makeErrorResult($current_row, '道岔类型', $o_crossroad_type, '道岔类型未填写');
                        $o_crossroad_type = '';
                    }

                    // 验证防挤压保护罩
                    if ($o_extrusion_protect) {
                        if (!in_array($o_extrusion_protect, ['是', '否'])) $excel_error['Y'] = ExcelWriteHelper::makeErrorResult($current_row, '防挤压保护罩(是、否)', $o_extrusion_protect, '只能填写：是、否');
                        if ($o_extrusion_protect == '是') {
                            $o_extrusion_protect = true;
                        } else {
                            $o_extrusion_protect = false;
                        }
                    } else {
                        $o_extrusion_protect = false;
                    }

                    // 验证牵引
                    if (!$o_traction) {
                        $excel_error['Z'] = ExcelWriteHelper::makeErrorResult($current_row, '牵引', $o_crossroad_type, '牵引未填写');
                        $o_traction = '';
                    }

                    /**
                     * 电机 所编号AA
                     * 电机 厂家AB
                     * 电机 厂编号AC
                     * 电机 型号AD
                     * 电机 生产日期AE
                     * 电机 寿命AG
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_dj = function () use ($current_row, &$dj_serial_number, &$dj_factory_name, &$dj_factory_device_code, &$dj_model_name, &$dj_made_at, &$dj_life_year, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($dj_factory_name) {
                            $dj_factory = Factory::with([])->where('name', $dj_factory_name)->first();
                            if (!$dj_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(电机)：{$dj_factory_name}");
                            // if (!$dj_factory) {
                            //     $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, "没有找到厂家：{$dj_factory_name}");
                            // }
                        } else {
                            $excel_error['AB'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, '没有填写电机厂家');
                            $dj_factory_name = '';
                        }
                        // 验证厂编号
                        if (!$dj_factory_device_code) {
                            $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂编号', $dj_factory_device_code, '没有填写电机编号');
                            $dj_factory_device_code = '';
                        }
                        // 验证型号
                        $dj_model = null;
                        if ($dj_serial_number && $dj_model_name) {
                            $dj_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "电机")
                                ->where("sm.name", $dj_model_name)
                                ->first();
                            if (!$dj_model) throw new ExcelInException("第{$current_row}行，没有找到电机型号：{$dj_model_name}");
                        }
                        // 验证所编号
                        if ($dj_serial_number && $dj_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $dj_serial_number)->where('device_model_unique_code', $dj_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，电机：{$dj_serial_number}所编号重复");
                        }
                        // 验证生产日期
                        if ($dj_made_at) {
                            try {
                                $dj_made_at = ExcelWriteHelper::getExcelDate($dj_made_at);
                            } catch (\Exception $e) {
                                $excel_error['AE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, $e->getMessage());
                                $dj_made_at = null;
                            }
                        } else {
                            $excel_error['AE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, '没有填写电机生产日期');
                            $dj_made_at = null;
                        }
                        // 验证寿命
                        $dj_scraping_at = null;
                        if (is_numeric($dj_life_year)) {
                            if ($dj_life_year < 0) {
                                $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                                $dj_scraping_at = null;
                            } else {
                                $dj_scraping_at = Carbon::parse($dj_made_at)->addYears($dj_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                            $dj_scraping_at = null;
                        }

                        return ($dj_serial_number && $dj_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $dj_factory_name,
                            'factory_device_code' => $dj_factory_device_code,
                            'identity_code' => '',
                            'category_unique_code' => $dj_model->category_unique_code,
                            'category_name' => "道岔专用",
                            'entire_model_unique_code' => $dj_model->unique_code,
                            'model_unique_code' => $dj_model->unique_code,
                            'model_name' => $dj_model->name,
                            'made_at' => $dj_made_at,
                            'scarping_at' => $dj_scraping_at,
                            'serial_number' => $dj_serial_number,
                            'work_area_unique_code' => $work_area_unique_code,
                        ] : null;
                    };

                    /**
                     * 移位接触器(左) 所编号AG
                     * 移位接触器(左) 厂家AH
                     * 移位接触器(左) 厂编号AI
                     * 移位接触器(左) 型号AJ
                     * 移位接触器(左) 生产日期AK
                     * 移位接触器(左) 寿命AL
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_ywjcq_l = function () use ($current_row, &$ywjcq_serial_number_l, &$ywjcq_factory_name_l, &$ywjcq_factory_device_code_l, &$ywjcq_model_name_l, &$ywjcq_made_at_l, &$ywjcq_life_year_l, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($ywjcq_factory_name_l) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_l)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(左))：{$ywjcq_factory_name_l}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AH'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂家', $ywjcq_factory_name_l, '没有填写移位接触器(左)厂家');
                            $ywjcq_factory_name_l = '';
                        }
                        // 验证厂编号
                        if (!$ywjcq_factory_device_code_l) {
                            $excel_error['AI'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂编号', $ywjcq_factory_device_code_l, '没有填写移位接触器(左)厂编号');
                            $ywjcq_factory_device_code_l = '';
                        }
                        // 验证型号
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $ywjcq_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "移位接触器")
                                ->where("sm.name", $ywjcq_model_name_l)
                                ->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(左)型号：{$ywjcq_model_name_l}");
                        }
                        // 验证所编号
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_l)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，移位接触器(左)：{$ywjcq_serial_number_l}所编号重复");
                        }
                        // 验证生产日期
                        if ($ywjcq_made_at_l) {
                            try {
                                $ywjcq_made_at_l = ExcelWriteHelper::getExcelDate($ywjcq_made_at_l);
                            } catch (\Exception $e) {
                                $excel_error['AK'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, $e->getMessage());
                                $ywjcq_made_at_l = null;
                            }
                        } else {
                            $excel_error['AK'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, '没有填写移位接触器(左)生产日期');
                            $ywjcq_made_at_l = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命
                        if (is_numeric($ywjcq_life_year_l)) {
                            if ($ywjcq_life_year_l < 0) {
                                $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_l)->addYears($ywjcq_life_year_l)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_l && $ywjcq_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $ywjcq_factory_name_l,
                            'factory_device_code' => $ywjcq_factory_device_code_l,
                            'identity_code' => '',
                            'category_unique_code' => $ywjcq_model->category_unique_code,
                            "category_name" => "道岔专用",
                            'entire_model_unique_code' => $ywjcq_model->unique_code,
                            'model_unique_code' => $ywjcq_model->unique_code,
                            'model_name' => $ywjcq_model->name,
                            'made_at' => $ywjcq_made_at_l,
                            'scarping_at' => $ywjcq_scraping_at,
                            'serial_number' => $ywjcq_serial_number_l,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    /**
                     * 移位接触器(右) 所编号AM
                     * 移位接触器(右) 厂家AN
                     * 移位接触器(右) 厂编号AO
                     * 移位接触器(右) 型号AP
                     * 移位接触器(右) 生产日期AQ
                     * 移位接触器(右) 寿命AR
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_ywjcq_r = function () use ($current_row, &$ywjcq_serial_number_r, &$ywjcq_factory_name_r, &$ywjcq_factory_device_code_r, &$ywjcq_model_name_r, &$ywjcq_made_at_r, &$ywjcq_life_year_r, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($ywjcq_factory_name_r) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_r)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(右))：{$ywjcq_factory_name_r}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AN'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂家', $ywjcq_factory_name_r, '没有填写移位接触器(右)厂家');
                            $ywjcq_factory_name_r = '';
                        }
                        // 验证厂编号
                        if (!$ywjcq_factory_device_code_r) {
                            $excel_error['AO'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂编号', $ywjcq_factory_device_code_r, '没有填写移位接触器(右)厂编号');
                            $ywjcq_factory_device_code_r = '';
                        }
                        // 验证型号
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $ywjcq_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "移位接触器")
                                ->where("sm.name", $ywjcq_model_name_r)
                                ->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(右)型号：{$ywjcq_model_name_r}");
                        }
                        // 验证所编号
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_r)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，移位接触器(右)：{$ywjcq_serial_number_r}所编号重复");
                        }
                        // 验证生产日期
                        if ($ywjcq_made_at_r) {
                            try {
                                $ywjcq_made_at_r = ExcelWriteHelper::getExcelDate($ywjcq_made_at_r);
                            } catch (\Exception $e) {
                                $excel_error['AQ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)生产日期', $ywjcq_made_at_r, $e->getMessage());
                                $ywjcq_made_at_r = null;
                            }
                        } else {
                            $excel_error['AQ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)生产日期', $ywjcq_made_at_r, '没有填写移位接触器(右)生产日期');
                            $ywjcq_made_at_r = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命
                        if (is_numeric($ywjcq_life_year_r)) {
                            if ($ywjcq_life_year_r < 0) {
                                $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_r)->addYears($ywjcq_life_year_r)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_r && $ywjcq_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $ywjcq_factory_name_r,
                            'factory_device_code' => $ywjcq_factory_device_code_r,
                            'identity_code' => '',
                            'category_unique_code' => $ywjcq_model->category_unique_code,
                            'category_name' => "道岔专用",
                            'entire_model_unique_code' => $ywjcq_model->unique_code,
                            'model_unique_code' => $ywjcq_model->unique_code,
                            'model_name' => $ywjcq_model->name,
                            'made_at' => $ywjcq_made_at_r,
                            'scarping_at' => $ywjcq_scraping_at,
                            'serial_number' => $ywjcq_serial_number_r,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    /**
                     * 减速器 所编号AS
                     * 减速器 厂家AT
                     * 减速器 厂编号AU
                     * 减速器 型号AV
                     * 减速器 生产日期AW
                     * 减速器 寿命AX
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_jsq = function () use ($current_row, &$jsq_serial_number, &$jsq_factory_name, &$jsq_factory_device_code, &$jsq_model_name, &$jsq_made_at, &$jsq_life_year, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($jsq_factory_name) {
                            $jsq_factory = Factory::with([])->where('name', $jsq_factory_name)->first();
                            if (!$jsq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(减速器)：{$jsq_factory_name}");
                            // if (!$jsq_factory) {
                            //     $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, "没有找到厂家：{$jsq_factory_name}");
                            // }
                        } else {
                            $excel_error['AT'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, '没有填写减速器厂家');
                            $jsq_factory_name = '';
                        }
                        // 验证厂编号
                        if (!$jsq_factory_device_code) {
                            $excel_error['AU'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂编号', $jsq_factory_device_code, '没有填写减速器编号');
                            $jsq_factory_device_code = '';
                        }
                        // 验证型号
                        $jsq_model = null;
                        if ($jsq_serial_number && $jsq_model_name) {
                            $jsq_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "减速器")
                                ->where("sm.name", $jsq_model_name)
                                ->first();
                            if (!$jsq_model) throw new ExcelInException("第{$current_row}行，没有找到减速器型号：{$jsq_model_name}");
                        }
                        // 验证所编号
                        if ($jsq_serial_number && $jsq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $jsq_serial_number)->where('device_model_unique_code', $jsq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，减速器：{$jsq_serial_number}所编号重复");
                        }
                        // 验证生产日期
                        if ($jsq_made_at) {
                            try {
                                $jsq_made_at = ExcelWriteHelper::getExcelDate($jsq_made_at);
                            } catch (\Exception $e) {
                                $excel_error['AW'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, $e->getMessage());
                                $jsq_made_at = null;
                            }
                        } else {
                            $excel_error['AW'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, '没有填写减速器生产日期');
                            $jsq_made_at = null;
                        }
                        $jsq_scraping_at = null;
                        // 验证寿命
                        if (is_numeric($jsq_life_year)) {
                            if ($jsq_life_year < 0) {
                                $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                                $jsq_scraping_at = null;
                            } else {
                                $jsq_scraping_at = Carbon::parse($jsq_made_at)->addYears($jsq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                            $jsq_scraping_at = null;
                        }

                        return ($jsq_serial_number && $jsq_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $jsq_factory_name,
                            'factory_device_code' => $jsq_factory_device_code,
                            'identity_code' => '',
                            'category_unique_code' => $jsq_model->category_unique_code,
                            'category_name' => '道岔专用',
                            'entire_model_unique_code' => $jsq_model->parent_unique_code,
                            'model_unique_code' => $jsq_model->unique_code,
                            'made_at' => $jsq_made_at,
                            'scarping_at' => $jsq_scraping_at,
                            'serial_number' => $jsq_serial_number,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    /**
                     * 油泵 所编号AY
                     * 油泵 厂家AZ
                     * 油泵 厂编号BA
                     * 油泵 型号BB
                     * 油泵 生产日期BC
                     * 油泵 寿命BD
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_yb = function () use ($current_row, &$yb_serial_number, &$yb_factory_name, &$yb_factory_device_code, &$yb_model_name, &$yb_made_at, &$yb_life_year, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($yb_factory_name) {
                            $yb_factory = Factory::with([])->where('name', $yb_factory_name)->first();
                            if (!$yb_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(油泵)：{$yb_factory_name}");
                            // if (!$yb_factory) {
                            //     $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, "没有找到厂家：{$yb_factory_name}");
                            // }
                        } else {
                            $excel_error['AZ'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, '没有填写油泵厂家');
                            $yb_factory_name = '';
                        }
                        // 验证厂编号
                        if (!$yb_factory_device_code) {
                            $excel_error['BA'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂编号', $yb_factory_device_code, '没有填写油泵编号');
                            $yb_factory_device_code = '';
                        }
                        // 验证型号
                        $yb_model = null;
                        if ($yb_serial_number && $yb_model_name) {
                            $yb_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "油泵")
                                ->where("sm.name", $yb_model_name)
                                ->first();
                            if (!$yb_model) throw new ExcelInException("第{$current_row}行，没有找到油泵型号：{$yb_model_name}");
                        }
                        // 验证所编号
                        if ($yb_serial_number && $yb_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $yb_serial_number)->where('device_model_unique_code', $yb_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，电机：{$yb_serial_number}所编号重复");
                        }
                        // 验证生产日期
                        if ($yb_made_at) {
                            try {
                                $yb_made_at = ExcelWriteHelper::getExcelDate($yb_made_at);
                            } catch (\Exception $e) {
                                $excel_error['BC'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, $e->getMessage());
                                $yb_made_at = null;
                            }
                        } else {
                            $excel_error['BC'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, '没有填写油泵生产日期');
                            $yb_made_at = null;
                        }
                        $yb_scraping_at = null;
                        // 验证寿命
                        if (is_numeric($yb_life_year)) {
                            if ($yb_life_year < 0) {
                                $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                                $yb_scraping_at = null;
                            } else {
                                $yb_scraping_at = Carbon::parse($yb_made_at)->addYears($yb_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                            $yb_scraping_at = null;
                        }

                        return ($yb_serial_number && $yb_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $yb_factory_name,
                            'factory_device_code' => $yb_factory_device_code,
                            'identity_code' => '',
                            'category_unique_code' => $yb_model->category_unique_code,
                            'category_name' => "道岔专用",
                            'entire_model_unique_code' => $yb_model->parent_unique_code,
                            'model_unique_code' => $yb_model->unique_code,
                            'model_name' => $yb_model->name,
                            'made_at' => $yb_made_at,
                            'scarping_at' => $yb_scraping_at,
                            'serial_number' => $yb_serial_number,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    /**
                     * 自动开闭器 所编号BE
                     * 自动开闭器 厂家BF
                     * 自动开闭器 厂编号BG
                     * 自动开闭器 型号BH
                     * 自动开闭器 生产日期BI
                     * 自动开闭器 寿命BJ
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_zdkbq = function () use ($current_row, &$zdkbq_serial_number, &$zdkbq_factory_name, &$zdkbq_factory_device_code, &$zdkbq_model_name, &$zdkbq_made_at, &$zdkbq_life_year, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($zdkbq_factory_name) {
                            $zdkbq_factory = Factory::with([])->where('name', $zdkbq_factory_name)->first();
                            if (!$zdkbq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(自动开闭器)：{$zdkbq_factory_name}");
                            // if (!$zdkbq_factory) {
                            //     $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, "没有找到厂家：{$zdkbq_factory_name}");
                            // }
                        } else {
                            $excel_error['BF'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, '没有填写自动开闭器厂家');
                            $zdkbq_factory_name = '';
                        }
                        // 验证厂编号
                        if (!$zdkbq_factory_device_code) {
                            $excel_error['BG'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂编号', $zdkbq_factory_device_code, '没有填写自动开闭器编号');
                            $zdkbq_factory_device_code = '';
                        }
                        // 验证型号
                        $zdkbq_model = null;
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $zdkbq_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "自动开闭器")
                                ->where("sm.name", $zdkbq_model_name)
                                ->first();
                            if (!$zdkbq_model) throw new ExcelInException("第{$current_row}行，没有找到自动开闭器型号：{$zdkbq_model_name}");
                        }
                        // 验证所编号
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $zdkbq_serial_number)->where('device_model_unique_code', $zdkbq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，自动开闭器：{$zdkbq_serial_number}所编号重复");
                        }
                        // 验证生产日期
                        if ($zdkbq_made_at) {
                            try {
                                $zdkbq_made_at = ExcelWriteHelper::getExcelDate($zdkbq_made_at);
                            } catch (\Exception $e) {
                                $excel_error['BI'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, $e->getMessage());
                                $zdkbq_made_at = null;
                            }
                        } else {
                            $excel_error['BI'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, '没有填写自动开闭器生产日期');
                            $zdkbq_made_at = null;
                        }
                        $zdkbq_scraping_at = null;
                        // 验证寿命
                        if (is_numeric($zdkbq_life_year)) {
                            if ($zdkbq_life_year < 0) {
                                $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                                $zdkbq_scraping_at = null;
                            } else {
                                $zdkbq_scraping_at = Carbon::parse($zdkbq_made_at)->addYears($zdkbq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                            $zdkbq_scraping_at = null;
                        }

                        return ($zdkbq_serial_number && $zdkbq_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $zdkbq_factory_name,
                            'factory_device_code' => $zdkbq_factory_device_code,
                            'identity_code' => '',
                            'category_unique_code' => $zdkbq_model->category_unique_code,
                            'category_name' => "道岔专用",
                            'entire_model_unique_code' => $zdkbq_model->unique_code,
                            'model_unique_code' => $zdkbq_model->unique_code,
                            'model_name' => $zdkbq_model->name,
                            'made_at' => $zdkbq_made_at,
                            'scarping_at' => $zdkbq_scraping_at,
                            'serial_number' => $zdkbq_serial_number,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    /**
                     * 摩擦连接器 所编号BK
                     * 摩擦连接器 厂家BL
                     * 摩擦连接器 厂编号BM
                     * 摩擦连接器 型号BN
                     * 摩擦连接器 生产日期BO
                     * 摩擦连接器 寿命BP
                     * @return array|null
                     * @throws ExcelInException
                     */
                    $check_mcljq = function () use ($current_row, &$mcljq_serial_number, &$mcljq_factory_name, &$mcljq_factory_device_code, &$mcljq_model_name, &$mcljq_made_at, &$mcljq_life_year, &$excel_error, $request, $work_area_unique_code, $status) {
                        // 验证厂家
                        if ($mcljq_factory_name) {
                            $mcljq_factory = Factory::with([])->where('name', $mcljq_factory_name)->first();
                            if (!$mcljq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(摩擦连接器)：{$mcljq_factory_name}");
                            // if (!$mcljq_factory) {
                            //     $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $mcljq_factory_name, "没有找到厂家：{$mcljq_factory_name}");
                            // }
                        } else {
                            $excel_error['BL'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂家', $mcljq_factory_name, '没有填写摩擦连接器厂家');
                            $mcljq_factory_name = '';
                        }
                        // 验证厂编号
                        if (!$mcljq_factory_device_code) {
                            $excel_error['BM'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂编号', $mcljq_factory_device_code, '没有填写摩擦连接器编号');
                            $mcljq_factory_device_code = '';
                        }
                        // 验证型号
                        $mcljq_model = null;
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $mcljq_model = DB::table("entire_models as sm")
                                ->selectRaw(join(",", ["sm.name", "sm.unique_code", "sm.parent_unique_code", "em.category_unique_code",]))
                                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                                ->whereNull("c.deleted_at")
                                ->whereNull("em.deleted_at")
                                ->whereNull("sm.deleted_at")
                                ->where("em.is_sub_model", false)
                                ->where("sm.is_sub_model", true)
                                ->where("c.name", "道岔专用")
                                ->where("em.name", "摩擦连接器")
                                ->where("sm.name", $mcljq_model_name)
                                ->first();
                            if (!$mcljq_model) throw new ExcelInException("第{$current_row}行，没有找到摩擦连接器型号：{$mcljq_model_name}");
                        }
                        // 验证所编号
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $mcljq_serial_number)->where('device_model_unique_code', $mcljq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，摩擦连接器：{$mcljq_serial_number}所编号重复");
                        }
                        // 验证生产日期
                        if ($mcljq_made_at) {
                            try {
                                $mcljq_made_at = ExcelWriteHelper::getExcelDate($mcljq_made_at);
                            } catch (\Exception $e) {
                                $excel_error['BO'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器生产日期', $mcljq_made_at, $e->getMessage());
                                $mcljq_made_at = null;
                            }
                        } else {
                            $excel_error['BO'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $mcljq_made_at, '没有填写电机生产日期');
                            $mcljq_made_at = null;
                        }
                        // 验证寿命
                        $mcljq_scraping_at = null;
                        if (is_numeric($mcljq_life_year)) {
                            if ($mcljq_life_year < 0) {
                                $excel_error['BP'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                                $mcljq_scraping_at = null;
                            } else {
                                $mcljq_scraping_at = Carbon::parse($mcljq_made_at)->addYears($mcljq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BP'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                            $mcljq_scraping_at = null;
                        }

                        return ($mcljq_serial_number && $mcljq_model) ? [
                            'entire_instance_identity_code' => '',
                            'status' => $status,
                            'factory_name' => $mcljq_factory_name,
                            'factory_device_code' => $mcljq_factory_device_code,
                            'identity_code' => '',
                            'category_unique_code' => $mcljq_model->category_unique_code,
                            'category_name' => "道岔专用",
                            'entire_model_unique_code' => $mcljq_model->unique_code,
                            'model_unique_code' => $mcljq_model->unique_code,
                            'model_name' => $mcljq_model->name,
                            'made_at' => $mcljq_made_at,
                            'scarping_at' => $mcljq_scraping_at,
                            'serial_number' => $mcljq_serial_number,
                            'work_area_unique_code' => $work_area_unique_code
                        ] : null;
                    };

                    // 写入待插入数据
                    $new_entire_instances[] = [
                        'entire_model_unique_code' => $sm->unique_code,
                        'serial_number' => $om_serial_number,
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'crossroad_number' => $o_maintain_location_code ?? '',
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'identity_code' => '',
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_fix_cycle_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'life_year' => $o_life_year,
                        'model_unique_code' => $sm->unique_code,
                        'model_name' => $sm->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                        'open_direction' => $o_open_direction,
                        'line_name' => $o_line_name,
                        'said_rod' => $o_said_rod,
                        'crossroad_type' => $o_crossroad_type,
                        'extrusion_protect' => $o_extrusion_protect,
                        'traction' => $o_traction,
                        'work_area_unique_code' => $work_area_unique_code,
                        'last_installed_time' => $o_last_installed_time,
                        'last_out_at' => $o_last_out_at,
                        'next_fixing_time' => $next_fixing_time,
                        'next_fixing_day' => $next_fixing_day,
                        'next_fixing_month' => $next_fixing_month,
                        'part_instances' => [
                            'dj' => $check_dj(),  // 电机
                            'ywjcq_l' => $check_ywjcq_l(),  // 移位接触器(左)
                            'ywjcq_r' => $check_ywjcq_r(),  // 移位接触器(右)
                            'jsq' => $check_jsq(),  // 减速器
                            'yb' => $check_yb(),  // 油泵
                            'zdkbq' => $check_zdkbq(),  // 自动开闭器
                            'mcljq' => $check_mcljq(),  // 摩擦连接器
                        ],
                    ];
                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 按照型号进行分组
                $new_entire_instances = collect($new_entire_instances)->groupBy('entire_model_unique_code')->toArray();
                // 获取设备对应型号总数
                $entire_instance_counts = EntireInstanceCount::with([])->pluck('count', 'entire_model_unique_code');

                // 赋码
                foreach ($new_entire_instances as $entire_model_unique_code => $new_entire_instance) {
                    // 整件赋码
                    list($new_entire_instance, $new_count) = CodeFacade::generateEntireInstanceIdentityCodes($entire_model_unique_code, $new_entire_instance);
                    $new_entire_instances[$entire_model_unique_code] = $new_entire_instance;
                    $entire_instance_counts[$entire_model_unique_code] = $new_count;

                    // 部件赋码
                    foreach ($new_entire_instance as $k => $ei) {
                        foreach ($ei['part_instances'] as $pk => $part_instance) {
                            $pic = $entire_instance_counts->get($part_instance['model_unique_code'], 0);
                            if ($part_instance) {
                                $new_entire_instances[$entire_model_unique_code][$k]['part_instances'][$pk]['entire_instance_identity_code'] = $new_entire_instance[$k]['identity_code'];
                                $new_entire_instances[$entire_model_unique_code][$k]['part_instances'][$pk]['identity_code'] = $part_instance['model_unique_code'] . env('ORGANIZATION_CODE') . str_pad(++$pic, 7, '0', 0) . 'H';
                                // $new_entire_instances[$entire_model_unique_code][$k]['part_instances'][$pk]['entire_instance_serial_number'] = $new_entire_instance[$k]['serial_number'];
                                $entire_instance_counts[$part_instance['model_unique_code']] = $pic;
                            }
                        }
                    }
                }

                // 写入数据库
                DB::begintransaction();
                // 生成入所单
                // $warehouse_report = WarehouseReport::with([])->create([
                //     'processor_id' => $request->get('account_id'),
                //     'processed_at' => date('Y-m-d H:i:s'),
                //     'type' => strtoupper($request->get('type')),
                //     'direction' => 'IN',
                //     'serial_number' => $new_warehouse_report_sn = CodeFacade::makeSerialNumber('IN'),
                //     'status' => 'DONE',
                //     'v250_task_order_sn' => $work_area_unique_code,
                // ]);
                // 生成设备/器材赋码记录单
                $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])->create([
                    'serial_number' => $entire_instance_excel_tagging_report_sn = EntireInstanceExcelTaggingReport::generateSerialNumber(),
                    'is_upload_create_device_excel_error' => false,
                    'work_area_type' => $work_area_type,
                    'processor_id' => session('account.id'),
                    'work_area_unique_code' => $work_area_unique_code,
                ]);

                // 添加新设备/器材
                $current_row_for_fix_workflow = 3;
                $inserted_count = 0;
                $new_entire_instances = array_collapse($new_entire_instances);
                unset($new_entire_instance);
                foreach ($new_entire_instances as $new_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $new_entire_instance['fixer_id'],
                        'fixed_at' => $new_entire_instance['fixed_at'],
                        'checker_id' => $new_entire_instance['checker_id'],
                        'checked_at' => $new_entire_instance['checked_at'],
                        'spot_checker_id' => $new_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $new_entire_instance['spot_checked_at'],
                    ];
                    unset(
                        $new_entire_instance['fixer_id'],
                        $new_entire_instance['fixed_at'],
                        $new_entire_instance['checker_id'],
                        $new_entire_instance['checked_at'],
                        $new_entire_instance['spot_checker_id'],
                        $new_entire_instance['spot_checked_at']
                    );
                    $nei = EntireInstance::with([])->create(array_except($new_entire_instance, ['part_instances']));

                    // 入所单设备
                    // $nwrei = WarehouseReportEntireInstance::with([])->create([
                    //     'warehouse_report_serial_number' => $warehouse_report->serial_number,
                    //     'entire_instance_identity_code' => $nei->identity_code,
                    // ]);

                    // 赋码单唯一编号
                    EntireInstanceExcelTaggingIdentityCode::with([])->create([
                        'entire_instance_excel_tagging_report_sn' => $entire_instance_excel_tagging_report->serial_number,
                        'entire_instance_identity_code' => $new_entire_instance['identity_code'],
                    ]);

                    // 整件赋码日志
                    EntireInstanceLogFacade::makeOne('赋码', $new_entire_instance['identity_code'], 0, '', $o_made_at ? ($scraping_at ? "出厂日期：{$o_made_at}；到期日期：{$scraping_at}；" : "出厂日期：{$o_made_at}；") : '');  // 赋码
                    // 部件赋码日志
                    foreach ($new_entire_instance['part_instances'] as $part_instance)
                        if ($part_instance) {
                            EntireInstanceLogFacade::makeOne(
                                '赋码',
                                $part_instance['identity_code'],
                                0,
                                '',
                                join("；", [
                                    $part_instance["made_at"] ? "出厂日期：" . Carbon::parse($part_instance['made_at'])->format("Y-m-d") : "",
                                    ($part_instance["made_at"] && $part_instance["scarping_at"]) ? "到期日期：" . Carbon::parse($part_instance["scarping_at"])->format("Y-m-d") : "",
                                ])
                            );
                        }

                    $inserted_count++;

                    // 如果有检修人和验收人生成检修单
                    if (
                        $fix_workflow_datum['fixer_id']
                        || ($fix_workflow_datum['checker_id']
                            && $fix_workflow_datum['checked_at'])
                    ) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', $request->get('workAreaUniqueCode'))
                            ->first();
                        if (!@$fix_workflow_datum['fixed_at'])
                            throw new ExcelInException("第{$current_row_for_fix_workflow}行，设备：{$nei->serial_number}填写了检修人或验收人，必须填写检修时间");
                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => '',
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $fix_workflow_datum['fixed_at'],
                            'status' => @$fix_workflow_datum['checker_id'] ? '1' : '0',
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }

                    // 添加部件
                    foreach ($new_entire_instance['part_instances'] as $part_instance)
                        if ($part_instance) EntireInstance::with([])->create($part_instance);

                    $current_row_for_fix_workflow++;
                }

                // 更新该型号下的所有设备/器材总数
                EntireInstanceCount::updates($entire_instance_counts->toArray());
                DB::commit();

                if (!empty($excel_errors)) {
                    $root_dir = storage_path('entireInstanceTagging/upload/errorExcels/createDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$entire_instance_excel_tagging_report_sn}");

                    EntireInstanceExcelTaggingReport::with([])
                        ->where('serial_number', $entire_instance_excel_tagging_report_sn)
                        ->update(['is_upload_create_device_excel_error' => true]);

                    return redirect("/entire/tagging/{$entire_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                            'page' => $request->get('page'),
                            'type' => $request->get('type'),
                        ]))
                        ->with('warning', "设备赋码：{$inserted_count}条。" . '其中' . count($excel_errors) . '行有错误。');
                }

                return redirect("/entire/tagging/{$entire_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]))
                    ->with('success', "设备赋码：{$inserted_count}条。");
            default:
            case 'relay':
            case 'synthesize':
            case 'powerSupplyPanel':
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(2)
                    ->withSheetIndex(0);
                $current_row = 2;

                // 继电器
                // 所编号、种类*、类型*、型号*、状态*(上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、出所日期、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)、开向(左、右)、线制、表示杆特征、道岔类型、转辙机组合类型、防挤压保护罩(是、否)、牵引
                $new_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    // 如果整行都没有数据则跳过
                    if (empty(array_filter($row_datum, function ($value) {
                        return !empty($value);
                    }))) continue;

                    try {
                        list(
                            $om_serial_number,  // 所编号A
                            $om_category_name,  // 种类B
                            $om_entire_model_name,  // 类型C
                            $om_sub_model_name,  // 型号D
                            $om_status_mame,  // 状态E
                            $o_factory_device_code,  // 厂编号F
                            $om_factory_name,  // 厂家G
                            $o_made_at,  // 生产日期H
                            $o_station_name,  // 车站I
                            $o_maintain_location_code,  // 组合位置J
                            $o_last_out_at,  // 出所日期K
                            $o_last_installed_time, // 上道日期L
                            $o_fixer_name,  // 检测/检修人M
                            $o_fixed_at, // 检测/检修时间N
                            $o_checker_name,  // 验收人O
                            $o_checked_at,  // 验收时间P
                            $o_spot_checker_name,  // 抽验人Q
                            $o_spot_checked_at,  // 抽验时间R
                            $o_life_year,  // 寿命年S
                            $o_fix_cycle_year,  // 周期修年T
                            $o_bind_crossroad_number,  // 用途U
                            $o_crossroad_number,  // 道岔号V
                            $o_open_direction,  // 开向W
                            $o_line_name  // 线制X
                            ) = $row_datum;
                    } catch (Exception $e) {
                        $pattern = '/Undefined offset: /';
                        $offset = preg_replace($pattern, '', $e->getMessage());
                        $column_name = ExcelWriteHelper::int2Excel($offset);
                        throw new ExcelInException("读取：{$column_name}列失败。");
                    }

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    // @fixme: 暂时去掉
                    // if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");

                    // 验证厂家
                    $om_factory_name = $om_factory_name ? trim($om_factory_name) : '';
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");

                    // 判断是否是设备
                    if (substr($em->unique_code, 0, 1) == 'S') {
                        // 设备
                        $is_device = false;
                    } else {
                        // 器材
                        $is_device = true;
                        // 验证型号
                        if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                        $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        if (!$sm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                        // 验证所编号是否重复
                        if ($om_serial_number) {
                            if (EntireInstance::with([])->where('serial_number', $om_serial_number)->where('model_unique_code', $sm->unique_code)->exists())
                                throw new ExcelInException("第{$current_row}，所编号重复（{$om_serial_number}）。型号：{$category->name} > {$em->name} > {$om_sub_model_name}");
                        }
                        // $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        // if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                        // if (!$sm && $pm) $sm = $pm;
                    }

                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：“{$om_status_mame}”错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d', strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (\Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        // $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证出所日期
                    if ($o_last_out_at) {
                        try {
                            $o_last_out_at = strtotime(ExcelWriteHelper::getExcelDate($o_last_out_at));
                            $o_last_out_at = date('Y-m-d', $o_last_out_at);
                        } catch (\Exception $e) {
                            $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, $e->getMessage());
                            $o_last_out_at = 0;
                            $o_last_out_at = null;
                        }
                    } else {
                        $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, '没有填写出所日期');
                        $o_last_out_at = 0;
                        $o_last_out_at = null;
                    }

                    // 验证上道日期
                    if ($o_last_installed_time) {
                        try {
                            $o_last_installed_time = strtotime(ExcelWriteHelper::getExcelDate($o_last_installed_time));
                        } catch (\Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '上道日期', $o_last_installed_time, $e->getMessage());
                            $o_last_installed_time = null;
                        }
                    } else {
                        $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '上道日期', $o_last_installed_time, '没有填写上道日期');
                        $o_last_installed_time = null;
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (\Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    }

                    // 验证检修时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (\Exception $e) {
                            $excel_error['P'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (\Exception $e) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $o_life_year = 0;
                            $scraping_at = null;
                        } else {
                            $o_life_year = intval($o_life_year);
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $o_life_year = 0;
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_fix_cycle_year)) {
                        if ($o_fix_cycle_year < 0) {
                            $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_fix_cycle_year = 0;
                        }
                    } else {
                        $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_fix_cycle_year = 0;
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['W'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右']))
                            $excel_error['W'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向建议填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['X'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }

                    // 计算下次周期修时间
                    $next_fixing_time = 0;
                    $next_fixing_day = null;
                    $next_fixing_month = null;
                    if (($em->fix_cycle_value > 0 || $o_fix_cycle_year > 0) && $o_last_out_at) {
                        $next_fixing_time = Carbon::parse($o_last_out_at)->addYears($o_fix_cycle_year > 0 ? $o_fix_cycle_year : $em->fix_cycle_value)->timestamp;
                        $next_fixing_day = date('Y-m-d', $next_fixing_time);
                        $next_fixing_month = date('Y-m-01', $next_fixing_time);
                    }

                    // 写入待插入数据
                    $new_entire_instances[] = [
                        'entire_model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'serial_number' => $om_serial_number ?? '',
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'maintain_location_code' => $o_maintain_location_code,
                        'factory_name' => $om_factory_name ?? '',
                        'factory_device_code' => $o_factory_device_code,
                        'identity_code' => '',
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_fix_cycle_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'life_year' => $o_life_year,
                        'model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'model_name' => $is_device ? $sm->name : $em->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'work_area_unique_code' => $work_area_unique_code,
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                        'bind_crossroad_number' => $o_bind_crossroad_number ?? '',
                        'last_out_at' => $o_last_out_at,
                        'last_installed_time' => $o_last_installed_time,
                        'crossroad_number' => $o_crossroad_number ?? '',
                        'open_direction' => $o_open_direction ?? '',
                        'line_name' => $o_line_name ?? '',
                        'next_fixing_time' => $next_fixing_time,
                        'next_fixing_day' => $next_fixing_day,
                        'next_fixing_month' => $next_fixing_month,
                    ];
                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 按照型号进行分组
                $new_entire_instances = collect($new_entire_instances)->groupBy('model_unique_code')->toArray();
                // 获取设备对应型号总数
                $entire_instance_counts = EntireInstanceCount::with([])->whereIn('entire_model_unique_code', array_keys($new_entire_instances))->pluck('count', 'entire_model_unique_code');

                // 赋码
                foreach ($new_entire_instances as $entire_model_unique_code => $new_entire_instance) {
                    list($new_entire_instance, $new_count) = CodeFacade::generateEntireInstanceIdentityCodes($entire_model_unique_code, $new_entire_instance);
                    $new_entire_instances[$entire_model_unique_code] = $new_entire_instance;
                    $entire_instance_counts[$entire_model_unique_code] = $new_count;
                }

                // 写入数据库
                DB::beginTransaction();
                // 生成入所单
                // $warehouse_report = WarehouseReport::with([])->create([
                //     'processor_id' => $request->get('account_id'),
                //     'processed_at' => date('Y-m-d H:i:s'),
                //     'type' => strtoupper($request->get('type')),
                //     'direction' => 'IN',
                //     'serial_number' => $new_warehouse_report_sn = Code::makeSerialNumber('IN'),
                //     'status' => 'DONE',
                // ]);
                // 生成设备/器材赋码记录单
                $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])->create([
                    'serial_number' => $entire_instance_excel_tagging_report_sn = EntireInstanceExcelTaggingReport::generateSerialNumber(),
                    'is_upload_create_device_excel_error' => false,
                    'work_area_type' => $work_area_type,
                    'processor_id' => session('account.id'),
                    'work_area_unique_code' => $work_area_unique_code,
                ]);

                // 添加新设备
                $current_row_for_fix_workflow = 2;
                $inserted_count = 0;
                $new_entire_instances = array_collapse($new_entire_instances);
                foreach ($new_entire_instances as $new_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $new_entire_instance['fixer_id'],
                        'fixed_at' => $new_entire_instance['fixed_at'],
                        'checker_id' => $new_entire_instance['checker_id'],
                        'checked_at' => $new_entire_instance['checked_at'],
                        'spot_checker_id' => $new_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $new_entire_instance['spot_checked_at'],
                    ];
                    unset(
                        $new_entire_instance['fixer_id'],
                        $new_entire_instance['fixed_at'],
                        $new_entire_instance['checker_id'],
                        $new_entire_instance['checked_at'],
                        $new_entire_instance['spot_checker_id'],
                        $new_entire_instance['spot_checked_at']
                    );
                    $nei = EntireInstance::with([])->create($new_entire_instance);

                    // // 入所单设备
                    // $nwrei = WarehouseReportEntireInstance::with([])->create([
                    //     'warehouse_report_serial_number' => $warehouse_report->serial_number,
                    //     'entire_instance_identity_code' => $nei->identity_code,
                    // ]);

                    // 赋码单设备/器材编号
                    EntireInstanceExcelTaggingIdentityCode::with([])->create([
                        'entire_instance_excel_tagging_report_sn' => $entire_instance_excel_tagging_report->serial_number,
                        'entire_instance_identity_code' => $new_entire_instance['identity_code'],
                    ]);

                    // 生成日志
                    EntireInstanceLogFacade::makeOne('设备赋码', $new_entire_instance['identity_code'], 0, '', $o_made_at ? ($scraping_at ? "出厂日期：{$o_made_at}；到期日期：{$scraping_at}；" : "出厂日期：{$o_made_at}；") : '');  // 待修入所
                    // EntireInstanceLogFacade::makeOne('入所', $new_entire_instance['identity_code'], 1, "/warehouse/report/{$nwrei->warehouse_report_serial_number}?show_type=D&page=1&current_work_area=&direction=IN&updated_at=", "经办人：" . session('account.nickname') . '；');

                    $inserted_count++;

                    // 如果有检修人和验收人生成检修单
                    if ($fix_workflow_datum['fixer_id'] || ($fix_workflow_datum['checker_id'] && $fix_workflow_datum['checked_at'])) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', '')
                            ->first();
                        if (!@$fix_workflow_datum['fixed_at'])
                            throw new ExcelInException("第{$current_row_for_fix_workflow}行，设备：{$nei->serial_number}填写检修人或验收人，必须填写检修时间");

                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => '',
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $fix_workflow_datum['fixed_at'],
                            'status' => @$fix_workflow_datum['checker_id'] ? '1' : '0',
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }
                    $current_row_for_fix_workflow++;
                }

                // 更新该型号下的所有设备总数
                EntireInstanceCount::updates($entire_instance_counts->toArray());
                DB::commit();

                if (!empty($excel_errors)) {
                    $root_dir = storage_path('entireInstanceTagging/upload/errorExcels/createDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$entire_instance_excel_tagging_report_sn}");

                    EntireInstanceExcelTaggingReport::with([])->where('serial_number', $entire_instance_excel_tagging_report_sn)->update(['is_upload_create_device_excel_error' => true]);

                    return redirect("/entire/tagging/{$entire_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                            'page' => $request->get('page'),
                            'type' => $request->get('type'),
                        ]))
                        ->with('warning', "设备赋码：{$inserted_count}条。" . '其中' . count($excel_errors) . '行有错误。');
                }

                return redirect("/entire/tagging/{$entire_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]))
                    ->with('success', "设备赋码：{$inserted_count}条。");
        }
    }

    /**
     * 上传批量修改Excel
     * @param Request $request
     * @param string $work_area_type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws ExcelInException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function uploadEditDevice(Request $request, string $work_area_type)
    {
        $origin_row = 2;
        $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
            ->originRow($origin_row)
            ->withSheetIndex(0);

        $current_row = $origin_row;
        $excel_errors = [];

        $serial_number = EntireInstanceExcelTaggingReport::generateSerialNumber();

        switch ($work_area_type) {
            case 'pointSwitch':
                // 转辙机工区
                // 唯一编号* A、所编号* B、厂编号 C、厂家 D、生产日期 E
                // 寿命 F、车站 G、道岔号 H、出所日期 I、开向 J
                // 线制 K、表示干特征 L、道岔类型 M、防挤压保护罩 N
                $edit_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;
                    list(
                        $om_identity_code, $om_serial_number, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_life_year, $o_station_name, $o_crossroad_number, $o_last_out_at, $o_open_direction,
                        $o_line_name, $o_said_rod, $o_crossroad_type, $o_extrusion_protect,
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$om_identity_code) throw new ExcelInException("第{$current_row}行，唯一编号或所编号必须填写一个");
                    // 验证唯一编号
                    if ($om_identity_code) {
                        $ei = EntireInstance::with([])->where('identity_code', $om_identity_code)->first();
                    } else {
                        // 验证所编号是否存在
                        $ei = EntireInstance::with([])->where('serial_number', $om_serial_number)->get();
                        if ($ei->isEmpty()) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}不存在");
                        if ($ei->count() > 1) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}存在多台设备");
                        $ei = $ei->first();
                        if (!$ei->identity_code) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}没有唯一编号");
                    }

                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");
                    // 验证车站
                    $station = null;
                    if ($o_station_name) {
                        $station = Maintain::with([])->where('name', $o_station_name)->first();
                        if (!$station) throw new ExcelInException("第{$current_row}行，没有找到车站：{$o_station_name}");
                    }

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['C'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d', strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (\Exception $e) {
                            $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['F'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $o_life_year = 0;
                            $scraping_at = null;
                        } else {
                            $o_life_year = intval($o_life_year);
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['F'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $o_life_year = 0;
                        $scraping_at = null;
                    }

                    // 验证出所日期
                    if ($o_last_out_at) {
                        try {
                            $o_last_out_at = strtotime(ExcelWriteHelper::getExcelDate($o_last_out_at));
                        } catch (\Exception $e) {
                            $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, $e->getMessage());
                            $o_last_out_at = null;
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, '没有填写出所日期');
                        $o_last_out_at = null;
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['J'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右'])) $excel_error['J'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向只能填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }

                    // 验证表示杆特征
                    if (!$o_said_rod) {
                        $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '表示杆特征', $o_said_rod, '表示杆特征未填写');
                        $o_said_rod = '';
                    }

                    // 验证道岔类型
                    if (!$o_crossroad_type) {
                        $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '道岔类型', $o_crossroad_type, '道岔类型未填写');
                        $o_crossroad_type = '';
                    }

                    // 验证防挤压保护罩
                    if ($o_extrusion_protect) {
                        if (!in_array($o_extrusion_protect, ['是', '否'])) $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '防挤压保护罩(是、否)', $o_extrusion_protect, '只能填写：是、否');
                        if ($o_extrusion_protect == '是') {
                            $o_extrusion_protect = true;
                        } else {
                            $o_extrusion_protect = false;
                        }
                    } else {
                        $o_extrusion_protect = false;
                    }

                    // 写入待插入数据
                    $edit_entire_instances[] = array_filter([
                        'identity_code' => $ei->identity_code,
                        'maintain_station_name' => $o_station_name,
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'made_at' => $o_made_at ?? '',
                        'scarping_at' => $scraping_at ?? '',
                        'life_year' => $o_life_year,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'open_direction' => $o_open_direction ?? '',
                        'line_name' => $o_line_name ?? '',
                        'said_rod' => $o_said_rod ?? '',
                        'crossroad_type' => $o_crossroad_type ?? '',
                        'crossroad_number' => $o_crossroad_number ?? '',
                        'extrusion_protect' => $o_extrusion_protect ?? '',
                    ], function ($value) {
                        return !empty($value);
                    });
                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 写入数据库
                DB::begintransaction();
                // 修改设备
                $edited_count = 0;
                $edited_identity_codes = [];
                foreach ($edit_entire_instances as $edit_entire_instance) {
                    array_filter($edit_entire_instance, function ($val) {
                        return !empty($val);
                    });
                    EntireInstance::with([])
                        ->where('identity_code', $edit_entire_instance['identity_code'])
                        ->update($edit_entire_instance);
                    $edited_identity_codes[] = $edit_entire_instance['identity_code'];

                    $edited_count++;
                }
                DB::commit();

                $entire_instances = EntireInstance::with([])->whereIn('identity_code', $edited_identity_codes)->get();
                $has_edit_device_error = false;
                $edit_device_error_filename = '';
                $with_msg = "上传设备数据补充：{$edited_count}条。";

                if (!empty($excel_errors)) {
                    $root_dir = storage_path('entireInstanceTagging/upload/errorExcels/editDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$serial_number}");

                    EntireInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->update(['is_upload_edit_device_excel_error' => true]);

                    $has_edit_device_error = true;
                    $edit_device_error_filename = "{$root_dir}/{$serial_number}.xls";
                    $with_msg = "修改数据：{$edited_count}条。" . '其中' . count($excel_errors) . '行有错误。';
                }

                return view('Entire.Instance.uploadEditDeviceReport', [
                    'entireInstances' => $entire_instances,
                    'hasEditDeviceError' => $has_edit_device_error,
                    'editDeviceErrorFilename' => $edit_device_error_filename,
                ])
                    ->with('warning', $with_msg);
            default:
            case 'relay':
            case 'synthesize':
            case 'powerSupplyPanel':
                // 继电器、综合、电源屏工区
                // 唯一编号* A、所编号* B、厂编号 C、厂家 D、生产日期 E
                // 车站 F、组合位置 G、寿命(年) H、出所日期 I
                // 道岔号 J、开向 K、线制 L
                $edit_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;
                    list(
                        $om_identity_code, $om_serial_number, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_station_name, $o_maintain_location_code, $o_life_year, $o_last_out_at,
                        $o_crossroad_number, $o_open_direction, $o_line_name
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$om_identity_code) throw new ExcelInException("第{$current_row}行，唯一编号或所编号必须填写一个");
                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");
                    // 验证车站
                    $station = null;
                    if ($o_station_name) {
                        $station = Maintain::with([])->where('name', $o_station_name)->first();
                        if (!$station) throw new ExcelinException("第{$current_row}行，没有找到车站：{$o_station_name}");
                    }

                    // 验证唯一编号
                    if ($om_identity_code) {
                        $ei = EntireInstance::with([])->where('identity_code', $om_identity_code)->first();
                        if (!$ei) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_identity_code}不存在");
                    } else {
                        // 验证所编号是否存在
                        $ei = EntireInstance::with([])->where('serial_number', $om_serial_number)->get();
                        if ($ei->isEmpty()) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}不存在");
                        if ($ei->count() > 1) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}存在多台设备");
                        $ei = $ei->first();
                        if (!$ei->identity_code) throw new ExcelInException("第{$current_row}行，设备/器材：{$om_serial_number}没有唯一编号");
                    }

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['C'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    $om_factory_name = $om_factory_name ? trim($om_factory_name) : '';
                    // if ($o_factory_name) {
                    //     $factory = Factory::with([])->where('name', $o_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $o_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$o_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $o_factory_name, '没有填写厂家', 'red');
                    //     $o_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d', strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (\Exception $e) {
                            $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['E'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $o_life_year = 0;
                            $scraping_at = null;
                        } else {
                            $o_life_year = intval($o_life_year);
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $o_life_year = 0;
                        $scraping_at = null;
                    }

                    // 验证出所日期
                    if ($o_last_out_at) {
                        try {
                            $o_last_out_at = strtotime(ExcelWriteHelper::getExcelDate($o_made_at));
                        } catch (Exception $e) {
                            $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, $e->getMessage());
                            $o_last_out_at = null;
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '出所日期', $o_last_out_at, '没有填写出所日期');
                        $o_last_out_at = null;
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右'])) $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向只能填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }


                    // 写入待修改数据
                    $edit_entire_instances[] = collect([
                        'identity_code' => $ei->identity_code,
                        'maintain_station_name' => $o_station_name,
                        'maintain_location_code' => $o_maintain_location_code,
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'fix_cycle_unit' => 'YEAR',
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'life_year' => $o_life_year,
                        'last_installed_time' => $o_last_out_at,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'crossroad_number' => $crossroad_number ?? '',
                        'open_direction' => $o_open_direction ?? '',
                        'line_name' => $o_line_name ?? '',
                    ])
                        ->filter(function ($value) {
                            return !empty($value);
                        })
                        ->toArray();

                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 写入数据库
                DB::begintransaction();
                // 修改设备/器材
                $edited_count = 0;
                $edited_identity_codes = [];
                foreach ($edit_entire_instances as $edit_entire_instance) {
                    array_filter($edit_entire_instance, function ($val) {
                        return !empty($val);
                    });
                    EntireInstance::with([])
                        ->where('identity_code', $edit_entire_instance['identity_code'])
                        ->update($edit_entire_instance);
                    $edited_identity_codes[] = $edit_entire_instance['identity_code'];

                    $edited_count++;
                }
                DB::commit();

                $entire_instances = EntireInstance::with([])->whereIn('identity_code', $edited_identity_codes)->get();
                $with_msg = "批量修改：{$edited_count}条 。";

                return view('Entire.Instance.uploadEditDeviceReport', [
                    'entireInstances' => $entire_instances,
                ])
                    ->with('success', $with_msg);
        }
    }

}
