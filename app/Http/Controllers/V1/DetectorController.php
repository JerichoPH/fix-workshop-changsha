<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;
use Throwable;

class DetectorController extends Controller
{
    final public function store(Request $request)
    {
        try {
            $request_data = $request->all();
            if (empty($request_data)) return JsonResponseFacade::errorValidate('没有收到检测数据');

            ['header' => $request_header, 'body' => $request_body] = $request_data;
            if (empty($request_header)) return JsonResponseFacade::errorValidate('没有收到检测数据(数据头)');
            if (empty($request_body)) return JsonResponseFacade::errorValidate('没有收到检测数据(数据体)');

            $entire_instance = EntireInstance::with([])->where('identity_code', $request_header['identity_code'] ?? '')->firstOrFail();
            $processor = null;
            $processor = Account::with([])->where('nickname', $request_header['tester_name'])->first();
            if (!$processor) return JsonResponseFacade::errorEmpty('检测人不存在');

            $is_allow = false;
            $conclusions = array_unique(array_pluck($request_body, 'conclusion'));
            switch (count($conclusions)) {
                case 0:
                    return JsonResponseFacade::errorValidate('检测数据(数据体)中缺少判定结果');
                case 1:
                    $is_allow = boolval(array_first($conclusions));
                    break;
            }

            $stages = [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
                '抽验' => 'WORKSHOP',
            ];
            if (!@$stages[$request_header['record_type']]) return JsonResponseFacade::errorValidate('阶段只能是：' . implode('、', array_keys($stages)));
            $current_stage = $stages[$request_header['record_type']] ?? '';

            $current_status = ($current_stage != 'CHECKED' && $current_stage != 'WORKSHOP') ? 'FIXING' : ($is_allow ? 'FIXED' : 'FIXING');

            try {
                $testing_time = @$request_header['testing_time'] ?? '';
                $processed_at = Carbon::parse($testing_time);
            } catch (\Exception $e) {
                return JsonResponseFacade::errorForbidden("检测时间格式不正确：{$testing_time}");
            }

            DB::beginTransaction();
            # 创建检修单
            $fix_workflow = FixWorkflow::with(['EntireInstance'])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'status' => $current_stage,
                    'processor_id' => $processor->id ?? 0,
                    'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW', $processed_at->format('Ymd')),
                    'processed_times' => 0,
                    'stage' => 'CHECKED',
                    'type' => 'FIX',
                ]);

            # 记录日志（检修）
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'name' => '开始检修',
                    'description' => '',
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $processor->id,
                    'station_unique_code' => '',
                ]);

            $fix_workflow_process_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $processed_at->format('Ymd'));
            $dest_dir = public_path('/check');
            if (!is_dir($dest_dir)) FileSystem::init(__FILE__)->makeDir($dest_dir);
            $filename = "{$fix_workflow_process_sn}.json";
            file_put_contents("{$dest_dir}/{$filename}", json_encode($request_data));

            $fix_workflow_process = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                    'stage' => $current_stage,
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fix_workflow_process_sn,
                    'numerical_order' => '1',
                    'is_allow' => intval($is_allow),
                    'processor_id' => $processor->id,
                    'processed_at' => $processed_at->format('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$filename}",
                    'check_type' => 'JSON2',
                    'upload_file_name' => $filename,
                ]);
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'name' => FixWorkflowProcess::$STAGE[$current_stage],
                    'description' => '操作人：' . $processor->nickname,
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $processor->id,
                    'station_unique_code' => '',
                ]);
            $fix_workflow
                ->EntireInstance
                ->fill([
                    'status' => $current_status,
                    'fixer_name' => $processor->nickname,
                    'fixed_at' => $processed_at->format('Y-m-d H:i:s'),
                    'checker_name' => ($current_status === 'FIXED' && in_array($current_stage, ['CHECKED', 'WORKSHOP'])) ? $processor->nickname : '',
                    'checked_at' => ($current_status === 'FIXED' && in_array($current_stage, ['CHECKED', 'WORKSHOP'])) ? $processed_at->format('Y-m-d H:i:s') : null,
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                ])
                ->saveOrFail();

            DB::table('part_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);
            DB::commit();

            return JsonResponseFacade::created([], '上传成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备器材");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    final public function postFile(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with([])->where('identity_code', $request->get('identity_code'))->firstOrFail();
            $fixer = null;
            $fixer = Account::with([])->where('nickname', $request->get('tester_name'))->first();
            // if (!$fixer) return JsonResponseFacade::errorEmpty('检测人不存在');
            if (!$request->hasFile('file')) return JsonResponseFacade::errorValidate('文件上传失败');
            $is_allow = boolval($request->get('is_allow'));

            $file = $request->file('file');
            $allowed_extensions = ['pdf'];
            if (!in_array($file->getClientOriginalExtension(), $allowed_extensions))
                return JsonResponseFacade::errorValidate('只能上传' . implode(',', $allowed_extensions) . '格式的文件');

            $stages = [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
                '抽验' => 'WORKSHOP',
            ];
            $current_stage = $stages[$request->get('record_type')] ?? 'CHECKED';
            $current_status = ($current_stage != 'CHECKED' && $current_stage != 'WORKSHOP') ? 'FIXING' : 'FIXED';

            try {
                $fixed_at = Carbon::parse($request->get('testing_time'));
            } catch (\Exception $e) {
                return JsonResponseFacade::errorForbidden("检测时间格式不正确：{$request->get('testing_time')}");
            }

            DB::beginTransaction();
            # 创建检修单
            $fix_workflow = FixWorkflow::with(['EntireInstance'])
                ->create([
                    'created_at' => $fixed_at,
                    'updated_at' => $fixed_at,
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'status' => $current_stage,
                    'processor_id' => $fixer->id ?? 0,
                    'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW', $fixed_at->format('Ymd')),
                    'processed_times' => 0,
                    'stage' => 'CHECKED',
                    'type' => 'FIX',
                ]);

            # 记录日志（检修）
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $fixed_at,
                    'updated_at' => $fixed_at,
                    'name' => '开始检修',
                    'description' => '',
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $fixer->id ?? 0,
                    'station_unique_code' => '',
                ]);

            $fix_workflow_process_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $fixed_at->format('Ymd'));
            $dest_dir = public_path('/check');
            if (!is_dir($dest_dir)) FileSystem::init(__FILE__)->makeDir($dest_dir);
            $extension = $file->getClientOriginalExtension();
            $filename = "{$fix_workflow_process_sn}.{$extension}";
            $file->move($dest_dir, $filename);

            $fix_workflow_process = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])
                ->create([
                    'created_at' => $fixed_at,
                    'updated_at' => $fixed_at,
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                    'stage' => $current_stage,
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fix_workflow_process_sn,
                    'numerical_order' => '1',
                    'is_allow' => intval($request->get('is_allow')),
                    'processor_id' => $fixer->id,
                    'processed_at' => $fixed_at->format('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$filename}",
                    'check_type' => strtoupper($extension),
                    'upload_file_name' => $filename,
                ]);
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $fixed_at,
                    'updated_at' => $fixed_at,
                    'name' => FixWorkflowProcess::$STAGE[$current_stage],
                    'description' => '操作人：' . $fixer->nickname ?? $request->get('tester_name'),
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $fixer->id ?? 0,
                    'station_unique_code' => '',
                ]);
            $fix_workflow
                ->EntireInstance
                ->fill([
                    'status' => $is_allow ? 'FIXED' : 'FIXING',
                    'fixer_name' => $fixer->nickname ?? $request->get('tester_name'),
                    'fixed_at' => $fixed_at->format('Y-m-d H:i:s'),
                    'checker_name' => $is_allow ? $fixer->nickname ?? $request->get('tester_name') : '',
                    'checked_at' => $is_allow ? $fixed_at->format('Y-m-d H:i:s') : null,
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                ])
                ->saveOrFail();

            DB::table('part_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);
            DB::commit();

            return JsonResponseFacade::created([], '上传成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备器材");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
