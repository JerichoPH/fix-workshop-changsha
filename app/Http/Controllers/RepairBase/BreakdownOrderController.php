<?php

namespace App\Http\Controllers\RepairBase;

use App\Exceptions\EntireInstanceLockException;
use App\Exceptions\FuncNotFoundException;
use App\Facades\BreakdownLogFacade;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\BreakdownLog;
use App\Model\BreakdownType;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\Maintain;
use App\Model\PivotBreakdownLogAndBreakdownType;
use App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes;
use App\Model\RepairBaseBreakdownOrder;
use App\Model\RepairBaseBreakdownOrderEntireInstance;
use App\Model\RepairBaseBreakdownOrderTempEntireInstance;
use App\Model\WarehouseReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class BreakdownOrderController extends Controller
{
    private $__work_areas = [];
    private $__category_with_work_area = [1 => 'S03', 2 => 'Q01'];

    public function __construct()
    {
        $this->__work_areas = array_flip(Account::$WORK_AREAS);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function index()
    {
        try {
            $IN = function () {
                $breakdown_orders = RepairBaseBreakdownOrder::with([
                    'InEntireInstances',
                    'InEntireInstances.OldEntireInstance',
                    'Processor',
                ])
                    ->where('direction', request('direction', 'IN'))
                    ->when(request('scene_workshop_code'), function ($query) {
                        return $query->where('scene_workshop_code', request('scene_workshop_code'));
                    })
                    ->when(request('station_code'), function ($query) {
                        return $query->where('station_code', request('station_code'));
                    })
                    ->when(request('created_at'), function ($query) {
                        $time = Carbon::createFromFormat('Y-m', request('created_at'));
                        $origin_at = $time->firstOfMonth()->format('Y-m-d');
                        $finish_at = $time->endOfMonth()->format('Y-m-d');
                        return $query->whereBetween('created_at', ["{$origin_at} 00:00:00", "{$finish_at} 23:59:59"]);
                    })
                    ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                    ->orderByDesc('updated_at')
                    ->paginate();
                $maintains = Maintain::with(['Subs'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();

                return view('RepairBase.BreakdownOrder.index', [
                    'breakdown_orders' => $breakdown_orders,
                    'maintains' => $maintains->toJson(),
                ]);
            };

            $OUT = function () {
                $breakdown_orders = RepairBaseBreakdownOrder::with([
                    'OutEntireInstances',
                    'OutEntireInstances.OldEntireInstance',
                    'Processor',
                ])
                    ->where('direction', request('direction', 'OUT'))
                    ->when(request('scene_workshop_code'), function ($query) {
                        return $query->where('scene_workshop_code', request('scene_workshop_code'));
                    })
                    ->when(request('station_code'), function ($query) {
                        return $query->where('station_code', request('station_code'));
                    })
                    ->when(request('created_at'), function ($query) {
                        $time = Carbon::createFromFormat('Y-m', request('created_at'));
                        $origin_at = $time->firstOfMonth()->format('Y-m-d');
                        $finish_at = $time->endOfMonth()->format('Y-m-d');
                        return $query->whereBetween('created_at', ["{$origin_at} 00:00:00", "{$finish_at} 23:59:59"]);
                    })
                    ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                    ->orderByDesc('updated_at')
                    ->paginate();
                $maintains = Maintain::with(['Subs'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();

                return view('RepairBase.BreakdownOrder.index', [
                    'breakdown_orders' => $breakdown_orders,
                    'maintains' => $maintains->toJson(),
                ]);
            };

            $func = strtoupper(request('direction'));
            return $$func();
        } catch (FuncNotFoundException $e) {
            return redirect('')->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect('')->with('danger', '???????????????');
        } catch (\Throwable $th) {
            // dd($th->getMessage(), $th->getFile(), $th->getLine());
            return redirect('')->with('danger', '????????????');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param string $serial_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $temp_entire_instances = RepairBaseBreakdownOrderTempEntireInstance::with(['EntireInstance',])->where('operator_id', session('account.id'));

            $work_area = $this->__work_areas[session('account.work_area')];
            $breakdown_types = BreakdownType::with([])
                ->when(
                    $work_area > 0,
                    function ($query) use ($work_area) {
                        return $query->where('work_area', $work_area);
                    }
                );

            return view('RepairBase.BreakdownOrder.create', [
                'temp_entire_instances' => $temp_entire_instances->get(),
                'temp_entire_instance_count' => $temp_entire_instances->count(),
                'breakdown_types' => $breakdown_types->pluck('name', 'id')->chunk(3)->toArray()
            ]);
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '???????????????????????????????????????????????????????????????');
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $IN = function () use ($request) {
                try {
                    $work_area = $this->__work_areas[session('account.work_area')];
                    // ?????????????????????
                    $breakdown_order_temp_entire_instances = RepairBaseBreakdownOrderTempEntireInstance::with([
                        'EntireInstance',
                        'EntireInstance.Station',
                        'EntireInstance.Station.Parent',
                        'BreakdownTypes',
                        'Operator',
                    ])
                        ->where('operator_id', session('account.id'))
                        ->get();
                    if ($breakdown_order_temp_entire_instances->isEmpty()) return response()->json(['message' => '?????????????????????'], 404);

                    // ??????
                    $processed_at = Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processedDate')} {$request->get('processedTime')}");

                    // ??????????????????????????????
                    $breakdown_order_in = new RepairBaseBreakdownOrder();
                    $breakdown_order_in->fill([
                        'created_at' => $processed_at,
                        'serial_number' => $new_breakdown_in_sn = CodeFacade::makeSerialNumber('BREAKDOWN_IN'),
                        'work_area_id' => $work_area,
                        'status' => 'DONE',
                        'direction' => 'IN',
                        'processor_id' => session('account.id'),
                        'processed_at' => $processed_at
                    ]);
                    $breakdown_order_in->saveOrFail();

                    // ??????????????????????????????
                    $breakdown_order_out = new RepairBaseBreakdownOrder();
                    $breakdown_order_out->fill([
                        'created_at' => $processed_at,
                        'serial_number' => $new_breakdown_out_sn = CodeFacade::makeSerialNumber('BREAKDOWN_OUT'),
                        'work_area_id' => $work_area,
                        'status' => 'UNDONE',
                        'direction' => 'OUT',
                        'in_sn' => $new_breakdown_in_sn,
                    ]);
                    $breakdown_order_out->saveOrFail();

                    // ????????????
                    $in_warehouse_sn = WarehouseReportFacade::batchInWithBreakdownOrderTempEntireInstances(
                        $breakdown_order_temp_entire_instances,
                        session('account.id'),
                        $processed_at,
                        $request->get('connectionName'),
                        $request->get('connectionPhone')
                    );

                    // ?????????????????????
                    $breakdown_order_entire_instances = [];
                    foreach ($breakdown_order_temp_entire_instances as $ei) {
                        $breakdown_order_entire_instances[] = [
                            'created_at' => $processed_at,
                            'updated_at' => $processed_at,
                            'old_entire_instance_identity_code' => $ei->entire_instance_identity_code,
                            'scene_workshop_name' => @$ei->EntireInstance->Station->Parent->name ?: '',
                            'maintain_station_name' => @$ei->EntireInstance->Station->name ?: '',
                            'maintain_location_code' => @$ei->EntireInstance->maintain_location_code ?: '',
                            'crossroad_number' => @$ei->EntireInstance->crossroad_number ?: '',
                            'in_sn' => $breakdown_order_in->serial_number,
                            'out_sn' => $breakdown_order_out->serial_number,
                            'in_warehouse_sn' => $in_warehouse_sn,
                            'source' => $ei->source,
                            'source_traction' => @$ei->EntireInstance->source_traction ?: '',
                            'source_crossroad_number' => @$ei->EntireInstance->source_crossroad_number ?: '',
                            'traction' => @$ei->EntireInstance->traction ?: '',
                            'open_direction' => @$ei->EntireInstance->open_direction ?: '',
                            'said_rod' => @$ei->EntireInstance->said_rod ?: '',
                            'crossroad_type' => @$ei->EntireInstance->crossroad_type ?: '',
                            'point_switch_group_type' => @$ei->EntireInstance->point_switch_group_type ?: '',
                            'extrusion_protect' => @$ei->EntireInstance->extrusion_protect ?: '',
                        ];
                    }
                    DB::table('repair_base_breakdown_order_entire_instances')->insert($breakdown_order_entire_instances);
                    // ???????????????
                    DB::table('repair_base_breakdown_order_temp_entire_instances')->whereIn('id', $breakdown_order_temp_entire_instances->pluck('id')->toArray())->delete();

                    return response()->json(['message' => '????????????', 'in_warehouse_sn' => $in_warehouse_sn, 'new_breakdown_out_sn' => $new_breakdown_out_sn]);
                } catch (\Exception $e) {
                    return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
                }
            };

            $OUT = function () use ($request) {
                try {
                    $in_order = RepairBaseBreakdownOrder::with([])
                        ->where('serial_number', request('sn'))
                        ->when(
                            $this->__work_areas[session(['account.work_area'])],
                            function ($query) {
                                return $query->where('work_area_id', $this->__work_areas[session('account.work_area')]);
                            }
                        )
                        ->firstOrFail();

                    $repeat = RepairBaseBreakdownOrder::with([])
                        ->where('direction', 'OUT')
                        ->where('in_sn', $request->get('sn'))
                        ->first();
                    if ($repeat) {
                        $out_sn = $repeat->serial_number;
                    } else {
                        $out_order = new RepairBaseBreakdownOrder();
                        $out_order->fill([
                            'serial_number' => $out_sn = CodeFacade::makeSerialNumber('BREAKDOWN_OUT'),
                            'scene_workshop_code' => $in_order['scene_workshop_code'],
                            'station_code' => $in_order['station_code'],
                            'direction' => 'OUT',
                            'work_area_id' => $in_order['work_area_id'],
                            'in_sn' => $request->get('sn'),
                        ])->saveOrFail();
                    }

                    // ????????????????????????????????????
                    DB::table('repair_base_breakdown_order_entire_instances')
                        ->where('in_sn', request('sn'))
                        ->update(['out_sn' => $out_sn]);

                    return response()->json(['message' => '????????????', 'return_url' => "/repairBase/breakdownOrder/{$out_sn}?direction=OUT"]);
                } catch (ModelNotFoundException $e) {
                    return response()->json(['message' => '??????????????????????????????????????????????????????????????????'], 404);
                } catch (\Exception $e) {
                    return response()->json(['message' => '????????????'], 500);
                } catch (\Throwable $th) {
                    return response()->json(['message' => '????????????'], 403);
                }
            };

            $func = strtoupper($request->get('direction'));
            return $$func();
        });
    }

    /**
     * Display the specified resource.
     *
     * @param string $serial_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show(string $serial_number)
    {
        try {
            $IN = function () use ($serial_number) {
                $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $scan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_scan is true
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $plan_sum = array_sum($plan_count);
                $scan_sum = array_sum($scan_count);
                $warehouse_sum = array_sum($warehouse_count);

                $breakdown_order = RepairBaseBreakdownOrder::with([
                    'InEntireInstances',
                    'InEntireInstances.OldEntireInstance',
                ])
                    ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();

                return view('RepairBase.BreakdownOrder.showIn', [
                    'breakdown_order' => $breakdown_order,
                    'plan_count' => $plan_count,
                    'scan_count' => $scan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'scan_sum' => $scan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            $OUT = function () use ($serial_number) {
                $plan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $plan_sum = array_sum($plan_count);
                $warehouse_sum = array_sum($warehouse_count);

                $breakdown_order = RepairBaseBreakdownOrder::with([
                    'OutEntireInstances',
                    'OutEntireInstances.NewEntireInstance',
                    'OutEntireInstances.NewEntireInstance.Station',
                    'OutEntireInstances.NewEntireInstance.Station.Parent',
                ])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();

                return view('RepairBase.BreakdownOrder.showOut', [
                    'breakdown_order' => $breakdown_order,
                    'plan_count' => $plan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            $func = strtoupper(request('direction'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????????????????????????????????????????????????????');
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getLine(), $e->getFile());
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
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
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * ??????????????????
     * @param int $id
     */
    final public function getTempEntireInstance(int $id)
    {
        try {
            $entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with([])->where('id', $id)->first();
            return JsonResponseFacade::data(['entire_instance' => $entire_instance]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    final public function postStationBreakdownExplain(Request $request, int $id)
    {
        try {
            $entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with([])->where('id', $id)->first();
            $entire_instance->fill($request->all())->saveOrFail();
            return JsonResponseFacade::created(['entire_instance' => $entire_instance], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getEntireInstances()
    {
        try {
            $entire_instances = EntireInstance::with([])
                ->whereNotIn('identity_code', DB::table('entire_instance_locks')->pluck('entire_instance_identity_code')->toArray())
                ->when(request('no'), function ($query) {
                    return $query
                        // ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_OUT', 'TRANSFER_IN'])
                        ->where(function ($query) {
                            return $query
                                ->where('identity_code', request('no'))
                                ->orWhere('serial_number', request('no'));
                        });
                })
                ->when(request('location'), function ($query) {
                    return $query
                        // ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_OUT', 'TRANSFER_IN'])
                        ->where(function ($query) {
                            return $query
                                ->where('crossroad_number', request('location'))
                                ->orWhere('maintain_location_code', request('location'));
                        });

                })
                // ->when(array_key_exists(
                // $this->__work_areas[session('account.work_area')], $this->__category_with_work_area),
                // function ($query) {
                // $current_category_unique_code = $this->__category_with_work_area[$this->__work_areas[session('account.work_area')]];
                // return $query->where('category_unique_code', $current_category_unique_code);
                // }, function ($query) {
                // return $query->whereNotIn('category_unique_code', ['S03', 'Q01']);
                // }
                // )
                ->get([
                    'identity_code',
                    'serial_number',
                    'model_name',
                    'maintain_location_code',
                    'crossroad_number',
                    'maintain_station_name',
                ]);

            if ($entire_instances->isEmpty()) return response()->json(['message' => '??????????????????????????????????????????'], 404);

            return response()->json(['message' => '????????????', 'data' => $entire_instances]);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function postEntireInstances(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                // ?????????????????????????????????
                $entire_instances = EntireInstance::with([])
                    ->whereNotIn('identity_code', DB::table('entire_instance_locks')->pluck('entire_instance_identity_code')->toArray())
                    ->when(request('no'), function ($query) {
                        return $query
                            // ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_OUT', 'TRANSFER_IN'])
                            ->where(function ($query) {
                                return $query
                                    ->where('identity_code', request('no'))
                                    ->orWhere('serial_number', request('no'));
                            });
                    })
                    ->when(request('location'), function ($query) {
                        return $query
                            // ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_OUT', 'TRANSFER_IN'])
                            ->where(function ($query) {
                                return $query
                                    ->where('crossroad_number', request('location'))
                                    ->orWhere('maintain_location_code', request('location'));
                            });

                    })
                    // ->when(array_key_exists(
                    // $this->__work_areas[session('account.work_area')], $this->__category_with_work_area),
                    // function ($query) {
                    // $current_category_unique_code = $this->__category_with_work_area[$this->__work_areas[session('account.work_area')]];
                    // return $query->where('category_unique_code', $current_category_unique_code);
                    // }, function ($query) {
                    // return $query->whereNotIn('category_unique_code', ['S03', 'Q01']);
                    // }
                    // )
                    ->get([
                        'identity_code',
                        'serial_number',
                        'model_name',
                        'maintain_location_code',
                        'crossroad_number',
                        'maintain_station_name',
                    ]);
                if ($entire_instances->isEmpty()) return response()->json(['message' => '??????????????????'], 404);

                $breakdown_order_temp_entire_instances = [];  // ????????????????????????
                foreach ($entire_instances as $entire_instance) {
                    $maintain = Maintain::with(['Parent'])->where('name', $entire_instance->maintain_station_name)->first();

                    // ??????????????????
                    if (!$maintain) return response()->json(['message' => "?????????????????? ???{$entire_instance->maintain_station_name}????????????????????????????????????????????????", 'details' => $maintain], 404);
                    if (!@$maintain->Parent) return response()->json(['message' => "????????????????????????{$entire_instance->maintain_station_name}????????????????????????????????????????????????????????????", 'details' => $maintain], 404);
                    if (!$entire_instance->maintain_location_code && !$entire_instance->crossroad_number)
                        return response()->json([
                            'message' => "?????????{$entire_instance->identity_code}/{$entire_instance->serial_number}?????????????????????????????????????????????????????????",
                            'details' => [
                                'maintain' => $maintain,
                                'maintain_location_code' => $entire_instance->maintain_location_code,
                                'crossroad_number' => $entire_instance->crossroad_number
                            ]
                        ], 404);

                    // ????????????
                    $repeat = RepairBaseBreakdownOrderTempEntireInstance::with([])
                        ->whereIn('entire_instance_identity_code', $entire_instances->pluck('identity_code')->toArray())
                        ->first();
                    if ($repeat) return response()->json(['message' => "?????????{$entire_instance->identity_code}/{$entire_instance->serial_number}???????????????"], 403);

                    $breakdown_order_temp_entire_instances[] = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'operator_id' => session('account.id'),
                        'entire_instance_identity_code' => $entire_instance->identity_code,
                    ];
                }

                // ???????????????
                RepairBaseBreakdownOrderTempEntireInstance::with([])->insert($breakdown_order_temp_entire_instances);
                return response()->json(['message' => '????????????']);
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function updateEntireInstances(Request $request, int $id)
    {
        try {
            $e = RepairBaseBreakdownOrderTempEntireInstance::with([])->where('id', $id)->firstOrFail();
            $e->fill($request->all())->saveOrFail();

            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ??????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteEntireInstances(Request $request)
    {
        try {
            $IN = function () use ($request) {
                $temp_entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with([])
                    ->where('entire_instance_identity_code', $request->get('identityCode'))
                    ->firstOrFail();
                $temp_entire_instance->delete();
                return response()->json(['message' => '????????????']);
            };

            $OUT = function () use ($request) {
                RepairBaseBreakdownOrderEntireInstance::with([])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->delete();

                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance' => function ($EntireInstance) {
                        return $EntireInstance->select([
                            'crossroad_number',
                            'identity_code',
                            'maintain_location_code',
                            'model_name', 'serial_number'
                        ]);
                    }
                ])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->get();

                return response()->json(['message' => '????????????', 'data' => $entire_instances]);
            };

            $func = strtoupper($request->get('direction'));
            return $$func();
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postScanEntireInstances(Request $request)
    {
        try {
            $IN = function () use ($request) {
                // ??????????????????
                $entire_instance = RepairBaseBreakdownOrderEntireInstance::with([])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->where('in_warehouse_sn', '')
                    ->firstOrFail();
                $entire_instance->fill(['in_scan' => true])->saveOrFail();

                $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $scan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_scan is true
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $plan_sum = array_sum($plan_count);
                $scan_sum = array_sum($scan_count);
                $warehouse_sum = array_sum($warehouse_count);

                // ???????????????????????????
                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance' => function ($OldEntireInstance) {
                        return $OldEntireInstance
                            ->select([
                                'crossroad_number',
                                'identity_code',
                                'maintain_location_code',
                                'model_name',
                                'serial_number'
                            ]);
                    }
                ])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('in_scan', true)
                    ->get();

                return response()->json([
                    'message' => '????????????',
                    'data' => $entire_instances,
                    'plan_count' => $plan_count,
                    'scan_count' => $scan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'scan_sum' => $scan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            $OUT = function () use ($request) {
                // ????????????????????????
                $entire_instance = RepairBaseBreakdownOrderEntireInstance::with([])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->where('new_entire_instance_identity_code', $request->get('identityCode'))
                    ->where('out_warehouse_sn', '')
                    ->firstOrFail();
                $entire_instance->fill(['out_scan' => true])->saveOrFail();

                // ???????????????????????????
                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance' => function ($OldEntireInstance) {
                        return $OldEntireInstance
                            ->select([
                                'crossroad_number',
                                'identity_code',
                                'maintain_location_code',
                                'model_name',
                                'serial_number'
                            ]);
                    },
                    'NewEntireInstance' => function ($NewEntireInstance) {
                        return $NewEntireInstance
                            ->select([
                                'identity_code',
                                'serial_number',
                                'location_unique_code'
                            ]);
                    }
                ])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->get();

                return response()->json([
                    'message' => '????????????',
                    'data' => $entire_instances,
                ]);
            };

            $func = strtoupper(request('direction'));
            return $$func();
        } catch (EntireInstanceLockException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $line = $th->getLine();
            $file = $th->getFile();
            return response()->json(['message' => '????????????', 'details' => [$msg, $line, $file]], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????'], 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteScanEntireInstances(Request $request)
    {
        try {
            $IN = function () use ($request) {
                $entire_instance = RepairBaseBreakdownOrderEntireInstance::with([])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->firstOrFail();
                $entire_instance->fill(['in_scan' => false])->saveOrFail();

                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance' => function ($OldEntireInstance) {
                        return $OldEntireInstance
                            ->select(['crossroad_number', 'identity_code', 'maintain_location_code', 'model_name', 'serial_number']);
                    },
                    'NewEntireInstance' => function ($NewEntireInstance) {
                        return $NewEntireInstance
                            ->select(['crossroad_number', 'identity_code', 'maintain_location_code', 'model_name', 'serial_number', 'location_unique_code']);
                    }
                ])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('in_scan', true)
                    ->get();

                $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $scan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_scan is true
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $plan_sum = array_sum($plan_count);
                $scan_sum = array_sum($scan_count);
                $warehouse_sum = array_sum($warehouse_count);


                return response()->json([
                    'message' => '????????????',
                    'data' => $entire_instances,
                    'plan_count' => $plan_count,
                    'scan_count' => $scan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'scan_sum' => $scan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            $OUT = function () use ($request) {
                $entire_instance = RepairBaseBreakdownOrderEntireInstance::with([])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->firstOrFail();
                $entire_instance->fill(['out_scan' => false])->saveOrFail();

                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance' => function ($OldEntireInstance) {
                        return $OldEntireInstance
                            ->with(['Station', 'Station.Parent'])
                            ->select(['maintain_station_name', 'crossroad_number', 'identity_code', 'maintain_location_code', 'model_name', 'serial_number']);
                    },
                    'NewEntireInstance' => function ($NewEntireInstance) {
                        return $NewEntireInstance
                            ->select(['crossroad_number', 'identity_code', 'maintain_location_code', 'model_name', 'serial_number', 'location_unique_code']);
                    }
                ])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->get();

                return response()->json([
                    'message' => '????????????',
                    'data' => $entire_instances,
                ]);
            };


            $func = strtoupper($request->get('direction'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * ??????????????????
     * @param string $sn
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getPrintLabel(string $sn)
    {
        try {
            $IN = function () use ($sn) {
                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'InOrder',
                    'OldEntireInstance'
                ])
                    ->when(
                        request('search_content'),
                        function ($query) {
                            return $query
                                ->whereHas('OldEntireInstance', function ($EntireInstance) {
                                    $EntireInstance->where('identity_code', request('search_content'))
                                        ->orWhere('serial_number', request('search_content'));
                                });
                        }
                    )
                    ->where('in_sn', $sn)
                    ->paginate();

                return view('RepairBase.BreakdownOrder.printLabelIn', [
                    'entire_instances' => $entire_instances,
                    'in_sn' => $sn,
                ]);
            };

            $OUT = function () use ($sn) {
                $breakdown_order = RepairBaseBreakdownOrder::with([])->where('serial_number', $sn)->firstOrFail();

                /**
                 * maintain_location_code
                 * crossroad_number
                 * maintain_station_name
                 */
                $entire_instances = RepairBaseBreakdownOrderEntireInstance::with([
                    'OldEntireInstance',
                    'OldEntireInstance.FixWorkflow' => function ($FixWorkflow) use ($breakdown_order) {
                        $FixWorkflow->where('created_at', '>', $breakdown_order->created_at);
                    },
                    'NewEntireInstance',
                    'BreakdownLog',
                    'BreakdownReportFiles',
                ])
                    ->selectRaw("*, concat(maintain_station_name,' ',maintain_location_code,' ',crossroad_number) as install_location")
                    ->where('out_sn', $sn)
                    ->get();

                // ?????????????????????????????????
                $work_area = $this->__work_areas[session('account.work_area')];
                $breakdown_types = BreakdownType::with([])
                    ->when(
                        $work_area > 0,
                        function ($query) use ($work_area) {
                            return $query->where('work_area', $work_area);
                        }
                    );

                // ????????????????????????????????????
                $install_locations = collect([]);
                foreach ($entire_instances as $entire_instance) {
                    $install_locations->push([
                        'maintain_station_name' => $entire_instance->maintain_station_name,
                        'maintain_location_code' => $entire_instance->maintain_location_code,
                        'crossroad_number' => $entire_instance->crossroad_number,
                    ]);
                }
                // ??????????????????????????????
                $breakdown_logs_as_install_location = BreakdownLog::with([])
                    ->selectRaw("concat(maintain_station_name,' ',maintain_location_code,' ',crossroad_number) as install_location")
                    ->whereIn('maintain_station_name', $install_locations->pluck('maintain_station_name')->toArray())
                    ->get()
                    ->groupBy('install_location')
                    ->toArray();

                $plan_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($sn);
                $usable_entire_instance_sum = $usable_entire_instances->sum(function ($value) {
                    return $value->count();
                });

                $old_count = DB::table('repair_base_breakdown_order_entire_instances')->where('out_sn', $sn)->count();
                $new_count = DB::table('repair_base_breakdown_order_entire_instances')->where('out_sn', $sn)->where('new_entire_instance_identity_code', '<>', '')->count();
                $is_all_bound = (($new_count === $old_count) && ($old_count > 0));  // ????????????????????????

                return view('RepairBase.BreakdownOrder.printLabelOut', [
                    'entire_instances' => $entire_instances,
                    'usable_entire_instances' => $usable_entire_instances,
                    'out_sn' => $sn,
                    'is_all_bound' => $is_all_bound,
                    'plan_sum' => $plan_sum,
                    'usable_entire_instance_sum' => $usable_entire_instance_sum,
                    'breakdown_logs_as_install_location' => $breakdown_logs_as_install_location,
                    'breakdown_types' => $breakdown_types->pluck('name', 'id')->chunk(3)->toArray(),
                ]);
            };

            $func = strtoupper(request('direction'));
            return $$func();
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ???????????????????????????????????????
     * @param string $out_sn
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    final private function _getUsableEntireInstancesWithOutSn(string $out_sn)
    {
        $must_warehouse_location = false;  // ?????????????????????

        $out_order = DB::table('repair_base_breakdown_orders')
            ->where('serial_number', $out_sn)
            ->first(['in_sn']);
        if (!$out_order) throw new \Exception('??????????????????', 404);
        if (!$out_order->in_sn) throw new \Exception('????????????????????????', 404);

        // ????????????????????????
        return EntireInstance::with(['FixWorkflows'])
            ->withCount('FixWorkflows')
            ->where('status', 'FIXED')
            ->when($must_warehouse_location, function ($query) {
                return $query
                    ->where('location_unique_code', '<>', null)
                    ->where('location_unique_code', '<>', '');
            })
            ->whereNotIn('identity_code', DB::table('entire_instance_locks')
                ->pluck('entire_instance_identity_code')
                ->toArray())
            ->whereIn('model_name', DB::table('repair_base_breakdown_order_entire_instances as oei')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('oei.in_sn', $out_order->in_sn)
                ->groupBy('ei.model_name')
                ->pluck('ei.model_name')
                ->toArray())
            ->orderByDesc('made_at')
            ->orderBy('fix_workflows_count')
            ->get()
            ->groupBy('model_name');
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $sn
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putDone(Request $request, string $sn)
    {
        try {
            $put_in = function () use ($request, $sn) {
                $breakdown_order = RepairBaseBreakdownOrder::with([
                    'InEntireInstances',
                ])
                    ->where('direction', 'IN')
                    ->where('serial_number', $sn)
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                $plan_sum = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                $warehouse_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                if (($plan_sum === 0) || ($warehouse_sum !== $plan_sum))
                    return response()->json(['message' => '????????????????????????????????????????????????'], 403);
                $breakdown_order->fill(['status' => 'DONE'])->saveOrFail();

                return response()->json(['message' => '??????????????????']);
            };

            $put_out = function () use ($request, $sn) {
                $breakdown_order = RepairBaseBreakdownOrder::with(['OutEntireInstances'])
                    ->where('direction', 'OUT')
                    ->where('serial_number', $sn)
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                $plan_sum = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.out_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                $warehouse_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.out_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                if (($plan_sum === 0) || ($warehouse_sum !== $plan_sum))
                    return response()->json(['message' => '????????????????????????????????????????????????'], 403);
                $breakdown_order->fill(['status' => 'DONE'])->saveOrFail();

                return response()->json(['message' => '??????????????????']);
            };

            switch (request('direction')) {
                default:
                case 'IN':
                    return $put_in();
                    break;
                case 'OUT':
                    return $put_out();
                    break;
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????????????????????????????????????????'], 404);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            return response()->json(['message' => '????????????', 'details' => [$msg, $line, $file]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????'], 403);
        }
    }

    /**
     * ?????????
     * @param Request $request
     * @param $sn
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \Throwable
     */
    final public function postWarehouse(Request $request, $sn)
    {
        try {
            return DB::transaction(function () use ($request, $sn) {
                $now = date('Y-m-d H:i:s');
                $out = function () use ($request, $sn, $now) {
                    $breakdownOrder = RepairBaseBreakdownOrder::with([
                        'OutEntireInstances' => function ($OutEntireInstances) {
                            $OutEntireInstances->where('out_scan', true);
                        },
                    ])
                        ->where('direction', 'OUT')
                        ->where('serial_number', $sn)
                        ->firstOrFail();

                    if ($breakdownOrder->OutEntireInstances->isEmpty())
                        return response()->json(['message' => '?????????????????????'], 404);

                    // ??????????????????????????????
                    EntireInstanceLock::freeLocks(
                        $breakdownOrder->OutEntireInstances->pluck('new_entire_instance_identity_code')->toArray(),
                        ['BREAKDOWN'],
                        function () use ($now, $request, &$breakdownOrder) {
                            foreach ($breakdownOrder
                                         ->OutEntireInstances
                                         ->groupBy('maintain_station_name')
                                         ->all() as $maintain_station_name => $item) {
                                $processed_at = Carbon::createFromFormat('Y-m-d H:i', "{$request->get('processedDate')} {$request->get('processedTime')}");

                                // ???????????????
                                $new_warehouse_report_out = new WarehouseReport();
                                $new_warehouse_report_out->fill([
                                    'processor_id' => session('account.id'),
                                    'processed_at' => $processed_at,
                                    'connection_name' => $request->get('connection_name'),
                                    'connection_phone' => $request->get('connection_phone'),
                                    'type' => 'BREAKDOWN',
                                    'direction' => 'OUT',
                                    'serial_number' => $new_warehouse_report_out_sn = CodeFacade::makeSerialNumber('OUT'),
                                    'scene_workshop_name' => array_first($item)->scene_workshop_name,
                                    'station_name' => array_first($item)->maintain_station_name,
                                    'work_area_id' => $breakdownOrder->work_area_id,
                                ]);
                                $new_warehouse_report_out->saveOrFail();

                                $batchOperation = [];
                                $warehouse_entire_instances = [];
                                $out_entire_instance_correspondences = [];
                                foreach ($breakdownOrder->OutEntireInstances as $entire_instance) {
                                    // ??????????????????
                                    DB::table('entire_instances')->where('identity_code', $entire_instance->new_entire_instance_identity_code)
                                        ->update([
                                            'source' => $entire_instance->source,
                                            'source_traction' => $entire_instance->source_traction,
                                            'source_crossroad_number' => $entire_instance->source_crossroad_number,
                                            'traction' => $entire_instance->traction,
                                            'open_direction' => $entire_instance->open_direction,
                                            'said_rod' => $entire_instance->said_rod,
                                            'maintain_location_code' => $entire_instance->maintain_location_code,
                                            'crossroad_number' => $entire_instance->crossroad_number,
                                            'maintain_station_name' => $entire_instance->maintain_station_name,
                                            'maintain_workshop_name' => $entire_instance->scene_workshop_name,
                                        ]);
                                    // ????????????????????????-???????????????????????????????????????????????????????????????
                                    DB::table('entire_instances')
                                        ->where('deleted_at', null)
                                        ->where('identity_code', $entire_instance->old_entire_instance_identity_code)
                                        ->update([
                                            'updated_at' => $now,
                                            'status' => 'UNINSTALLED',
                                            // 'maintain_workshop_name' => env('JWT_ISS'),
                                            'maintain_station_name' => '',
                                            'crossroad_number' => '',
                                            'maintain_location_code' => '',
                                        ]);
                                    // TODO??? ???????????????????????????????????????
                                    DB::table('entire_instances')
                                        ->where('deleted_at', null)
                                        ->where('identity_code', $entire_instance->new_entire_instance_identity_code)
                                        ->update([
                                            'updated_at' => $now,
                                            'last_installed_time' => time(),
                                        ]);
                                    // ????????????
                                    $install_location = $entire_instance->maintain_location_code ?? $entire_instance->crossroad_number;
                                    // ????????????
                                    DB::table('entire_instance_logs')
                                        ->insert([
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                            'name' => '??????????????????',
                                            'description' => '',
                                            'entire_instance_identity_code' => $entire_instance->new_entire_instance_identity_code,
                                            'type' => 1,
                                            'url' => "/warehouse/report/{$new_warehouse_report_out_sn}?show_type=D&direction=OUT",
                                        ]);
                                    // ????????????
                                    DB::table('entire_instance_logs')
                                        ->insert([
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                            'name' => '??????',
                                            'description' => "{$entire_instance->new_entire_instance_identity_code}?????????{$entire_instance->old_entire_instance_identity_code}????????????{$install_location}",
                                            'type' => 1,
                                            'url' => '',
                                        ]);
                                    // ????????????
                                    DB::table('entire_instance_logs')
                                        ->insert([
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                            'description' => "{$entire_instance->old_entire_instance_identity_code}???{$entire_instance->new_entire_instance_identity_code}??????????????????{$install_location}",
                                            'type' => 1,
                                            'url' => '',
                                        ]);

                                    // ??????????????????????????? @todo: ?????????????????????????????????????????????????????????
                                    $batchOperation[] = [
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                        'name' => '??????????????????',
                                        'description' => '',
                                        'entire_instance_identity_code' => $entire_instance->new_entire_instance_identity_code,
                                        'type' => 1,
                                        'url' => "/warehouse/report/{$new_warehouse_report_out_sn}?show_type=D&direction=OUT",
                                    ];

                                    // ???????????????
                                    $warehouse_entire_instances[] = [
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                        'warehouse_report_serial_number' => $new_warehouse_report_out_sn,
                                        'entire_instance_identity_code' => $entire_instance->new_entire_instance_identity_code,
                                        'maintain_station_name' => @$entire_instance->NewEntireInstance->maintain_station_name,
                                        'maintain_location_code' => @$entire_instance->NewEntireInstance->maintain_location_code,
                                        'crossroad_number' => @$entire_instance->NewEntireInstance->crossroad_number,
                                        'traction' => @$entire_instance->NewEntireInstance->traction,
                                        'line_name' => @$entire_instance->NewEntireInstance->line_name,
                                        'crossroad_type' => @$entire_instance->NewEntireInstance->crossroad_type,
                                        'extrusion_protect' => @$entire_instance->NewEntireInstance->extrusion_protect,
                                        'point_switch_group_type' => @$entire_instance->NewEntireInstance->point_switch_group_type,
                                        'open_direction' => @$entire_instance->NewEntireInstance->open_direction,
                                        'said_rod' => @$entire_instance->NewEntireInstance->said_rod,
                                    ];

                                    // ????????????????????????
                                    $out_entire_instance_correspondences[] = [
                                        'old' => $entire_instance->old_entire_instance_identity_code,
                                        'new' => $entire_instance->new_entire_instance_identity_code,
                                        'location' => @$entire_instance->maintain_location_code . @$entire_instance->crossroad_number,
                                        'station' => $entire_instance->maintain_station_name,
                                        'out_warehouse_sn' => $new_warehouse_report_out_sn,
                                        'account_id' => session('account.id'),
                                    ];
                                    // ?????????????????????
                                    EntireInstanceFacade::nextFixingTimeWithIdentityCode($entire_instance->new_entire_instance_identity_code);
                                }

                                // ?????????????????????
                                if ($warehouse_entire_instances)
                                    DB::table('warehouse_report_entire_instances')->insert($warehouse_entire_instances);

                                if ($batchOperation) {
                                    // ??????????????????
                                    DB::table('entire_instances')
                                        ->whereIn('identity_code', array_pluck($batchOperation, 'entire_instance_identity_code'))
                                        ->update([
                                            'status' => 'INSTALLED',
                                            'location_unique_code' => '',
                                            'is_bind_location' => 0,
                                            'last_out_at' => date('Y-m-d H:i:s')
                                        ]);
                                    // ??????????????????
                                    DB::table('part_instances')
                                        ->whereIn('entire_instance_identity_code', array_pluck($batchOperation, 'entire_instance_identity_code'))
                                        ->update([
                                            'status' => 'FIXED',
                                            'location_unique_code' => '',
                                            'is_bind_location' => 0,
                                        ]);
                                    // ???????????????
                                    $out_entire_instances = $breakdownOrder->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
                                    DB::table('repair_base_breakdown_order_entire_instances')
                                        ->whereIn('new_entire_instance_identity_code', $out_entire_instances)
                                        ->update(['out_scan' => false, 'out_warehouse_sn' => $new_warehouse_report_out_sn]);
                                    // ?????????????????????????????????
                                    DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);
                                }
                            }

                            $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`',
                                [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                                ->pluck('aggregate', 'model_name')
                                ->toArray();

                            $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                                [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                                ->pluck('aggregate', 'model_name')
                                ->toArray();

                            $plan_sum = array_sum($plan_count);
                            $warehouse_sum = array_sum($warehouse_count);

                            // ??????????????????????????????????????????????????????????????????0?????????????????????????????????????????????????????????????????????????????????
                            $breakdownOrder->status = (($plan_sum == $warehouse_sum) && (($plan_sum + $warehouse_sum) > 0)) ? 'DONE' : 'UNSATISFIED';
                            $breakdownOrder->saveOrFail();
                        }
                    );

                    // ??????????????????
                    $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`',
                        [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                        ->pluck('aggregate', 'model_name')
                        ->toArray();
                    // ??????????????????
                    $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                        [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                        ->pluck('aggregate', 'model_name')
                        ->toArray();
                    $plan_sum = array_sum($plan_count);
                    $warehouse_sum = array_sum($warehouse_count);

                    // ??????????????????????????????????????????????????????????????????0?????????????????????????????????????????????????????????????????????????????????
                    $breakdownOrder->status = (($plan_sum == $warehouse_sum) && (($plan_sum + $warehouse_sum) > 0)) ? 'DONE' : 'UNSATISFIED';
                    $breakdownOrder->saveOrFail();

                    $plan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`",
                        [$this->__work_areas[session('account.work_area')], $sn]))
                        ->pluck('aggregate', 'model_name')
                        ->toArray();

                    $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_breakdown_order_entire_instances` as `oei`
inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                        [$this->__work_areas[session('account.work_area')], $sn]))
                        ->pluck('aggregate', 'model_name')
                        ->toArray();

                    $plan_sum = array_sum($plan_count);
                    $warehouse_sum = array_sum($warehouse_count);
                    // ????????????????????????????????????????????????
                    if ($plan_sum > 0 && $plan_sum == $warehouse_sum)
                        $breakdownOrder->fill(['status' => 'DONE', 'processed_at' => now()])->saveOrFail();

                    return response()->json(['message' => '????????????']);
                };

                $func = strtolower($request->get('direction'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '????????????????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        }
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstance(Request $request)
    {
        try {
            $old_entire_instances = RepairBaseBreakdownOrderEntireInstance::with(['OldEntireInstance'])
                ->where('out_sn', $request->get('outSn'))
                ->whereIn('old_entire_instance_identity_code', $request->get('oldIdentityCodes'))
                ->where('new_entire_instance_identity_code', '')
                ->get();
            if ($old_entire_instances->isEmpty()) return JsonResponseFacade::errorEmpty('????????????????????????');

            $old_entire_instances_by_model_name = [];
            foreach ($old_entire_instances as $old_entire_instance) {
                if (!array_key_exists($old_entire_instance->OldEntireInstance->model_name, $old_entire_instances_by_model_name))
                    $old_entire_instances_by_model_name[$old_entire_instance->OldEntireInstance->model_name] = [];
                $old_entire_instances_by_model_name[$old_entire_instance->OldEntireInstance->model_name][] = $old_entire_instance;
            }

            $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'));
            if (is_null($usable_entire_instances)) return response()->json(['message' => '????????????????????????'], 404);

            $band_count = 0;

            DB::beginTransaction();
            foreach ($old_entire_instances_by_model_name as $model_name => $old_entire_instances) {
                $usable_entire_instances_by_model_name = $usable_entire_instances->get($model_name);
                foreach ($old_entire_instances as $old_entire_instance) {
                    // ????????????
                    EntireInstanceLock::setOnlyLock(
                        $usable_entire_instances_by_model_name->first()->identity_code,
                        ['BREAKDOWN'],
                        "?????????{$usable_entire_instances_by_model_name->first()->identity_code}???????????????????????????????????????????????????????????????{$request->get('outSn')}"
                    );
                    // ??????????????????
                    $old_entire_instance->fill(['new_entire_instance_identity_code' => $usable_entire_instances_by_model_name->shift()->identity_code])->saveOrFail();
                    // ??????????????????
                    EntireInstanceFacade::copyLocation($old_entire_instance->old_entire_instance_identity_code, $old_entire_instance->new_entire_instance_identity_code);
                    // ????????????+1
                    $band_count++;
                }
            }
            DB::commit();

            return JsonResponseFacade::created([], "???????????????{$band_count}???");
        } catch (\Throwable $th) {
            return JsonResponseFacade::errorException($th);
        }
    }

    /**
     * ???????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstances(Request $request)
    {
        try {
            $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'));
            if (is_null($usable_entire_instances)) return response()->json(['message' => '????????????????????????'], 404);

            $out_order = DB::table('repair_base_breakdown_orders')->where('serial_number', $request->get('outSn'))->first(['in_sn']);
            if (!$out_order) return response()->json(['??????????????????????????????????????????'], 404);

            $old_entire_instances = DB::table('repair_base_breakdown_order_entire_instances as oei')
                ->select(['ei.identity_code', 'ei.model_name'])
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('in_sn', $out_order->in_sn)
                ->where('new_entire_instance_identity_code', '')
                ->get()
                ->groupBy('model_name')
                ->all();

            DB::transaction(function () use ($old_entire_instances, $usable_entire_instances, $out_order, $request) {
                $new_entire_instance_identity_codes = [];
                $copy_locations = [];
                foreach ($old_entire_instances as $model_name => $entire_instances) {
                    foreach ($entire_instances as $entire_instance) {
                        if ($usable_entire_instances->get($entire_instance->model_name)) {
                            if (!$usable_entire_instance = @$usable_entire_instances->get($entire_instance->model_name)->shift()->identity_code) continue;
                            DB::table('repair_base_breakdown_order_entire_instances')
                                ->where('in_sn', $out_order->in_sn)
                                ->where('old_entire_instance_identity_code', $entire_instance->identity_code)
                                ->update(['new_entire_instance_identity_code' => $usable_entire_instance]);

                            $new_entire_instance_identity_codes[] = $usable_entire_instance;
                            $copy_locations[$usable_entire_instance] = $entire_instance->identity_code;
                        }
                    }
                }

                // ????????????????????????
                EntireInstanceFacade::copyLocations($copy_locations);

                // ????????????
                $entire_instance_lock_remarks = [];
                foreach ($new_entire_instance_identity_codes as $new_entire_instance_identity_code)
                    $entire_instance_lock_remarks[$new_entire_instance_identity_code] = "?????????{$new_entire_instance_identity_code}???????????????????????????????????????????????????????????????{$request->get('outSn')}";
                return EntireInstanceLock::setOnlyLocks($new_entire_instance_identity_codes, ['BREAKDOWN'], $entire_instance_lock_remarks);
            });

            return response()->json(['message' => '????????????????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBindEntireInstance(Request $request)
    {
        try {
            $old_entire_instance = RepairBaseBreakdownOrderEntireInstance::with([])
                ->where('out_sn', $request->get('outSn'))
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->firstOrFail();

            // ?????????????????????????????????????????????
            if ($old_entire_instance->new_entire_instance_identity_code) {
                EntireInstanceLock::freeLock(
                    $old_entire_instance->new_entire_instance_identity_code,
                    ['BREAKDOWN'],
                    function () use ($request, $old_entire_instance) {
                        // ????????????
                        EntireInstanceLock::setOnlyLock(
                            $request->get('newIdentityCode'),
                            ['BREAKDOWN'],
                            "?????????{$request->get('newIdentityCode')}???????????????????????????????????????????????????????????????{$request->get('outSn')}",
                            function () use ($request, $old_entire_instance) {
                                $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();

                                // ??????????????????
                                EntireInstanceFacade::copyLocation($old_entire_instance->old_entire_instance_identity_code, $request->get('newIdentityCode'));
                            }
                        );
                    }
                );
            } else {
                // ????????????
                EntireInstanceLock::setOnlyLock(
                    $request->get('newIdentityCode'),
                    ['BREAKDOWN'],
                    "?????????{$request->get('newIdentityCode')}???????????????????????????????????????????????????????????????{$request->get('outSn')}",
                    function () use ($request, $old_entire_instance) {
                        $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();

                        // ??????????????????
                        EntireInstanceFacade::copyLocation($old_entire_instance->old_entire_instance_identity_code, $request->get('newIdentityCode'));
                    }
                );
            }

            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstance(Request $request)
    {
        try {
            $old_entire_instances = RepairBaseBreakdownOrderEntireInstance::with([])
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->where('out_sn', $request->get('outSn'))
                ->where('new_entire_instance_identity_code', '<>', '')
                ->get();
            if ($old_entire_instances->isEmpty()) return JsonResponseFacade::errorEmpty('?????????????????????/????????????????????????');

            $unband_count = 0;

            DB::beginTransaction();
            // ??????????????????????????????????????????
            foreach ($old_entire_instances as $old_entire_instance) {
                // ????????????
                EntireInstanceLock::freeLock($old_entire_instance->new_entire_instance_identity_code, ['BREAKDOWN']);
                // ?????????????????????
                EntireInstanceFacade::clearLocation($old_entire_instance->new_entire_instance_identity_code);
                $old_entire_instance->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                $unband_count++;
            }
            DB::commit();

            return JsonResponseFacade::deleted([], "???????????????{$unband_count}???");
        } catch (\Throwable $th) {
            return JsonResponseFacade::errorException($th);
        }
    }

    /**
     * ???????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstances(Request $request)
    {
        try {
            $out_order = RepairBaseBreakdownOrder::with([
                'OutEntireInstances',
            ])
                ->where('serial_number', $request->get('outSn'))
                ->first();
            if (!$out_order) return response()->json(['??????????????????????????????????????????'], 404);

            // ????????????
            $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
            $ret = EntireInstanceLock::freeLocks(
                $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all(),
                ['BREAKDOWN'],
                function () use ($out_order) {
                    // ???????????????????????????
                    $new_entire_instance_identity_codes = DB::table('repair_base_breakdown_order_entire_instances')
                        ->where('in_sn', $out_order->in_sn)
                        ->pluck('new_entire_instance_identity_code')
                        ->toArray();
                    EntireInstanceFacade::clearLocations($new_entire_instance_identity_codes);

                    // ??????????????????????????????????????????
                    DB::table('repair_base_breakdown_order_entire_instances')
                        ->where('in_sn', $out_order->in_sn)
                        ->update(['updated_at' => date('Y-m-d'), 'new_entire_instance_identity_code' => '']);
                }
            );

            return response()->json(['message' => '????????????', 'details' => $ret]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * ?????????????????????????????????
     */
    final public function getMission()
    {
        if (request('download') == '1')
            return $this->_makeMissionExcel();


        $date = Carbon::createFromFormat('Y-m', request('date', date('Y-m')));
        $origin_at = $date->firstOfMonth()->format('Y-m-d 00:00:00');
        $finish_at = $date->endOfMonth()->format('Y-m-d 23:59:59');

        $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name,ei.model_unique_code
from `repair_base_breakdown_order_entire_instances` as `oei`
         inner join repair_base_breakdown_orders o on `o`.`serial_number` = `oei`.in_sn
         inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`created_at` between ? and ?
  and `o`.`work_area_id` = ?
group by `ei`.`model_name`;', [$origin_at, $finish_at, $this->__work_areas[session('account.work_area')]]))
            ->all();

        $plan_count2 = [];
        foreach ($plan_count as $item) {
            if (!array_key_exists($item->model_unique_code, $plan_count2)) $plan_count2[$item->model_unique_code] = [];
            $plan_count2[$item->model_unique_code] = $item;
        }

        $work_area_id = $this->__work_areas[session('account.work_area')];

        // ?????????????????????
        $accounts = DB::table('accounts')
            ->where('deleted_at', null)
            ->where('work_area', $work_area_id)
            ->where('supervision', false)
            ->pluck('nickname', 'id')
            ->toArray();

        // ??????????????????????????????
        $year = $date->year;
        $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);
        $file_path = storage_path("app/?????????/{$year}/{$year}-{$month}/{$work_area_id}-????????????.json");
        $account_statistics = @file_get_contents($file_path) ? json_decode(file_get_contents($file_path), true) : [];

        return view('RepairBase.BreakdownOrder.mission', [
            'plan_count' => $plan_count2,
            'accounts' => $accounts,
            'account_statistics' => $account_statistics,
        ]);
    }

    /**
     * ??????????????????excel
     * @return \Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function _makeMissionExcel()
    {
        try {
            $cell_key = [
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
                'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
            ];

            $date = request('date');
            $work_area = session('account.work_area');
            $filename = "?????????{$work_area}???????????????({$date})";

            ExcelWriteHelper::download(
                function ($excel) use ($cell_key) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();

                    list($year, $month) = explode('-', request('date'));
                    $month = str_pad($month, 2, '0', 0);
                    $fs = FileSystem::init(__FILE__);
                    $current_work_area = $this->__work_areas[session('account.work_area')];

                    // ????????????
                    $accounts = DB::table('accounts')
                        ->where('deleted_at', null)
                        ->where('work_area', $current_work_area)
                        ->where('supervision', false)
                        ->pluck('nickname', 'id');
                    // ??????????????????
                    $account_missions = $fs->setPath(storage_path("app/?????????/{$year}/{$year}-{$month}/{$current_work_area}-????????????.json"))->fromJson();
                    $model_names = array_keys($account_missions['statistics_model']);

                    // ????????????
                    $col = 2;
                    $current_sheet->setCellValue("A1", "??????/??????");
                    $current_sheet->setCellValue("B1", "??????");
                    $current_sheet->getColumnDimension('A')->setWidth(20);
                    foreach ($accounts as $account_nickname) {
                        $current_sheet->setCellValue("{$cell_key[$col]}1", $account_nickname);
                        $current_sheet->getColumnDimension("{$cell_key[$col]}")->setWidth(15);
                        $col++;
                    }

                    // ????????????
                    $row = 2;
                    foreach ($model_names as $model_name) {
                        // ??????
                        $current_sheet->setCellValue("A{$row}", $model_name);  // ????????????
                        $current_sheet->setCellValue("B{$row}", $account_missions['statistics_model'][$model_name]);  // ????????????

                        // ????????????
                        $col = 2;
                        foreach ($accounts as $account_id => $account_nickname) {
                            $current_sheet->setCellValue("{$cell_key[$col]}{$row}", $account_missions['statistics'][$account_id][$model_name]['number']);
                            $col++;
                        }
                        $row++;
                    }

                    return $excel;
                },
                $filename
            );
        } catch (\Exception $exception) {
            return back()->with('info', '?????????');
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postMission(Request $request)
    {
        try {
            $date = Carbon::createFromFormat('Y-m', $request->get('date'));
            $year = $date->year;
            $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);
            $root_dir = storage_path("app/?????????");
            if (!is_dir($root_dir)) mkdir($root_dir, 0777);  // ?????????????????????????????????
            $year_path = "{$root_dir}/{$year}";
            $month_path = "{$year_path}/{$year}-{$month}";
            if (!is_dir($year_path)) mkdir($year_path, 0777);  // ????????????????????????????????????
            for ($i = 1; $i <= 12; $i++) {
                $m = str_pad($i, 2, '0', STR_PAD_LEFT);
                $path = "{$year_path}/{$year}-{$m}";
                if (!is_dir($path)) mkdir($path, 0777);
                for ($j = 1; $j <= 3; $j++) {
                    $file_path = "{$path}/{$j}-????????????.json";
                    if (!is_file($file_path)) file_put_contents($file_path, '[]');  // ????????????????????????????????????
                }
            }

            $statistics = [];
            $statistics_account = [];
            $statistics_model = [];
            foreach ($request->post() as $key => $number) {
                list($model_unique_code, $account_id, $model_name) = explode(':', $key);
                // ????????????
                if (!array_key_exists($account_id, $statistics)) $statistics[$account_id] = [];
                $statistics[$account_id][$model_name] = [
                    'model_unique_code' => $model_unique_code,
                    'model_name' => $model_name,
                    'number' => $number,
                    'account_id' => $account_id,
                ];

                // ????????????????????????
                if (!array_key_exists($account_id, $statistics_account)) $statistics_account[$account_id] = 0;
                $statistics_account[$account_id] += $number;

                // ????????????????????????
                if (!array_key_exists($model_name, $statistics_model)) $statistics_model[$model_name] = 0;
                $statistics_model[$model_name] += $number;
            }

            $work_area_id = $this->__work_areas[session('account.work_area')];
            file_put_contents("{$month_path}/{$work_area_id}-????????????.json", json_encode([
                'statistics' => $statistics,
                'statistics_account' => $statistics_account,
                'statistics_model' => $statistics_model
            ]));

            shell_exec("chmod -R 777 {$root_dir}");

            return response()->json(['message' => '????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        }
    }

    /**
     * ????????????????????????????????????????????????
     */
    final public function getBreakdownLog()
    {
        try {
            $temp_entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with(['BreakdownTypes'])->where('id', request('breakdownOrderTempId'))->firstOrFail();
            $checked_breakdown_type_ids = $temp_entire_instance->BreakdownTypes->pluck('breakdown_type_id')->toArray();

            return response()->json([
                'message' => '????????????',
                'checked_breakdown_type_ids' => $checked_breakdown_type_ids,
                'explain' => $temp_entire_instance->in_warehouse_breakdown_explain,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ???????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putBreakdownLog(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $now = date('Y-m-d H:i:s');
                if (!$request->get('breakdown_order_temp_ids')) return response()->json(['message' => '????????????????????????'], 403);
                $temp_entire_instances = RepairBaseBreakdownOrderTempEntireInstance::with([])
                    ->whereIn('id', $request->get('breakdown_order_temp_ids'))
                    ->get();

                // ?????????????????????????????????
                PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])
                    ->whereIn('repair_base_breakdown_order_temp_entire_instance_id', $request->get('breakdown_order_temp_ids'))
                    ->delete();

                // ???????????????
                $b2b = [];
                foreach ($temp_entire_instances as $temp_entire_instance) {
                    if ($request->get('breakdown_type_ids'))
                        foreach ($request->get('breakdown_type_ids') as $breakdown_type_id) {
                            $b2b[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'repair_base_breakdown_order_temp_entire_instance_id' => $temp_entire_instance->id,
                                'breakdown_type_id' => $breakdown_type_id,
                                'type' => 'WAREHOUSE_IN',
                            ];
                        }
                }
                // ???????????????????????????????????????????????????
                PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])->insert($b2b);

                return response()->json(['message' => '????????????']);
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBreakdownType(Request $request)
    {
        try {
            DB::beginTransaction();
            $breakdown_order_entire_instances = RepairBaseBreakdownOrderEntireInstance::with(['OldEntireInstance'])
                ->whereIn('old_entire_instance_identity_code', explode(',', $request->get('old_identity_codes')))
                ->where('out_sn', $request->get('out_sn'))
                ->get();
            $breakdown_order_entire_instance_identity_codes = $breakdown_order_entire_instances->pluck('old_entire_instance_identity_code');
            $diff = $breakdown_order_entire_instance_identity_codes->diff(explode(',', $request->get('old_identity_codes')));
            if ($diff->isNotEmpty()) return JsonResponseFacade::errorForbidden('???????????????????????????' . join(',', $diff->toArray()));

            // ??????????????????
            $breakdown_types = BreakdownType::with([])->whereIn('id', $request->get('breakdown_type_ids') ?? [])->get();
            $breakdown_type_names = $breakdown_types->pluck('name')->implode(',');
            $entire_instance_log_ids = collect([]);
            $breakdown_log_id = 0;
            foreach ($breakdown_order_entire_instances as $repair_base_breakdown_order_entire_instance) {
                if ($repair_base_breakdown_order_entire_instance->entire_instance_log_ids) {
                    // ???????????????????????????????????????????????????
                    BreakdownLog::with([])->where('id', $repair_base_breakdown_order_entire_instance->breakdown_log_id)->forceDelete();
                    PivotBreakdownLogAndBreakdownType::with([])->where('breakdown_log_id', $repair_base_breakdown_order_entire_instance->breakdown_log_id)->forceDelete();
                    EntireInstanceLog::with([])->whereIn('id', explode(',', $repair_base_breakdown_order_entire_instance->entire_instance_log_ids))->forceDelete();
                }

                // ??????????????????
                if ($breakdown_types->isNotEmpty()) {
                    [
                        'entire_instance_log_id' => $entire_instance_log_id,
                        'breakdown_log_id' => $breakdown_log_id,
                    ] = BreakdownLogFacade::createWarehouseIn(
                        $repair_base_breakdown_order_entire_instance->OldEntireInstance,
                        $request->get('explain') ?? '',
                        now()->format('Y-m-d H:i:s'),
                        $breakdown_types->pluck('id')->toArray(),
                        session('account.nickname')
                    );
                    $entire_instance_log_ids[] = $entire_instance_log_id;
                }
            }

            // ?????????????????????
            if ($breakdown_types->isNotEmpty()) {
                RepairBaseBreakdownOrderEntireInstance::with([])
                    ->whereIn('id', $breakdown_order_entire_instances->pluck('id')->toArray())
                    ->update([
                        'updated_at' => now(),
                        'breakdown_types' => $breakdown_type_names,
                        'entire_instance_log_ids' => $entire_instance_log_ids->implode(','),
                        'breakdown_log_id' => $breakdown_log_id,
                    ]);
            }
            DB::commit();

            return JsonResponseFacade::created([], "????????????");
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

}
