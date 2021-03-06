<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Model\EntireInstance;
use App\Model\FixWorkflowProcess;
use App\Model\PivotInDeviceAndOutDevice;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class SearchController extends Controller
{
    /**
     * 搜索详情
     * @param $entireInstanceIdentityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($entireInstanceIdentityCode)
    {
        try {
            $entireInstance = EntireInstance::with([
                'EntireModel',
                'EntireModel.Category',
                'EntireModel.Category.PartCategories',
                'EntireModel.Measurements',
                'EntireModel.Measurements.PartModel',
                'WarehouseReportByOut',
                'PartInstances',
                'PartInstances.PartCategory',
                // 'PartInstances.PartModel',
                // 'PartInstances.PartModel.PartCategory',
                'FixWorkflows' => function ($fixWorkflow) {
                    $fixWorkflow->orderByDesc('id');
                },
                'FixWorkflow.WarehouseReport',
                'FixWorkflow.Processor',
                'FixWorkflow.FixWorkflowProcesses',
                'FixWorkflow.FixWorkflowProcesses.Measurement',
                'FixWorkflow.FixWorkflowProcesses.Processor',
                'FixWorkflow.FixWorkflowProcesses.Measurement.PartModel',
                'FixWorkflow.EntireInstance.PartInstances',
                'FixWorkflow.EntireInstance.PartInstances.PartModel',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
                'BreakdownLogs' => function ($BreakdownLogs) {
                    $BreakdownLogs->orderByDesc('id');
                },
                'BreakdownLogs.BreakdownTypes',
                'Station',
                'Station.Parent',
                'WithSendRepairInstances'
            ])
                ->withTrashed()
                ->where('identity_code', $entireInstanceIdentityCode)
                ->firstOrFail();

            # 已经存在的部件，循环空部件时不显示
            // $partCategoryIds = [];
            // foreach ($entireInstance->PartInstances as $partInstance)
            //     $partCategoryIds[] = $partInstance->PartModel->part_category_id;

            // # 获取最后一次检修人
            // $fixer = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'FIX_AFTER')->first();
            // # 获取最后一次验收人
            // $checker = FixWorkflowProcess::with(['Processor'])->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'CHECKED')->first();

            $entireInstanceLogs = DB::table('entire_instance_logs')
                ->where('deleted_at', null)
                ->where('entire_instance_identity_code', $entireInstanceIdentityCode)
                ->orderByDesc('id')
                ->get();

            $entireInstanceLogsWithMonth = [];
            $breakdownLogsWithMonth = [];
            try {
                foreach ($entireInstanceLogs as $entireInstanceLog) {
                    $month = Carbon::createFromFormat('Y-m-d H:i:s', $entireInstanceLog->created_at)->format('Y-m');
                    $entireInstanceLogsWithMonth[$month][] = $entireInstanceLog;
                }
                foreach ($entireInstance->BreakdownLogs as $breakdownLog) {
                    $month = Carbon::createFromFormat('Y-m-d H:i:s', $breakdownLog->created_at)->format('Y-m');
                    $breakdownLogsWithMonth[$month][] = $breakdownLog;
                }
            } catch (\Exception $e) {
            }
            krsort($entireInstanceLogsWithMonth);
            krsort($breakdownLogsWithMonth);

            # 获取最后一次检测记录（左下侧显示）
            $lastFixWorkflowRecodeEntire = FixWorkflowProcess::with([
                'FixWorkflowRecords',
                'FixWorkflowRecords.Measurement',
                'FixWorkflowRecords.EntireInstance',
                'FixWorkflowRecords.EntireInstance.EntireModel',
            ])
                ->orderByDesc('id')
                ->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)
                ->first();
            $check_json_data = [];
            switch (@$lastFixWorkflowRecodeEntire->check_type ?? '') {
                case 'JSON':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body']['测试项目'];
                        }
                    }
                    break;
                case 'JSON2':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body'];
                        }
                    }
                    break;
            }
            # 上一张检测单
            $lastFixWorkflow = DB::table('fix_workflows')->select('entire_instance_identity_code', 'stage', 'status', 'serial_number')->where('entire_instance_identity_code', $entireInstanceIdentityCode)->orderByDesc('id')->first();

            # 故障类型
            $breakdownTypes = DB::table('breakdown_types')
                ->where('deleted_at', null)
                ->where('category_unique_code', $entireInstance->category_unique_code)
                ->pluck('name', 'id')
                ->chunk(3);

            # 备品统计
            if ($entireInstance->status === '上道') {
                if ($entireInstance->maintain_station_name) {
                    // 车站
                    @$maintain_station_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_station_name', $entireInstance->maintain_station_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    @$maintain_workshop_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_workshop_name', $entireInstance->maintain_workshop_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    # 车间编码
                    @$scene_workshop_unique_code = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('parent_unique_code');
                    # 车站id
                    @$stationId = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('id');
                    # 车间名称
                    @$workshopName = DB::table('maintains')->where('unique_code', $scene_workshop_unique_code)->value('name');
                    # 类型编码
                    @$entire_model_unique_code = DB::table('entire_models')->where('unique_code', $entireInstance->model_unique_code)->value('parent_unique_code');
                    # 所属车间距离
                    @$workshop_distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $workshopName)->value('distance') / 1000, 2);
                    # 所属车站距离
                    @$station_distance = 0;
                    # 长沙电务段信号检修车间距离
                    @$workshop_name = DB::table('maintains')->where('type', 'WORKSHOP')->value('name');
                    @$distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $workshop_name)->value('distance') / 1000, 2);
                    #长沙电务段信号检修车间
                    @$workshop_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_workshop_name', $workshop_name)
                        ->where('status', 'FIXED')
                        ->count();
                    # 获取最近的两个车站
                    @$stations = DB::table('distance')->where('maintains_id', $stationId)->where('distance', '!=', 0)->orderBy(DB::raw('distance + 0'))->get()->toArray();
                    # 计算临近两个车站的备品数
                    $i = 0;
                    $stations2 = [];
                    foreach ($stations as $station) {
                        $num = DB::table('entire_instances')
                            ->where('category_unique_code', @$entireInstance->category_unique_code)
                            ->where('model_unique_code', @$entireInstance->model_unique_code)
                            ->where('maintain_station_name', @$station->maintains_name)
                            ->where('status', 'INSTALLING')
                            ->count();
                        if ($num >= 1) {
                            @$stations2[$i]->maintains_name = $station->maintains_name;
                            @$stations2[$i]->maintain_station_num = $num;
                            @$stations2[$i]->distance = round(@$station->distance / 1000, 2);
                            @$stations2[$i]->lon = DB::table('maintains')->where('name', @$station->maintains_name)->value('lon');
                            @$stations2[$i]->lat = DB::table('maintains')->where('name', @$station->maintains_name)->value('lat');
                            @$stations2[$i]->contact = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact');
                            @$stations2[$i]->contact_phone = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact_phone');
                            @$stations2[$i]->contact_address = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact_address');
                            $i += 1;
                        }
                        if ($i == 2) {
                            break;
                        }
                    }
                    # 车间车站基础数据(地图标点)
                    @$stationData = DB::table('maintains')->where('id', $stationId)->first();
                    @$workshopData = DB::table('maintains')->where('unique_code', $scene_workshop_unique_code)->first();
                    @$baseData = DB::table('maintains')->where('type', 'WORKSHOP')->first();
                    $data = [
                        'belongToStation' => ['name' => @$entireInstance->maintain_station_name, 'count' => @$maintain_station_num, 'distance' => @$station_distance, 'lon' => @$stationData->lon, 'lat' => @$stationData->lat, 'contact' => @$stationData->contact, 'contact_phone' => @$stationData->contact_phone, 'contact_address' => @$stationData->contact_address],
                        'belongToWorkshop' => ['name' => @$workshopName, 'count' => @$maintain_workshop_num, 'distance' => @$workshop_distance, 'lon' => @$workshopData->lon, 'lat' => @$workshopData->lat, 'contact' => @$workshopData->contact, 'contact_phone' => @$workshopData->contact_phone, 'contact_address' => @$workshopData->contact_address],
                        'nearStation' => @$stations,
                        'WORKSHOP' => ['name' => @$workshop_name, 'count' => @$workshop_num, 'distance' => @$distance, 'lon' => @$baseData->lon, 'lat' => @$baseData->lat, 'contact' => @$baseData->contact, 'contact_phone' => @$baseData->contact_phone, 'contact_address' => @$baseData->contact_address],
                    ];
                } else {
                    // 车间
                    @$maintain_station_num = 0;
                    @$maintain_workshop_num = DB::table('entire_instances')
                        ->where('category_unique_code', @$entireInstance->category_unique_code)
                        ->where('model_unique_code', @$entireInstance->model_unique_code)
                        ->where('maintain_workshop_name', @$entireInstance->maintain_workshop_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    # 车间编码
                    @$scene_workshop_unique_code = DB::table('maintains')->where('name', @$entireInstance->maintain_workshop_name)->value('unique_code');
                    @$stationId = DB::table('maintains')->where('name', @$entireInstance->maintain_workshop_name)->value('id');
                    # 类型编码
                    @$entire_model_unique_code = DB::table('entire_models')->where('unique_code', @$entireInstance->model_unique_code)->value('parent_unique_code');
                    # 所属车间距离
                    @$workshop_distance = 0;
                    # 所属车站距离
                    @$station_distance = 0;
                    # 长沙电务段信号检修车间距离
                    @$workshop_name = DB::table('maintains')->where('type', 'WORKSHOP')->value('name');
                    @$distance = round(DB::table('distance')->where('maintains_id', @$stationId)->where('maintains_name', @$workshop_name)->value('distance') / 1000, 2);
                    #长沙电务段信号检修车间
                    @$workshop_num = DB::table('entire_instances')
                        ->where('category_unique_code', @$entireInstance->category_unique_code)
                        ->where('model_unique_code', @$entireInstance->model_unique_code)
                        ->where('maintain_workshop_name', @$workshop_name)
                        ->where('status', 'FIXED')
                        ->count();
                    # 获取最近的两个车站
                    $stations = DB::table('distance')->where('maintains_id', @$stationId)->where('distance', '!=', 0)->orderBy(DB::raw('distance + 0'))->limit(2)->get()->toArray();
                    # 计算临近两个车站的备品数
                    $i = 0;
                    $stations2 = [];
                    foreach ($stations as $station) {
                        $num = DB::table('entire_instances')
                            ->where('category_unique_code', @$entireInstance->category_unique_code)
                            ->where('model_unique_code', @$entireInstance->model_unique_code)
                            ->where('maintain_station_name', @$station->maintains_name)
                            ->where('status', 'INSTALLING')
                            ->count();
                        if ($num >= 1) {
                            @$stations2[$i]->maintains_name = $station->maintains_name;
                            @$stations2[$i]->maintain_station_num = $num;
                            @$stations2[$i]->distance = round(@$station->distance / 1000, 2);
                            @$stations2[$i]->lon = DB::table('maintains')->where('name', @$station->maintains_name)->value('lon');
                            @$stations2[$i]->lat = DB::table('maintains')->where('name', @$station->maintains_name)->value('lat');
                            @$stations2[$i]->contact = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact');
                            @$stations2[$i]->contact_phone = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact_phone');
                            @$stations2[$i]->contact_address = DB::table('maintains')->where('name', @$station->maintains_name)->value('contact_address');
                            $i += 1;
                        }
                        if ($i == 2) {
                            break;
                        }
                    }
                    # 车间车站基础数据(地图标点)
                    @$workshopData = DB::table('maintains')->where('unique_code', @$scene_workshop_unique_code)->first();
                    @$baseData = DB::table('maintains')->where('type', 'WORKSHOP')->first();
                    @$data = [
                        'belongToStation' => ['name' => '', 'count' => '', 'distance' => '', 'lon' => '', 'lat' => '', 'contact' => '', 'contact_phone' => '', 'contact_address' => ''],
                        'belongToWorkshop' => ['name' => @$entireInstance->maintain_workshop_name, 'count' => @$maintain_workshop_num, 'distance' => @$workshop_distance, 'lon' => @$workshopData->lon, 'lat' => @$workshopData->lat, 'contact' => @$workshopData->contact, 'contact_phone' => @$workshopData->contact_phone, 'contact_address' => @$workshopData->contact_address],
                        'nearStation' => $stations,
                        'WORKSHOP' => ['name' => @$workshop_name, 'count' => @$workshop_num, 'distance' => @$distance, 'lon' => @$baseData->lon, 'lat' => @$baseData->lat, 'contact' => @$baseData->contact, 'contact_phone' => @$baseData->contact_phone, 'contact_address' => @$baseData->contact_address],
                    ];
                }
            } else {
                $maintain_station_num = '';
                $maintain_workshop_num = '';
                $scene_workshop_unique_code = '';
                $entire_model_unique_code = '';
                $workshop_distance = '';
                $station_distance = '';
                $workshop_name = '';
                $workshop_num = '';
                $distance = '';
                $stations2 = [];
                $data = '';
            }

            return view('Search.show', [
                'fixWorkflows' => @$entireInstance->FixWorkflows,
                'entireInstance' => @$entireInstance,
                'fixWorkflow' => @$entireInstance->FixWorkflow,
                'lastFixWorkflowRecodeEntire' => @$lastFixWorkflowRecodeEntire,
                'entireInstanceLogs' => collect(@$entireInstanceLogsWithMonth),
                // 'fixer' => @$fixer->Processor ? @$fixer->Processor->nickname : '',
                // 'checker' => @$checker->Processor ? @$checker->Processor->nickname : '',
                // 'fixed_at' => @$fixer->updated_at ? date('Y-m-d', strtotime(@$fixer->updated_at)) : '',
                // 'checker_at' => @$checker->updated_at ? date('Y-m-d', strtotime(@$checker->updated_at)) : '',
                'breakdownLogsWithMonth' => @$breakdownLogsWithMonth,
                'entireInstanceIdentityCode' => @$entireInstanceIdentityCode,
                'check_json_data' => @$check_json_data,
                'lastFixWorkflow' => @$lastFixWorkflow,
                'breakdownTypes' => @$breakdownTypes,
                // 'partCategoryIds' => @$partCategoryIds,
                'maintain_station_num' => @$maintain_station_num,
                'maintain_workshop_num' => @$maintain_workshop_num,
                'scene_workshop_unique_code' => @$scene_workshop_unique_code,
                'entire_model_unique_code' => @$entire_model_unique_code,
                'workshop_distance' => @$workshop_distance,
                'station_distance' => @$station_distance,
                'workshop_name' => @$workshop_name,
                'workshop_num' => @$workshop_num,
                'distance' => @$distance,
                'stations2' => @$stations2,
                'data_as_json' => json_encode(@$data)
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Exception $e) {
            $exceptionMessage = $e->getMessage();
            $exceptionLine = $e->getLine();
            $exceptionFile = $e->getFile();
            return CommonFacade::ddExceptionWithAppDebug($e);
            // return back()->with('danger', $exceptionMessage . ':' . $exceptionFile . ':' . $exceptionLine);
        }
    }

    /**
     * 查看绑定设备下所有器材
     * @param string $bindDeviceCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getBindDevice(string $bindDeviceCode)
    {
        try {
            $entireInstances = EntireInstance::with([
                'Category',
            ])
                ->where('bind_device_code', $bindDeviceCode)
                ->orderByDesc('category_unique_code')
                ->orderByDesc('updated_at')
                ->get();

            return view('Search.bindDevice', [
                'bindDeviceCode' => $bindDeviceCode,
                'entireInstances' => $entireInstances,
                'statuses' => EntireInstance::$STATUSES,
            ]);
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 查看绑定道岔号下所有器材
     * @param string $bind_crossroad_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getBindCrossroadNumber(string $bind_crossroad_number)
    {
        try {
            $entireInstances = EntireInstance::with([
                'Category',
            ])
                ->where('bind_crossroad_number', $bind_crossroad_number)
                ->orderByDesc('category_unique_code')
                ->orderByDesc('updated_at')
                ->get();

            return view('Search.bindCrossroadNumber', [
                'bindCrossroadNumber' => $bind_crossroad_number,
                'entireInstances' => $entireInstances,
                'statuses' => EntireInstance::$STATUSES,
            ]);
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }
}
