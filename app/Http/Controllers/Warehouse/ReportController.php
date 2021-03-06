<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\FuncNotFoundException;
use App\Exceptions\MaintainNotFoundException;
use App\Facades\CodeFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use App\Model\Maintain;
use App\Model\WarehouseBatchReport;
use App\Model\WarehouseInBatchReport;
use App\Model\WarehouseReport;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Factory|RedirectResponse|View
     */
    public function index()
    {
        try {
            ['dateList' => $warehouse_statistics_date_list, 'statistics' => $warehouse_statistics, 'paragraph_code' => $paragraph_code] = WarehouseReportFacade::generateStatisticsFor7Days();

            list($origin_at, $finish_at) = explode('~', request('updated_at', Carbon::now()->startOfMonth()->format('Y-m-d') . "~" . Carbon::now()->endOfMonth()->format('Y-m-d')));

            $scene_workshops = DB::table('maintains as s')
                ->select(['s.name', 'sw.name as scene_workshop_name'])
                ->join(DB::raw('maintains sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                ->where('s.deleted_at', null)
                ->where('sw.deleted_at', null)
                ->where('s.type', 'STATION')
                ->orderBy('s.id')
                ->get()
                ->toArray();
            $stations = [];
            foreach ($scene_workshops as $scene_workshop) $stations[$scene_workshop->scene_workshop_name][] = $scene_workshop->name;

            // $work_areas = ['??????', '???????????????', '???????????????', '????????????'];

            $warehouse_reports = DB::table('warehouse_reports as wr')
                ->select(['wr.*', 'a.nickname'])
                ->join(DB::raw('accounts a'), 'a.id', '=', 'wr.processor_id')
                ->where('wr.deleted_at', null)
                ->when(request('direction'), function ($query) {
                    return $query->where('wr.direction', request('direction'));
                })
                ->when(request('current_work_area_type'), function ($query) {
                    $query->join(DB::raw('accounts a'), 'a.id', '=', 'wr.processor_id')
                        ->join(DB::raw('work_areas wa'), 'wa.unique_code', '=', 'a.work_area_unique_code')
                        ->where('wa.type', request('current_work_area_type'));
                })
                ->when(request('updated_at'), function ($query) {
                    list($origin_at, $finish_at) = explode('~', request('updated_at'));
                    return $query->whereBetween('wr.updated_at', [Carbon::parse($origin_at)->startOfDay(), Carbon::parse($finish_at)->endOfDay()]);
                })
                ->orderByDesc('wr.processed_at')
                ->paginate();

            return view('Warehouse.Report.index', [
                'warehouse_reports' => $warehouse_reports,
                'directions' => WarehouseReport::$DIRECTION,
                'types' => WarehouseReport::$TYPE,
                'origin_at' => $origin_at,
                'finish_at' => $finish_at,
                'warehouse_statistics_date_list_as_json' => json_encode($warehouse_statistics_date_list),
                'warehouse_statistics_as_json' => json_encode($warehouse_statistics),
                // 'work_areas' => $work_areas,
                'stations' => $stations,
                'stations_as_json' => json_encode($stations)
            ]);
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/')->with('danger', '????????????');
        }
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $serialNumber
     * @return Factory|Application|RedirectResponse|View
     */
    final public function show(Request $request, $serialNumber)
    {
        try {
            if ($request->get('download') == '1') {
                # ??????Excel
                $entire_instances = DB::table('warehouse_report_entire_instances as wr')
                    ->select([
                        'wr.warehouse_report_serial_number',
                        'ei.model_name',
                        'oe.new',
                        'oe.old',
                        'oe.location',
                        'oe.station',
                        'oe.new_tid',
                        'oe.old_tid',
                    ])
                    ->join(DB::raw('entire_instances as ei'), 'ei.identity_code', '=', 'wr.entire_instance_identity_code')
                    ->join(DB::raw('out_entire_instance_correspondences as oe'), 'oe.new', '=', 'ei.identity_code')
                    ->where('oe.out_warehouse_sn', $serialNumber)
                    ->where('warehouse_report_serial_number', $serialNumber)
                    ->get();
                if ($entire_instances->isEmpty()) return back()->with('danger', '?????????????????????????????????????????????????????????Excel');
                ExcelWriteHelper::download(function ($excel) use ($entire_instances) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->getColumnDimension('A')->setWidth(5);
                    $current_sheet->getColumnDimension('B')->setWidth(22);
                    $current_sheet->getColumnDimension('C')->setWidth(27);
                    $current_sheet->getColumnDimension('D')->setWidth(15);
                    $current_sheet->getColumnDimension('E')->setWidth(12);
                    $current_sheet->getColumnDimension('F')->setWidth(22);
                    $current_sheet->getColumnDimension('G')->setWidth(27);

                    # ??????
                    $current_sheet->setCellValue("A1", "??????");
                    $current_sheet->setCellValue("B1", "???????????????");
                    $current_sheet->setCellValue("C1", "?????????TID");
                    $current_sheet->setCellValue("D1", "??????");
                    $current_sheet->setCellValue("E1", "??????");
                    $current_sheet->setCellValue("F1", "???????????????");
                    $current_sheet->setCellValue("G1", "?????????TID");

                    # ????????????
                    $row = 2;
                    foreach ($entire_instances as $entire_instance) {
                        $current_sheet->setCellValue("A{$row}", $row - 1);
                        $current_sheet->setCellValue("B{$row}", $entire_instance->new);
                        $current_sheet->setCellValue("C{$row}", $entire_instance->new_tid);
                        $current_sheet->setCellValue("D{$row}", $entire_instance->station);
                        $current_sheet->setCellValue("E{$row}", $entire_instance->location);
                        $current_sheet->setCellValue("F{$row}", $entire_instance->old);
                        $current_sheet->setCellValue("G{$row}", $entire_instance->old_tid);
                        $row++;
                    }

                    return $excel;
                }, array_first($entire_instances->toArray())->station . "??????????????????????????????{$serialNumber}");
            }

            // ??????????????????
            $factories = \App\Model\Factory::with([])->get();
            $scene_workshops = DB::table('maintains as sc')->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))->where('sc.type', 'SCENE_WORKSHOP')->get();
            $stations = DB::table('maintains as s')->where('s.type', 'STATION')->get()->groupBy('parent_unique_code');

            switch ($request->get('show_type')) {
                case 'D':
                default:
                    $warehouseReport = WarehouseReport::with([
                        'Processor',
                        'WarehouseReportEntireInstances' => function ($WarehouseReportEntireInstances) {
                            $WarehouseReportEntireInstances->orderByDesc('id');
                        },
                        'WarehouseReportEntireInstances.EntireInstance',
                        'WarehouseReportEntireInstances.EntireInstance.EntireModel',
                    ])
                        ->where('serial_number', $serialNumber)
                        ->firstOrFail();

                    $entireModels = [];
                    foreach ($warehouseReport->WarehouseReportEntireInstances as $warehouseReportEntireInstance) {
                        if (!$warehouseReportEntireInstance->EntireInstance) continue;  // ????????????/????????????????????????
                        $entireModels[$warehouseReportEntireInstance->EntireInstance->EntireModel->name][] = $warehouseReportEntireInstance->EntireInstance->identity_code;
                    }

                    switch ($request->get('type')) {
                        case 'print':
                            $view = view('Warehouse.Report.print');
                            break;
                        default:
                            $view = view('Warehouse.Report.showDetail');
                            break;
                    }

                    return $view
                        ->with('warehouseReport', $warehouseReport)
                        ->with('entireModels', $entireModels);
                case 'E':
                    $warehouseReport = WarehouseReport::with([
                        'Processor',
                        'WarehouseReportEntireInstances',
                        'WarehouseReportEntireInstances.EntireInstance',
                        'WarehouseReportEntireInstances.EntireInstance.EntireModel',
                    ])
                        ->where('serial_number', $serialNumber)
                        ->firstOrFail();
                    $entireModels = [];
                    foreach ($warehouseReport->WarehouseReportEntireInstances as $warehouseReportEntireInstance) {
                        if (!$warehouseReportEntireInstance->EntireInstance) continue;  // ????????????/????????????????????????
                        $entireModels[$warehouseReportEntireInstance->EntireInstance->EntireModel->name][] = $warehouseReportEntireInstance->EntireInstance->identity_code;
                    }
                    return view('Warehouse.Report.showEntireInstances', [
                        'warehouseReport' => $warehouseReport,
                        'entireModels' => $entireModels,
                        'factories_as_json'=>$factories->toJson(),
                        'scene_workshops_as_json' => $scene_workshops->toJson(),
                        'stations_as_json' => $stations->toJson(),
                    ]);
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '???????????????');
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            return back()->with('danger', "{$msg}<br>{$line}<br>{$file}");
        }
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
     * @param Request $request
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
     * ??????????????????
     */
    final public function getPrintNormalLabel()
    {
        $work_areas = [
            "???????????????" => 1,
            "???????????????" => 2,
            "????????????" => 3,
        ];

        $with_work_area = function (Builder $db): Builder {
            if (session('account.work_area') == "???????????????") {
                return $db->where("ei.category_unique_code", "S03");
            } elseif (session('account.work_area') == "???????????????") {
                return $db->where("ei.category_unique_code", "Q01");
            } elseif (session('account.work_area') == '????????????') {
                return $db->whereNotIn("ei.category_unique_code", ["S03", "Q01"]);
            } else {
                return $db;
            }
        };

        switch (request('type')) {
            case 'BUY_IN':
                # ?????????????????????
                $entire_instances = DB::table('warehouse_in_batch_reports as w')
                    ->select([
                        'ei.created_at',
                        'ei.identity_code',
                        'ei.serial_number',
                        'ei.rfid_code',
                        'ei.model_name',
                        'ei.factory_name',
                        'ei.factory_device_code',
                        'ei.maintain_station_name',
                        'ei.maintain_location_code',
                    ])
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'w.entire_instance_identity_code')
                    ->where('ei.deleted_at', null)
                    ->where('w.processor_id', session('account.id'))
                    ->when(request('search_mode'), function ($query) {
                        if (!request('search_content')) return $query;
                        $search_content = request('search_content');
                        return $query->where('ei.' . request('search_mode'), 'like', "%{$search_content}%");
                    })
                    ->orderByDesc('w.id')
                    ->paginate(50);

                //                $entire_instances = $with_work_area(
                //                    DB::table('entire_instances as ei')
                //                        ->select([
                //                            'ei.created_at',
                //                            'ei.identity_code',
                //                            'ei.serial_number',
                //                            'ei.rfid_code',
                //                            'ei.model_name',
                //                            'ei.factory_name',
                //                            'ei.factory_device_code',
                //                            'ei.maintain_station_name',
                //                            'ei.maintain_location_code',
                //                        ])
                //                        ->where('ei.deleted_at', null)
                //                        ->whereIn('ei.status', ['BUY_IN', 'FIXING', 'FIXED'])
                //                        ->when(request('search_mode'), function ($query) {
                //                            if (!request('search_content')) return $query;
                //                            $search_content = request('search_content');
                //                            return $query->where(request('search_mode'), 'like', "%{$search_content}%");
                //                        })
                //                        ->orderByDesc('id')
                //                )
                //                    ->paginate(25);
                return view('Warehouse.Report.printNormalLabelWithBuyIn', ['entireInstances' => $entire_instances]);
                break;
            case 'CYCLE_FIX':
                # ??????????????????
                if (request('date', null)) {
                    list($current_year, $month) = explode('-', request("date", Carbon::now()->addMonth(2)->format("Y-m")));
                    $months = [$month];
                } else {
                    $current_year = date('Y');
                    $month = null;
                    $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
                }
                $next_year = Carbon::createFromDate($current_year)->addYear()->year;

                $stations = DB::table('maintains as m')
                    ->leftJoin(DB::raw('maintains m2'), 'm2.unique_code', '=', 'm.parent_unique_code')
                    ->where('m2.parent_unique_code', env('ORGANIZATION_CODE'))
                    ->pluck('m.name', 'm.unique_code');
                $current_station_name = request('station_name');
                $current_model_name = request('model_name');

                $fs = FileSystem::init(__FILE__);

                # ???????????????????????????????????????
                $dates = $fs->setPath(storage_path("app/?????????/dateList.json"))->fromJson();
                if (!$dates) return HttpResponseHelper::errorEmpty("????????????????????????");
                $date_lists = [];
                foreach ($dates as $date) if (explode('-', $date)[0] == $current_year || explode('-', $date)[0] == $next_year) $date_lists[] = $date;

                # ?????????????????????????????????????????????
                $current_model_names = [];
                $cycle_fix_without_location = [];
                foreach ($months as $month) {
                    $file_dir = storage_path("app/?????????/{$current_year}/{$current_year}-{$month}");
                    if (!is_file("{$file_dir}/?????????-???????????????-??????.json")) return back()->with("danger", "??????????????????????????????");
                    $location_with_cycle_fix = $fs->setPath("{$file_dir}/?????????-???????????????-??????.json")->fromJson();
                    foreach ($location_with_cycle_fix as $model_name => $item) {
                        $current_model_names[] = $model_name;
                        if (($current_model_name && $current_model_name != $model_name)) continue;
                        foreach ($item as $station_name => $value) {
                            if ($current_station_name) {
                                # ??????????????????????????????????????????????????????
                                if ($station_name === $current_station_name)
                                    foreach ((array)$value as $identity_code => $statistics)
                                        $cycle_fix_without_location[$identity_code] = $statistics['new_identity_code'];
                            } else {
                                # ??????????????????????????????????????????
                                foreach ((array)$value as $identity_code => $statistics) $cycle_fix_without_location[$identity_code] = $statistics['new_identity_code'];
                            }
                        }
                    }
                }
                $current_model_names = array_unique($current_model_names);

                # ????????????????????????
                $old_entire_instances = $with_work_area(
                    DB::table("entire_instances as ei")
                        ->select(["ei.identity_code", "ei.maintain_station_name", "ei.maintain_location_code", "ei.model_name", "ei.next_fixing_day", "ei.serial_number"])
                        ->whereIn("ei.identity_code", array_keys($cycle_fix_without_location))
                        ->orderBy("ei.maintain_station_name")
                )
                    ->paginate(100);

                # ????????????????????????
                $new_entire_instances = [];
                foreach ($with_work_area(
                             DB::table('entire_instances as ei')
                                 ->select(['ei.model_name', 'ei.rfid_code', 'ei.identity_code', 'ei.serial_number'])
                                 ->where('ei.deleted_at', null)
                                 ->where('ei.status', 'FIXED')
                         )
                             ->get() as $item) {
                    if (!array_key_exists($item->model_name, $new_entire_instances)) $new_entire_instances[$item->model_name] = [];
                    $new_entire_instances[$item->model_name][] = "{$item->identity_code}_{$item->rfid_code}";
                }

                return view('Warehouse.Report.printNormalLabelWithCycleFix', [
                    'newEntireInstances' => $new_entire_instances,
                    'newEntireInstancesAsJson' => TextHelper::toJson($new_entire_instances),
                    'cycleFixWithoutLocation' => $cycle_fix_without_location,
                    'oldEntireInstances' => $old_entire_instances,
                    'locationWithCycleFix' => $location_with_cycle_fix,
                    'dateLists' => $date_lists,
                    'year' => $current_year,
                    'month' => $month,
                    'stations' => $stations,
                    'current_station_name' => $current_station_name,
                    'current_model_name' => $current_model_name,
                    'current_model_names' => $current_model_names,
                ]);
                break;
            case 'OUT':
                $entire_instances = [];
                $old_entire_instance = [];
                if (request('identityCode', null)) {
                    switch (strlen(request('identityCode'))) {
                        case 14:
                        case 19:
                            $identity_code = request('identityCode');
                            break;
                        case 24:
                            if (substr(request('identityCode'), 0, 4) == '130E') {
                                $identity_code = CodeFacade::hexToIdentityCode(request('identityCode'));
                            } else {
                                $identity_code = DB::table('entire_instances as ei')->where('ei.rfid_code', request('identityCode'))->first(['identity_code']);
                                if (!$identity_code) return back()->with('danger', '???????????????');
                                $identity_code = $identity_code->identity_code;
                            }
                            break;
                        default:
                            return back()->with('danger', '????????????????????????');
                            break;
                    }
                    $old_entire_instance = DB::table('entire_instances as ei')
                        ->where('identity_code', $identity_code)
                        ->first(['model_name', 'maintain_station_name', 'maintain_location_code', 'rfid_code', 'identity_code', 'serial_number']);
                    if (!$old_entire_instance) return back()->with('danger', '???????????????');

                    $entire_instances = DB::table('entire_instances as ei')
                        ->select(['identity_code', 'model_name', 'maintain_station_name', 'maintain_location_code', 'rfid_code', 'serial_number'])
                        ->where('model_name', $old_entire_instance->model_name)
                        ->where(function ($query) {
                            $query->where('maintain_location_code', null)
                                ->whereOr('maintain_location_code', '');
                        })
                        ->where('status', 'FIXED')
                        ->paginate();
                }
                return view('Warehouse.Report.printNormalLabelWithOut', [
                    'entireInstances' => $entire_instances,
                    'oldEntireInstance' => $old_entire_instance,
                ]);
                break;
        }
        return back()->with("danger", "??????????????????");
    }

    /**
     * ?????????????????????????????????????????????
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    final public function postPrintNormalLabel(Request $request)
    {
        try {
            switch ($request->get('type')) {
                case 'BUY_IN':
                    # @ TODO ????????????????????????????????????????????????????????????????????????
                    //                foreach ($request->get('identityCodes') as $identity_code)
                    //                    DB::table('entire_instances as ei')->where('identity_code', $identity_code)->update(['serial_number' => $identity_code]);
                    return response()->json('????????????');
                    break;
                case 'CYCLE_FIX':
                    $successes = 0;
                    $no_exists = ['old' => [], 'new' => []];
                    foreach ($request->get('identityCode') as $old_identity_code => $new_identity_code) {
                        $selectedForData = $request->get('selectedForData');
                        list($year, $month) = explode('-', $selectedForData[$old_identity_code]);
                        # todo::????????????????????????
                        //                        $fs = FileSystem::init(storage_path("app/?????????/" . $year . "/" . "{$year}-{$month}" . "/" . "?????????-???????????????-??????.json"));
                        //                        $locations = $fs->fromJson();
                        $file = storage_path("app/?????????/" . $year . "/" . "{$year}-{$month}" . "/" . "?????????-???????????????-??????.json");
                        $locations = json_decode(file_get_contents($file), true);


                        # ?????????????????????????????????????????? @todo ?????????????????????
                        //                    DB::table('entire_instances as ei')->where('ei.identity_code', $old_identity_code)->update(['serial_number' => $old_identity_code]);
                        # ??????????????????????????????????????????
                        //                    DB::table('entire_instances as ei')->where('ei.identity_code', $new_identity_code)->update(['serial_number' => $new_identity_code]);

                        $old = DB::table('entire_instances as ei')
                            ->where('identity_code', $old_identity_code)
                            ->first(['ei.maintain_station_name', 'ei.maintain_location_code', 'ei.rfid_code']);
                        if (!$old) $no_exists['old'][] = $old_identity_code;

                        if (!empty($new_identity_code)) {
                            # ????????????????????????????????????????????????
                            $new = DB::table('entire_instances as ei')
                                ->where('ei.identity_code', $new_identity_code)
                                ->first(['id', 'rfid_code']);
                            if (!$new) $no_exists['new'][] = $new_identity_code;
                            # ?????????????????????????????????
                            DB::table('entire_instances as ei')
                                ->where('ei.identity_code', $new_identity_code)
                                ->update(['ei.maintain_station_name' => $old->maintain_station_name, 'ei.maintain_location_code' => $old->maintain_location_code]);

                            # ????????????????????????
                            $oe = DB::table('out_entire_instance_correspondences')->where('new', $new_identity_code)->first(['id']);
                            if ($oe) {
                                # ????????????????????????????????????
                                DB::table('out_entire_instance_correspondences')->where('id', $oe->id)->update([
                                    'new' => $new_identity_code,
                                    'old' => $old_identity_code,
                                    'station' => $old->maintain_station_name,
                                    'location' => $old->maintain_location_code,
                                    'new_tid' => strval($new->rfid_code),
                                    'old_tid' => strval($old->rfid_code),
                                ]);
                            } else {
                                # ???????????????????????????????????????
                                DB::table('out_entire_instance_correspondences')
                                    ->where('new', $new_identity_code)
                                    ->insert([
                                        'new' => $new_identity_code,
                                        'old' => $old_identity_code,
                                        'station' => $old->maintain_station_name,
                                        'location' => $old->maintain_location_code,
                                        'new_tid' => '',
                                        'old_tid' => strval($old->rfid_code),
                                    ]);
                            }

                            $successes += 1;
                        } else {
                            # ??????????????????????????????
                            DB::table('out_entire_instance_correspondences')->where('old', $old_identity_code)->delete();
                            $successes += 1;
                        }

                        # ??????????????????
                        foreach ($locations as $entire_model_name => $location)
                            foreach ($location as $station_name => $entire_instances)
                                foreach ($entire_instances as $identity_code => $entire_instance)
                                    if ($identity_code == $old_identity_code) $locations[$entire_model_name][$station_name][$identity_code]['new_identity_code'] = strval($new_identity_code);
                        //                        $fs->toJson($locations);
                        file_put_contents($file, json_encode($locations, 256));
                    }

                    return response()->json(['count' => count($request->get('identityCode')), 'successes' => $successes, 'no_exists' => $no_exists]);
                    break;
                case 'OUT':
                    $old = DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('oldIdentityCode'))->first(['maintain_station_name', 'maintain_location_code', 'rfid_code']);
                    if (!$old) return response()->make('????????????????????????' . $request->get('oldIdentityCode'), 500);
                    $new = DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('newIdentityCode'))->first(['rfid_code']);
                    if (!$new) return response()->make('????????????????????????' . $request->get('newIdentityCode'), 500);

                    # ??????????????????????????????????????? @todo ?????????????????????
                    //                DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('oldIdentityCode'))->update(['serial_number' => $request->get('oldIdentityCode')]);
                    # ???????????????????????????????????????
                    //                DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('newIdentityCode'))->update(['serial_number' => $request->get('oldIdentityCode')]);

                    DB::transaction(function () use ($request, $old) {
                        # ????????????
                        DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('newIdentityCode'))->update(['maintain_station_name' => $old->maintain_station_name, 'maintain_location_code' => $old->maintain_location_code]);
                        # ?????????????????????
                        DB::table('entire_instances as ei')->where('ei.identity_code', $request->get('oldIdentityCode'))->update(['maintain_station_name' => '', 'maintain_location_code' => '']);
                        # ????????????????????????
                        $oe = DB::table('out_entire_instance_correspondences')->where('new', $request->get('newIdentityCode'))->first(['id']);
                        if ($oe) {
                            # ????????????????????????????????????
                            DB::table('out_entire_instance_correspondences')->where('id', $oe->id)->update([
                                'new' => $request->get('newIdentityCode'),
                                'old' => $request->get('oldIdentityCode'),
                                'station' => $old->maintain_station_name,
                                'location' => $old->maintain_location_code,
                                'old_tid' => strval($old->rfid_code),
                                'new_tid' => strval($old->rfid_code),
                            ]);
                        } else {
                            # ???????????????????????????
                            DB::table('out_entire_instance_correspondences')
                                ->insert([
                                    'new' => $request->get('newIdentityCode'),
                                    'old' => $request->get('oldIdentityCode'),
                                    'station' => $old->maintain_station_name,
                                    'location' => $old->maintain_location_code,
                                    'old_tid' => strval($old->rfid_code),
                                    'new_tid' => strval($old->rfid_code),
                                ]);
                        }
                    });
                    return response()->json(DB::table('entire_instances as ei')->where('identity_code', request('newIdentityCode'))->first(['maintain_station_name', 'maintain_location_code', 'rfid_code', 'model_name', 'identity_code']));
                    break;
            }
            return response()->make('????????????');
        } catch (Exception $exception) {
            $msg = $exception->getMessage();
            $line = $exception->getLine();
            $file = $exception->getFile();

            return response()->make("{$msg}\r\n{$file}\r\n{$line}");
        }
    }

    /**
     * ??????????????????
     * @return mixed
     */
    final public function getScanInBatch()
    {
        $qrCodeContents = [];

        switch (request('type')) {
            case 'IN':
            default:
                $warehouseBatchReports = WarehouseInBatchReport::with([
                    'EntireInstance',
                    'EntireInstance.EntireModel',
                    'EntireInstance.Category',
                ])
                    ->where('processor_id', session('account.id'))
                    ->get();
                break;
            case 'OUT':
                $warehouseBatchReports = WarehouseBatchReport::with([
                    'EntireInstance',
                    'EntireInstance.EntireModel',
                    'EntireInstance.Category',
                ])
                    ->where('processor_id', session('account.id'))
                    ->get();
                break;
        }

        return view('Warehouse.Report.scanInBatch', [
            'warehouseBatchReports' => $warehouseBatchReports,
            'qrCodeContents' => $qrCodeContents,
        ]);
    }

    /**
     * ????????????2
     */
    final public function getScanBatch()
    {
        try {
            $entire_instances = WarehouseInBatchReport::with([])
                ->orderByDesc('id')
                ->where('processor_id', session('account.id'))
                ->where('direction', request('direction'))
                ->get();

            $title = (request('direction') == 'IN' ? '???' : '???') . '???';

            return view('Warehouse.Report.scanInBatch2', [
                'entire_instances' => $entire_instances,
                'title' => $title,
            ]);
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '???????????????');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }

    }

    /**
     * ????????????2
     */
    final public function getScanBatchOut()
    {
        try {
            $entire_instances = WarehouseInBatchReport::with([])
                ->orderByDesc('id')
                ->where('processor_id', session('account.id'))
                ->where('direction', request('direction'))
                ->get();

            $title = (request('direction') == 'IN' ? '???' : '???') . '???';

            return view('Warehouse.Report.scanInBatch2', [
                'entire_instances' => $entire_instances,
                'title' => $title,
            ]);
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '???????????????');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }

    }

    /**
     * ????????????2????????????
     * @param Request $request
     * @return RedirectResponse|mixed
     */
    public function postScanBatch(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $repeat = WarehouseInBatchReport::with([])->where('entire_instance_identity_code', $request->get('code'))->where('direction', $request->get('direction'))->first();
                if ($repeat) return back()->with('danger', '????????????');
                $entire_instances = EntireInstance::with([])->where('identity_code', $request->get('code'))->orWhere('serial_number', $request->get('code'))->get();

                if ($entire_instances->isEmpty()) return back()->with('danger', '??????????????????');
                if ($entire_instances->count() > 1) {
                    # ?????????
                    foreach ($entire_instances as $entire_instance) {
                        WarehouseInBatchReport::with([])->create([
                            'entire_instance_identity_code' => $entire_instance->identity_code ?? '',
                            'processor_id' => session('account.id'),
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
                            'direction' => $request->get('direction'),
                        ]);
                    }
                } else {
                    # ?????????
                    WarehouseInBatchReport::with([])->create([
                        'entire_instance_identity_code' => $entire_instances->first()->identity_code ?? '',
                        'processor_id' => session('account.id'),
                        'maintain_station_name' => $entire_instances->first()->maintain_station_name ?? '',
                        'maintain_location_code' => $entire_instances->first()->maintain_location_code ?? '',
                        'crossroad_number' => $entire_instances->first()->crossroad_number ?? '',
                        'traction' => $entire_instances->first()->traction ?? '',
                        'line_name' => $entire_instances->first()->line_name ?? '',
                        'crossroad_type' => $entire_instances->first()->crossroad_type ?? '',
                        'extrusion_protect' => $entire_instances->first()->extrusion_protect ?? '',
                        'point_switch_group_type' => $entire_instances->first()->point_switch_group_type ?? '',
                        'open_direction' => $entire_instances->first()->open_direction ?? '',
                        'said_rod' => $entire_instances->first()->said_rod ?? '',
                        'direction' => $request->get('direction'),
                    ]);
                }

                return redirect("warehouse/report/scanBatch?direction={$request->get('direction')}");
            });
        } catch (FuncNotFoundException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '???????????????');
        } catch (\Throwable $th) {
            dd($th->getMessage(), $th->getFile(), $th->getLine());
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ????????????2????????????
     * @param Request $request
     * @return RedirectResponse|mixed
     */
    public function postScanBatchOut(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $repeat = WarehouseInBatchReport::with([])->where('entire_instance_identity_code', $request->get('code'))->where('direction', $request->get('direction'))->first();
                if ($repeat) return back()->with('danger', '????????????');
                $entire_instances = EntireInstance::with([])->where('identity_code', $request->get('code'))->orWhere('serial_number', $request->get('code'))->get();

                if ($entire_instances->isEmpty()) return back()->with('danger', '??????????????????');
                if ($entire_instances->count() > 1) {
                    # ?????????
                    foreach ($entire_instances as $entire_instance) {
                        WarehouseInBatchReport::with([])->create([
                            'entire_instance_identity_code' => $entire_instance->identity_code ?? '',
                            'processor_id' => session('account.id'),
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
                            'direction' => $request->get('direction'),
                        ]);
                    }
                } else {
                    # ?????????
                    WarehouseInBatchReport::with([])->create([
                        'entire_instance_identity_code' => $entire_instances->first()->identity_code ?? '',
                        'processor_id' => session('account.id'),
                        'maintain_station_name' => $entire_instances->first()->maintain_station_name ?? '',
                        'maintain_location_code' => $entire_instances->first()->maintain_location_code ?? '',
                        'crossroad_number' => $entire_instances->first()->crossroad_number ?? '',
                        'traction' => $entire_instances->first()->traction ?? '',
                        'line_name' => $entire_instances->first()->line_name ?? '',
                        'crossroad_type' => $entire_instances->first()->crossroad_type ?? '',
                        'extrusion_protect' => $entire_instances->first()->extrusion_protect ?? '',
                        'point_switch_group_type' => $entire_instances->first()->point_switch_group_type ?? '',
                        'open_direction' => $entire_instances->first()->open_direction ?? '',
                        'said_rod' => $entire_instances->first()->said_rod ?? '',
                        'direction' => $request->get('direction'),
                    ]);
                }

                return redirect("warehouse/report/scanBatch?direction={$request->get('direction')}");
            });
        } catch (FuncNotFoundException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '???????????????');
        } catch (\Throwable $th) {
            dd($th->getMessage(), $th->getFile(), $th->getLine());
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    final public function deleteScanBatch(Request $request, int $id)
    {
        try {
            if ($id == 0) {
                # ????????????????????????????????????
                WarehouseInBatchReport::with([])->where('direction', $request->get('direction'))->where('processor_id', session('account.id'))->delete();
            } else {
                # ??????????????????
                WarehouseInBatchReport::with([])->where('direction', $request->get('direction'))->where('id', $id)->where('processor_id', session('account.id'))->delete();
            }

            return response()->json(['message' => '????????????']);
        } catch (FuncNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ??????????????????
     * s@param Request $request
     * @return JsonResponse
     */
    final public function postScanBatchWarehouse(Request $request)
    {
        try {
            $title = (request('direction') == 'IN' ? '???' : '???') . '???';
            $warehouse_in_batch_reports = WarehouseInBatchReport::with([])->where('direction', $request->get('direction'))->orderBy('id')->get();
            if ($warehouse_in_batch_reports->isEmpty()) return response()->json(['message' => "??????????????????{$title}"], 403);

            $processed_at = date('Y-m-d H:i:s', strtotime($request->get('processedDate') . ' ' . $request->get('processedTime') . ':00'));

            $in = function () use ($request, $warehouse_in_batch_reports, $processed_at) {
                return WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                    $warehouse_in_batch_reports->pluck('entire_instance_identity_code')->toArray(),
                    session('account.id'),
                    $processed_at,
                    'FIXING',
                    $request->get('connectionName') ?? '',
                    $request->get('connectionPhone') ?? ''
                );
            };

            $out = function () use ($request, $warehouse_in_batch_reports, $processed_at) {
                return WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                    $warehouse_in_batch_reports->pluck('entire_instance_identity_code')->toArray(),
                    session('account.id'),
                    $processed_at,
                    'NORMAL',
                    $request->get('connectionName') ?? '',
                    $request->get('connectionPhone') ?? ''
                );
            };

            # ???????????????????????????
            WarehouseInBatchReport::with([])->whereIn('id', $warehouse_in_batch_reports->pluck('id')->toArray())->delete();

            $func = strtolower($request->get('direction'));

            return response()->json(['message' => "{$title}??????", 'warehouse_report_sn' => $$func()]);
        } catch (MaintainNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (FuncNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function postScanInBatch(Request $request)
    {
        try {
            $time = Carbon::create()->format('Y-m-d H:i:s');

            $entire_instance = EntireInstance::with([])
                ->where('status', '<>', 'SCRAP')
                ->when(request('searchType'), function ($query) {
                    switch (request('searchType')) {
                        case '????????????':
                        default:
                            $field_name = 'identity_code';
                            break;
                        case '?????????':
                            $field_name = 'serial_number';
                            break;
                        case '?????????':
                            $field_name = 'status';
                            break;
                    }
                    $query->where($field_name, request('qrCodeContent'));
                })
                ->first();

            if (!$entire_instance) return response()->make('???????????????', 404);
            if (WarehouseInBatchReport::with([])->where('entire_instance_identity_code', $entire_instance->identity_code)->first()) return response()->make('????????????', 403);

            switch ($request->get('type')) {
                case 'IN':
                default:
                    $insertRet = DB::table('warehouse_in_batch_reports')
                        ->insert([
                            'created_at' => $time,
                            'updated_at' => $time,
                            'entire_instance_identity_code' => $entire_instance->identity_code,
                            'fix_workflow_serial_number' => '',
                            'processor_id' => session('account.id'),
                            'maintain_station_name' => @$entire_instance->maintain_station_name ?: '',
                            'maintain_location_code' => @$entire_instance->maintain_location_code ?: '',
                            'crossroad_number' => @$entire_instance->crossroad_number ?: '',
                            'traction' => @$entire_instance->traction ?: '',
                            'line_name' => @$entire_instance->line_name ?: '',
                            'crossroad_type' => @$entire_instance->crossroad_type ?: '',
                            'extrusion_protect' => @$entire_instance->extrusion_protect ?: '',
                            'point_switch_group_type' => @$entire_instance->point_switch_group_type ?: '',
                            'open_direction' => @$entire_instance->open_direction ?: '',
                            'said_rod' => @$entire_instance->said_rod ?: '',
                        ]);
                    break;
                case 'OUT':
                    $insertRet = DB::table('warehouse_batch_reports')
                        ->insert([
                            'created_at' => $time,
                            'updated_at' => $time,
                            'entire_instance_identity_code' => $entire_instance->identity_code,
                            'processor_id' => session('account.id'),
                        ]);
                    break;
            }

            return response()->json($insertRet);
        } catch (ModelNotFoundException $e) {
            return Response::make('???????????????', 404);
        } catch (Exception $e) {
            $msg = "{$e->getMessage()}:{$e->getFile()}:{$e->getLine()}";
            return Response::make($msg, 500);
        }
    }

    /**
     * ???????????????????????????
     * @param string $identity_code
     * @return Factory|View
     */
    final public function getPointSwitchModifyLocation(string $identity_code)
    {
        $maintains = DB::table('maintains as m')
            ->select(['m.name as station_name', 'm2.name as scene_workshop_name'])
            ->leftJoin(DB::raw('maintains as m2'), 'm2.unique_code', '=', 'm.parent_unique_code')
            ->where('m.deleted_at', null)
            ->where('m2.deleted_at', null)
            ->where('m2.parent_unique_code', env('ORGANIZATION_CODE'))
            ->get();
        $ret = [];
        foreach ($maintains as $maintain) $ret[$maintain->scene_workshop_name][] = $maintain->station_name;

        return view('Warehouse.Report.pointSwitchModifyLocation_ajax', ['maintains_json' => TextHelper::toJson($ret), 'identity_code' => $identity_code]);
    }

    /**
     * ?????????????????????
     * @param Request $request
     * @param string $identity_code
     * @return JsonResponse
     */
    final public function postPointSwitchModifyLocation(Request $request, string $identity_code)
    {
        $entire_instance = DB::table('entire_instances as ei')->where('deleted_at', null)->where('identity_code', $identity_code)->first();
        if (!$entire_instance) return HttpResponseHelper::errorEmpty();
        DB::table('entire_instances as ei')->where('deleted_at', null)->where('identity_code', $identity_code)->update(['maintain_station_name' => $request->get('station_name'), 'crossroad_number' => $request->get('crossroad_number')]);
        return HttpResponseHelper::created('????????????');
    }

    /**
     * ???????????????
     * @param Request $request
     */
    public function postCleanBatch(Request $request)
    {
        switch ($request->get('type')) {
            case 'IN':
            default:
                DB::table('warehouse_in_batch_reports')->truncate();
                break;
            case 'OUT':
                DB::table('warehouse_batch_reports')->truncate();
                break;
        }
    }

    /**
     * ???????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function postMakeFixWorkflow(Request $request)
    {
        try {
            $newFixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW');
            DB::transaction(function () use ($newFixWorkflowSerialNumber, $request) {
                # ????????????????????????????????????????????????
                $unFixed = FixWorkflow::where('entire_instance_identity_code', $request->get('entireInstanceIdentityCode'))->whereNotIn('status', ['FIXED'])->count('id');
                if ($unFixed) throw new Exception('????????????????????????????????????');

                $entireInstance = EntireInstance::where('identity_code', $request->get('entireInstanceIdentityCode'))->firstOrFail();

                # ???????????????
                $fixWorkflow = new FixWorkflow;
                $fixWorkflow->fill([
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                    'status' => 'FIXING',
                    'processor_id' => session('processor_id'),
                    'serial_number' => $newFixWorkflowSerialNumber,
                    'stage' => 'PART',
                ])->saveOrFail();

                # ???????????????????????????????????????????????????????????????
                $entireInstance->fill([
                    'fix_workflow_serial_number' => $newFixWorkflowSerialNumber,
                    'status' => 'FIXING',
                    'in_warehouse' => false
                ])->saveOrFail();

                # ??????????????????????????????
                DB::table('part_instances')
                    ->where('entire_instance_identity_code', $entireInstance->identity_code)
                    ->update(['status' => 'FIXING']);

                # ?????????????????????????????????
                DB::table('warehouse_batch_reports')->where('entire_instance_identity_code', $request->get('entireInstanceIdentityCode'))->update(['fix_workflow_serial_number' => $newFixWorkflowSerialNumber]);
            });

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (Exception $exception) {
            return Response::make($exception->getMessage(), 404);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function postDeleteBatch(Request $request)
    {
        switch ($request->get('type')) {
            case 'IN':
            default:
                DB::table('warehouse_in_batch_reports')->where('id', $request->get('id'))->delete();
                break;
            case 'OUT':
                DB::table('warehouse_batch_reports')->where('id', $request->get('id'))->delete();
                break;
        }

        return Response::make('????????????');
    }

    /**
     * ?????????????????????
     * @return Factory|View
     */
    final public function modelOutBatch()
    {
        $organizationCode = env('ORGANIZATION_CODE');
        # ???????????????????????????????????????
        $workshops = Maintain::where('parent_unique_code', env('ORGANIZATION_CODE'))->where('type', 'SCENE_WORKSHOP')->get();
        # ?????????????????????????????????
        $accounts = DB::table('accounts')
            ->where('deleted_at', null)
            ->where(function ($query) {
                $query->where('workshop_code', null)
                    ->orWhere('workshop_code', env('ORGANIZATION_CODE'));
            })
            ->orderByDesc('id')->get();

        return view('Warehouse.Report.install_ajax')
            ->with('accounts', $accounts)
            ->with('workshops', $workshops);
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\Response|string
     */
    final public function postOutBatch(Request $request)
    {

        if ($request->get('processor_id', null) === null) return response()->make('??????????????????', 421);
        if ($request->get('processed_at', null) === null) return response()->make('???????????????', 421);

        $entireInstanceIdentityCodes = DB::table('warehouse_batch_reports')
            ->where('deleted_at', null)
            ->pluck('entire_instance_identity_code')
            ->toArray();
        if (empty($entireInstanceIdentityCodes)) return response()->make('????????????????????????', 404);

        if (!empty($entireInstanceIdentityCodes)) {
            WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                $entireInstanceIdentityCodes,
                $request->get('processor_id'),
                $request->get('processed_at'),
                'NORMAL',
                $request->get('connection_name', ''),
                $request->get('connection_phone', '')
            );

            DB::table('warehouse_batch_reports')->truncate();
            DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $entireInstanceIdentityCodes)->update(['status' => 'INSTALLED']);
            DB::table('part_instances')->where('deleted_at', null)->whereIn('entire_instance_identity_code', $entireInstanceIdentityCodes)->update(['status' => 'INSTALLED']);

            return response()->make('????????????');
        } else {
            return response()->make('?????????????????????');
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function postInBatch(Request $request)
    {
        try {
            switch ($request->get('type')) {
                case 'IN':
                default:
                    $warehouseBatchReports = WarehouseInBatchReport::with('EntireInstance')->get();
                    if ($warehouseBatchReports->isEmpty()) return response()->make('????????????????????????', 404);
                    break;
                case 'OUT':
                    $warehouseBatchReports = WarehouseBatchReport::with('EntireInstance')->get();
                    if ($warehouseBatchReports->isEmpty()) return response()->make('????????????????????????', 404);
                    break;
            }

            $repeat = WarehouseReportFacade::inBatch($warehouseBatchReports, 'FIXING');
            if ($repeat) {
                $repeatStr = '';
                foreach ($repeat as $item) {
                    $serialNumber = $item->serial_number ? "????????????{$item->serial_number}" : "";
                    $repeatStr .= "{$serialNumber}\r\n????????????{$item->factory_device_code}";
                }
                throw new Exception(count($repeat) . "???????????????????????????\r\n{$repeatStr}");
            }

            $name = "";
            if ($request->get('type') == 'IN') $name = '_in';
            DB::table("warehouse{$name}_batch_reports")->truncate();

            return HttpResponseHelper::created('??????????????????');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('???????????????');
        } catch (Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * ?????????????????????
     */
    public function postMakeFixWorkflowBatch()
    {
        try {
            $warehouseBatchReports = WarehouseBatchReport::with('EntireInstance')->get();

            $newFixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW');
            $i = 0;
            $fail = [];
            DB::transaction(function () use ($newFixWorkflowSerialNumber, $warehouseBatchReports, &$fail, &$i) {
                foreach ($warehouseBatchReports as $warehouseBatchReport) {
                    $newFixWorkflowSerialNumber = $newFixWorkflowSerialNumber . ++$i;

                    # ????????????????????????????????????????????????
                    $unFixed = FixWorkflow::where('entire_instance_identity_code', $warehouseBatchReport->entire_instance_identity_code)->whereNotIn('status', ['FIXED'])->count('id');
                    if ($unFixed) {
                        $fail[] = $warehouseBatchReport->EntireInstance;
                        continue;
                    }

                    $entireInstance = EntireInstance::where('identity_code', $warehouseBatchReport->entire_instance_identity_code)->firstOrFail();

                    # ???????????????
                    $fixWorkflow = new FixWorkflow;
                    $fixWorkflow->fill([
                        'entire_instance_identity_code' => $warehouseBatchReport->entire_instance_identity_code,
                        'status' => 'FIXING',
                        'processor_id' => session('processor_id'),
                        'serial_number' => $newFixWorkflowSerialNumber,
                        'stage' => 'PART',
                    ])->saveOrFail();

                    # ???????????????????????????????????????????????????????????????
                    $entireInstance->fill([
                        'fix_workflow_serial_number' => $newFixWorkflowSerialNumber,
                        'status' => 'FIXING',
                        'in_warehouse' => false
                    ])->saveOrFail();

                    # ??????????????????????????????
                    DB::table('part_instances')
                        ->where('entire_instance_identity_code', $entireInstance->identity_code)
                        ->update(['status' => 'FIXING']);

                    # ?????????????????????????????????
                    DB::table('warehouse_batch_reports')->where('entire_instance_identity_code', $warehouseBatchReport->entire_insatance_identity_code)->update(['fix_workflow_serial_number' => $newFixWorkflowSerialNumber]);
                }
            });

            if ($fail) {
                $failStr = '';
                foreach ($fail as $item) {
                    $serialNumber = $item->serial_number ? "????????????{$item->serial_number}" : "";
                    $failStr .= "{$serialNumber}\r\n????????????{$item->factory_device_code}";
                }
                throw new Exception(count($fail) . "??????????????????????????????????????????\r\n{$failStr}");
            }

            return Response::make('???????????????????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (Exception $exception) {
            return Response::make($exception->getMessage(), 404);
        }
    }

    /**
     * ??????????????????
     * @param string $serial_number
     * @return Factory|RedirectResponse|View
     */
    final public function printLabel(string $serial_number)
    {
        try {
            $direction = request('direction', 'IN');
            $warehouse_report_entire_instances = DB::table('warehouse_report_entire_instances as wrei')
                ->selectRaw('wrei.entire_instance_identity_code,ei.serial_number as ei_sn,ei.model_name,ei.maintain_station_name,ei.maintain_location_code,ei.crossroad_number,ei.traction,ei.line_name,ei.open_direction,ei.said_rod')
                ->leftJoin(DB::raw('entire_instances ei'), 'wrei.entire_instance_identity_code', '=', 'ei.identity_code')
                ->when($direction, function ($query) use ($direction) {
                    switch ($direction) {
                        default:
                        case 'IN':
                            $query->orderByDesc('wrei.id');
                            break;
                        case 'OUT':
                            $query->orderBy('ei.maintain_station_name')->orderBy('ei.maintain_location_code');
                            break;
                    }
                })
                ->where('warehouse_report_serial_number', $serial_number)
                ->get()
                ->toArray();
            if ($direction == 'OUT') {
                $identityCodes = array_column($warehouse_report_entire_instances, 'entire_instance_identity_code');
                $breakdowns = DB::table('breakdown_logs')->selectRaw("count(id) as count,entire_instance_identity_code")->whereIn('entire_instance_identity_code', $identityCodes)->groupBy('entire_instance_identity_code')->orderBy('count')->pluck('count', 'entire_instance_identity_code')->toArray();
                foreach ($warehouse_report_entire_instances as $warehouse_report_entire_instance) {
                    $warehouse_report_entire_instance->breakdown_count = $breakdowns[$warehouse_report_entire_instance->entire_instance_identity_code] ?? 0;
                }
                $warehouse_report_entire_instances = collect($warehouse_report_entire_instances)->sortByDesc('breakdown_count');
            }

            return view('Warehouse.Report.printLabel', [
                'warehouse_report_entire_instances' => $warehouse_report_entire_instances,
                'direction' => $direction
            ]);
        } catch (Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function storeIdentityCodeWithPrint(Request $request)
    {
        try {
            $identityCodes = $request->get('identityCodes');
            if (empty($identityCodes)) return HttpResponseHelper::errorEmpty('???????????????');
            $account_id = session('account.id');
            if (empty($account_id)) return HttpResponseHelper::errorValidate('?????????????????????????????????');
            DB::transaction(function () use ($account_id, $identityCodes, $request) {
                $now = date('Y-m-d H:i:s');
                DB::table('print_identity_codes')->where('account_id', $account_id)->delete();
                $insert = [];
                foreach ($identityCodes as $identityCode) {
                    $insert[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'account_id' => $account_id,
                        'entire_instance_identity_code' => $identityCode
                    ];
                }
                DB::table('print_identity_codes')->insert($insert);
            });

            return HttpResponseHelper::created('ok');
        } catch (Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    protected function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Warehouse.Report.{$viewName}";
    }
}
