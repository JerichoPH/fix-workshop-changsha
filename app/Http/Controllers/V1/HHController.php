<?php

namespace App\Http\Controllers\V1;

use App\Facades\BreakdownLogFacade;
use App\Http\Controllers\Controller;
use App\Model\BreakdownLog;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\Maintain;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;

class HHController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * 接口1：根据车站获取设备列表
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getEntireInstancesByStationName()
    {
        try {
            $entire_instances = EntireInstance::with([
                'Station',
                'Station.Parent',
                'WarehouseReportByOut',
                'Factory'
            ])
                ->where('maintain_station_name', request('station_name'))
                ->get();

            $statuses = ['INSTALLED' => '在道', 'FIXING' => '在修', 'INSTALLING' => '备品', 'SCRAP' => '废品'];
            $statuses2 = array_flip(EntireInstance::$STATUSES);

            $ret = [];
            $i = 0;
            foreach ($entire_instances as $entire_instance) {
                if ($i > -1) {
                    $i++;
                    $ret[] = [
                        'vcCode' => @$entire_instance->identity_code ?: '',  # 设备唯一编号
                        'vcOrgCode' => '',  # 机构代码
                        'vcOrgName' => '',  # 机构名称
                        'vcStationCode' => @$entire_instance->Station->unique_code ?: '',  # 车站名称
                        'vcStationName' => @$entire_instance->Station->name ?: '',  # 车站
                        'vcModelCode' => @$entire_instance->model_unique_code ?: '',  # 型号
                        'vcModelName' => @$entire_instance->model_name ?: '',  # 型号名称
                        'vcFactoryCode' => @$entire_instance->Factory ? $entire_instance->Factory->name : '',  # 厂家编号
                        'vcFactoryName' => @$entire_instance->factory_name ?: '',  # 厂家名称
                        'vcFactoryNumber' => $entire_instance->factory_device_code ?: '',  # 厂编号
                        'dtFactoryDate' => @$entire_instance->made_at ? Carbon::createFromFormat('Y-m-d H:i:s', $entire_instance->made_at)->toDateString() : '',  # 出厂日期
                        'dtEquUseDate' => @$entire_instance->last_installed_time ? date('Y-m-d', $entire_instance->last_installed_time) : '',  # 上道时间
                        'vcEquUsePlace' => @$entire_instance->maintai_location_code ?: @$entire_instance->crossroad_number,  # 安装位置
//                        'nState' => @$statuses[$statuses2[$entire_instance->status]] ?: '其他',  # 状态
                        'nState' => $statuses2[$entire_instance->status] ?: '其他',  # 状态
                        'vcPicUrl' => '',  # 图片URL
                        'vcPicName' => '',  # 图片名称
                        'vcFileUrl' => '',  # 图纸URL
                        'vcFileName' => '',  # 图纸名称
//                        'dtCs' => @$entire_instance->WarehouseReportByOut->updated_at ? Carbon::createFromFormat('Y-m-d H:i:s', $entire_instance->WarehouseReportByOut->updated_at)->toDateString() : '',  # 最近一次出所日期
                        'dtCs' => $entire_instance->last_out_at ? explode(' ', $entire_instance->last_out_at)[0] : '',  # 最近一次出所日期
                        'vcCsNumber' => @$entire_instance->last_warehouse_report_serial_number_by_out ?: '',  # 最近一次出所编号
                        'dtUseLife' => @$entire_instance->scarping_at ? Carbon::createFromFormat('Y-m-d H:i:s', $entire_instance->scarping_at)->toDateString() : '',  # 报废日期
//                        'traction' => $entire_instance->traction ?? '',  # 牵引
//                        'open_direction' => $entire_instance->open_direction ?? '',  # 开向
//                        'line_name' => $entire_instance->line_name ?? '',  # 线制
//                        'said_rod' => $entire_instance->said_rod ?? '',  # 表示干特征
//                        'crossroad_type' => $entire_instance->crossroad_type ?? '',  # 道岔类型
//                        'point_switch_group_type' => $entire_instance->point_switch_group_type ?? '',  # 转辙机分组类型
//                        'extrusion_protect' => $entire_instance->extrusion_protect ? '有' : '无',  # 是否有防挤压好护罩
                        'remark' => '',  # 备注
//                        'serial_number' => $entire_instance->serial_number,  # 设备所编号
                    ];
                }
            }

            return response()->json(['content' => '读取成功', 'ret' => 0, 'total' => count($ret), 'data' => $ret], 200, [], 512);
        } catch (ModelNotFoundException $e) {
            return response()->json(['content' => '数据不存在', 'details' => [class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine()], 'ret' => -2, 'total' => 0, 'data' => []], 404, [], 512);
        } catch (\Throwable $e) {
            return response()->json(['content' => '意外错误', 'details' => [class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine()], 'ret' => -1, 'total' => 0, 'data' => []], 500, [], 512);
        }
    }

    /**
     * 接口7 通过唯一编号，获取检修单
     * @param string $identityCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getEntireInstance(string $identityCode)
    {
        return response()
            ->json(
                EntireInstance::with([
                    'FixWorkflows',
                    'FixWorkflows.FixWorkflowProcesses',
                    'FixWorkflows.FixWorkflowProcesses.FixWorkflowRecords',
                ])
                    ->where('identity_code', $identityCode)
                    ->first()
            );
    }

    /**
     * 接口2：根据设备唯一编号获取检修记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postFixWorkflowsByEIID(Request $request)
    {
        try {
            $vc_codes = $request->get('vcCodes');
            if (!$vc_codes) return response()->json(['content' => '设备唯一编号不能为空', 'ret' => -2, 'total' => 0, 'data' => []], 404, [], 512);

            $fix_workflows = FixWorkflow::with([
                'FixWorkflowProcesses' => function ($FixWorkflowProcesses) {
                    $FixWorkflowProcesses->orderByDesc('id');
                },
                'EntireInstance',
                'EntireInstance.Station',
            ])
                ->whereIn('entire_instance_identity_code', explode(',', $vc_codes))
                ->orderByDesc('id')
                ->get();

            $ret = collect([]);
            foreach ($fix_workflows as $fix_workflow) {
                $last_fixer = FixWorkflowProcess::with(['Processor'])
                    ->where('fix_workflow_serial_number', $fix_workflow->serial_number)
                    ->whereIn('stage', ['FIX_BEFORE', 'FIX_AFTER'])
                    ->orderByDesc('id')
                    ->first();

                $last_fixer_nickname = $last_fixer ? $last_fixer->Processor->nickname : '无';

                $ret->push([
                    'vcRepairNumber' => $fix_workflow->serial_number,  # 检修单唯一编号
                    'vcRepairName' => $last_fixer_nickname,  # 最后检修人
                    'vcRepairPlace' => env('ORGANIZATION_NAME') . '电务段检修基地',  # 所属电务段
                    'dtRepairTime' => $fix_workflow->updated_at->toDateString(),  # 检修时间
                    'vcRepairType' => '',  # 检修类型
                    'vcCode' => $fix_workflow->entire_instance_identity_code,  # 设备唯一编号
                    'vcOrgCode' => '', # 所属机构代码
                    'vcOrgName' => '',  # 所属机构名称
                    'vcStationCode' => $fix_workflow->EntireInstance->Station->unique_code,  # 车站代码
                    'vcStationName' => $fix_workflow->EntireInstance->Station->name,  # 车站名称
                    'vcModelCode' => $fix_workflow->EntireInstance->model_unique_code,  # 型号代码
                    'vcModelName' => $fix_workflow->EntireInstance->model_name,  # 型号名称
                    'nState' => $fix_workflow->EntireInstance->status,  # 状态
                    'dtLastModifyDate' => $fix_workflow->EntireInstance->updated_at->toDateString(),  # 最后更新时间
                    'nStageState' => '',  # 检修阶段
                    'remark' => '',  # 备注
                ]);
            }

            return response()->json(['content' => '读取成功', 'ret' => 0, 'total' => $ret->count(), 'data' => $ret], 200, [], 512);
        } catch (ModelNotFoundException $e) {
            return response()->json(['content' => '数据不存在', 'ret' => -2, 'total' => 0, 'data' => []], 404, [], 512);
        } catch (\Throwable $th) {
            return response()->json(['content' => '意外错误', 'ret' => -1, 'total' => 0, 'data' => []], 500, [], 512);
        }
    }

    /**
     * 接口3：根据检修过程获取具体检测记录
     * @param Request $request
     * @param string $fix_workflow_sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getRecodesByProcessSN(Request $request, string $fix_workflow_sn)
    {
        try {
            $func1 = function () use ($request, $fix_workflow_sn) {
                $ret = [];
                $fix_workflow_processes = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $fix_workflow_sn)->get();

                foreach ($fix_workflow_processes as $fix_workflow_process) {
                    if ($fix_workflow_process->check_type == 'JSON' && $fix_workflow_process->upload_file_name) {
                        if (!is_file(public_path("check/{$fix_workflow_process->upload_file_name}"))) continue;
                        $fix_workflow_recode_file = json_decode(file_get_contents(public_path("check/{$fix_workflow_process->upload_file_name}")), true);

                        $fix_workflow_recodes = $fix_workflow_recode_file['body']['测试项目'];
                        $fix_workflow_header = $fix_workflow_recode_file['header'];
                        foreach ($fix_workflow_recodes as $fix_workflow_recode) {
                            $ret[] = [
                                'vcId' => $fix_workflow_recode['流水号'],  # 检测记录流水号
                                'vcPid' => $fix_workflow_process->fix_workflow_serial_number,  # 检修单号
                                'vcCode' => $fix_workflow_header['条码编号'],  # 设备唯一编号
                                'vcWhole' => $fix_workflow_recode['类型'],  #整件/部件
                                'vcCheckItem' => $fix_workflow_recode['项目编号'],  # 检测项
                                'vcStandardValue' => $fix_workflow_recode['标准值'],  # 标准值
                                'vcCheckValue' => $fix_workflow_recode['测试值'],  # 实测值
                                'vcCheckResult' => $fix_workflow_recode['判定结论'] == 1 ? '合格' : '不合格',  # 检测结果
                                'vcCheckPeople' => $fix_workflow_process->Processor->nickname ?? '无',  # 经办人
                                'dtCheckTime' => $fix_workflow_process->processed_at ?? '无',  # 检测时间
                                'remark' => $fix_workflow_process->stage ?? '无'  # 备注
                            ];
                        }
                    }
                }

                return $ret;
            };

            $func2 = function () use ($request, $fix_workflow_sn) {
                $fix_workflow_recodes = FixWorkflowRecord::with([
                    'FixWorkflowProcess',
                    'Processor',
                    'Measurement',
                ])
                    ->whereHas('FixWorkflowProcess', function ($FixWorkflowProcess) use ($fix_workflow_sn) {
                        $FixWorkflowProcess->where('fix_workflow_serial_number', $fix_workflow_sn);
                    })
                    ->get();

                $types = ['ENTIRE' => 1, 'PART' => 2];
                $is_allows = ['不合格', '合格'];

                $ret = [];
                foreach ($fix_workflow_recodes as $fix_workflow_recode) {
                    $ret[] = [
                        'vcId' => $fix_workflow_recode->serial_number,  # 检测记录流水号
                        'vcPid' => $fix_workflow_recode->FixWorkflowProcess->fix_workflow_serial_number,  # 检修单号
                        'vcCode' => $fix_workflow_recode->entire_instance_identity_code,  # 设备唯一编号
                        'vcWhole' => $types[$fix_workflow_recode->type],  # 整件/部件
                        'vcCheckItem' => @$fix_workflow_recode->Measurement->key ?: '无',  # 检测项
                        'vcStandardValue' => @$fix_workflow_recode->Measurement->allow_min . ' ~ ' . @$fix_workflow_recode->Measurement->allow_max . @$fix_workflow_recode->Measurement->unit,  # 标准值
                        'vcCheckValue' => $fix_workflow_recode->measured_value . @$fix_workflow_recode->Measurement->unit,  # 实测值
                        'vcCheckResult' => $is_allows[$fix_workflow_recode->is_allow],  # 检测结果
                        'vcCheckPeople' => @$fix_workflow_recode->Processor->nickname ?: '无',  # 经办人
                        'dtCheckTime' => @$fix_workflow_recode->updated_at ? $fix_workflow_recode->updated_at->toDateString() : '',  # 检测时间
                        'remark' => $fix_workflow_recode->FixWorkflowProcess->stage,  # 备注
                    ];
                }

                return $ret;
            };

            $ret = $func2();
            return response()->json(['content' => '读取成功', 'ret' => 0, 'total' => count($ret), 'data' => $ret], 200, [], 512);
        } catch (ModelNotFoundException $e) {
            return response()->json(['content' => '数据不存在', 'ret' => -2, 'total' => 0, 'data' => []], 404, [], 512);
        } catch (\Throwable $th) {
            return response()->json(['content' => '意外错误', 'details' => [class_basename($th), $th->getMessage(), $th->getFile(), $th->getLine()], 'ret' => -1, 'total' => 0, 'data' => []], 500, [], 512);
        }
    }

    /**
     * 接口4：上报现场故障描述
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBreakdownExplain(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $now = date('Y-m-d H:i:s');
                $maintain_station = Maintain::with(['Parent'])->where('name', $request->get('stationName'))->first();
                if (!$maintain_station) return HttpResponseHelper::errorEmpty('车站不存在');
                if (!$request->get('submitterName')) return HttpResponseHelper::errorEmpty('上报人不能为空');
                if (!$request->get('submittedAt')) return HttpResponseHelper::errorEmpty('上报时间不能为空');

                $breakdown_logs = [];  # 现场故障描述
                $entire_instance_logs = [];  # 设备日志
                foreach ($request->get('vcCodes') as $identity_code) {
                    $entire_instance = EntireInstance::with([])->where('identity_code', $identity_code)->first();
                    if (!$entire_instance) return HttpResponseHelper::errorEmpty("设备不存在：{$identity_code}");

                    # 现场故障描述
                    BreakdownLogFacade::createStation(
                        $entire_instance,
                        $request->get('vcGzxx') ?? '',  # 故障描述
                        $request->get('dtGzTime') ?? '', # 上报时间
                        $request->get('crossroadNumber') ?? '',  # 道岔号
                        $request->get('submitterName') ?? ''  # 上报人
                    );

                    # 故障记录
                    $breakdown_logs[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'entire_instance_identity_code' => $identity_code,  # 设备唯一编号
                        'explain' => $request->get('vcGzxx'),  # 故障描述
                        'scene_workshop_name' => @$maintain_station->Parent->name,  # 设备安装现场车间
                        'maintain_station_name' => $maintain_station->name,  # 设备安装车站
                        'maintain_location_code' => $entire_instance->maintain_location_code,  # 设备组合位置
                        'crossroad_number' => $request->get('crossroadNumber'),  # 道岔号
                        'type' => 'STATION',  # 现场故障类型
                        'submitter_name' => $request->get('submitterName'),  # 上报人姓名
                        'submitted_at' => $request->get('dtGzTime'),  # 上报时间
                    ];

                    # 设备日志
                    $entire_instance_logs[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'name' => '现场故障描述',
                        'description' => $request->get('vcGzxx'),
                        'entire_instance_identity_code' => $identity_code,
                        'type' => 5,
                    ];
                }
                # 写入故障描述日志
                BreakdownLog::with([])->insert($breakdown_logs);
                # 写入设备日志
                EntireInstanceLog::with([])->insert($entire_instance_logs);

                return true;
            });

            return response()->json(['content' => '操作成功', 'ret' => 0, 'total' => 0, 'data' => []], 200, [], 512);
        } catch (ModelNotFoundException $e) {
            return response()->json(['content' => '数据不存在', 'ret' => -2, 'total' => 0, 'data' => []], 404, [], 512);
        } catch (\Throwable $th) {
            return response()->json(['content' => '意外错误', 'ret' => -1, 'total' => 0, 'data' => []], 500, [], 512);
        }
    }

    /**
     * 接口5：设备日志
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postEntireInstanceLogsByEIID(Request $request)
    {
        try {
            $entireInstanceLogs = EntireInstanceLog::with([])
                ->select([
                    'created_at',
                    'name',
                    'description',
                    'entire_instance_identity_code',
                ])
                ->whereIn('entire_instance_identity_code', explode(',', $request->get('vcCodes')))
                ->get();

            return response()->json(['content' => '读取成功', 'ret' => 0, 'total' => $entireInstanceLogs->count(), 'data' => $entireInstanceLogs], 200, [], 512);
        } catch (ModelNotFoundException $e) {
            return response()->json(['content' => '数据不存在', 'ret' => -2, 'total' => 0, 'data' => []], 200, [], 512);
        } catch (\Throwable $e) {
            return response()->json(['content' => '意外错误', 'details' => [class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine()], 'ret' => -1, 'total' => 0, 'data' => []], 500, [], 512);
        }
    }
}
