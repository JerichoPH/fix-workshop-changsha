<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Exceptions\FixWorkflowException;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\PartInstance;
use App\Model\UnCycleFixReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FixWorkflowService
{
    /**
     * 创建新检修单
     * @param string $fixWorkflowSerialNumber
     * @return bool
     */
    public function make(string $fixWorkflowSerialNumber): bool
    {
        DB::transaction(function () use ($fixWorkflowSerialNumber) {
            $fixWorkflow = new FixWorkflow;
            $fixWorkflow->fill([
                'entire_instance_identity_code' => request('identity_code'),
                'status' => 'FIXING',
                'processor_id' => session('processor_id'),
                'serial_number' => $fixWorkflowSerialNumber,
                'stage' => 'UNFIX',
                'type' => request('type'),
                'check_serial_number' => request('type', 'FIX') == 'CHECK' ? FixWorkflow::where('entire_instance_identity_code', request('identity_code'))->where('type', 'FIX')->where('status', 'FIXED')->firstOrFail(['serial_number'])->serial_number : null,
            ])
                ->saveOrFail();

            # 修改整件实例中检修单序列号、状态和在库状态
            $fixWorkflow->EntireInstance->fill([
                'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                'status' => 'FIXING',
                'in_warehouse' => false
            ])
                ->saveOrFail();

            # 修改实例中部件的状态
            DB::table('part_instances')
                ->where('entire_instance_identity_code', request('identity_code'))
                ->update(['status' => 'FIXING']);

            # 添加整件非正常检修记录
            $fixUnCycleReport = new UnCycleFixReport;
            $fixUnCycleReport->fill([
                'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                'fix_workflow_serial_number' => $fixWorkflow->serial_number,
            ]);
        });
        return true;
    }

    /**
     * 通过整件唯一编号生成检修单
     * @param string $entireInstanceIdentityCode
     * @return bool
     */
    final public function makeByEntireInstanceIdentityCode(string $entireInstanceIdentityCode): bool
    {
        $timeStr = date('Y-m-d H:i:s');
        DB::transaction(function () use ($entireInstanceIdentityCode, $timeStr) {
            DB::table('fix_workflows')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'status' => 'FIXING',
                'processor_id' => session('processor_id'),
                'serial_number' => $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW'),
                'stage' => 'UNFIX',
                'type' => 'FIX',
                'check_serial_number' => request('type', 'FIX') == 'CHECK' ? FixWorkflow::where('entire_instance_identity_code', request('identity_code'))->where('type', 'FIX')->where('status', 'FIXED')->firstOrFail(['serial_number'])->serial_number : null,
            ]);

            # 修改整件实例中检修单序列号、状态和在库状态
            DB::table('entire_instances')
                ->where('identity_code', $entireInstanceIdentityCode)
                ->update([
                    'updated_at' => $timeStr,
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                    'status' => 'FIXING',
                    'in_warehouse' => false
                ]);

            # 修改实例中部件的状态
            DB::table('part_instances')
                ->where('entire_instance_identity_code', request('identity_code'))
                ->update(['updated_at' => $timeStr, 'status' => 'FIXING']);

            # 添加整件非正常检修记录
            DB::table('un_cycle_fix_reports')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
            ]);
        });
        return true;
    }

    /**
     * 通过整件出厂编号，生成检修单
     * @param string $factoryDeviceCode
     * @return bool
     */
    final public function makeByFactoryDeviceCode(string $factoryDeviceCode): bool
    {
        $timeStr = date('Y-m-d H:i:s');
        DB::transaction(function () use ($factoryDeviceCode, $timeStr) {
            $entireInstance = DB::table('entire_instances')->where('factory_device_code', $factoryDeviceCode)->first();
            if (!$entireInstance) throw new \Exception('不存在的出厂编号');

            DB::table('fix_workflows')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'status' => 'FIXING',
                'processor_id' => session('processor_id'),
                'serial_number' => $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW'),
                'stage' => 'UNFIX',
                'type' => 'FIX',
                'check_serial_number' => request('type', 'FIX') == 'CHECK' ? FixWorkflow::where('entire_instance_identity_code', request('identity_code'))->where('type', 'FIX')->where('status', 'FIXED')->firstOrFail(['serial_number'])->serial_number : null,
            ]);

            # 修改整件实例中检修单序列号、状态和在库状态
            DB::table('entire_instances')
                ->where('fix_workflow_serial_number', $fixWorkflowSerialNumber)
                ->update([
                    'updated_at' => $timeStr,
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                    'status' => 'FIXING',
                    'in_warehouse' => false
                ]);

            # 修改实例中部件的状态
            DB::table('part_instances')
                ->where('entire_instance_identity_code', request('identity_code'))
                ->update(['updated_at' => $timeStr, 'status' => 'FIXING']);

            # 添加整件非正常检修记录
            DB::table('un_cycle_fix_reports')->insert([
                'created_at' => $timeStr,
                'updated_at' => $timeStr,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
            ]);
        });
        return true;
    }

    /**
     * 批量生成
     * @param Collection $entireInstances
     * @return int
     */
    final public function batch(Collection $entireInstances): int
    {
        $entireInstanceIdentityCodes = [];
        $insertFixWorkflowData = [];
        $insertUnCycleReportData = [];

        DB::transaction(function () use (
            $entireInstances,
            &$entireInstanceIdentityCodes,
            &$insertFixWorkflowData,
            &$insertUnCycleReportData
        ) {
            $currentDatetime = date('Y-m-d H:i:s');
            $i = 0;
            # 创建检修单
            foreach ($entireInstances as $entireInstance) {
                $i += 1;
                $entireInstanceIdentityCodes[] = $entireInstance->identity_code;

                # 待插入检修单列表
                $insertFixWorkflowData[] = [
                    'created_at' => $currentDatetime,
                    'updated_at' => $currentDatetime,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                    'status' => 'FIXING',
                    'processor_id' => 0,
                    'serial_number' => $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW') . "{$i}",
                    'stage' => 'UNFIX',
                    'type' => 'FIX',
                ];

                # 修改整件状态
                DB::table('entire_instances')->where('identity_code', $entireInstance->identity_code)->update([
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                    'status' => 'FIXING',
                    'in_warehouse' => false
                ]);

                # 待添加整件非正常检修记录
                $insertUnCycleReportData[] = [
                    'entire_instance_identity_code' => $entireInstance['identity_code'],
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                ];
            }

            # 插入检修单
            DB::table('fix_workflows')->insert($insertFixWorkflowData);

            # 插入非正常检修记录
            DB::table('un_cycle_fix_reports')->insert($insertUnCycleReportData);

            # 修改实例中部件的状态
            DB::table('part_instances')->whereIn('entire_instance_identity_code', $entireInstanceIdentityCodes)->update(['status' => 'FIXING']);
        });

        return count($insertFixWorkflowData);
    }

    /**
     * 根据整件编号列表批量创建检修单
     * @param array $entireInstanceIdentityCodes
     * @return int
     */
    final public function batchByEntireInstanceIdentityCodes(array $entireInstanceIdentityCodes): int
    {
        $insertFixWorkflowData = [];
        $insertUnCycleReportData = [];
        $fixWorkflowSerialNumbersWaitInsert = [];

        DB::transaction(function () use (
            $entireInstanceIdentityCodes,
            &$insertFixWorkflowData,
            &$insertUnCycleReportData,
            &$fixWorkflowSerialNumbersWaitInsert
        ) {
            $currentDatetime = date('Y-m-d H:i:s');
            $i = 0;
            # 创建检修单
            foreach ($entireInstanceIdentityCodes as $entireInstanceIdentityCode) {
                $i += 1;

                # 待插入检修单列表
                $insertFixWorkflowData[] = [
                    'created_at' => $currentDatetime,
                    'updated_at' => $currentDatetime,
                    'entire_instance_identity_code' => $entireInstanceIdentityCode,
                    'status' => 'FIXING',
                    'processor_id' => 0,
                    'serial_number' => $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW') . "{$i}",
                    'stage' => 'UNFIX',
                    'type' => 'FIX',
                    'note' => '周期自动生成',
                    'is_cycle' => false,
                ];

                # 待修改整件状态
                DB::table('entire_instances')
                    ->where('identity_code', $entireInstanceIdentityCode)
                    ->update([
                        'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                        'status' => 'FIXING',
                        'in_warehouse' => false
                    ]);

                # 待添加整件非正常检修记录
                $insertUnCycleReportData[] = [
                    'entire_instance_identity_code' => $entireInstanceIdentityCode,
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                ];
            }

            # 插入检修单
            DB::table('fix_workflows')->insert($insertFixWorkflowData);

            # 插入非正常检修记录
            DB::table('un_cycle_fix_reports')->insert($insertUnCycleReportData);

            # 修改实例中部件的状态
            DB::table('part_instances')->whereIn('entire_instance_identity_code', $entireInstanceIdentityCodes)->update(['status' => 'FIXING']);
        });

        return count($insertFixWorkflowData);
    }

    /**
     * 生成模拟数据（空检测值）
     * @param EntireInstance $entireInstance
     * @param string|null $fixedAt
     * @param string|null $checkedAt
     * @param int|null $fixerId
     * @param int|null $checkerId
     * @param string|null $spotCheckedAt
     * @param int|null $spotCheckerId
     * @throws FixWorkflowException
     * @throws \Throwable
     */
    final public function mockEmpty(
        EntireInstance $entireInstance,
        string $fixedAt = null,
        string $checkedAt = null,
        int $fixerId = null,
        int $checkerId = null,
        string $spotCheckedAt = null,
        int $spotCheckerId = null
    )
    {
        try {
            $workAreas = ['S03' => 1, 'Q1' => 2];
            $workArea = in_array($entireInstance->category_unique_code, $workAreas) ? $workAreas[$entireInstance->category_unqiue_code] : 3;

            if (!$fixerId) {
                $fixers = Account::with([])->where('work_area', $workArea)->get();
                $fixer = $fixers->random();
            } else {
                $fixer = Account::with([])->where('id', $fixerId)->first();
            }
            if (!$checkerId) {
                $checkers = Account::with([])->where('work_area', $workArea)->get();
                $checker = $checkers->random();
            } else {
                $checker = Account::with([])->where('id', $checkerId)->first();
            }
            if ($spotCheckerId) {
                $spot_checker = Account::with([])->where('id', $spotCheckerId)->first();
            }

            if ($fixedAt) $fixedAt = Carbon::parse($fixedAt);
            if ($checkedAt) $checkedAt = Carbon::parse($checkedAt);
            if ($spotCheckedAt) $spotCheckedAt = Carbon::parse($spotCheckedAt);

            $old_fix_workflow = FixWorkflow::with(['EntireInstance'])->where('serial_number', $entireInstance->fix_workflow_serial_number)->first();
            if (!$old_fix_workflow || ($old_fix_workflow && @$old_fix_workflow->status ?: '' == 'CHECKED')) {
                # 创建检修单
                $fixWorkflow = FixWorkflow::with(['EntireInstance'])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d'),
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                    'status' => 'CHECKED',
                    'processor_id' => $checker->id,
                    'serial_number' => $fixWorkflowSn = CodeFacade::makeSerialNumber('FIX_WORKFLOW', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')),
                    'processed_times' => 0,
                    'stage' => 'CHECKED',
                    'type' => 'FIX',
                ]);

                # 同步设备检修单号
                $entireInstance->fix_workflow_serial_number = $fixWorkflowSn;  # 检修单号
                $entireInstance->last_fix_workflow_at = @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d');  # 检修时间
                $entireInstance->saveOrFail();

                # 记录日志（检修）
                EntireInstanceLog::with([])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'name' => '设备开始检修',
                    'description' => '',
                    'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                ]);

                # 修前检
                $fixWorkflowProcess1 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                    'stage' => 'FIX_BEFORE',
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fixWorkflowProcessSn1 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')) . '_1',
                    'numerical_order' => '1',
                    'is_allow' => 1,
                    'processor_id' => $fixer->id,
                    'processed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$fixWorkflowProcessSn1}.json",
                    'check_type' => 'JSON',
                    'upload_file_name' => "{$fixWorkflowProcessSn1}.json",
                ]);
                EntireInstanceLog::with([])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'name' => '修前检',
                    'description' => '检修人：' . $fixer->nickname,
                    'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                ]);
            } else {
                $fixWorkflow = $old_fix_workflow;
            }

            if ($fixedAt) {
                if ($fixer) {
                    # 修后检
                    $fixWorkflowProcess2 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'FIX_AFTER',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn2 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')) . '_2',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $fixer->id,
                        'processed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn2}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn2}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'name' => '修后检',
                        'description' => '检修人：' . $fixer->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    $fixWorkflow->EntireInstance->fill(['fixer_name' => $fixer->nickname, 'fixed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s')])->saveOrFail();
                }
            }

            if ($checkedAt) {
                if ($checker) {
                    # 验收
                    $fixWorkflowProcess3 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'CHECKED',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn3 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$checkedAt ? $checkedAt->format('Ymd') : date('Ymd')) . '_3',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $checker->id,
                        'processed_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn3}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn3}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'name' => '验收',
                        'description' => '验收人：' . $checker->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    # 修改设备和部件状态
                    $entireInstance
                        ->fill([
                            'status' => 'FIXED',
                            'checker_name' => $checker->nickname,
                            'checked_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        ])
                        ->saveOrFail();
                    PartInstance::with([])->where('entire_instance_identity_code', $entireInstance->identity_code)->update(['status' => 'FIXED']);
                }
            }

            if ($spotCheckedAt) {
                # 抽验
                if ($spot_checker) {
                    $fixWorkflowProcess4 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'CHECKED',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn4 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$spotCheckedAt ? $spotCheckedAt->format('Ymd') : date('Ymd')) . '_4',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $spot_checker->id,
                        'processed_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn4}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn4}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'name' => '抽验',
                        'description' => '抽验人：' . $checker->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    # 修改设备和部件状态
                    $entireInstance
                        ->fill([
                            'status' => 'FIXED',
                            'spot_checker_name' => $spot_checker->nickname,
                            'spot_checked_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        ])
                        ->saveOrFail();
                    PartInstance::with([])->where('entire_instance_identity_code', $entireInstance->identity_code)->update(['status' => 'FIXED']);
                }
            }
        } catch (ModelNotFoundException $e) {
            throw new FixWorkflowException('数据不存在', 404);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            throw $e;
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            throw $e;
        }
    }

    /**
     * 生成模拟数据（空检测值）不修改设备为成品
     * @param EntireInstance $entireInstance
     * @param string|null $fixedAt
     * @param string|null $checkedAt
     * @param int|null $fixerId
     * @param int|null $checkerId
     * @param string|null $spotCheckedAt
     * @param int|null $spotCheckerId
     * @throws FixWorkflowException
     * @throws \Throwable
     */
    final public function mockEmptyWithOutEditFixed(
        EntireInstance $entireInstance,
        string $fixedAt = null,
        string $checkedAt = null,
        int $fixerId = null,
        int $checkerId = null,
        string $spotCheckedAt = null,
        int $spotCheckerId = null
    )
    {
        try {
            $workAreas = ['S03' => 1, 'Q1' => 2];
            $workArea = in_array($entireInstance->category_unique_code, $workAreas) ? $workAreas[$entireInstance->category_unqiue_code] : 3;

            if (!$fixerId) {
                $fixers = Account::with([])->where('work_area', $workArea)->get();
                $fixer = $fixers->random();
            } else {
                $fixer = Account::with([])->where('id', $fixerId)->first();
            }
            if (!$checkerId) {
                $checkers = Account::with([])->where('work_area', $workArea)->get();
                $checker = $checkers->random();
            } else {
                $checker = Account::with([])->where('id', $checkerId)->first();
            }
            if ($spotCheckerId) {
                $spot_checker = Account::with([])->where('id', $spotCheckerId)->first();
            }

            if ($fixedAt) $fixedAt = Carbon::parse($fixedAt);
            if ($checkedAt) $checkedAt = Carbon::parse($checkedAt);
            if ($spotCheckedAt) $spotCheckedAt = Carbon::parse($spotCheckedAt);

            $old_fix_workflow = FixWorkflow::with(['EntireInstance'])->where('serial_number', $entireInstance->fix_workflow_serial_number)->first();
            if (!$old_fix_workflow || ($old_fix_workflow && @$old_fix_workflow->status ?: '' == 'CHECKED')) {
                # 创建检修单
                $fixWorkflow = FixWorkflow::with(['EntireInstance'])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d'),
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                    'status' => 'CHECKED',
                    'processor_id' => $checker->id,
                    'serial_number' => $fixWorkflowSn = CodeFacade::makeSerialNumber('FIX_WORKFLOW', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')),
                    'processed_times' => 0,
                    'stage' => 'CHECKED',
                    'type' => 'FIX',
                ]);

                # 同步设备检修单号
                $entireInstance->fix_workflow_serial_number = $fixWorkflowSn;  # 检修单号
                $entireInstance->last_fix_workflow_at = @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d');  # 检修时间
                $entireInstance->saveOrFail();

                # 记录日志（检修）
                EntireInstanceLog::with([])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'name' => '设备开始检修',
                    'description' => '',
                    'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                ]);

                # 修前检
                $fixWorkflowProcess1 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                    'stage' => 'FIX_BEFORE',
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fixWorkflowProcessSn1 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')) . '_1',
                    'numerical_order' => '1',
                    'is_allow' => 1,
                    'processor_id' => $fixer->id,
                    'processed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$fixWorkflowProcessSn1}.json",
                    'check_type' => 'JSON',
                    'upload_file_name' => "{$fixWorkflowProcessSn1}.json",
                ]);
                EntireInstanceLog::with([])->create([
                    'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                    'name' => '修前检',
                    'description' => '检修人：' . $fixer->nickname,
                    'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                ]);
            } else {
                $fixWorkflow = $old_fix_workflow;
            }

            if ($fixedAt) {
                if ($fixer) {
                    # 修后检
                    $fixWorkflowProcess2 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'FIX_AFTER',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn2 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$fixedAt ? $fixedAt->format('Ymd') : date('Ymd')) . '_2',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $fixer->id,
                        'processed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn2}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn2}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'updated_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s'),
                        'name' => '修后检',
                        'description' => '检修人：' . $fixer->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    $entireInstance->fill(['fixer_name' => $fixer->nickname, 'fixed_at' => @$fixedAt ? $fixedAt->format('Y-m-d') : date('Y-m-d H:i:s')])->saveOrFail();
                }
            }

            if ($checkedAt) {
                if ($checker) {
                    # 验收
                    $fixWorkflowProcess3 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'CHECKED',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn3 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$checkedAt ? $checkedAt->format('Ymd') : date('Ymd')) . '_3',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $checker->id,
                        'processed_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn3}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn3}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        'name' => '验收',
                        'description' => '验收人：' . $checker->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    # 修改设备和部件状态
                    $entireInstance
                        ->fill([
                            'checker_name' => $checker->nickname,
                            'checked_at' => @$checkedAt ? $checkedAt->format('Y-m-d') : date('Y-m-d'),
                        ])
                        ->saveOrFail();
                    PartInstance::with([])
                        ->where('entire_instance_identity_code', $entireInstance->identity_code)
                        ->update(['status' => 'FIXED']);
                }
            }

            if ($spotCheckedAt) {
                # 抽验
                if ($spot_checker) {
                    $fixWorkflowProcess4 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
                        'created_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                        'stage' => 'CHECKED',
                        'type' => 'ENTIRE',
                        'auto_explain' => '',
                        'serial_number' => $fixWorkflowProcessSn4 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', @$spotCheckedAt ? $spotCheckedAt->format('Ymd') : date('Ymd')) . '_4',
                        'numerical_order' => '1',
                        'is_allow' => 1,
                        'processor_id' => $spot_checker->id,
                        'processed_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'upload_url' => "/check/{$fixWorkflowProcessSn4}.json",
                        'check_type' => 'JSON',
                        'upload_file_name' => "{$fixWorkflowProcessSn4}.json",
                    ]);
                    EntireInstanceLog::with([])->create([
                        'created_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'updated_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        'name' => '抽验',
                        'description' => '抽验人：' . $checker->nickname,
                        'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                        'type' => 2,
                        'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
                    ]);
                    # 修改设备和部件状态
                    $entireInstance
                        ->fill([
                            'spot_checker_name' => $spot_checker->nickname,
                            'spot_checked_at' => @$spotCheckedAt ? $spotCheckedAt->format('Y-m-d') : date('Y-m-d'),
                        ])
                        ->saveOrFail();
                    PartInstance::with([])
                        ->where('entire_instance_identity_code', $entireInstance->identity_code)
                        ->update(['status' => 'FIXED']);
                }
            }
        } catch (ModelNotFoundException $e) {
            throw new FixWorkflowException('数据不存在', 404);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            throw $e;
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            throw $e;
        }
    }
}


