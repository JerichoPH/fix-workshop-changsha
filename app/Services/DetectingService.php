<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DetectingService
{
    /**
     * 安路信 衡阳
     * @param array $data
     * @param array $config
     * @return bool
     * @throws \Throwable
     */
    public function ALX_B051(array $data, array $config)
    {
        $header = $data['header'];
        $body = $data['body'];
        $currentDatetime = date('Y-m-d H:i:s');
        # 获取设备信息
        $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->where('identity_code', $header['条码编号'])->firstOrFail();
//        $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->where('serial_number', $header['条码编号'])->firstOrFail();

        # 检查是否通过
        $isAllow = true;
        foreach ($body['测试项目'] as $item) {
            if ($item['判定结论'] == 0) {
                $isAllow = false;
                break;
            }
        }
        # 创建检测单
        if ($entireInstance->FixWorkflow == null) {
            # 不存在的检修单，新建检修单
            \App\Facades\FixWorkflowFacade::makeByEntireInstanceIdentityCode($entireInstance->identity_code);
            $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->where('serial_number', $header['条码编号'])->firstOrFail();
        }

        $fixWorkflowProcessesCount = $entireInstance->FixWorkflow == null ? 0 : count($entireInstance->FixWorkflow->FixWorkflowProcesses);
        $fixWorkflowProcess = new FixWorkflowProcess;
        $processId = DB::table('accounts')->where('identity_code', $header['测试人'])->first(['id']) ? DB::table('accounts')->where('identity_code', $header['测试人'])->first(['id'])->id : 1;
        $fixWorkflowProcess->fill([
            'fix_workflow_serial_number' => $entireInstance->fix_workflow_serial_number,
            'note' => '',
            'stage' => $config['stage'][$header['记录类型']],
            'type' => 'ENTIRE',
            'auto_explain' => '检测台自动上报数据',
            'serial_number' => $fixWorkflowProcessSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS_ENTIRE'),
            'numerical_order' => $fixWorkflowProcessesCount + 1,
            'is_allow' => $isAllow,
            'processor_id' => $processId,
            'processed_at' => $header['time'],
        ])->saveOrFail();

        # 创建检测记录
        $insertFixWorkflowRecord = [];
        $i = 0;
        foreach ($body['测试项目'] as $item) {
            $i += 1;
            if (!DB::table('measurements')->where('identity_code', $item['项目编号'])->first()) continue;
            $insertFixWorkflowRecord[] = [
                'created_at' => $currentDatetime,
                'updated_at' => $currentDatetime,
                'fix_workflow_process_serial_number' => $fixWorkflowProcessSerialNumber,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'part_instance_identity_code' => '',
                'note' => '',
                'measurement_identity_code' => $item['项目编号'],
                'measured_value' => $item['测试值'],
                'processed_at' => $header['time'],
                'serial_number' => env('ORGANIZATION_CODE') . date('YmdHis') . time() . "_{$i}",
                'type' => DB::table('measurements')->where('identity_code', $item['项目编号'])->where('part_model_unique_code', '<>', null)->first() ? 'PART' : 'ENTIRE',
                'is_allow' => $item['判定结论'],
            ];
        }
        return DB::table('fix_workflow_records')->insert($insertFixWorkflowRecord);
    }

    /**
     * 安路信 株洲
     * @param array $data
     * @param array $config
     * @return bool
     * @throws \Throwable
     */
    public function ALX_B049(array $data, array $config)
    {
        $header = $data['header'];
        $body = $data['body'];
        $currentDatetime = date('Y-m-d H:i:s');
        # 获取设备信息
        $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->orWhere('identity_code', $header['条码编号'])->orWhere('serial_number', $header['条码编号'])->firstOrFail();
//        $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->where('serial_number', $header['条码编号'])->firstOrFail();

        # 检查是否通过
        $isAllow = true;
        foreach ($body['测试项目'] as $item) {
            if ($item['判定结论'] == '不合格') {
                $isAllow = false;
                break;
            }
        }
        # 创建检测单
        $fix_workflow_is_fixed = true;  # 上一张检修单已完成
        if ($entireInstance->FixWorkflow == null) $fix_workflow_is_fixed = false;  # 上一张检修单不存在
        if (!empty($entireInstance->FixWorkflow) && array_key_exists('status', $entireInstance->FixWorkflow))
            if (array_flip(FixWorkflow::$STATUS)[$entireInstance->FixWorkflow->status] != 'FIXED') $fix_workflow_is_fixed = false;  # 上一张检修单未完成
        if (!$fix_workflow_is_fixed) {
            # 当前设备检修单上一张检修单未完成或从未检测
            \App\Facades\FixWorkflowFacade::makeByEntireInstanceIdentityCode($entireInstance->identity_code);
            $entireInstance = EntireInstance::with(['FixWorkflow', 'FixWorkflow.FixWorkflowProcesses'])->orWhere('identity_code', $header['条码编号'])->orWhere('serial_number', $header['条码编号'])->firstOrFail();
        }
        $stage = array_key_exists($header['记录类型'], $config['stage']) ? $config['stage'][$header['记录类型']] : $config['stage']['验收'];
        $fixWorkflowProcessesCount = $entireInstance->FixWorkflow == null ? 0 : count($entireInstance->FixWorkflow->FixWorkflowProcesses);
        $fixWorkflowProcess = new FixWorkflowProcess;
        $processId = DB::table('accounts')->where('identity_code', $header['测试人'])->first(['id']) ? DB::table('accounts')->where('identity_code', $header['测试人'])->first(['id'])->id : 1;
        $fixWorkflowProcess->fill([
            'fix_workflow_serial_number' => $entireInstance->fix_workflow_serial_number,
            'note' => '',
            'stage' => $stage,
            'type' => 'ENTIRE',
            'auto_explain' => '安路信检测数据',
            'serial_number' => $fixWorkflowProcessSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS_ENTIRE'),
            'numerical_order' => $fixWorkflowProcessesCount + 1,
            'is_allow' => $isAllow,
            'processor_id' => $processId,
            'processed_at' => $header['time'],
        ])->saveOrFail();

        # 创建检测记录
        $insertFixWorkflowRecord = [];
        $i = 0;
        foreach ($body['测试项目'] as $item) {
            $i += 1;
            if (!DB::table('measurements')->where('identity_code', $item['项目编号'])->first()) continue;
            if (empty($item['判定结论'])) continue;

            if ($item['判定结论'] == '合格') {
                $tmp_is_allow = true;
            } else {
                $tmp_is_allow = false;
            }
            $insertFixWorkflowRecord[] = [
                'created_at' => $currentDatetime,
                'updated_at' => $currentDatetime,
                'fix_workflow_process_serial_number' => $fixWorkflowProcessSerialNumber,
                'entire_instance_identity_code' => $entireInstance->identity_code,
                'part_instance_identity_code' => '',
                'note' => '',
                'measurement_identity_code' => $item['项目编号'],
                'measured_value' => $item['测试值'],
                'processed_at' => $header['time'],
                'serial_number' => env('ORGANIZATION_CODE') . date('YmdHis') . time() . "_{$i}",
                'type' => DB::table('measurements')->where('identity_code', $item['项目编号'])->where('part_model_unique_code', '<>', null)->first() ? 'PART' : 'ENTIRE',
                'is_allow' => $tmp_is_allow,
            ];
        }

        # 修改状态
        if ($stage == 'CHECKED' && $isAllow) {
            DB::table('fix_workflows')->where('serial_number', $entireInstance->fix_workflow_serial_number)->update(['stage' => 'CHECKED', 'status' => 'FIXED']);
            $entireInstance->fill(['status' => 'FIXED'])->save();
        } else {
            DB::table('fix_workflows')->where('serial_number', $entireInstance->fix_workflow_serial_number)->update(['stage' => $config['stage'][$header['记录类型']], 'status' => 'FIXING']);
            $entireInstance->fill(['status' => 'FIXING'])->save();
        }

        # 写入日志
        DB::table('entire_instance_logs')->insert([
            'created_at' => Carbon::now()->format("Y-m-d H:i:s"),
            'updated_at' => Carbon::now()->format("Y-m-d H:i:s"),
            'name' => '检修完成',
            'description' => '安路信验收台导入',
            'entire_instance_identity_code' => $entireInstance->identity_code,
            'type' => 2,
            'url' => "/measurement/fixWorkflow/{$entireInstance->FixWorkflow->serial_number}/edit",
        ]);

        return DB::table('fix_workflow_records')->insert($insertFixWorkflowRecord);
    }
}
