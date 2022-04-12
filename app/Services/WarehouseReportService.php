<?php

namespace App\Services;

use App\Exceptions\EntireInstanceNotFoundException;
use App\Exceptions\MaintainNotFoundException;
use App\Facades\BreakdownLogFacade;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\SuPuRuiApi;
use App\Facades\SuPuRuiSdk;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\Maintain;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jericho\Time;

class WarehouseReportService
{
    /**
     * 采购入所
     * @param Request $request
     * @return string
     * @throws Exception
     */
    final public function buyInOnce(Request $request): string
    {
        // 生成部件档案
        $currentTime = date('Y-m-d');
        $factoryUniqueCode = DB::table('factories')->where('deleted_at', null)->where('name', $request->get('factory_name'))->first(['unique_code'])->unique_code;
        $newEntireInstanceIdentityCode = CodeFacade::makeEntireInstanceIdentityCode($request->get('entire_model_unique_code'), $factoryUniqueCode);
        $categoryName = DB::table('categories')->where('deleted_at', null)->where('unique_code', $request->get('category_unique_code'))->first(['name']);
        if (!$categoryName) throw new Exception('种类不存在');

        // 获取部件数据
        $parts = $request->except([
            'type', '_token',
            'category_unique_code',
            'entire_model_unique_code',
            'factory_name',
            'factory_device_code',
            'processor_id',
            'connection_name',
            'connection_phone',
            'processed_at',
            'auto_insert_fix_workflow',
            'category_unique_name',
        ]);

        DB::transaction(function () use (
            $request,
            $parts,
            $newEntireInstanceIdentityCode,
            $currentTime,
            $factoryUniqueCode,
            $categoryName
        ) {
            // 获取型号名称
            switch (substr($request->get('entire_model_unique_code'), 0, 1)) {
                case "Q":
                    $table = "entire_models";
                    break;
                case "S":
                    $table = "part_models";
                    break;
                default:
                    throw new Exception("没有对应的型号");
                    break;
            }
            $modelName = DB::table($table)->where('unique_code', $request->get('entire_model_unique_code'))->first(['name']);
            if (!$modelName) throw new Exception("没有对应的型号");

            // 插入整件实例
            $entireInstance = new EntireInstance;
            $entireInstance
                ->fill([
                    'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                    'status' => $request->get('type'),
                    'factory_name' => $request->get('factory_name'),
                    'factory_device_code' => $request->get('factory_device_code'),
                    'identity_code' => $newEntireInstanceIdentityCode,
                    'in_warehouse' => false,
                    'category_unique_code' => $request->get('category_unique_code'),
                    'category_name' => $categoryName->name,
                    'is_flush_serial_number' => true,
                    'made_at' => $request->get('made_at'),
                    'scarping_at' => Carbon::createFromFormat('Y-m-d', $request->get('made_at'))->addYear($request->get('lifetime'))->format('Y-m-d'),
                    'model_unique_code' => $request->get('entire_model_unique_code'),
                    'model_name' => $modelName->name,
                    // 'maintain_workshop_name' => env('JWT_ISS'),
                ])
                ->saveOrFail();

            $partModelCount = DB::table('pivot_entire_model_and_part_models')->where('entire_model_unique_code', $request->get('entire_model_unique_code'))->count('part_model_unique_code');
            if ($partModelCount) {
                $i = 0;
                $partInstances = [];
                $entireInstanceLogs = [];
                foreach ($parts as $partModelUniqueCode => $part) {
                    foreach ($part as $partFactoryDeviceCode) {
                        if ($partFactoryDeviceCode == null) continue;
                        $i += 1;

                        // 插入部件实例
                        $partInstances[] = [
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime,
                            'part_model_unique_code' => $partModelUniqueCode,
                            'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                            'status' => $request['type'],
                            'factory_name' => $request['factory_name'],
                            'factory_device_code' => $partFactoryDeviceCode,
                            'identity_code' => CodeFacade::makePartInstanceIdentityCode($partModelUniqueCode) . "_{$i}",
                            'category_unique_code' => $request->get('category_unique_code'),
                            'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                        ];

                        // 插入整件操作日志
                        $entireInstanceLogs[] = [
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime,
                            'name' => '新采购入所',
                            'description' => "包含部件{$partFactoryDeviceCode}（{$partModelUniqueCode}）",
                            'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                            'type' => 0,
                        ];
                    }
                }

                if ($partInstances == []) throw new Exception('部件不能为空');
                if (!DB::table('part_instances')->insert($partInstances)) throw new Exception('创建部件失败');
                if (!EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs)) throw new \Exception('创建整件实例操作日志失败');
            }

            // 生成入所单
            $warehouseReport = new WarehouseReport;
            $warehouseReport->fill([
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => $request->get('type'),
                'direction' => 'IN',
                'serial_number' => CodeFacade::makeSerialNumber('IN'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ])->saveOrFail();

            // 插入入所单整件实例
            $warehouseReportEntireInstance = new WarehouseReportEntireInstance;
            $warehouseReportEntireInstance->fill([
                'warehouse_report_serial_number' => $warehouseReport->serial_number,
                'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                'un_cycle_fix_count' => 1,
            ])->saveOrFail();

            if ($request->get('auto_insert_fix_workflow')) {
                $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW');
                // 插入检修单
                $fixWorkflow = new FixWorkflow;
                $fixWorkflow->fill([
                    'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                    'warehouse_report_serial_number' => $warehouseReport->serial_number,
                    'status' => 'FIXING',
                    'processor_id' => $request->get('processor_id'),
                    'serial_number' => $fixWorkflowSerialNumber,
                    'stage' => 'UNFIX',
                ])->saveOrFail();

                // 修改整件实例中检修单序列号和状态
                $entireInstance->fill(['fix_workflow_serial_number' => $fixWorkflowSerialNumber, 'status' => 'FIXING'])->saveOrFail();
            }

            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 插入整件操作日志
            $entireInstanceLog = new EntireInstanceLog;
            $entireInstanceLog->fill([
                'name' => '新采购入所',
                'entire_instance_identity_code' => $newEntireInstanceIdentityCode,
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    '供应商：' . $request->get('factory_name') ?? '',
                ]),
                'type' => 1,
                'url' => "/warehouse/report/{$warehouseReport->serial_number}",
            ])->saveOrFail();
        });
        return $newEntireInstanceIdentityCode;
    }

    /**
     * 返厂维修出所
     * @param Request $request
     * @param FixWorkflow $fixWorkflow
     */
    final public function returnFactoryOutOnce(Request $request, FixWorkflow $fixWorkflow)
    {
        DB::transaction(function () use ($request, $fixWorkflow) {
            // 修改检修单状态
            $fixWorkflow->fill(['status' => 'RETURN_FACTORY'])->saveOrFail();

            // 修改整件状态
            $fixWorkflow->EntireInstance->fill(['status' => 'RETURN_FACTORY'])->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')->where('entire_instance_identity_code', $fixWorkflow->EntireInstance->identity_code)->update(['status' => 'RETURN_FACTORY']);

            // 生成入所单
            $warehouseReport = new WarehouseReport;
            $warehouseReport->fill([
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => 'RETURN_FACTORY',
                'direction' => 'OUT',
                'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('OUT'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ])
                ->saveOrFail();

            // 生成出所设备记录
            $warehouseReportEntireInstance = new WarehouseReportEntireInstance;
            $warehouseReportEntireInstance->fill([
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                'maintain_station_name' => $fixWorkflow->EntireInstance->maintain_station_name,
                'maintain_location_code' => $fixWorkflow->EntireInstance->maintain_location_code,
                'crossroad_number' => $fixWorkflow->EntireInstance->crossroad_number,
                'traction' => $fixWorkflow->EntireInstance->traction,
                'line_name' => $fixWorkflow->EntireInstance->line_name,
                'crossroad_type' => $fixWorkflow->EntireInstance->crossroad_type,
                'extrusion_protect' => $fixWorkflow->EntireInstance->extrusion_protect,
                'point_switch_group_type' => $fixWorkflow->EntireInstance->point_switch_group_type,
                'open_direction' => $fixWorkflow->EntireInstance->open_direction,
                'said_rod' => $fixWorkflow->EntireInstance->said_rod,
            ])->saveOrFail();

            // 生成整件操作日志
            $maintain = Maintain::with(['Parent'])->where('name', $fixWorkflow->EntireInstance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$fixWorkflow->EntireInstance->maintain_station_name}");
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            EntireInstanceLog::with([])->create([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'entire_instance_identity_code' => $fixWorkflow->EntireInstance->identity_code,
                'name' => '返厂入所',
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->Station->name ?? '',
                    // '位置：' . $fixWorkflow->EntireInstance->maintain_location_code ?? '' . $fixWorkflow->EntireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/report/warehouse/{$newWarehouseReportSerialNumber}",
            ]);
        });
    }

    /**
     * 返厂入所
     * @param Request $request
     * @param FixWorkflow $fixWorkflow
     */
    final public function factoryReturnInOnce(Request $request, FixWorkflow $fixWorkflow)
    {
        DB::transaction(function () use ($request, $fixWorkflow) {
            // 修改检修单状态
            $fixWorkflow->fill(['status' => 'FACTORY_RETURN'])->saveOrFail();

            // 修改整件状态
            $fixWorkflow->EntireInstance->fill(['status' => 'FACTORY_RETURN'])->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')->where('entire_instance_identity_code', $fixWorkflow->EntireInstance->identity_code)->update(['status' => 'FACTORY_RETURN']);

            // 生成入所单
            $warehouseReport = new WarehouseReport;
            $warehouseReport->fill([
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => 'FACTORY_RETURN',
                'direction' => 'IN',
                'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ])
                ->saveOrFail();

            // 生成出所设备记录
            $warehouseReportEntireInstance = new WarehouseReportEntireInstance;
            $warehouseReportEntireInstance->fill([
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                'maintain_station_name' => $fixWorkflow->EntireInstance->maintain_station_name,
                'maintain_location_code' => $fixWorkflow->EntireInstance->maintain_location_code,
                'crossroad_number' => $fixWorkflow->EntireInstance->crossroad_number,
                'traction' => $fixWorkflow->EntireInstance->traction,
                'line_name' => $fixWorkflow->EntireInstance->line_name,
                'crossroad_type' => $fixWorkflow->EntireInstance->crossroad_type,
                'extrusion_protect' => $fixWorkflow->EntireInstance->extrusion_protect,
                'point_switch_group_type' => $fixWorkflow->EntireInstance->point_switch_group_type,
                'open_direction' => $fixWorkflow->EntireInstance->open_direction,
                'said_rod' => $fixWorkflow->EntireInstance->said_rod,
            ])->saveOrFail();

            $maintain = Maintain::with(['Parent'])->where('name', $fixWorkflow->EntireInstance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$fixWorkflow->EntireInstance->maintain_station_name}");
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            EntireInstanceLog::with([])->create([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'entire_instance_identity_code' => $fixWorkflow->EntireInstance->identity_code,
                'name' => '返厂入所',
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->Station->name ?? '',
                    // '位置：' . $fixWorkflow->EntireInstance->maintain_location_code ?? '' . $fixWorkflow->EntireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/report/warehouse/{$newWarehouseReportSerialNumber}",
            ]);
        });
    }

    /**
     * 维修入所
     * @param Request $request
     * @param EntireInstance $entireInstance
     * @return bool
     * @throws \Throwable
     */
    final public function fixingInOnce(Request $request, EntireInstance $entireInstance): bool
    {
        $timeStr = date('Y-m-d H:i:s');
        DB::transaction(function () use ($request, $entireInstance, $timeStr) {
            // 生成入所单
            DB::table('warehouse_reports')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => 'FIXING',
                'direction' => 'IN',
                'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ]);

            // 生成入所设备记录
            DB::table('warehouse_report_entire_instances')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'maintain_station_name' => $entireInstance->maintain_station_name,
                'maintain_location_code' => $entireInstance->maintain_location_code,
                'crossroad_number' => $entireInstance->crossroad_number,
                'traction' => $entireInstance->traction,
                'line_name' => $entireInstance->line_name,
                'crossroad_type' => $entireInstance->crossroad_type,
                'extrusion_protect' => $entireInstance->extrusion_protect,
                'point_switch_group_type' => $entireInstance->point_switch_group_type,
                'open_direction' => $entireInstance->open_direction,
                'said_rod' => $entireInstance->said_rod,
            ]);

            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            EntireInstanceLog::with([])->create([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'name' => '入所检修',
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . $entireInstance->Station->Parent->name ?? '' . ' ' . $entireInstance->Station->name ?? '',
                    // '位置：' . $entireInstance->maintain_location_code ?? '' . $entireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/report/warehouse/{$newWarehouseReportSerialNumber}",
            ]);

            // 修改整件状态
            $entireInstance->fill([
                'un_cycle_fix_count' => $entireInstance->un_cycle_fix_count + 1,  // 非周期检修的次数+1
                'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                'updated_at' => now()->format('Y-m-d H:i:s'),  // 更新日期
                'status' => 'FIXING',  // 状态：待修
                'maintain_station_name' => '',  // 所属车站
                'crossroad_number' => '',  // 道岔号
                'open_direction' => '',  // 开向
                'maintain_location_code' => '',  // 室内上道位置
                'next_fixing_time' => null,  // 下次周期修时间戳
                'next_fixing_month' => null,  // 下次周期修月份
                'next_fixing_day' => null,  // 下次周期修日期
            ])
                ->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')->where('entire_instance_identity_code', $entireInstance->identity_code)->update(['status' => 'FIXING']);

        });
        return true;
    }

    /**
     * 通过整件编号办理检修入所
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return bool
     */
    final public function fixingInOnceWithEntireInstanceIdentityCode(Request $request, string $entireInstanceIdentityCode): bool
    {
        $timeStr = date('Y-m-d H:i:s');
        DB::transaction(function () use ($request, $entireInstanceIdentityCode, $timeStr) {
            // 生成入所单
            DB::table('warehouse_reports')
                ->insert([
                    'created_at' => $timeStr,
                    'updated_at' => $timeStr,
                    'processor_id' => $request->get('processor_id'),
                    'processed_at' => $request->get('processed_at'),
                    'connection_name' => $request->get('connection_name'),
                    'connection_phone' => $request->get('connection_phone'),
                    'type' => 'FIXING',
                    'direction' => 'IN',
                    'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                    'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
                ]);

            $entireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $entireInstanceIdentityCode)->first();
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            EntireInstanceLog::with([])->create([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'name' => '入所检修',
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . $entireInstance->Station->Parent->name ?? '' . ' ' . $entireInstance->Station->name ?? '',
                    // '位置：' . $entireInstance->maintain_location_code ?? '' . $entireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/report/warehouse/{$newWarehouseReportSerialNumber}",
            ]);

            // 生成入所设备记录
            DB::table('warehouse_report_entire_instances')
                ->insert([
                    'created_at' => $timeStr,
                    'updated_at' => $timeStr,
                    'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                    'entire_instance_identity_code' => $entireInstanceIdentityCode,
                    'maintain_station_name' => $entireInstance->maintain_station_name,
                    'maintain_location_code' => $entireInstance->maintain_location_code,
                    'crossroad_number' => $entireInstance->crossroad_number,
                    'traction' => $entireInstance->traction,
                    'line_name' => $entireInstance->line_name,
                    'crossroad_type' => $entireInstance->crossroad_type,
                    'extrusion_protect' => $entireInstance->extrusion_protect,
                    'point_switch_group_type' => $entireInstance->point_switch_group_type,
                    'open_direction' => $entireInstance->open_direction,
                    'said_rod' => $entireInstance->said_rod,
                ]);

            // 修改整件状态
            DB::table('entire_instances')
                ->where('identity_code', $entireInstanceIdentityCode)
                ->update([
                    'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                    'updated_at' => now()->format('Y-m-d H:i:s'),  // 更新日期
                    'status' => 'FIXING',  // 状态：待修
                    'maintain_station_name' => '',  // 所属车站
                    'crossroad_number' => '',  // 道岔号
                    'open_direction' => '',  // 开向
                    'maintain_location_code' => '',  // 室内上道位置
                    'next_fixing_time' => null,  // 下次周期修时间戳
                    'next_fixing_month' => null,  // 下次周期修月份
                    'next_fixing_day' => null,  // 下次周期修日期
                ]);

            // 修改部件状态
            DB::table('part_instances')
                ->where('entire_instance_identity_code', $entireInstanceIdentityCode)
                ->update(['status' => 'FIXING']);
        });
        return true;
    }

    /**
     * 维修单：入所
     * @param Request $request
     * @param FixWorkflow $fixWorkflow
     */
    final public function fixWorkflowInOnce(Request $request, FixWorkflow $fixWorkflow)
    {
        DB::transaction(function () use ($request, $fixWorkflow) {
            $entireInstance = $fixWorkflow->EntireInstance;

            // 生成入所单
            $warehouseReport = new WarehouseReport;
            $warehouseReport
                ->fill([
                    'processor_id' => $request->get('processor_id'),
                    'processed_at' => $request->get('processed_at'),
                    'connection_name' => $request->get('connection_name'),
                    'connection_phone' => $request->get('connection_phone'),
                    'type' => 'FIXING',
                    'direction' => 'IN',
                    'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                ])
                ->saveOrFail();

            $maintain = Maintain::with(['Parent'])->where('name', $entireInstance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$entireInstance->maintain_station_name}");
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            EntireInstanceLog::with([])->create([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'name' => '入所检修',
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                    // '位置：' . $entireInstance->maintain_location_code ?? '' . $entireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/report/warehouse/{$warehouseReport->serial_number}",

            ]);

            // 生成入所设备记录
            $warehouseReportEntireInstance = WarehouseReportEntireInstance::with([])->create([
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                'maintain_station_name' => $entireInstance->maintain_station_name,
                'maintain_location_code' => $entireInstance->maintain_location_code,
                'crossroad_number' => $entireInstance->crossroad_number,
                'traction' => $entireInstance->traction,
                'line_name' => $entireInstance->line_name,
                'crossroad_type' => $entireInstance->crossroad_type,
                'extrusion_protect' => $entireInstance->extrusion_protect,
                'point_switch_group_type' => $entireInstance->point_switch_group_type,
                'open_direction' => $entireInstance->open_direction,
                'said_rod' => $entireInstance->said_rod,
            ]);

            // 修改整件状态
            $fixWorkflow
                ->EntireInstance
                ->fill([
                    'in_warehouse' => false,
                    'un_cycle_fix_count' => $fixWorkflow->EntireInstance->un_cycle_fix_count + 1,  // 非周期检修的次数+1
                    'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                    'updated_at' => now()->format('Y-m-d H:i:s'),  // 更新日期
                    'status' => 'FIXING',  // 状态：待修
                    'maintain_station_name' => '',  // 所属车站
                    'crossroad_number' => '',  // 道岔号
                    'open_direction' => '',  // 开向
                    'maintain_location_code' => '',  // 室内上道位置
                    'next_fixing_time' => null,  // 下次周期修时间戳
                    'next_fixing_month' => null,  // 下次周期修月份
                    'next_fixing_day' => null,  // 下次周期修日期
                ])
                ->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')->where('entire_instance_identity_code', $fixWorkflow->EntireInstance->identity_code)->update(['status' => 'FIXING']);
        });
    }

    /**
     * 批量入所
     * @param Collection $warehouseBatchReports
     * @param string $status
     * @return array
     */
    final public function inBatch(Collection $warehouseBatchReports, string $status = 'FIXING')
    {
        $repeat = [];
        $entireInstances = [];
        DB::transaction(function () use ($warehouseBatchReports, &$repeat, &$entireInstances, $status) {
            $i = 0;

            // 生成入所单
            $warehouseReport = WarehouseReport::with([])->create([
                'processor_id' => session('account.id'),
                'processed_at' => date('Y-m-d'),
                'type' => 'FIXING',
                'direction' => 'IN',
                'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN') . ++$i,
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ]);

            foreach ($warehouseBatchReports as $warehouseBatchReport) {
                $maintain = Maintain::with(['Parent'])->where('name', $warehouseBatchReport->EntireInstance->maintain_station_name)->first();
                if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$warehouseBatchReport->EntireInstance->maintain_station_name}");

                // 生成整件操作日志
                $entireInstanceLog = new EntireInstanceLog();
                $entireInstanceLog
                    ->fill([
                        'name' => '入所检修',
                        'entire_instance_identity_code' => $warehouseBatchReport->entire_instance_identity_code,
                        'description' => implode('；', [
                            '经办人：' . session('account.nickname') ?? '',
                            // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                            // '位置：' . $warehouseBatchReport->EntireInstance->maintain_location_code ?? '' . $warehouseBatchReport->EntireInstance->crossroad_number ?? '',
                        ]),
                        'type' => 1,
                        'url' => "/report/warehouse/{$warehouseReport->serial_number}",
                    ])
                    ->saveOrFail();

                // 生成入所设备记录
                $warehouseReportEntireInstance = new WarehouseReportEntireInstance;
                $warehouseReportEntireInstance->fill([
                    'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                    'entire_instance_identity_code' => $warehouseBatchReport->entire_instance_identity_code,
                    'maintain_station_name' => $warehouseBatchReport->maintain_station_name,
                    'maintain_location_code' => $warehouseBatchReport->maintain_location_code,
                    'crossroad_number' => $warehouseBatchReport->crossroad_number,
                    'traction' => $warehouseBatchReport->traction,
                    'line_name' => $warehouseBatchReport->line_name,
                    'crossroad_type' => $warehouseBatchReport->crossroad_type,
                    'extrusion_protect' => $warehouseBatchReport->extrusion_protect,
                    'point_switch_group_type' => $warehouseBatchReport->point_switch_group_type,
                    'open_direction' => $warehouseBatchReport->open_direction,
                    'said_rod' => $warehouseBatchReport->said_rod,
                ])->saveOrFail();

                // 修改整件状态
                $entireInstances[] = $warehouseBatchReport->EntireInstance;
                $warehouseBatchReport
                    ->EntireInstance
                    ->fill([
                        'status' => $status,  // 状态
                        'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                        'maintain_station_name' => '',  // 所属车站
                        'crossroad_number' => '',  // 道岔号
                        'open_direction' => '',  // 开向
                        'maintain_location_code' => '',  // 室内上道位置
                        'next_fixing_time' => null,  // 下次周期修时间戳
                        'next_fixing_month' => null,  // 下次周期修月份
                        'next_fixing_day' => null,  // 下次周期修日期
                    ])
                    ->saveOrFail();

                // 修改部件状态
                DB::table('part_instances')->where('entire_instance_identity_code', $warehouseBatchReport->entire_instance_identity_code)->update(['status' => $status]);
            }
        });
        return $repeat;
    }

    /**
     * 单设备入所
     * @param Request $request
     * @param EntireInstance $entireInstance
     */
    final public function inOnce(Request $request, EntireInstance $entireInstance)
    {
        DB::transaction(function () use ($request, $entireInstance) {
            // 修改整件状态
            $entireInstance->fill([
                // 'maintain_workshop_name' => env('JWT_ISS'),
                'status' => 'FIXING',
                'un_cycle_fix_count' => $entireInstance->un_cycle_fix_count + 1,
            ])->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')->where('entire_instance_identity_code', $entireInstance->identity_code)->update(['status' => 'FIXING']);

            // 生成入所单
            $warehouseReport = new WarehouseReport;
            $warehouseReport->fill([
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => 'FIXING',
                'direction' => 'IN',
                'serial_number' => $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ])
                ->saveOrFail();

            // 生成入所设备记录
            $warehouseReportEntireInstance = new WarehouseReportEntireInstance;
            $warehouseReportEntireInstance->fill([
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $entireInstance->identity_code,
            ])->saveOrFail();

            $maintain = Maintain::with(['Parent'])->where('name', $entireInstance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$entireInstance->maintain_station_name}");
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            $entireInstanceLog = new EntireInstanceLog();
            $entireInstanceLog->fill([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'name' => '入所检修',
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                    // '位置：' . $entireInstance->maintain_location_code ?? '' . $entireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/warehouse/report/{$newWarehouseReportSerialNumber}",
            ])
                ->saveOrFail();

            // 获取安装位置
            $entireInstanceMaintainStationName = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->first(['unique_code']);
            $entireInstanceMaintainUniqueCode = $entireInstanceMaintainStationName ? $entireInstanceMaintainStationName->unique_code : 'G00000';
            $entireInstance->load('PartInstance');

            $entireInstance
                ->fill([
                    'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                    'status' => 'FIXING',  // 状态：待修
                    'maintain_station_name' => '',  // 所属车站
                    'crossroad_number' => '',  // 道岔号
                    'open_direction' => '',  // 开向
                    'maintain_location_code' => '',  // 室内上道位置
                    'next_fixing_time' => null,  // 下次周期修时间戳
                    'next_fixing_month' => null,  // 下次周期修月份
                    'next_fixing_day' => null,  // 下次周期修日期
                ])
                ->saveOrFail();
        });
    }

    /**
     * 单设备出所
     * @param Request $request
     * @param EntireInstance $entireInstance
     * @return array
     */
    final public function outOnce(Request $request, EntireInstance $entireInstance)
    {
        $ret = [];
        DB::transaction(function () use ($request, $entireInstance, &$ret) {
            $type = $request->get('maintain_station_name', null) . $request->get('maintain_location_code', null) ? 'INSTALLED' : 'INSTALLING';
            // 修改整件数据
            $entireInstance = EntireInstance::with([
                'PartInstance',
                'FixWorkflow',
                'EntireModel' => function ($EntireModel) {
                    return $EntireModel->select(['fix_cycle_value'])->where('fix_cycle_value', '>', 0);
                },
            ])
                ->where('identity_code', $entireInstance->identity_code)
                ->firstOrFail();
            $entireInstance->fill($request->all())->saveOrFail();

            // 生成出所单
            $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('OUT');
            $warehouseReport = WarehouseReport::with([])->create([
                'processor_id' => $request->get('processor_id'),
                'processed_at' => $request->get('processed_at'),
                'connection_name' => $request->get('connection_name'),
                'connection_phone' => $request->get('connection_phone'),
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => $newWarehouseReportSerialNumber,
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ]);

            // 生成出所设备记录
            $warehouseReportEntireInstance = WarehouseReportEntireInstance::with([])->create([
                'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'maintain_station_name' => $entireInstance->maintain_station_name,
                'maintain_location_code' => $entireInstance->maintain_location_code,
                'crossroad_number' => $entireInstance->crossroad_number,
                'traction' => $entireInstance->traction,
                'line_name' => $entireInstance->line_name,
                'crossroad_type' => $entireInstance->crossroad_type,
                'extrusion_protect' => $entireInstance->extrusion_protect,
                'point_switch_group_type' => $entireInstance->point_switch_group_type,
                'open_direction' => $entireInstance->open_direction,
                'said_rod' => $entireInstance->said_rod,
            ]);

            // 修改整件状态和最后一次出所单流水号
            $nextFixingData = EntireInstanceFacade::nextFixingTime($entireInstance);
            $entireInstance->fill(
                array_merge($nextFixingData, [
                    'status' => $type,
                    'in_warehouse' => false,
                    'last_warehouse_report_serial_number_by_out' => $warehouseReport->serial_number,
                    'maintain_station_name' => $request->get('maintain_station_name'),
                    'maintain_location_code' => $request->get('maintain_location_code'),
                    'last_installed_time' => strtotime($request->get('processed_at', date('Y-m-d H:i:s'))),
                    'last_out_at' => $request->get('processed_at', date('Y-m-d H:i:s')),
                    'location_unique_code' => '',
                ])
            )
                ->saveOrFail();

            // 修改部件状态
            DB::table('part_instances')
                ->where('entire_instance_identity_code', $entireInstance->identity_code)
                ->update([
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => $type,
                ]);

            $maintain = Maintain::with(['Parent'])->where('name', $entireInstance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$entireInstance->maintain_station_name}");
            $account = Account::with([])->where('id', $request->get('processor_id'))->first();

            // 生成整件操作日志
            $entireInstanceLog = new EntireInstanceLog;
            $entireInstanceLog->fill([
                'created_at' => $request->get('processed_at'),
                'updated_at' => $request->get('processed_at'),
                'name' => '出所安装',
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $request->get('connection_name') ?? '' . ' ' . $request->get('connection_phone') ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                    // '位置：' . $entireInstance->maintain_location_code ?? '' . $entireInstance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/warehouse/report/{$newWarehouseReportSerialNumber}",
            ])
                ->saveOrFail();

            // 获取安装位置
            $entireInstanceMaintainStationName = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->first(['unique_code']);
            $entireInstanceMaintainUniqueCode = $entireInstanceMaintainStationName ? $entireInstanceMaintainStationName->unique_code : 'G00000';
        });
        return $ret;
    }

    /**
     * 批量生成入所
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @param string $type
     * @param Collection $newEntireInstances
     * @return bool
     */
    final public function batch(
        int $processorId,
        string $processedAt,
        string $connectionName,
        string $connectionPhone,
        string $type,
        Collection $newEntireInstances
    ): bool
    {
        DB::transaction(function () use (
            $processorId,
            $processedAt,
            $connectionName,
            $connectionPhone,
            $type,
            $newEntireInstances
        ) {
            // 生成入所单
            DB::table('warehouse_reports')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'processor_id' => $processorId,
                'processed_at' => $processedAt,
                'connection_name' => $connectionName,
                'connection_phone' => $connectionPhone,
                'type' => $type,
                'direction' => 'IN',
                'serial_number' => $warehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN'),
                'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
            ]);

            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            foreach ($newEntireInstances as $newEntireInstance) {
                // 插入入所单整件实例
                $warehouseReportEntireInstances[] = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                    'entire_instance_identity_code' => $newEntireInstance->identity_code,
                ];

                $maintain = Maintain::with(['Parent'])->where('name', $newEntireInstance->maintain_station_name)->first();
                if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$newEntireInstance->maintain_station_name}");
                $account = Account::with([])->where('id', $processorId)->first();

                // 生成整件操作日志
                $entireInstanceLogs[] = [
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'name' => '入所检修',
                    'entire_instance_identity_code' => $newEntireInstance->identity_code,
                    'description' => implode('；', [
                        '经办人：' . $account->nickname ?? '',
                        '联系人：' . $connectionName ?? '' . ' ' . $connectionPhone ?? '',
                        // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                        // '位置：' . $newEntireInstance->maintain_location_code ?? '' . $newEntireInstance->crossroad_number ?? '',
                    ]),
                    'type' => 1,
                    'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
                ];
            }
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);
            EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);

            DB::table('entire_instances as ei')->where('ei.identity_code', $newEntireInstance->identity_code)->update([
                'updated_at' => now()->format('Y-m-d H:i:s'),  // 更新日期
                'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                'status' => 'FIXING',  // 状态：待修
                'maintain_station_name' => '',  // 所属车站
                'crossroad_number' => '',  // 道岔号
                'open_direction' => '',  // 开向
                'maintain_location_code' => '',  // 室内上道位置
                'next_fixing_time' => null,  // 下次周期修时间戳
                'next_fixing_month' => null,  // 下次周期修月份
                'next_fixing_day' => null,  // 下次周期修日期
            ]);
        });
    }

    /**
     * 批量出所（没有安装位置）
     * @param array $entire_instance_identity_codes
     * @param int $processor_id
     * @param string $processed_at
     * @param string $type
     * @param string|null $connection_name
     * @param string|null $connection_phone
     * @return string
     * @throws EntireInstanceNotFoundException
     * @throws MaintainNotFoundException
     */
    final public function batchOutWithEntireInstanceIdentityCodes(
        array $entire_instance_identity_codes,
        int $processor_id,
        string $processed_at,
        string $type = 'NORMAL',
        string $connection_name = null,
        string $connection_phone = null
    )
    {
        DB::beginTransaction();
        // 生成入所单
        $warehouse_report = WarehouseReport::with([])->create([
            'processor_id' => $processor_id,
            'processed_at' => $processed_at,
            'connection_name' => $connection_name,
            'connection_phone' => $connection_phone,
            'type' => 'INSTALL',
            'direction' => 'OUT',
            'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('OUT'),
            'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
        ]);

        $warehouse_report_entire_instances = [];
        $entire_instance_logs = [];
        $now = date('Y-m-d H:i:s');
        foreach ($entire_instance_identity_codes as $entire_instance_identity_code) {
            $entire_instance = EntireInstance::with([])->where('identity_code', $entire_instance_identity_code)->first();
            if (!$entire_instance) throw new EntireInstanceNotFoundException("设备：{$entire_instance_identity_code}没有找到");
            $warehouse_report_entire_instances[] = [
                'created_at' => $now,
                'updated_at' => $now,
                'warehouse_report_serial_number' => $warehouse_report_sn,
                'entire_instance_identity_code' => $entire_instance->identity_code,
                'in_warehouse_breakdown_explain' => $entire_instance->in_warehouse_breakdown_explain ?? '',
                'maintain_station_name' => $entire_instance->maintain_station_name ?? '',
                'maintain_location_code' => $entire_instance->maintain_location_code ?? '',
                'crossroad_number' => $entire_instance->crossroad_number ?? '',
                'traction' => $entire_instance->traction ?? '',
                'line_name' => $entire_instance->line_name ?? '',
                'crossroad_type' => $entire_instance->crossroad_type ?? '',
                'extrusion_protect' => $entire_instance->extrusion_protect ?? '',
                'point_switch_group_type' => $entire_instance->point_switch_group_type ?? '',
                'open_direction' => $entire_instance->open_direction ?? '',
                'said_rod' => $entire_instance->said_rod ?? '',
            ];

            // 重新计算周期修
            EntireInstanceFacade::nextFixingTimeWithIdentityCode($entire_instance->identity_code);

            $maintain = Maintain::with(['Parent'])->where('name', $entire_instance->maintain_station_name)->first();
            if (!$maintain) throw new MaintainNotFoundException("设备：{$entire_instance->identity_code} 没有找到车站：{$entire_instance->maintain_station_name}");
            $account = Account::with([])->where('id', $processor_id)->first();

            // 生成整件操作日志
            $entire_instance_logs[] = [
                'created_at' => $now,
                'updated_at' => $now,
                'name' => WarehouseReport::$TYPE[$type] . WarehouseReport::$DIRECTION['OUT'],
                'entire_instance_identity_code' => $entire_instance->identity_code,
                'description' => implode('；', [
                    '经办人：' . $account->nickname ?? '',
                    '联系人：' . $connection_name ?? '' . ' ' . $connection_phone ?? '',
                    // '车站：' . @$maintain->Parent ? $maintain->Parent->name : '' . ' ' . $maintain->name ?? '',
                    // '安装位置：' . $entire_instance->maintain_location_code ?? '' . $entire_instance->crossroad_number ?? '',
                ]),
                'type' => 1,
                'url' => "/warehouse/report/{$warehouse_report_sn}",
            ];
        }
        WarehouseReportEntireInstance::with([])->insert($warehouse_report_entire_instances);  // 写入出所单设备
        EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  // 写入日志

        // 修改设备状态
        DB::table('entire_instances')
            ->whereIn('identity_code', $entire_instance_identity_codes)
            ->update([
                'updated_at' => $now,
                'status' => 'TRANSFER_OUT',
                'in_warehouse_breakdown_explain' => '',
                'last_warehouse_report_serial_number_by_out' => $warehouse_report_sn,
                'crossroad_number' => '',  // 道岔号
                'open_direction' => '',  // 开向
                'location_unique_code' => '',  // 仓库位置
                'is_bind_location' => 0,  // 绑定位置
                'last_out_at' => date('Y-m-d H:i:s'),  // 最后出所时间
            ]);
        // 修改部件状态
        DB::table('part_instances')->whereIn('entire_instance_identity_code', $entire_instance_identity_codes)->update(['updated_at' => $now, 'status' => 'FIXED']);

        DB::commit();
        return $warehouse_report_sn;
    }


    /**
     * 故障修批量入所
     * @param Collection $breakdown_order_temp_entire_instances
     * @param int $processor_id
     * @param string $processed_at
     * @param string|null $connection_name
     * @param string|null $connection_phone
     * @return mixed
     */
    final public function batchInWithBreakdownOrderTempEntireInstances(
        Collection $breakdown_order_temp_entire_instances,
        int $processor_id,
        string $processed_at,
        string $connection_name = null,
        string $connection_phone = null
    )
    {
        return DB::transaction(function ()
        use (
            $breakdown_order_temp_entire_instances,
            $processor_id,
            $processed_at,
            $connection_name,
            $connection_phone
        ) {
            $now = date('Y-m-d H:i:s');
            // 生成入所单
            DB::table('warehouse_reports')
                ->insert([
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'processor_id' => $processor_id,
                    'processed_at' => $processed_at,
                    'connection_name' => $connection_name,
                    'connection_phone' => $connection_phone,
                    'type' => 'BREAKDOWN',
                    'direction' => 'IN',
                    'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('IN'),
                    'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
                ]);

            // 生成入所单设备
            $warehouse_report_entire_instances = [];
            $entire_instance_logs = [];
            foreach ($breakdown_order_temp_entire_instances as $breakdown_entire_instance) {
                $breakdown_log = null;

                // 生成入所单设备
                $warehouse_report_entire_instances[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'warehouse_report_serial_number' => $warehouse_report_sn,
                    'entire_instance_identity_code' => $breakdown_entire_instance->entire_instance_identity_code,
                    'in_warehouse_breakdown_explain' => $breakdown_entire_instance->in_warehouse_breakdown_explain ?: '',
                    'maintain_station_name' => @$breakdown_entire_instance->EntireInstance->Station->name ?: '',
                    'maintain_location_code' => @$breakdown_entire_instance->EntireInstance->maintain_location_code ?: '',
                    'crossroad_number' => @$breakdown_entire_instance->EntireInstance->crossroad_number ?: '',
                    'traction' => @$breakdown_entire_instance->EntireInstance->traction ?: '',
                    'line_name' => @$breakdown_entire_instance->EntireInstance->line_name ?: '',
                    'open_direction' => @$breakdown_entire_instance->EntireInstance->open_direction ?: '',
                    'said_rod' => @$breakdown_entire_instance->EntireInstance->said_rod ?: '',
                    'crossroad_type' => @$breakdown_entire_instance->EntireInstance->crossroad_type ?: '',
                    'point_switch_group_type' => @$breakdown_entire_instance->EntireInstance->point_switch_group_type ?: '',
                    'extrusion_protect' => @$breakdown_entire_instance->EntireInstance->extrusion_protect ?: '',
                ];

                $maintain = Maintain::with(['Parent'])->where('name', $breakdown_entire_instance->EntireInstance->maintain_station_name)->first();
                if (!$maintain) throw new MaintainNotFoundException("车站：{$breakdown_entire_instance->EntireinInstance->maintain_station_name}没有找到，请更正信息后再试");
                $account = Account::with([])->where('id', $processor_id)->first();

                // 生成整件操作日志
                EntireInstanceLog::with([])->create([
                    'name' => '故障修入所',
                    'entire_instance_identity_code' => $breakdown_entire_instance->entire_instance_identity_code,
                    'description' => implode('；', [
                        '经办人：' . $account->nickname ?? '',
                        '联系人：' . $connection_name ?? '' . ' ' . $connection_phone ?? '',
                        // '车站：' . @$maintain->Parent ? @$maintain->Parent->name : '' . ' ' . $breakdown_entire_instance->EntireInstance->maintain_station_name ?? '',
                        // '安装位置：' . $breakdown_entire_instance->EntireInstance->maintain_location_code ?? '' . $breakdown_entire_instance->EntireInstance->crossroad_number ?? '',
                    ]),
                    'type' => 1,
                    'url' => "/warehouse/report/{$warehouse_report_sn}",
                ]);

                // 生成故障日志
                if (
                    $breakdown_entire_instance->BreakdownTypes->isNotEmpty()
                    || $breakdown_entire_instance->in_warehouse_breakdown_explain
                ) {
                    // 循环创建设备日志
                    BreakdownLogFacade::createWarehouseIn(
                        $breakdown_entire_instance->EntireInstance,
                        $breakdown_entire_instance->in_warehouse_breakdown_explain ?? '',
                        date('Y-m-d H:i:s'),
                        $breakdown_entire_instance->BreakdownTypes->pluck('breakdown_type_id')->toArray(),
                        session('account.nickname')
                    );
                }

                // 更新设备状态和入所故障描述
                EntireInstance::with([])
                    ->where('identity_code', $breakdown_entire_instance->entire_instance_identity_code)
                    ->update([
                        'in_warehouse_breakdown_explain' => $breakdown_entire_instance->in_warehouse_breakdown_explain,
                        'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                        'updated_at' => $now,  // 更新日期
                        'status' => 'FIXING',  // 状态：待修
                        'maintain_station_name' => '',  // 所属车站
                        'crossroad_number' => '',  // 道岔号
                        'open_direction' => '',  // 开向
                        'maintain_location_code' => '',  // 室内上道位置
                        'next_fixing_time' => null,  // 下次周期修时间戳
                        'next_fixing_month' => null,  // 下次周期修月份
                        'next_fixing_day' => null,  // 下次周期修日期
                        'location_unique_code' => '',  // 仓库位置
                        'is_bind_location' => 0,  // 绑定位置
                    ]);
            }

            WarehouseReportEntireInstance::with([])->insert($warehouse_report_entire_instances);  // 写入入所设备
            EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  // 写入日志

            return $warehouse_report_sn;
        });
    }

    /**
     * 通过整件编号列表，批量入所
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @param string $type
     * @param array $entireInstanceIdentityCodes
     * @return bool
     */
    final public function batchInWithEntireInstanceIdentityCodes(
        array $entireInstanceIdentityCodes,
        int $processorId,
        string $processedAt,
        string $type = 'NORMAL',
        string $connectionName = null,
        string $connectionPhone = null
    )
    {
        return DB::transaction(function () use (
            $processorId,
            $processedAt,
            $connectionName,
            $connectionPhone,
            $type,
            $entireInstanceIdentityCodes
        ) {
            $now = date('Y-m-d H:i:s');
            // 生成入所单
            DB::table('warehouse_reports')
                ->insert([
                    'created_at' => $now,
                    'updated_at' => $now,
                    'processor_id' => $processorId,
                    'processed_at' => $processedAt,
                    'connection_name' => $connectionName,
                    'connection_phone' => $connectionPhone,
                    'type' => $type,
                    'direction' => 'IN',
                    'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('IN'),
                    'work_area_id' => array_flip(Account::$WORK_AREAS)[session('account.work_area')],
                ]);

            $warehouseReportEntireInstances = [];
            $entireInstanceLogs = [];
            foreach ($entireInstanceIdentityCodes as $entireInstanceIdentityCode) {
                $entire_instance = EntireInstance::with([])->where('identity_code', $entireInstanceIdentityCode)->first();
                if (!$entire_instance) throw new EntireInstanceNotFoundException("没有找到设备：{$entireInstanceIdentityCode}");
                // 插入入所单整件实例
                $warehouseReportEntireInstances[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'warehouse_report_serial_number' => $warehouse_report_sn,
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'in_warehouse_breakdown_explain' => '',
                    'maintain_station_name' => $entire_instance->maintain_station_name ?? '',
                    'maintain_location_code' => $entire_instance->maintain_location_code ?? '',
                    'crossroad_number' => $entire_instance->crossroad_number ?? '',
                    'traction' => $entire_instance->traction ?? '',
                    'line_name' => $entire_instance->line_name ?? '',
                    'crossroad_type' => $entire_instance->crossroad_type ?? '',
                    'extrusion_protect' => $entire_instance->extrusion_protect ?? '',
                    'point_switch_group_type' => $entire_instance->point_switch_group_type ?? '',
                    'open_direction' => $entire_instance->open_direction ?? '',
                    'said_rod' => $entire_instance->said_rod ?? '',
                ];

                $maintain = Maintain::with(['Parent'])->where('name', $entire_instance->maintain_station_name)->first();
                // if (!$maintain) throw new MaintainNotFoundException("没有找到车站：{$entire_instance->maintain_station_name}");
                $account = Account::with([])->where('id', $processorId)->first();

                // 生成整件操作日志
                $entireInstanceLogs[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'name' => WarehouseReport::$TYPE[$type] . WarehouseReport::$DIRECTION['IN'],
                    'description' => implode('；', [
                        '经办人：' . @$account->nickname ?? '',
                        '联系人：' . @$connectionName ?? '' . ' ' . @$connectionPhone ?? '',
                        // '车站：' . @$maintain->Parent ? @$maintain->Parent->name : '' . ' ' . @$maintain->name ?? '',
                        // '安装位置：' . @$entire_instance->maintain_location_code ?? '' . @$entire_instance->crossroad_number ?? '',
                    ]),
                    'entire_instance_identity_code' => @$entire_instance->identity_code,
                    'type' => 1,
                    'url' => "/warehouse/report/{$warehouse_report_sn}",
                ];
            }

            WarehouseReportEntireInstance::with([])->insert($warehouseReportEntireInstances);
            EntireInstanceLog::with([])->insert($entireInstanceLogs);
            // 修改设备状态
            DB::table('entire_instances')
                ->whereIn('identity_code', $entireInstanceIdentityCodes)
                ->update([
                    'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                    'updated_at' => $now,  // 更新日期
                    'status' => 'FIXING',  // 状态：待修
                    'maintain_station_name' => '',  // 所属车站
                    'crossroad_number' => '',  // 道岔号
                    'open_direction' => '',  // 开向
                    'maintain_location_code' => '',  // 室内上道位置
                    'next_fixing_time' => null,  // 下次周期修时间戳
                    'next_fixing_month' => null,  // 下次周期修月份
                    'next_fixing_day' => null,  // 下次周期修日期
                    'location_unique_code' => '',  // 仓库位置
                    'is_bind_location' => 0,  // 绑定位置
                ]);

            return $warehouse_report_sn;
        });
    }

    /**
     * 获取7天出入所统计
     * @return array
     */
    final public function generateStatisticsFor7Days(): array
    {
        $dateList = [];
        $directions = [
            'IN' => '入所',
            'OUT' => '出所',
        ];

        for ($i = 6; $i >= 0; $i--) {
            $time = Carbon::today()->subDay($i);
            $time = $time->format('Y-m-d');
            $dateList[] = $time;  // 当前时间标记
        }

        $originTime = array_first($dateList) . ' 00:00:00';
        $finishTime = array_last($dateList) . ' 23:59:59';

        $statistics = DB::table('warehouse_report_entire_instances as wrei')
            ->selectRaw("count(c.name) as t,c.name as c,wr.direction as d,DATE_FORMAT(wr.created_at, '%Y-%m-%d') as time")
            ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'wrei.entire_instance_identity_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
            ->join(DB::raw('warehouse_reports wr'), 'wr.serial_number', '=', 'wrei.warehouse_report_serial_number')
            ->whereBetween('wr.updated_at', [$originTime, $finishTime])
            ->groupBy(DB::raw('c,d,time'))
            ->get();
        $statistics2 = $statistics->groupBy('time')->all();

        $do_default = function () use ($dateList, $statistics2, $directions) {
            // 空数据
            $statisticsForWarehouse = [];
            foreach ($dateList as $date) {
                $statisticsForWarehouse["转辙机(入所)"][$date] = 0;
                $statisticsForWarehouse["转辙机(出所)"][$date] = 0;
                $statisticsForWarehouse["继电器(入所)"][$date] = 0;
                $statisticsForWarehouse["继电器(出所)"][$date] = 0;
                $statisticsForWarehouse["综合(入所)"][$date] = 0;
                $statisticsForWarehouse["综合(出所)"][$date] = 0;
            }

            foreach ($dateList as $date) {
                if (!array_key_exists($date, $statistics2)) continue;
                foreach ($statistics2[$date] as $val) {
                    if ($val) {
                        switch ($val->c) {
                            case '转辙机':
                            case '继电器':
                                $statisticsForWarehouse["{$val->c}({$directions[$val->d]})"][$val->time] += $val->t;
                                break;
                            default:
                                $statisticsForWarehouse["综合({$directions[$val->d]})"][$val->time] += $val->t;
                                break;
                        }
                    }
                }
            }

            return ['dateList' => $dateList, 'statistics' => $statisticsForWarehouse, 'paragraph_code' => env('ORGANIZATION_CODE')];
        };
        $do_powerSupplyPanel = function () use ($dateList, $statistics2, $directions) {
            // 空数据
            $statisticsForWarehouse = [];
            foreach ($dateList as $date) {
                $statisticsForWarehouse["转辙机(入所)"][$date] = 0;
                $statisticsForWarehouse["转辙机(出所)"][$date] = 0;
                $statisticsForWarehouse["继电器(入所)"][$date] = 0;
                $statisticsForWarehouse["继电器(出所)"][$date] = 0;
                $statisticsForWarehouse["电源屏(入所)"][$date] = 0;
                $statisticsForWarehouse["电源屏(出所)"][$date] = 0;
                $statisticsForWarehouse["综合(入所)"][$date] = 0;
                $statisticsForWarehouse["综合(出所)"][$date] = 0;
            }

            foreach ($dateList as $date) {
                if (!array_key_exists($date, $statistics2)) continue;
                foreach ($statistics2[$date] as $val) {
                    if ($val) {
                        switch ($val->c) {
                            case '转辙机':
                            case '继电器':
                                $statisticsForWarehouse["{$val->c}({$directions[$val->d]})"][$val->time] += $val->t;
                                break;
                            case '电源屏专用':
                                $statisticsForWarehouse["电源屏({$directions[$val->d]})"][$val->time] += $val->t;
                                break;
                            default:
                                $statisticsForWarehouse["综合({$directions[$val->d]})"][$val->time] += $val->t;
                                break;
                        }
                    }
                }
            }

            return ['dateList' => $dateList, 'statistics' => $statisticsForWarehouse, 'paragraph_code' => env('ORGANIZATION_CODE')];
        };

        switch (env('ORGANIZATION_CODE')) {
            case 'B048':
            case 'B049':
            case 'B050':
            case 'B052':
            case 'B053':
            case 'B054':
                // 广州、长沙、怀化、惠州、肇庆、海南 统计电源屏工区
                return $do_powerSupplyPanel();
            default:
                return $do_default();
        }
    }
}
