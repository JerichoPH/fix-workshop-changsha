<?php

namespace App\Http\Controllers\RepairBase;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\Maintain;
use App\Model\RepairBaseExchangeModelOrder;
use App\Model\RepairBaseExchangeModelOrderEntireInstance;
use App\Model\RepairBaseExchangeModelOrderModel;
use App\Model\WarehouseReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class ExchangeModelOrderController extends Controller
{
    private $__work_areas = [];

    public function __construct()
    {
        $this->__work_areas = array_flip(Account::$WORK_AREAS);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function index()
    {
        $index_in = function () {
            $exchange_model_orders = RepairBaseExchangeModelOrder::with([
                'SceneWorkshop',
                'Station',
                'InEntireInstances',
                'InEntireInstances.OldEntireInstance',
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
                ->paginate();
            $maintains = Maintain::with(['Subs'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();

            return view('RepairBase.ExchangeModelOrder.index', [
                'exchange_model_orders' => $exchange_model_orders,
                'maintains' => $maintains->toJson(),
            ]);
        };

        $index_out = function () {
            $exchange_model_orders = RepairBaseExchangeModelOrder::with([
                'SceneWorkshop',
                'Station',
                'OutEntireInstances',
                'OutEntireInstances.OldEntireInstance',
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
                ->paginate();
            $maintains = Maintain::with(['Subs'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();

            return view('RepairBase.ExchangeModelOrder.index', [
                'exchange_model_orders' => $exchange_model_orders,
                'maintains' => $maintains->toJson(),
            ]);
        };

        switch (request('direction')) {
            default:
            case 'IN':
                return $index_in();
                break;
            case 'OUT':
                return $index_out();
                break;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param string $serial_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create(string $serial_number)
    {
        try {
            $exchange_model_order = RepairBaseExchangeModelOrder::with([
                'SceneWorkshop',
                'Station',
                'InEntireInstances',
                'InEntireInstances.OldEntireInstance',
            ])
                ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                ->where('serial_number', $serial_number)
                ->firstOrFail();

            $station_models = RepairBaseExchangeModelOrderModel::with([])
                ->where('exchange_model_order_sn', $exchange_model_order->serial_number)
                ->get();

            return view('RepairBase.ExchangeModelOrder.create', [
                'exchange_model_order' => $exchange_model_order,
                'station_models' => $station_models
            ]);
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '???????????????????????????????????????????????????????????????');
        } catch (\Exception $e) {
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
        $store_in = function () use ($request) {
            try {
                return DB::transaction(function () use ($request) {
                    if (!$request->get('created_at_create'))
                        return response()->json(['message' => '???????????????'], 405);

                    if ($this->__work_areas[session('account.work_area')] <= 0)
                        return response()->json(['message' => '??????????????????????????????'], 405);

                    $work_area_to_category_unique_code = [
                        1 => 'S03',
                        2 => 'Q01',
                    ];

                    $repeat = RepairBaseExchangeModelOrder::with([])
                        ->where('station_code', $request->get('station_code_create'))
                        ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                        ->where('created_at', Carbon::createFromFormat('Y-m', $request->get('created_at_create'))->format('Y-m-01'))
                        ->first();
                    if ($repeat) return response()->json(['message' => '', 'return_url' => '/repairBase/exchangeModelOrder/' . $repeat->serial_number], 555);

                    $new_sn = CodeFacade::makeSerialNumber('EXCHANGE_MODEL_IN');

                    # ??????????????????
                    $station = DB::table('maintains')
                        ->select(['name'])
                        ->where('deleted_at', null)
                        ->where('unique_code', $request->get('station_code_create'))
                        ->first();
                    if (!$station) return back()->with('danger', '????????????????????????');

                    # ??????????????????????????????????????????
                    if (key_exists($this->__work_areas[session('account.work_area')], $work_area_to_category_unique_code)) {
                        $category_unique_code = $work_area_to_category_unique_code[$this->__work_areas[session('account.work_area')]];
                        if ($this->__work_areas[session('account.work_area')] === 1) {
                            # ???????????????
                            $station_models = DB::select("
select count(ei.model_name) as aggregate,
       ei.model_name        as part_model_name,
       ei.model_unique_code as part_model_unique_code,
       em.name              as entire_model_name,
       em.unique_code       as entire_model_unique_code,
       c.name               as category_name,
       c.unique_code        as category_unique_code
from entire_instances ei
         join part_models pm on pm.unique_code = ei.model_unique_code
         join entire_models em on em.unique_code = pm.entire_model_unique_code
         join categories c on c.unique_code = ei.category_unique_code
where ei.maintain_station_name = ?
  and ei.category_unique_code = ?
  and em.is_sub_model is false
group by ei.model_name, ei.model_unique_code, em.name, em.unique_code, c.name, c.unique_code", [$station->name, $category_unique_code]);
                        } else {
                            # ???????????????
                            $station_models = DB::select("
select count(ei.model_name) as aggregate,
       ei.model_name        as sub_model_name,
       ei.model_unique_code as sub_model_unique_code,
       em.name              as entire_model_name,
       em.unique_code       as entire_model_unique_code,
       c.name               as category_name,
       c.unique_code        as category_unique_code
from entire_instances ei
         join entire_models sm on sm.unique_code = ei.model_unique_code
         join entire_models em on em.unique_code = sm.parent_unique_code
         join categories c on c.unique_code = ei.category_unique_code
where ei.maintain_station_name = ?
  and ei.category_unique_code = ?
  and sm.is_sub_model is true
  and em.is_sub_model is false
group by ei.model_name, ei.model_unique_code, em.name, em.unique_code, c.name, c.unique_code", [$station->name, $category_unique_code]);
                        }
                    } else {
                        # ????????????
                        $station_models = DB::select("
select count(ei.model_name) as aggregate,
       ei.model_name        as sub_model_name,
       ei.model_unique_code as sub_model_unique_code,
       em.name              as entire_model_name,
       em.unique_code       as entire_model_unique_code,
       c.name               as category_name,
       c.unique_code        as category_unique_code
from entire_instances ei
         join entire_models em on em.unique_code = ei.model_unique_code
         join categories c on c.unique_code = ei.category_unique_code
where ei.maintain_station_name = ?
  and ei.category_unique_code not in ('S03', 'Q01')
group by ei.model_name, ei.model_unique_code, em.name, em.unique_code, c.name, c.unique_code;", [$station->name]);
                    }
                    if (!$station_models) return response()->json(['message' => '???????????????????????????'], 404);

                    $new_exchange_model_order = new RepairBaseExchangeModelOrder();
                    $new_exchange_model_order->fill([
                        'created_at' => Carbon::createFromFormat('Y-m', $request->get('created_at_create'))->format('Y-m-01'),
                        'serial_number' => $new_sn,
                        'scene_workshop_code' => $request->get('scene_workshop_code_create'),
                        'station_code' => $request->get('station_code_create'),
                        'work_area_id' => $this->__work_areas[session('account.work_area')]
                    ]);
                    $new_exchange_model_order->saveOrFail();

                    $insert_exchange_model_order_models = [];
                    $now = date('Y-m-d H:i:s');
                    foreach ($station_models as $station_model) {
                        $tmp = [
                            'created_at' => $now,
                            'updated_at' => $now,
                            'entire_model_name' => $station_model->entire_model_name,
                            'entire_model_unique_code' => $station_model->entire_model_unique_code,
                            'category_name' => $station_model->category_name,
                            'category_unique_code' => $station_model->category_unique_code,
                            'work_area_id' => $this->__work_areas[session('account.work_area')],
                            'number' => $station_model->aggregate,
                            'exchange_model_order_sn' => $new_sn,
                        ];
                        if ($this->__work_areas[session('account.work_area')] == 1) {
                            # ???????????????????????????
                            $tmp['model_name'] = $station_model->part_model_name;
                            $tmp['model_unique_code'] = $station_model->part_model_unique_code;
                        } else {
                            # ????????????????????????
                            $tmp['model_name'] = $station_model->sub_model_name;
                            $tmp['model_unique_code'] = $station_model->sub_model_unique_code;
                        }
                        $insert_exchange_model_order_models[] = $tmp;
                    }
                    DB::table('repair_base_exchange_model_order_models')->insert($insert_exchange_model_order_models);

                    return response()->json(['message' => '????????????', 'new_serial_number' => $new_exchange_model_order->serial_number]);
                });
            } catch (\Exception $e) {
                return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
            }
        };

        $store_out = function () use ($request) {
            try {
                $in_order = RepairBaseExchangeModelOrder::with([])
                    ->where('serial_number', request('sn'))
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                $repeat = RepairBaseExchangeModelOrder::with([])
                    ->where('direction', 'OUT')
                    ->where('in_sn', $request->get('sn'))
                    ->first();
                if ($repeat) {
                    $out_sn = $repeat->serial_number;
                } else {
                    $out_order = new RepairBaseExchangeModelOrder();
                    $out_order->fill([
                        'serial_number' => $out_sn = CodeFacade::makeSerialNumber('EXCHANGE_MODEL_OUT'),
                        'scene_workshop_code' => $in_order['scene_workshop_code'],
                        'station_code' => $in_order['station_code'],
                        'direction' => 'OUT',
                        'work_area_id' => $in_order['work_area_id'],
                        'in_sn' => $request->get('sn'),
                    ])->saveOrFail();
                }

                # ????????????????????????????????????
                DB::table('repair_base_exchange_model_order_entire_instances')
                    ->where('in_sn', request('sn'))
                    ->update(['out_sn' => $out_sn]);

                return response()->json(['message' => '????????????', 'return_url' => "/repairBase/exchangeModelOrder/{$out_sn}?direction=OUT"]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message' => '?????????????????????????????????????????????????????????????????????'], 404);
            } catch (\Exception $e) {
                return response()->json(['message' => '????????????'], 500);
            } catch (\Throwable $th) {
                return response()->json(['message' => '????????????'], 403);
            }
        };

        switch (request('direction')) {
            default:
            case'IN':
                return $store_in();
                break;
            case 'OUT':
                return $store_out();
                break;
        }
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
            $show_in = function () use ($serial_number) {
                $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $scan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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

                $exchange_model_order = RepairBaseExchangeModelOrder::with([
                    'SceneWorkshop',
                    'Station',
                ])
                    ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();

                $in_entire_instances = RepairBaseExchangeModelOrderEntireInstance::with(['OldEntireInstance'])
                    ->where('in_sn', $exchange_model_order->serial_number)
                    ->where('in_warehouse_sn', '')
                    ->paginate();

                return view('RepairBase.ExchangeModelOrder.showIn', [
                    'exchange_model_order' => $exchange_model_order,
                    'in_entire_instances' => $in_entire_instances,
                    'plan_count' => $plan_count,
                    'scan_count' => $scan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'scan_sum' => $scan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            $show_out = function () use ($serial_number) {
                $plan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $serial_number]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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

                $exchange_model_order = RepairBaseExchangeModelOrder::with([
                    'SceneWorkshop',
                    'Station',
                    'OutEntireInstances',
                    'OutEntireInstances.OldEntireInstance',
                ])
                    ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();

                return view('RepairBase.ExchangeModelOrder.showOut', [
                    'exchange_model_order' => $exchange_model_order,
                    'plan_count' => $plan_count,
                    'warehouse_count' => $warehouse_count,
                    'plan_sum' => $plan_sum,
                    'warehouse_sum' => $warehouse_sum,
                ]);
            };

            switch (request('direction')) {
                default:
                case 'IN':
                    return $show_in();
                    break;
                case 'OUT':
                    return $show_out();
                    break;
            }
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????????????????????????????????????????????????????');
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
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
     * ????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postEntireInstances(Request $request)
    {
        try {
            $exchange_model_order = RepairBaseExchangeModelOrder::with([
                'Station',
                'Models' => function ($Models) {
                    return $Models->where('picked', true);
                }
            ])
                ->where('serial_number', $request->get('exchangeModelOrderSn'))
                ->whereIn('work_area_id', [$this->__work_areas[session('account.work_area')], 0])
                ->firstOrFail();

            $entire_instances = EntireInstance::with([])
                ->select([
                    'identity_code',
                    'maintain_location_code',
                    'crossroad_number',
                    'source',
                    'source_traction',
                    'source_crossroad_number',
                    'traction',
                    'open_direction',
                    'said_rod',
                ])
                ->where('maintain_station_name', $exchange_model_order->Station->name)
                ->whereIn('status', ['INSTALLED', 'INSTALLING'])
                ->whereIn('model_unique_code', $exchange_model_order->Models->pluck('model_unique_code')->unique()->all())
                ->get();

            $insert = [];
            $now = date('Y-m-d H:i:s');
            foreach ($entire_instances as $entire_instance) {
                $insert[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'old_entire_instance_identity_code' => $entire_instance->identity_code,
                    'maintain_location_code' => $entire_instance->maintain_location_code,
                    'crossroad_number' => $entire_instance->crossroad_number,
                    'in_sn' => $exchange_model_order->serial_number,
                    'source' => '',
                    'source_traction' => $entire_instance->source,
                    'source_crossroad_number' => $entire_instance->source_traction,
                    'traction' => $entire_instance->source_crossroad_number,
                    'open_direction' => $entire_instance->traction,
                    'said_rod' => $entire_instance->open_direction,
                ];
            }
            DB::table('repair_base_exchange_model_order_entire_instances')->insert($insert);

            return response()->json(['message' => '????????????', 'sn' => $exchange_model_order->serial_number]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '?????????????????????????????????????????????????????????????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postModels(Request $request)
    {
        try {
            $model = RepairBaseExchangeModelOrderModel::with([])
                ->where('id', $request->get('id'))
                ->firstOrFail();
            $model->fill(['picked' => true])->saveOrFail();

            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '????????????????????????????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteModels(Request $request)
    {
        try {
            $model = RepairBaseExchangeModelOrderModel::with([])
                ->where('id', $request->get('id'))
                ->firstOrFail();
            $model->fill(['picked' => false])->saveOrFail();

            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '????????????????????????????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 500);
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
            $scan_in = function () use ($request) {
                # ??????????????????
                $entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->where('in_warehouse_sn', '')
                    ->firstOrFail();
                $entire_instance->fill(['in_scan' => true])->saveOrFail();

                return response()->json(['message' => '????????????']);
            };

            $scan_out = function () use ($request) {
                # ????????????????????????
                $entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->where('new_entire_instance_identity_code', $request->get('identityCode'))
                    ->where('out_warehouse_sn', '')
                    ->firstOrFail();

                $entire_instance->fill(['out_scan' => true])->saveOrFail();

                # ???????????????????????????
                $entire_instances = RepairBaseExchangeModelOrderEntireInstance::with([
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

            switch (request('direction')) {
                default:
                case 'IN':
                    return $scan_in();
                    break;
                case 'OUT':
                    return $scan_out();
                    break;
            }

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
            $delete_in = function () use ($request) {
                $entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([])
                    ->where('in_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->firstOrFail();
                $entire_instance->fill(['in_scan' => false])->saveOrFail();

                $entire_instances = RepairBaseExchangeModelOrderEntireInstance::with([
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
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $request->get('breakdownOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $scan_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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

            $delete_out = function () use ($request) {
                $entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([])
                    ->where('out_sn', $request->get('breakdownOrderSn'))
                    ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                    ->firstOrFail();
                $entire_instance->fill(['out_scan' => false])->saveOrFail();

                $entire_instances = RepairBaseExchangeModelOrderEntireInstance::with([
                    'OldEntireInstance' => function ($OldEntireInstance) {
                        return $OldEntireInstance
                            ->select(['crossroad_number', 'identity_code', 'maintain_location_code', 'model_name', 'serial_number']);
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

            switch (request('direction')) {
                default:
                case 'IN':
                    return $delete_in();
                    break;
                case 'OUT':
                    return $delete_out();
                    break;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
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
            $print_in = function () use ($sn) {
                $entire_instances = RepairBaseExchangeModelOrderEntireInstance::with([
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

                return view('RepairBase.ExchangeModelOrder.printLabelIn', [
                    'entire_instances' => $entire_instances,
                    'in_sn' => $sn,
                ]);
            };

            $print_out = function () use ($sn) {
                $entire_instances = RepairBaseExchangeModelOrderEntireInstance::with([
                    'OldEntireInstance',
                    'NewEntireInstance',
                ])
                    ->where('out_sn', $sn)
                    ->get();

                $plan_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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

                $old_count = DB::table('repair_base_exchange_model_order_entire_instances')->where('out_sn', $sn)->count();
                $new_count = DB::table('repair_base_exchange_model_order_entire_instances')->where('out_sn', $sn)->where('new_entire_instance_identity_code', '<>', '')->count();
                $is_all_bound = (($new_count === $old_count) && ($old_count > 0));  # ????????????????????????

                return view('RepairBase.ExchangeModelOrder.printLabelOut', [
                    'entire_instances' => $entire_instances,
                    'usable_entire_instances' => $usable_entire_instances,
                    'out_sn' => $sn,
                    'is_all_bound' => $is_all_bound,
                    'plan_sum' => $plan_sum,
                    'usable_entire_instance_sum' => $usable_entire_instance_sum,
                ]);
            };

            switch (request('direction')) {
                default:
                case 'IN':
                    return $print_in();
                case 'OUT':
                    return $print_out();
            }
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
        $must_warehouse_location = false;  # ?????????????????????

        $out_order = DB::table('repair_base_exchange_model_orders')
            ->where('serial_number', $out_sn)
            ->first(['in_sn']);
        if (!$out_order) throw new \Exception('??????????????????', 404);
        if (!$out_order->in_sn) throw new \Exception('????????????????????????', 404);

        # ????????????????????????
        return DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.model_name', 'ei.location_unique_code'])
            ->where('status', 'FIXED')
            ->when($must_warehouse_location, function ($query) {
                return $query
                    ->where('location_unique_code', '<>', null)
                    ->where('location_unique_code', '<>', '');
            })
            ->whereNotIn('identity_code', DB::table('entire_instance_locks')
                ->where('lock_name', 'EXCHANGE_MODEL')
                ->pluck('entire_instance_identity_code')
                ->toArray())
            ->whereIn('model_name', DB::table('repair_base_exchange_model_order_entire_instances as oei')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('oei.in_sn', $out_order->in_sn)
                ->groupBy('ei.model_name')
                ->pluck('ei.model_name')
                ->toArray())
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
                $exchange_model_order = RepairBaseExchangeModelOrder::with([
                    'SceneWorkshop',
                    'Station',
                    'InEntireInstances',
                ])
                    ->where('direction', 'IN')
                    ->where('serial_number', $sn)
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                $plan_sum = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                $warehouse_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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
                $exchange_model_order->fill(['status' => 'DONE'])->saveOrFail();

                return response()->json(['message' => '??????????????????']);
            };

            $put_out = function () use ($request, $sn) {
                $exchange_model_order = RepairBaseExchangeModelOrder::with(['OutEntireInstances'])
                    ->where('direction', 'OUT')
                    ->where('serial_number', $sn)
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                $plan_sum = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.out_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $sn]))
                    ->pluck('aggregate', 'model_name')
                    ->sum();

                $warehouse_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.out_sn
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
                $exchange_model_order->fill(['status' => 'DONE'])->saveOrFail();

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
     * @return \Illuminate\Http\JsonResponse|void
     */
    final public function postWarehouse(Request $request)
    {
        try {
            $now = date('Y-m-d H:i:s');

            $warehouse_in = function () use ($request, $now) {
                $exchange_model_order = RepairBaseExchangeModelOrder::with([
                    'SceneWorkshop',
                    'Station',
                    'InEntireInstances' => function ($InEntireInstances) {
                        $InEntireInstances->where('in_scan', true);
                    },
                ])
                    ->where('direction', 'IN')
                    ->where('serial_number', $request->get('exchangeModelOrderSn'))
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                if ($exchange_model_order->InEntireInstances->isEmpty())
                    return response()->json(['message' => '?????????????????????'], 404);

                # ???????????????
                $new_warehouse_report = new WarehouseReport();
                $new_warehouse_report->fill([
                    'processor_id' => session('account.id'),
                    'processed_at' => $now,
                    'connection_name' => '',
                    'connection_phone' => '',
                    'type' => 'EXCHANGE_MODEL',
                    'direction' => 'IN',
                    'serial_number' => $new_warehouse_sn = CodeFacade::makeSerialNumber('IN'),
                    'scene_workshop_name' => $exchange_model_order->SceneWorkshop->name,
                    'station_name' => $exchange_model_order->Station->name,
                    'work_area_id' => $exchange_model_order->work_area_id,
                ]);
                $new_warehouse_report->saveOrFail();

                $logs = [];
                $warehouse_entire_instances = [];
                foreach ($exchange_model_order->InEntireInstances as $entire_instance) {
                    $logs[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'name' => '?????????????????????',
                        'description' => '',
                        'entire_instance_identity_code' => $entire_instance->old_entire_instance_identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$new_warehouse_sn}?show_type=D&direction=IN",
                    ];
                    $warehouse_entire_instances[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'warehouse_report_serial_number' => $new_warehouse_sn,
                        'entire_instance_identity_code' => $entire_instance->old_entire_instance_identity_code,
                    ];
                }

                # ?????????????????????
                if ($warehouse_entire_instances) DB::table('warehouse_report_entire_instances')->insert($warehouse_entire_instances);

                if ($logs) {
                    # ????????????
                    EntireInstanceLogFacade::makeBatchUseArray($logs);
                    # ??????????????????
                    DB::table('entire_instances')
                        ->whereIn('identity_code', array_pluck($logs, 'entire_instance_identity_code'))
                        ->update(['status' => 'FIXING', 'maintain_location_code' => null, 'crossroad_type' => null]);
                    # ??????????????????
                    DB::table('part_instances')
                        ->whereIn('entire_instance_identity_code', array_pluck($logs, 'entire_instance_identity_code'))
                        ->update(['status' => 'FIXING']);
                    # ???????????????
                    $in_entire_instances = $exchange_model_order->InEntireInstances->pluck('old_entire_instance_identity_code')->all();
                    DB::table('repair_base_exchange_model_order_entire_instances')
                        ->whereIn('old_entire_instance_identity_code', $in_entire_instances)
                        ->update(['in_scan' => false, 'in_warehouse_sn' => $new_warehouse_sn]);
                }

                $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
group by `ei`.`model_name`',
                    [$this->__work_areas[session('account.work_area')], $request->get('exchangeModelOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.in_sn = ?
  and `oei`.in_warehouse_sn <> ''
group by `ei`.`model_name`",
                    [$this->__work_areas[session('account.work_area')], $request->get('exchangeModelOrderSn')]))
                    ->pluck('aggregate', 'model_name')
                    ->toArray();

                $plan_sum = array_sum($plan_count);
                $warehouse_sum = array_sum($warehouse_count);

                # ??????????????????????????????????????????????????????????????????0????????????????????????????????????????????????????????????????????????????????????
                $order = RepairBaseExchangeModelOrder::with([])
                    ->where('serial_number', $sn)
                    ->where('direction', 'IN')
                    ->firstOrFail();
                $order->status = (($plan_sum == $warehouse_sum) && (($plan_sum + $warehouse_sum) > 0)) ? 'DONE' : 'UNSATISFIED';
                $order->saveOrFail();

                return response()->json(['message' => '????????????']);
            };

            $warehouse_out = function () use ($request, $now) {
                $exchange_model_order = RepairBaseExchangeModelOrder::with([
                    'SceneWorkshop',
                    'Station',
                    'OutEntireInstances' => function ($OutEntireInstances) {
                        $OutEntireInstances->where('out_scan', true);
                    },
                ])
                    ->where('direction', 'OUT')
                    ->where('serial_number', $request->get('exchangeModelOrderSn'))
                    ->where('work_area_id', $this->__work_areas[session('account.work_area')])
                    ->firstOrFail();

                if ($exchange_model_order->OutEntireInstances->isEmpty())
                    return response()->json(['message' => '?????????????????????'], 404);


                # ????????????
                EntireInstanceLock::freeLocks(
                    $exchange_model_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->toArray(),
                    ['EXCHANGE_MODEL'],
                    function () use ($now, $request, $exchange_model_order) {
                        # ???????????????
                        $new_warehouse_report = new WarehouseReport();
                        $new_warehouse_report->fill([
                            'processor_id' => session('account.id'),
                            'processed_at' => $now,
                            'connection_name' => $request->get('connection_name'),
                            'connection_phone' => $request->get('connection_phone'),
                            'type' => 'EXCHANGE_MODEL',
                            'direction' => 'OUT',
                            'serial_number' => $new_warehouse_sn = CodeFacade::makeSerialNumber('OUT'),
                            'scene_workshop_name' => $exchange_model_order->SceneWorkshop->name,
                            'station_name' => $exchange_model_order->Station->name,
                            'work_area_id' => $exchange_model_order->work_area_id,
                        ]);
                        $new_warehouse_report->saveOrFail();

                        $logs = [];
                        $warehouse_entire_instances = [];
                        foreach ($exchange_model_order->OutEntireInstances as $entire_instance) {
                            # ??????????????????
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
                                ]);

                            # ????????????
                            $logs[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'name' => '?????????????????????',
                                'description' => '',
                                'entire_instance_identity_code' => $entire_instance->new_entire_instance_identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$new_warehouse_sn}?show_type=D&direction=OUT",
                            ];

                            # ?????????
                            $warehouse_entire_instances[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'warehouse_report_serial_number' => $new_warehouse_sn,
                                'entire_instance_identity_code' => $entire_instance->new_entire_instance_identity_code,
                            ];
                        }

                        # ?????????????????????
                        if ($warehouse_entire_instances)
                            DB::table('warehouse_report_entire_instances')->insert($warehouse_entire_instances);

                        if ($logs) {
                            # ????????????
                            EntireInstanceLogFacade::makeBatchUseArray($logs);
                            # ??????????????????
                            DB::table('entire_instances')
                                ->whereIn('identity_code', array_pluck($logs, 'entire_instance_identity_code'))
                                ->update(['status' => 'TRANSFER_OUT']);
                            # ??????????????????
                            DB::table('part_instances')
                                ->whereIn('entire_instance_identity_code', array_pluck($logs, 'entire_instance_identity_code'))
                                ->update(['status' => 'FIXED']);
                            # ???????????????
                            $out_entire_instances = $exchange_model_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
                            DB::table('repair_base_exchange_model_order_entire_instances')
                                ->whereIn('new_entire_instance_identity_code', $out_entire_instances)
                                ->update(['out_scan' => false, 'out_warehouse_sn' => $new_warehouse_sn]);
                        }

                        $plan_count = collect(DB::select('
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
group by `ei`.`model_name`',
                            [$this->__work_areas[session('account.work_area')],$request->get('exchangeModelOrderSn')]))
                            ->pluck('aggregate', 'model_name')
                            ->toArray();

                        $warehouse_count = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_exchange_model_order_entire_instances` as `oei`
inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `o`.`work_area_id` = ?
  and `oei`.out_sn = ?
  and `oei`.out_warehouse_sn <> ''
group by `ei`.`model_name`",
                            [$this->__work_areas[session('account.work_area')], $request->get('exchangeModelOrderSn')]))
                            ->pluck('aggregate', 'model_name')
                            ->toArray();

                        $plan_sum = array_sum($plan_count);
                        $warehouse_sum = array_sum($warehouse_count);

                        # ??????????????????????????????????????????????????????????????????0????????????????????????????????????????????????????????????????????????????????????
                        $order = RepairBaseExchangeModelOrder::with([])
                            ->where('serial_number', $request->get('exchangeModelOrderSn'))
                            ->where('direction', 'OUT')
                            ->firstOrFail();
                        $order->status = (($plan_sum == $warehouse_sum) && (($plan_sum + $warehouse_sum) > 0)) ? 'DONE' : 'UNSATISFIED';
                        $order->saveOrFail();
                    }
                );

                return response()->json(['message' => '????????????']);
            };

            switch (request('direction')) {
                default:
                case 'IN':
                    return $warehouse_in();
                    break;
                case 'OUT':
                    return $warehouse_out();
                    break;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????????????????'], 404);
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
            $old_entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([
                'OldEntireInstance'
            ])
                ->where('out_sn', $request->get('outSn'))
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->firstOrFail();

            $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'))
                ->get($old_entire_instance->OldEntireInstance->model_name);
            if (is_null($usable_entire_instances)) return response()->json(['message' => '????????????????????????'], 404);

            # ????????????
            EntireInstanceLock::setOnlyLock(
                $usable_entire_instances->first()->identity_code,
                ['EXCHANGE_MODEL'],
                function () use ($old_entire_instance, $usable_entire_instances) {
                    $old_entire_instance->fill(['new_entire_instance_identity_code' => $usable_entire_instances->first()->identity_code])->saveOrFail();
                }
            );

            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
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

            $out_order = DB::table('repair_base_exchange_model_orders')->where('serial_number', $request->get('outSn'))->first(['in_sn']);
            if (!$out_order) return response()->json(['?????????????????????????????????????????????'], 404);

            $old_entire_instances = DB::table('repair_base_exchange_model_order_entire_instances as oei')
                ->select(['ei.identity_code', 'ei.model_name'])
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('in_sn', $out_order->in_sn)
                ->where('new_entire_instance_identity_code', '')
                ->get()
                ->groupBy('model_name')
                ->all();

            $ret = DB::transaction(function () use ($old_entire_instances, $usable_entire_instances, $out_order) {
                $new_entire_instance_identity_codes = [];
                foreach ($old_entire_instances as $model_name => $entire_instances) {
                    foreach ($entire_instances as $entire_instance) {
                        if ($usable_entire_instances->get($entire_instance->model_name)) {
                            if (!$usable_entire_instance = @$usable_entire_instances->get($entire_instance->model_name)->shift()->identity_code) continue;
                            DB::table('repair_base_exchange_model_order_entire_instances')
                                ->where('in_sn', $out_order->in_sn)
                                ->where('old_entire_instance_identity_code', $entire_instance->identity_code)
                                ->update(['new_entire_instance_identity_code' => $usable_entire_instance]);

                            $new_entire_instance_identity_codes[] = $usable_entire_instance;
                        }
                    }
                }
                # ????????????
                return EntireInstanceLock::setOnlyLocks($new_entire_instance_identity_codes, ['EXCHANGE_MODEL']);
            });

            return response()->json(['message' => '????????????????????????', 'details' => $ret]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 500);
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
            $old_entire_instance = RepairBaseExchangeModelOrderEntireInstance::with([])
                ->where('out_sn', $request->get('outSn'))
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->firstOrFail();

            # ?????????????????????????????????????????????
            if ($old_entire_instance->new_entire_instance_identity_code) {
                EntireInstanceLock::freeLock(
                    $old_entire_instance->new_entire_instance_identity_code,
                    ['EXCHANGE_MODEL'],
                    function () use ($request, $old_entire_instance) {
                        # ????????????
                        EntireInstanceLock::setOnlyLock(
                            $request->get('newIdentityCode'),
                            ['EXCHANGE_MODEL'],
                            function () use ($request, $old_entire_instance) {
                                $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
                            }
                        );
                    }
                );
            } else {
                # ????????????
                EntireInstanceLock::setOnlyLock(
                    $request->get('newIdentityCode'),
                    ['EXCHANGE_MODEL'],
                    function () use ($request, $old_entire_instance) {
                        $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
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
            $ei = RepairBaseExchangeModelOrderEntireInstance::with([])
                ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                ->where('out_sn', $request->get('outSn'))
                ->firstOrFail();

            # ????????????
            EntireInstanceLock::freeLock(
                $ei->new_entire_instance_identity_code,
                ['EXCHANGE_MODEL'],
                function () use ($ei) {
                    $ei->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                }
            );
            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
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
            $out_order = RepairBaseExchangeModelOrder::with([
                'OutEntireInstances',
            ])
                ->where('serial_number', $request->get('outSn'))
                ->first();
            if (!$out_order) return response()->json(['?????????????????????????????????????????????'], 404);

            # ????????????
            $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
            $ret = EntireInstanceLock::freeLocks(
                $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all(),
                ['EXCHANGE_MODEL'],
                function () use ($out_order) {
                    DB::table('repair_base_exchange_model_order_entire_instances')
                        ->where('in_sn', $out_order->in_sn)
                        ->update(['new_entire_instance_identity_code' => '']);
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
     * ????????????????????????????????????
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
from `repair_base_exchange_model_order_entire_instances` as `oei`
         inner join repair_base_exchange_model_orders o on `o`.`serial_number` = `oei`.in_sn
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

        # ?????????????????????
        $accounts = DB::table('accounts')
            ->where('deleted_at', null)
            ->where('work_area', $work_area_id)
            ->where('supervision', false)
            ->pluck('nickname', 'id')
            ->toArray();

        # ??????????????????????????????
        $year = $date->year;
        $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);
        $file_path = storage_path("app/????????????/{$year}/{$year}-{$month}/{$work_area_id}-????????????.json");
        $account_statistics = @file_get_contents($file_path) ? json_decode(file_get_contents($file_path), true) : [];

        return view('RepairBase.ExchangeModelOrder.mission', [
            'plan_count' => $plan_count2,
            'accounts' => $accounts,
            'account_statistics' => $account_statistics,
        ]);
    }

    /**
     * ??????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postMission(Request $request)
    {
        try {
            $date = Carbon::createFromFormat('Y-m', $request->get('date'));
            $year = $date->year;
            $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);
            $root_dir = storage_path("app/????????????");
            if (!is_dir($root_dir)) mkdir($root_dir, 0777);  # ????????????????????????????????????
            $year_path = "{$root_dir}/{$year}";
            $month_path = "{$year_path}/{$year}-{$month}";
            if (!is_dir($year_path)) mkdir($year_path, 0777);  # ????????????????????????????????????
            for ($i = 1; $i <= 12; $i++) {
                $m = str_pad($i, 2, '0', STR_PAD_LEFT);
                $path = "{$year_path}/{$year}-{$m}";
                if (!is_dir($path)) mkdir($path, 0777);
                for ($j = 1; $j <= 3; $j++) {
                    $file_path = "{$path}/{$j}-????????????.json";
                    if (!is_file($file_path)) file_put_contents($file_path, '[]');  # ????????????????????????????????????
                }
            }

            $statistics = [];
            $statistics_account = [];
            $statistics_model = [];
            foreach ($request->post() as $key => $number) {
                list($model_unique_code, $account_id, $model_name) = explode(':', $key);
                # ????????????
                if (!array_key_exists($account_id, $statistics)) $statistics[$account_id] = [];
                $statistics[$account_id][$model_name] = [
                    'model_unique_code' => $model_unique_code,
                    'model_name' => $model_name,
                    'number' => $number,
                    'account_id' => $account_id,
                ];

                # ????????????????????????
                if (!array_key_exists($account_id, $statistics_account)) $statistics_account[$account_id] = 0;
                $statistics_account[$account_id] += $number;

                # ????????????????????????
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
     * ??????????????????excel
     * @return \Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function _makeMissionExcel()
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
            $filename = "????????????{$work_area}???????????????({$date})";

            ExcelWriteHelper::download(
                function ($excel) use ($cell_key) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();

                    list($year, $month) = explode('-', request('date'));
                    $month = str_pad($month, 2, '0', 0);
                    $fs = FileSystem::init(__FILE__);
                    $current_work_area = $this->__work_areas[session('account.work_area')];

                    # ????????????
                    $accounts = DB::table('accounts')
                        ->where('deleted_at', null)
                        ->where('work_area', $current_work_area)
                        ->where('supervision', false)
                        ->pluck('nickname', 'id');
                    # ??????????????????
                    $account_missions = $fs->setPath(storage_path("app/????????????/{$year}/{$year}-{$month}/{$current_work_area}-????????????.json"))->fromJson();
                    $model_names = array_keys($account_missions['statistics_model']);

                    # ????????????
                    $col = 2;
                    $current_sheet->setCellValue("A1", "??????/??????");
                    $current_sheet->setCellValue("B1", "??????");
                    $current_sheet->getColumnDimension('A')->setWidth(20);
                    foreach ($accounts as $account_nickname) {
                        $current_sheet->setCellValue("{$cell_key[$col]}1", $account_nickname);
                        $current_sheet->getColumnDimension("{$cell_key[$col]}")->setWidth(15);
                        $col++;
                    }

                    # ????????????
                    $row = 2;
                    foreach ($model_names as $model_name) {
                        # ??????
                        $current_sheet->setCellValue("A{$row}", $model_name);  # ????????????
                        $current_sheet->setCellValue("B{$row}", $account_missions['statistics_model'][$model_name]);  # ????????????

                        # ????????????
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
}
