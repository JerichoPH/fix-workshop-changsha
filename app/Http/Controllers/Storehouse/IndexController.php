<?php

namespace App\Http\Controllers\Storehouse;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\QueryConditionFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Model\PartInstance;
use App\Model\TmpMaterial;
use App\Model\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\HttpResponseHelper;

class IndexController extends Controller
{
    private $_current_time = null;

    public function __construct()
    {
        $this->_current_time = date('Y-m-d H:i:s');
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function show($id)
    {
        try {
            $warehouses = Warehouse::with(['WithAccount', 'WithWarehouseMaterials.WithEntireInstance', 'WithWarehouseMaterials.WithPartInstance'])
                ->where('id', $id)
                ->firstOrFail();
            return view('Storehouse.Index.show', [
                'warehouses' => $warehouses
            ]);
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 500);
        } catch (\Exception $exception) {
            return Response::make("异常错误", 500);
        }
    }

    /**
     * 入库单查询
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function inWithOrder(Request $request)
    {
        $originAt = Carbon::now()->startOfMonth()->toDateString();
        $finishAt = Carbon::now()->endOfMonth()->toDateString();
        $updated_at = $request->get('updated_at');
        $use_made_at = $request->get('use_made_at');
        $warehouses = Warehouse::with(['WithAccount'])
            ->when(
                session('account.read_scope') === 1,
                function ($query) {
                    return $query->where('account_id', session('account.id'));
                }
            )
            ->where('state', 'END')
            ->where('direction', 'IN_WAREHOUSE')
            ->when(
                $use_made_at == 1,
                function ($query) use ($updated_at) {
                    if (!empty($updated_at)) {
                        $tmp_updated_at = explode('~', $updated_at);
                        $tmp_left = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[0])->startOfDay()->format('Y-m-d H:i:s');
                        $tmp_right = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[1])->endOfDay()->format('Y-m-d H:i:s');
                        return $query->whereBetween('updated_at', [$tmp_left, $tmp_right]);
                    }
                }
            )
            ->orderBy('updated_at', 'desc')
            ->paginate();
        $warehouseUniqueCodes = array_column($warehouses->toArray()['data'], 'unique_code');
        $statistics = [];
        if (!empty($warehouseUniqueCodes)) {
            $entireCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, ei.category_name')
                ->join(DB::raw('entire_instances ei'), 'wm.material_unique_code', '=', 'ei.identity_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('ei.deleted_at', null)
                ->where('wm.material_type', 'ENTIRE')
                ->groupBy(['wm.warehouse_unique_code', 'ei.category_name'])
                ->get()->toArray();

            foreach ($entireCounts as $entireCount) {
                $statistics[$entireCount->warehouse_unique_code][] = [
                    'category_name' => $entireCount->category_name,
                    'count' => $entireCount->count
                ];
            }

            $partCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, pc.name as part_category_name, c.name as category_name')
                ->join(DB::raw('part_instances pi'), 'wm.material_unique_code', '=', 'pi.identity_code')
                ->join(DB::raw('part_categories pc'), 'pi.part_category_id', '=', 'pc.id')
                ->join(DB::raw('categories c'), 'pi.category_unique_code', '=', 'c.unique_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('wm.material_type', 'PART')
                ->where('pi.deleted_at', null)
                ->where('pc.deleted_at', null)
                ->groupBy(['wm.warehouse_unique_code', 'pc.name', 'c.name'])
                ->get()->toArray();
            foreach ($partCounts as $partCount) {
                $statistics[$partCount->warehouse_unique_code][] = [
                    'category_name' => $partCount->category_name . ' ' . $partCount->part_category_name,
                    'count' => $partCount->count
                ];
            }
        }

        return view('Storehouse.Index.In.order', [
            'warehouses' => $warehouses,
            'originAt' => empty($updated_at) ? $originAt : explode('~', $updated_at)[0],
            'finishAt' => empty($updated_at) ? $finishAt : explode('~', $updated_at)[1],
            'statistics' => $statistics,
        ]);
    }

    /**
     * 入库
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function inWithIndex()
    {
        try {
            $accountId = session('account.id', 0);
            $tmpMaterials = TmpMaterial::with(['WithEntireInstance', 'WithPartInstance'])->where('state', 'IN_WAREHOUSE')->where('account_id', $accountId)->get();

            return view('Storehouse.Index.In.scan', [
                'tmpMaterials' => $tmpMaterials
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 扫码入库-新建
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function inWithStore(Request $request)
    {
        try {
            $uniqueCode = $request->get('unique_code', '');
            $accountId = session('account.id', '');
            if (empty($accountId)) return HttpResponseHelper::errorValidate('用户不存在，请重新登录');
            if (empty($uniqueCode)) return HttpResponseHelper::errorEmpty('编码不能为空');
            if (substr($uniqueCode, 0, strlen(env('ORGANIZATION_LOCATION_CODE'))) == env('ORGANIZATION_LOCATION_CODE')) {
                $position = DB::table('positions')->where('unique_code', $uniqueCode)->first();
                if (empty($position)) return HttpResponseHelper::errorEmpty('位置不存在');
                $tmpMaterials = DB::table('tmp_materials')->where('location_unique_code', '')->where('state', 'IN_WAREHOUSE')->where('account_id', $accountId)->first();
                if (empty($tmpMaterials)) return HttpResponseHelper::errorEmpty('请先扫描设备');
                DB::table('tmp_materials')->where('location_unique_code', '')->where('state', 'IN_WAREHOUSE')->where('account_id', $accountId)->update(['location_unique_code' => $uniqueCode]);
            } else {
                $materialType = 'ENTIRE';
                $materialTypeName = '整件';
                if (substr($uniqueCode, 0, 4) == '130E' || substr($uniqueCode, 0, 4) == '130F') {
                    # rfid_epc
                    $db = EntireInstance::with([])->where('identity_code', CodeFacade::hexToIdentityCode($uniqueCode));
                } elseif (substr($uniqueCode, 0, 2) == 'E2' && strlen($uniqueCode) == 24) {
                    # rfid_code
                    $db = EntireInstance::with([])->where('rfid_code', $uniqueCode);
                } elseif (strlen($uniqueCode) < 14) {
                    # 部件
                    $materialType = 'PART';
                    $materialTypeName = '部件';
                    $db = PartInstance::with([])->where('identity_code', $uniqueCode);
                } else {
                    $db = EntireInstance::with([])->where('identity_code', $uniqueCode);
                }
                $entireInstance = $db->first();

                if (empty($entireInstance)) return HttpResponseHelper::errorEmpty('设备编码不存在');
                $identity_code = $entireInstance->identity_code;
                EntireInstanceLock::setOnlyLock(
                    $identity_code,
                    ['IN_WAREHOUSE'],
                    "{$materialTypeName}设备：{$identity_code}，在入库中被使用。详情：入库操作人员：" . session('account.account'),
                    function () use ($identity_code, $accountId, $materialType) {
                        DB::table('tmp_materials')->updateOrInsert(
                            ['material_unique_code' => $identity_code, 'account_id' => $accountId, 'state' => 'IN_WAREHOUSE'],
                            ['material_unique_code' => $identity_code, 'account_id' => session('account.id'), 'state' => 'IN_WAREHOUSE', 'material_type' => $materialType]
                        );
                    }
                );
            }

            return HttpResponseHelper::created('绑定位置成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 入库-删除
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function inWithDestroy($id)
    {
        try {
            $tmpMaterial = TmpMaterial::with([])->where('id', $id)->firstOrFail();
            EntireInstanceLock::freeLock(
                $tmpMaterial->material_unique_code,
                ['IN_WAREHOUSE'],
                function () use ($tmpMaterial) {
                    $tmpMaterial->delete();
                }
            );
            return HttpResponseHelper::created('删除成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 入库-确认入库
     * @return \Illuminate\Http\JsonResponse
     */
    final public function inWithConfirm()
    {
        try {
            $accountId = session('account.id', '');
            if (empty($accountId)) return HttpResponseHelper::errorValidate('用户不存在，请重新登录');
            $checkTmp = DB::table('tmp_materials')->where('location_unique_code', '<>', '')->where('state', 'IN_WAREHOUSE')->where('account_id', $accountId)->first();
            if (empty($checkTmp)) return HttpResponseHelper::errorValidate('存在未分配位置的设备');
            $tmpMaterials = TmpMaterial::with(['WithPosition', 'WithEntireInstance'])->where('state', 'IN_WAREHOUSE')->where('account_id', $accountId)->get();
            if ($tmpMaterials->isEmpty()) return HttpResponseHelper::errorEmpty('绑定数据为空');

            $warehouseId = DB::transaction(function () use ($tmpMaterials) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('IN_WAREHOUSE');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'IN_WAREHOUSE',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->save();
                $warehouseId = $warehouse->id;

                foreach ($tmpMaterials as $tmpMaterial) {
                    $areaType = @$tmpMaterial->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->type['value'] ?: 'FIXED';
                    $location = $tmpMaterial->WithPosition ? $tmpMaterial->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $tmpMaterial->WithPosition->WithTier->WithShelf->WithPlatoon->name . $tmpMaterial->WithPosition->WithTier->WithShelf->name . $tmpMaterial->WithPosition->WithTier->name . $tmpMaterial->WithPosition->name : '';
                    EntireInstanceLock::freeLock(
                        $tmpMaterial->material_unique_code,
                        ['IN_WAREHOUSE'],
                        function () use ($tmpMaterial, $areaType, $warehouseUniqueCode, $location, $warehouseId) {
                            switch ($tmpMaterial->material_type['value']) {
                                case 'ENTIRE':
                                    DB::table('entire_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                        'status' => $areaType,
                                        'location_unique_code' => $tmpMaterial->location_unique_code,
                                        'is_bind_location' => 1,
                                        'in_warehouse_time' => $this->_current_time,
                                        'updated_at' => $this->_current_time,
                                        'maintain_station_name' => '',
//                                        'maintain_workshop_name' => env('JWT_ISS'),
                                        'maintain_location_code' => '',
                                        'crossroad_number' => '',
                                        'warehousein_at' => $this->_current_time,
                                        'fix_cycle_value' => ''
                                    ]);
                                    DB::table('warehouse_materials')->insert([
                                        'created_at' => $this->_current_time,
                                        'updated_at' => $this->_current_time,
                                        'material_unique_code' => $tmpMaterial->material_unique_code,
                                        'warehouse_unique_code' => $warehouseUniqueCode,
                                        'material_type' => 'ENTIRE'
                                    ]);
                                    # 日志
                                    $description = '';
                                    if (!empty($tmpMaterial->WithEntireInstance->maintain_station_name) || !empty($tmpMaterial->WithEntireInstance->maintain_location_code) || !empty($tmpMaterial->WithEntireInstance->crossroad_number)) $description .= "上道位置：{$tmpMaterial->WithEntireInstance->maintain_station_name} " . ($tmpMaterial->WithEntireInstance->maintain_location_code ?? '') . ($tmpMaterial->WithEntireInstance->crossroad_number ?? '') . "；";
                                    $description .= "入库位置：{$location}；经办人：" . session('account.nickname') . '；';
                                    EntireInstanceLogFacade::makeOne('入库', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'ENTIRE');
                                    break;
                                case 'PART':
                                    DB::table('part_instances')->where('identity_code', $tmpMaterial->material_unique_code)
                                        ->update([
                                            'status' => $areaType,
                                            'location_unique_code' => $tmpMaterial->location_unique_code,
                                            'is_bind_location' => 1,
                                            'in_warehouse_time' => $this->_current_time,
                                            'updated_at' => $this->_current_time,
                                        ]);
                                    DB::table('warehouse_materials')->insert([
                                        'created_at' => $this->_current_time,
                                        'updated_at' => $this->_current_time,
                                        'material_unique_code' => $tmpMaterial->material_unique_code,
                                        'warehouse_unique_code' => $warehouseUniqueCode,
                                        'material_type' => 'PART'
                                    ]);
                                    # 日志
                                    $description = "入库位置：{$location}；经办人：" . session('account.nickname') . '；';
                                    EntireInstanceLogFacade::makeOne('入库', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'PART');
                                    break;
                                default:
                                    break;
                            }
                            DB::table('tmp_materials')->where('id', $tmpMaterial->id)->delete();
                        }
                    );
                }
                return $warehouseId;
            });

            return HttpResponseHelper::data(['message' => '入库成功', 'return_url' => '/storehouse/index/' . $warehouseId]);
        } catch (ModelNotFoundException $e) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $e) {
            return HttpResponseHelper::error($e->getMessage(), [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]);
        }
    }

    /**
     * 仓库设备
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function material()
    {
        try {
            # 统计所有种类库房内数量
            $root_dir = storage_path("app/basicInfo");
            $statuses = [
                'ALL' => '全部',
                'BUY_IN' => '新购',
                'INSTALLING' => '备品',
                'INSTALLED' => '上道',
                'TRANSFER_OUT' => '出所在途',
                'TRANSFER_IN' => '入所在途',
                'UNINSTALLED' => '下道',
                'FIXING' => '待修',
                'FIXED' => '成品',
                'RETURN_FACTORY' => '送修',
                'FACTORY_RETURN' => '送修入所',
                'SCRAP' => '报废',
            ];

            $query_condition = QueryConditionFacade::init($root_dir)
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB()
                ->setStatus([
                    'ALL' => '全部',
                    'FIXING' => '待修',
                    'FIXED' => '成品',
                ])
                ->setStorehouses()
                ->setAreas()
                ->setPlatoons()
                ->setShelves()
                ->setTiers()
                ->setPositions();

            $query_condition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                '',
                '',
                '',
                strval(request("status_unique_code")),
                strval(request("storehouse_unique_code")),
                strval(request("area_unique_code")),
                strval(request("platoon_unique_code")),
                strval(request("shelf_unique_code")),
                strval(request("tier_unique_code")),
                strval(request("position_unique_code"))
            );

//            dd($query_condition->get());

            switch (request('material_type')) {
                default:
                case 'ENTIRE':
                    # 图表
                    $a = DB::table('entire_instances as ei')
                        ->selectRaw("
                        count(c.unique_code) as aggregate,
                        c.unique_code        as cu,
                        c.name               as cn,
                        ei.status            as eis")
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("ei.deleted_at", null)
                        ->where('pm.deleted_at', null)
                        ->where('pc.deleted_at', null)
                        ->where('pc.is_main', true)
                        ->where('em.deleted_at', null)
                        ->where('em.is_sub_model', false)
                        ->where('c.deleted_at', null)
                        ->where('ei.is_bind_location', 1)
                        ->whereIn("ei.status", ["FIXED", "FIXING"])
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->groupBy(['c.unique_code', 'c.name', 'ei.status'])
                        ->get();

                    $b = DB::table('entire_instances as ei')
                        ->selectRaw("
                        count(c.unique_code) as aggregate,
                        c.unique_code        as cu,
                        c.name               as cn,
                        ei.status            as eis")
                        ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("ei.deleted_at", null)
                        ->where('sm.deleted_at', null)
                        ->where('sm.is_sub_model', true)
                        ->where('em.deleted_at', null)
                        ->where('em.is_sub_model', false)
                        ->where('c.deleted_at', null)
                        ->where('ei.is_bind_location', 1)
                        ->whereIn("ei.status", ["FIXED", "FIXING"])
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->groupBy(['c.unique_code', 'c.name', 'ei.status'])
                        ->get();
                    $statistics = $a->merge($b)->toJson();

                    # 搜索整件
                    $partInstanceSql = DB::table('entire_instances as ei')
                        ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        ei.model_name,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type,
                        '设备' as material_type_name")
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("ei.deleted_at", null)
                        ->where('pm.deleted_at', null)
                        ->where('pc.deleted_at', null)
                        ->where('em.deleted_at', null)
                        ->where('em.is_sub_model', false)
                        ->where('ei.is_bind_location', 1)
                        ->where('c.deleted_at', null)
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("current_status_unique_code") && $query_condition->get("current_status_unique_code") != "ALL") {
                                    return $query->where("ei.status", $query_condition->get("current_status_unique_code"));
                                } else {
                                    return $query->whereIn("ei.status", ["FIXED", "FIXING"]);
                                }
                            },
                            function ($query) {
                                return $query->whereIn("ei.status", ["FIXED", "FIXING"]);
                            }
                        )
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->orderByDesc('ei.updated_at');

                    $entireInstanceSql = DB::table('entire_instances as ei')
                        ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        ei.model_name,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type,
                        '器材' as material_type_name")
                        ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("ei.deleted_at", null)
                        ->where('sm.deleted_at', null)
                        ->where('sm.is_sub_model', true)
                        ->where('em.deleted_at', null)
                        ->where('em.is_sub_model', false)
                        ->where('c.deleted_at', null)
                        ->where('ei.is_bind_location', 1)
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("current_status_unique_code") && $query_condition->get("current_status_unique_code") != "ALL") {
                                    return $query->where("ei.status", $query_condition->get("current_status_unique_code"));
                                } else {
                                    return $query->whereIn("ei.status", ["FIXED", "FIXING"]);
                                }
                            },
                            function ($query) {
                                return $query->whereIn("ei.status", ["FIXED", "FIXING"]);
                            }
                        )
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->orderByDesc('ei.updated_at')
                        ->unionAll($partInstanceSql);

                    $entireInstances = DB::table(DB::raw("({$entireInstanceSql->toSql()}) as a"))->mergeBindings($entireInstanceSql)->paginate();
                    break;
                case 'PART':
                    $statistics = DB::table('part_instances as pi')
                        ->selectRaw("
                        count(c.unique_code) as aggregate,
                        c.unique_code        as cu,
                        c.name               as cn,
                        pi.status            as eis")
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'pi.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("pi.deleted_at", null)
                        ->where('pm.deleted_at', null)
                        ->where('pc.deleted_at', null)
                        ->where('em.deleted_at', null)
                        ->where('em.is_sub_model', false)
                        ->where('c.deleted_at', null)
                        ->whereIn("pi.status", ['FIXED', 'FIXING'])
                        ->where('pi.is_bind_location', 1)
                        ->groupBy(['c.unique_code', 'c.name', 'pi.status'])
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pi.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->get()
                        ->toJson();

                    $entireInstances = DB::table("part_instances as pi")
                        ->selectRaw("
                        pi.identity_code,
                        pi.factory_name,
                        pm.name as model_name,
                        pc.name as material_type_name,
                        pi.status,
                        pi.location_unique_code,
                        position.name as position_name,
                        tier.name          as tier_name,
                        shelf.name         as shelf_name,
                        platoon.name       as platoon_name,
                        area.name          as area_name,
                        storehous.name     as storehous_name,
                        'PART' as material_type
                    ")
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                        ->leftJoin(DB::raw('positions position'), 'pi.location_unique_code', '=', 'position.unique_code')
                        ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                        ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                        ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                        ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                        ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                        ->where("pi.deleted_at", null)
                        ->where('pc.deleted_at', null)
                        ->where('pi.is_bind_location', 1)
                        ->when(
                            $query_condition->get("current_status_unique_code"),
                            function ($query) use ($query_condition) {
                                if ($query_condition->get("current_status_unique_code") && $query_condition->get("current_status_unique_code") != "ALL") {
                                    return $query->where("pi.status", $query_condition->get("current_status_unique_code"));
                                } else {
                                    return $query->whereIn("pi.status", ["FIXED", "FIXING"]);
                                }
                            },
                            function ($query) {
                                return $query->whereIn("pi.status", ["FIXED", "FIXING"]);
                            }
                        )
                        ->when(
                            $query_condition->get("current_category_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_entire_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_sub_model_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                            }
                        )
                        ->when(
                            $query_condition->get("current_storehouse_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_area_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_platoon_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_shelf_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_tier_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                            })
                        ->when(
                            $query_condition->get("current_position_unique_code"),
                            function ($q) use ($query_condition) {
                                return $q->where("pi.location_unique_code", $query_condition->get("current_position_unique_code"));
                            })
                        ->orderByDesc("pi.id")
                        ->paginate();
                    break;
            }
//            dd($query_condition->toJson());
            return view('Storehouse.Index.material', [
                "queryConditions" => $query_condition->toJson(),
                "statuses" => $statuses,
                "entireInstances" => $entireInstances,
                "status" => $statuses,
                'status_as_json' => json_encode(array_flip($statuses)),
                'statisticsAsJson' => $statistics,
            ]);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
    }

    /**
     * 添加临时设备
     * @return \Illuminate\Http\JsonResponse
     */
    final public function tmpWarehouseMaterialStore()
    {
        try {
            $identityCode = request('identityCode', '');
            $materialType = request('materialType', '');
            $state = request('state', '');
            if (empty($identityCode) || empty($materialType)) return HttpResponseHelper::errorEmpty('参数不足');
            $materialTypeName = $materialType == 'ENTIRE' ? '整件' : '部件';
            $states = Warehouse::$DIRECTION;
            $name = $states[$state] ?? '';
            EntireInstanceLock::setOnlyLock(
                $identityCode,
                [$state],
                "{$materialTypeName}设备：{$identityCode}，在{$name}中被使用。详情：{$name}操作人员：" . session('account.account'),
                function () use ($identityCode, $materialType, $state) {
                    DB::table('tmp_materials')->updateOrInsert(
                        ['material_unique_code' => $identityCode, 'account_id' => session('account.id'), 'state' => $state],
                        ['material_unique_code' => $identityCode, 'account_id' => session('account.id'), 'state' => $state, 'material_type' => $materialType]
                    );
                }
            );

            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 删除临时设备
     * @return mixed
     */
    final public function tmpWarehouseMaterialWithCodeDestroy()
    {
        try {
            $identityCode = request('identityCode');
            $state = request('state');
            $tmpMaterial = TmpMaterial::with([])->where('material_unique_code', $identityCode)->where('state', $state)->firstOrFail();
            EntireInstanceLock::freeLock(
                $tmpMaterial->material_unique_code,
                [$state],
                function () use ($tmpMaterial) {
                    $tmpMaterial->delete();
                }
            );

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 报废单
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function scrapWithOrder(Request $request)
    {
        $originAt = Carbon::now()->startOfMonth()->toDateString();
        $finishAt = Carbon::now()->endOfMonth()->toDateString();
        $updated_at = $request->get('updated_at');
        $use_made_at = $request->get('use_made_at');
        $warehouses = Warehouse::with(['WithAccount'])
            ->when(
                session('account.read_scope') === 1,
                function ($query) {
                    return $query->where('account_id', session('account.id'));
                }
            )
            ->where('state', 'END')
            ->where('direction', 'SCRAP')
            ->when(
                $use_made_at == 1,
                function ($query) use ($updated_at) {
                    if (!empty($updated_at)) {
                        $tmp_updated_at = explode('~', $updated_at);
                        $tmp_left = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[0])->startOfDay()->format('Y-m-d H:i:s');
                        $tmp_right = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[1])->endOfDay()->format('Y-m-d H:i:s');
                        return $query->whereBetween('updated_at', [$tmp_left, $tmp_right]);
                    }
                }
            )
            ->orderBy('updated_at', 'desc')
            ->paginate();
        $warehouseUniqueCodes = array_column($warehouses->toArray()['data'], 'unique_code');
        $statistics = [];
        if (!empty($warehouseUniqueCodes)) {
            $entireCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, ei.category_name')
                ->join(DB::raw('entire_instances ei'), 'wm.material_unique_code', '=', 'ei.identity_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('ei.deleted_at', null)
                ->where('wm.material_type', 'ENTIRE')
                ->groupBy(['wm.warehouse_unique_code', 'ei.category_name'])
                ->get()->toArray();

            foreach ($entireCounts as $entireCount) {
                $statistics[$entireCount->warehouse_unique_code][] = [
                    'category_name' => $entireCount->category_name,
                    'count' => $entireCount->count
                ];
            }

            $partCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, pc.name as part_category_name, c.name as category_name')
                ->join(DB::raw('part_instances pi'), 'wm.material_unique_code', '=', 'pi.identity_code')
                ->join(DB::raw('part_categories pc'), 'pi.part_category_id', '=', 'pc.id')
                ->join(DB::raw('categories c'), 'pi.category_unique_code', '=', 'c.unique_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('wm.material_type', 'PART')
                ->where('pi.deleted_at', null)
                ->where('pc.deleted_at', null)
                ->groupBy(['wm.warehouse_unique_code', 'pc.name', 'c.name'])
                ->get()->toArray();
            foreach ($partCounts as $partCount) {
                $statistics[$partCount->warehouse_unique_code][] = [
                    'category_name' => $partCount->category_name . ' ' . $partCount->part_category_name,
                    'count' => $partCount->count
                ];
            }
        }

        return view('Storehouse.Index.Scrap.order', [
            'warehouses' => $warehouses,
            'originAt' => empty($updated_at) ? $originAt : explode('~', $updated_at)[0],
            'finishAt' => empty($updated_at) ? $finishAt : explode('~', $updated_at)[1],
            'statistics' => $statistics,
        ]);
    }

    /**
     * 报废-设备列表
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Throwable
     */
    final public function scrapWithInstance()
    {
        try {
            $materialType = request('materialType', 'ENTIRE');
            $identity_code = request('identity_code', '');
            $status_code = request('status_code', '');
            $statuses = EntireInstance::$STATUSES;
            unset($statuses['FRMLOSS']);
            unset($statuses['SCRAP']);
            unset($statuses['INSTALLED']);
            $query_condition = QueryConditionFacade::init(storage_path("app/basicInfo"))
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB()
                ->setStorehouses()
                ->setAreas()
                ->setPlatoons()
                ->setShelves()
                ->setTiers()
                ->setPositions();

            $query_condition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                '',
                '',
                '',
                '',
                strval(request("storehouse_unique_code")),
                strval(request("area_unique_code")),
                strval(request("platoon_unique_code")),
                strval(request("shelf_unique_code")),
                strval(request("tier_unique_code")),
                strval(request("position_unique_code"))
            );
            $entireInstances = [];
            $materialUniqueCodes = [];
            if (!empty(request()->keys())) {
                $otherUniqueCodes = DB::table('entire_instance_locks')->where('lock_name', '<>', 'SCRAP')->pluck('entire_instance_identity_code')->toArray();
                switch ($materialType) {
                    default:
                    case 'ENTIRE':
                        # 搜索整件
                        $partInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        c.name as category_name,
                        '' as part_category_name,
                        ei.model_name,
                        ei.serial_number,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type
                        ")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('pm.deleted_at', null)
                            ->where('pc.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('ei.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('ei.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc('ei.updated_at');

                        $entireInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        '' as part_category_name,
                        c.name as category_name,
                        ei.model_name,
                        ei.serial_number,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type
                        ")
                            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('sm.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('ei.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('ei.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc('ei.updated_at')
                            ->unionAll($partInstanceSql);

                        $entireInstances = DB::table(DB::raw("({$entireInstanceSql->toSql()}) as a"))->mergeBindings($entireInstanceSql)->paginate();
                        break;
                    case 'PART':
                        # 搜索部件
                        $entireInstances = DB::table("part_instances as pi")
                            ->selectRaw("
                        pi.identity_code,
                        pi.factory_name,
                        pm.name as model_name,
                        pc.name as part_category_name,
                        c.name as category_name,
                        '' as serial_number,
                        pi.status,
                        pi.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'PART' as material_type
                        ")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'pi.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("pi.deleted_at", null)
                            ->where('pc.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('pi.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('pi.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('pi.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("pi.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pi.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc("pi.id")
                            ->paginate();
                        break;
                }
                $materialUniqueCodes = DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'SCRAP')->pluck('material_unique_code')->toArray();
            }

            return view('Storehouse.Index.Scrap.instance', [
                'currentMaterialType' => $materialType,
                "queryConditions" => $query_condition->toJson(),
                "entireInstances" => $entireInstances,
                "statuses" => $statuses,
                'materialUniqueCodes' => $materialUniqueCodes
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 报废-确认报废
     * @return \Illuminate\Http\JsonResponse
     */
    final public function scrapWithConfirm()
    {
        try {
            $tmpMaterials = DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'SCRAP')->get();
            if ($tmpMaterials->isEmpty()) return JsonResponseFacade::errorEmpty();
            $warehouseId = DB::transaction(function () use ($tmpMaterials) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('SCRAP');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'SCRAP',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->saveOrFail();
                $warehouseId = $warehouse->id;
                foreach ($tmpMaterials as $tmpMaterial) {
                    EntireInstanceLock::freeLock(
                        $tmpMaterial->material_unique_code,
                        ['SCRAP'],
                        function () use ($tmpMaterial, $warehouseUniqueCode, $warehouseId) {
                            $description = '经办人：' . session('account.nickname') . '；';
                            if ($tmpMaterial->material_type == 'ENTIRE') {
                                DB::table('warehouse_materials')->insert([
                                    'created_at' => $this->_current_time,
                                    'updated_at' => $this->_current_time,
                                    'material_unique_code' => $tmpMaterial->material_unique_code,
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'ENTIRE'
                                ]);

                                DB::table('entire_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => 'SCRAP',
                                    'updated_at' => $this->_current_time,
                                ]);
                                EntireInstanceLogFacade::makeOne('报废', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'ENTIRE');

                            };
                            if ($tmpMaterial->material_type == 'PART') {
                                DB::table('warehouse_materials')->insert([
                                    'created_at' => $this->_current_time,
                                    'updated_at' => $this->_current_time,
                                    'material_unique_code' => $tmpMaterial->material_unique_code,
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'PART'
                                ]);

                                DB::table('part_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => 'SCRAP',
                                    'updated_at' => $this->_current_time,
                                ]);
                                EntireInstanceLogFacade::makeOne('报损', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'PART');
                            };
                        }
                    );
                }
                DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'SCRAP')->delete();
                DB::table('warehouses')->where('direction', 'SCRAP')->where('account_id', session('account.id'))->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => $this->_current_time,
                ]);
                return $warehouseId;
            });
            return JsonResponseFacade::data(['message' => '报废成功', 'return_url' => '/storehouse/index/' . $warehouseId]);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }


    /**
     * 报损
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function frmLossWithOrder(Request $request)
    {
        $originAt = Carbon::now()->startOfMonth()->toDateString();
        $finishAt = Carbon::now()->endOfMonth()->toDateString();
        $updated_at = $request->get('updated_at');
        $use_made_at = $request->get('use_made_at');
        $warehouses = Warehouse::with(['WithAccount'])
            ->when(
                session('account.read_scope') === 1,
                function ($query) {
                    return $query->where('account_id', session('account.id'));
                }
            )
            ->where('state', 'END')
            ->where('direction', 'FRMLOSS')
            ->when(
                $use_made_at == 1,
                function ($query) use ($updated_at) {
                    if (!empty($updated_at)) {
                        $tmp_updated_at = explode('~', $updated_at);
                        $tmp_left = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[0])->startOfDay()->format('Y-m-d H:i:s');
                        $tmp_right = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[1])->endOfDay()->format('Y-m-d H:i:s');
                        return $query->whereBetween('updated_at', [$tmp_left, $tmp_right]);
                    }
                }
            )
            ->orderBy('updated_at', 'desc')
            ->paginate();
        $warehouseUniqueCodes = array_column($warehouses->toArray()['data'], 'unique_code');
        $statistics = [];
        if (!empty($warehouseUniqueCodes)) {
            $entireCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, ei.category_name')
                ->join(DB::raw('entire_instances ei'), 'wm.material_unique_code', '=', 'ei.identity_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('ei.deleted_at', null)
                ->where('wm.material_type', 'ENTIRE')
                ->groupBy(['wm.warehouse_unique_code', 'ei.category_name'])
                ->get()->toArray();

            foreach ($entireCounts as $entireCount) {
                $statistics[$entireCount->warehouse_unique_code][] = [
                    'category_name' => $entireCount->category_name,
                    'count' => $entireCount->count
                ];
            }

            $partCounts = DB::table('warehouse_materials as wm')
                ->selectRaw('count(wm.material_unique_code) as count, wm.warehouse_unique_code, pc.name as part_category_name, c.name as category_name')
                ->join(DB::raw('part_instances pi'), 'wm.material_unique_code', '=', 'pi.identity_code')
                ->join(DB::raw('part_categories pc'), 'pi.part_category_id', '=', 'pc.id')
                ->join(DB::raw('categories c'), 'pi.category_unique_code', '=', 'c.unique_code')
                ->whereIn('wm.warehouse_unique_code', $warehouseUniqueCodes)
                ->where('wm.material_type', 'PART')
                ->where('pi.deleted_at', null)
                ->where('pc.deleted_at', null)
                ->groupBy(['wm.warehouse_unique_code', 'pc.name', 'c.name'])
                ->get()->toArray();
            foreach ($partCounts as $partCount) {
                $statistics[$partCount->warehouse_unique_code][] = [
                    'category_name' => $partCount->category_name . ' ' . $partCount->part_category_name,
                    'count' => $partCount->count
                ];
            }
        }

        return view('Storehouse.Index.FrmLoss.order', [
            'warehouses' => $warehouses,
            'originAt' => empty($updated_at) ? $originAt : explode('~', $updated_at)[0],
            'finishAt' => empty($updated_at) ? $finishAt : explode('~', $updated_at)[1],
            'statistics' => $statistics,
        ]);
    }

    /**
     * 报损-设备列表
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Throwable
     */
    final public function frmLossWithInstance()
    {
        try {
            $materialType = request('materialType', 'ENTIRE');
            $identity_code = request('identity_code', '');
            $status_code = request('status_code', '');
            $statuses = EntireInstance::$STATUSES;
            unset($statuses['FRMLOSS']);
            unset($statuses['SCRAP']);
            unset($statuses['INSTALLED']);
            $query_condition = QueryConditionFacade::init(storage_path("app/basicInfo"))
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB()
                ->setStorehouses()
                ->setAreas()
                ->setPlatoons()
                ->setShelves()
                ->setTiers()
                ->setPositions();

            $query_condition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                '',
                '',
                '',
                '',
                strval(request("storehouse_unique_code")),
                strval(request("area_unique_code")),
                strval(request("platoon_unique_code")),
                strval(request("shelf_unique_code")),
                strval(request("tier_unique_code")),
                strval(request("position_unique_code"))
            );
            $entireInstances = [];
            $materialUniqueCodes = [];
            if (!empty(request()->keys())) {
                $otherUniqueCodes = DB::table('entire_instance_locks')->where('lock_name', '<>', 'FRMLOSS')->pluck('entire_instance_identity_code')->toArray();
                switch ($materialType) {
                    default:
                    case 'ENTIRE':
                        # 搜索整件
                        $partInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        c.name as category_name,
                        '' as part_category_name,
                        ei.model_name,
                        ei.serial_number,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type
                        ")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('pm.deleted_at', null)
                            ->where('pc.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('ei.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('ei.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("ei.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc('ei.updated_at');

                        $entireInstanceSql = DB::table('entire_instances as ei')
                            ->selectRaw("
                        ei.identity_code,
                        ei.factory_name,
                        '' as part_category_name,
                        c.name as category_name,
                        ei.model_name,
                        ei.serial_number,
                        ei.status,
                        ei.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'ENTIRE' as material_type
                        ")
                            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("ei.deleted_at", null)
                            ->where('sm.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.deleted_at', null)
                            ->where('em.is_sub_model', false)
                            ->where('c.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('ei.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('ei.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('ei.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("ei.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("ei.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("ei.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc('ei.updated_at')
                            ->unionAll($partInstanceSql);

                        $entireInstances = DB::table(DB::raw("({$entireInstanceSql->toSql()}) as a"))->mergeBindings($entireInstanceSql)->paginate();
                        break;
                    case 'PART':
                        # 搜索部件
                        $entireInstances = DB::table("part_instances as pi")
                            ->selectRaw("
                        pi.identity_code,
                        pi.factory_name,
                        pm.name as model_name,
                        pc.name as part_category_name,
                        c.name as category_name,
                        '' as serial_number,
                        pi.status,
                        pi.location_unique_code,
                        position.name as position_name,
                        tier.name as tier_name,
                        shelf.name as shelf_name,
                        platoon.name as platoon_name,
                        area.name as area_name,
                        storehous.name as storehous_name,
                        'PART' as material_type
                        ")
                            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                            ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                            ->leftJoin(DB::raw('positions position'), 'pi.location_unique_code', '=', 'position.unique_code')
                            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                            ->where("pi.deleted_at", null)
                            ->where('pc.deleted_at', null)
                            ->when(
                                !empty($status_code),
                                function ($query) use ($status_code) {
                                    return $query->where('pi.status', $status_code);
                                },
                                function ($query) use ($statuses) {
                                    return $query->whereIn('pi.status', array_keys($statuses));
                                }
                            )
                            ->when(
                                !empty($otherUniqueCodes),
                                function ($q) use ($otherUniqueCodes) {
                                    return $q->whereNotIn('pi.identity_code', $otherUniqueCodes);
                                }
                            )
                            ->when(
                                !empty($status_code),
                                function ($q) use ($status_code) {
                                    return $q->where("pi.status", $status_code);
                                }
                            )
                            ->when(
                                !empty($identity_code),
                                function ($q) use ($identity_code) {
                                    return $q->where("pi.identity_code", $identity_code);
                                }
                            )
                            ->when(
                                $query_condition->get("current_category_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("c.unique_code", $query_condition->get("current_category_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_entire_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_sub_model_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                                }
                            )
                            ->when(
                                $query_condition->get("current_storehouse_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("storehous.unique_code", $query_condition->get("current_storehouse_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_area_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("area.unique_code", $query_condition->get("current_area_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_platoon_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("platoon.unique_code", $query_condition->get("current_platoon_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_shelf_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("shelf.unique_code", $query_condition->get("current_shelf_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_tier_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("tier.unique_code", $query_condition->get("current_tier_unique_code"));
                                })
                            ->when(
                                $query_condition->get("current_position_unique_code"),
                                function ($q) use ($query_condition) {
                                    return $q->where("pi.location_unique_code", $query_condition->get("current_position_unique_code"));
                                })
                            ->orderByDesc("pi.id")
                            ->paginate();
                        break;
                }
                $materialUniqueCodes = DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'FRMLOSS')->pluck('material_unique_code')->toArray();
            }

            return view('Storehouse.Index.FrmLoss.instance', [
                'currentMaterialType' => $materialType,
                "queryConditions" => $query_condition->toJson(),
                "entireInstances" => $entireInstances,
                "statuses" => $statuses,
                'materialUniqueCodes' => $materialUniqueCodes
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 报损-确认报损
     * @return \Illuminate\Http\JsonResponse
     */
    final public function frmLossWithConfirm()
    {
        try {
            $tmpMaterials = DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'FRMLOSS')->get();
            if ($tmpMaterials->isEmpty()) return HttpResponseHelper::errorEmpty('数据为空');
            $warehouseId = DB::transaction(function () use ($tmpMaterials) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('FRMLOSS');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'FRMLOSS',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->saveOrFail();
                $warehouseId = $warehouse->id;
                foreach ($tmpMaterials as $tmpMaterial) {
                    EntireInstanceLock::freeLock(
                        $tmpMaterial->material_unique_code,
                        ['FRMLOSS'],
                        function () use ($tmpMaterial, $warehouseUniqueCode, $warehouseId) {
                            $description = '经办人：' . session('account.nickname') . '；';
                            if ($tmpMaterial->material_type == 'ENTIRE') {
                                DB::table('warehouse_materials')->insert([
                                    'created_at' => $this->_current_time,
                                    'updated_at' => $this->_current_time,
                                    'material_unique_code' => $tmpMaterial->material_unique_code,
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'ENTIRE'
                                ]);

                                DB::table('entire_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => 'FRMLOSS',
                                    'updated_at' => $this->_current_time,
                                ]);
                                EntireInstanceLogFacade::makeOne('报损', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'ENTIRE');

                            };
                            if ($tmpMaterial->material_type == 'PART') {
                                DB::table('warehouse_materials')->insert([
                                    'created_at' => $this->_current_time,
                                    'updated_at' => $this->_current_time,
                                    'material_unique_code' => $tmpMaterial->material_unique_code,
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'PART'
                                ]);

                                DB::table('part_instances')->where('identity_code', $tmpMaterial->material_unique_code)->update([
                                    'status' => 'FRMLOSS',
                                    'updated_at' => $this->_current_time,
                                ]);
                                EntireInstanceLogFacade::makeOne('报损', $tmpMaterial->material_unique_code, 0, "/storehouse/index/{$warehouseId}", $description, 'PART');
                            };
                        }
                    );
                }
                DB::table('tmp_materials')->where('account_id', session('account.id'))->where('state', 'FRMLOSS')->delete();
                DB::table('warehouses')->where('direction', 'FRMLOSS')->where('account_id', session('account.id'))->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => $this->_current_time,
                ]);
                return $warehouseId;
            });

            return HttpResponseHelper::data(['message' => '报损成功', 'return_url' => '/storehouse/index/' . $warehouseId]);
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), [get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

}
