<?php

namespace App\Http\Controllers\Report;

use App\Facades\QueryConditionFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Model\Log;

class PropertyController extends Controller
{
    /**
     * 资产管理
     */
    final public function property()
    {
        $currentYear = date('Y');
        /**
         * 资产管理 ✅
         * @return array
         */
        $property = function () use ($currentYear): array {
            $fileDir = storage_path('app/property');
            if (!is_dir($fileDir)) return [];

            return json_decode(file_get_contents("{$fileDir}/{$currentYear}/devicesAsKind.json"), true);
        };

        return view('Report.Property.property', [
            'propertyDevicesAsKindAsJson' => json_encode($property())
        ]);
    }

    /**
     * 根据种类获取设备列表（资产管理）
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function propertyCategory(string $categoryUniqueCode)
    {
        try {
            /**
             * 资产管理
             * @return array
             */
            $property = function () use ($categoryUniqueCode) {
                $currentYear = date('Y');
                $fileDir = storage_path('app/property');
                if (!is_dir($fileDir)) return [];

                $tmp = json_decode(file_get_contents("{$fileDir}/{$currentYear}/devicesAsKind.json"), true);
                $devicesAsCategory = $tmp[$categoryUniqueCode]['subs'];
                $devicesAsModel = [];
                foreach ($devicesAsCategory as $entireModelUniqueCode => $entireModel) {
                    foreach ($entireModel['subs'] as $modelUniqueCode => $model) {
                        $devicesAsModel[$modelUniqueCode] = $model;
                    }
                }

                return [$devicesAsModel];
            };
            list($devicesAsModel) = $property();

            return view('Report.Property.propertyCategory', [
                'devicesAsModelAsJson' => json_encode($devicesAsModel),
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据型号名称获取设备列表（资产管理）
     * @return Factory|RedirectResponse|View
     */
    final public function propertySubModel()
    {
        try {
            $now = Carbon::now()->format("Y-m-d");
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode("~", request("date_made_at", "{$now} 00:00:00~{$now} 23:59:59"));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode("~", request("date_created_at", "{$now} 00:00:00~{$now} 23:59:59"));
            list($dateNextFixingDayOrigin, $dateNextFixingDayFinish) = explode("~", request("date_next_fixing_day", "{$now} 00:00:00~{$now} 23:59:59"));

            $query_condition = QueryConditionFacade::init(__DIR__)
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB()
                ->setStatus();

            $query_condition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                strval(request("factory_unique_code")),
                strval(request("scene_workshop_unique_code")),
                strval(request("station_name")),
                strval(request("status_unique_code")),
                "",
                "",
                "",
                "",
                "",
                "",
                strval(request("line_unique_code"))
            );

            $partSql = DB::table("entire_instances as ei")
                ->select([
                    "ei.created_at",  # 创建时间
                    "ei.updated_at",  # 修改时间
                    "ei.identity_code",  # 唯一编号
                    "ei.factory_name",  # 供应商名称
                    "ei.factory_device_code",  # 出厂编号
                    "ei.serial_number",  # 出所编号
                    "ei.maintain_station_name",  # 站场名称
                    "ei.maintain_location_code",  # 安装位置
                    "ei.crossroad_number", # 道岔
                    "ei.line_name", # 线制
                    "ei.to_direction", # 去向
                    "ei.open_direction", # 开向
                    "ei.traction", # 牵引
                    "ei.said_rod", # 表示杆
                    "ei.last_installed_time",  # 最后安装时间
                    "ei.category_name",  # 种类名称
                    "ei.model_name",  # 型号名称
                    "ei.status", # 状态
                    "ei.next_fixing_time",  # 下次周期修时间
                    "ei.scarping_at",  # 报废时间
                    "ei.fix_cycle_value as ei_fix_cycle_value",  # 设备周期修时长
                    "em.fix_cycle_value as model_fix_cycle_value",  # 型号周期修时长
                    "ei.last_fix_workflow_at as fw_updated_at",  # 上次检修时间
                ])
                ->join(DB::raw("part_instances pi"), "pi.entire_instance_identity_code", "=", "ei.identity_code")
                ->join(DB::raw("part_models pm"), "pm.unique_code", "=", "pi.part_model_unique_code")
                ->join(DB::raw("part_categories pc"), "pc.id", "=", "pm.part_category_id")
                ->join(DB::raw("entire_models em"), "em.unique_code", "=", "pm.entire_model_unique_code")
                ->join(DB::raw("categories c"), "c.unique_code", "=", "em.category_unique_code")
                ->join(DB::raw("factories f"), "f.name", "=", "ei.factory_name")
                ->join(DB::raw("maintains s"), "s.name", "=", "ei.maintain_station_name")
                ->join(DB::raw("maintains sc"), "sc.unique_code", "=", "s.parent_unique_code")
                ->leftJoin(DB::raw('fix_workflows fw'), 'fw.entire_instance_identity_code', '=', 'ei.identity_code')
                ->leftJoin(DB::raw('fix_workflow_processes fwp'), 'fwp.fix_workflow_serial_number', '=', 'fw.serial_number')
                ->where("ei.deleted_at", null)
                ->where("ei.status", "<>", "SCARP")
                ->where("pm.deleted_at", null)
                ->where("pc.deleted_at", null)
                ->where("pc.is_main", true)
                ->where("em.deleted_at", null)
                ->where("em.is_sub_model", false)
                ->where("c.deleted_at", null)
                ->where("f.deleted_at", null)
                ->where("s.deleted_at", null)
                ->where("sc.deleted_at", null)
                ->where("ei.category_unique_code", $query_condition->get("current_category_unique_code"))
                ->when($query_condition->get("current_entire_model_unique_code"), function ($q) use ($query_condition) {
                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                })
                ->when($query_condition->get("current_sub_model_unique_code"), function ($q) use ($query_condition) {
                    return $q->where("pm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                })
                ->when(
                    $query_condition->get("current_factory_unique_code"),
                    function ($query) use ($query_condition) {
                        return $query->where("f.unique_code", $query_condition->get("current_factory_unique_code"));
                    }
                )
                ->when(
                    $query_condition->get("maintain_type"),
                    function ($query) use ($query_condition) {
                        if ($query_condition->get("maintain_type") == "current_station_name") {
                            return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                        } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                            return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                        } else {
                            return $query;
                        }
                    }
                )
                ->when(
                    $query_condition->get("current_status_unique_code"),
                    function ($q) use ($query_condition) {
                        return $q->where("ei.status", $query_condition->get("current_status_unique_code"));
                    }
                )
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->orderByDesc('ei.updated_at');

            $entireSql = DB::table("entire_instances as ei")
                ->select([
                    "ei.created_at",  # 创建时间
                    "ei.updated_at",  # 修改时间
                    "ei.identity_code",  # 唯一编号
                    "ei.factory_name",  # 供应商名称
                    "ei.factory_device_code",  # 出厂编号
                    "ei.serial_number",  # 出所编号
                    "ei.maintain_station_name",  # 站场名称
                    "ei.maintain_location_code",  # 安装位置
                    "ei.crossroad_number", # 道岔
                    "ei.line_name", # 线制
                    "ei.to_direction", # 去向
                    "ei.open_direction", # 开向
                    "ei.traction", # 牵引
                    "ei.said_rod", # 表示杆
                    "ei.last_installed_time",  # 最后安装时间
                    "ei.category_name",  # 种类名称
                    "ei.model_name", # 子类名称
                    "ei.status", # 状态
                    "ei.next_fixing_time",  # 下次周期修时间
                    "ei.scarping_at",  # 报废时间
                    "ei.fix_cycle_value as ei_fix_cycle_value",  # 设备周期修时长
                    "sm.fix_cycle_value as model_fix_cycle_value",  # 型号周期修时长
                    "ei.last_fix_workflow_at as fw_updated_at",  # 上次检修时间
                ])
                ->join(DB::raw("entire_models sm"), "sm.unique_code", "=", "ei.entire_model_unique_code")
                ->join(DB::raw("entire_models em"), "em.unique_code", "=", "sm.parent_unique_code")
                ->join(DB::raw("categories c"), "c.unique_code", "=", "em.category_unique_code")
                ->join(DB::raw("factories f"), "f.name", "=", "ei.factory_name")
                ->join(DB::raw("maintains s"), "s.name", "=", "ei.maintain_station_name")
                ->join(DB::raw("maintains sc"), "sc.unique_code", "=", "s.parent_unique_code")
                ->where("ei.deleted_at", null)
                ->where("ei.status", "<>", "SCARP")
                ->where("sm.deleted_at", null)
                ->where("sm.is_sub_model", true)
                ->where("em.deleted_at", null)
                ->where("em.is_sub_model", false)
                ->where("c.deleted_at", null)
                ->where("f.deleted_at", null)
                ->where("s.deleted_at", null)
                ->where("sc.deleted_at", null)
                ->where("ei.category_unique_code", $query_condition->get("current_category_unique_code"))
                ->when($query_condition->get("current_entire_model_unique_code"), function ($q) use ($query_condition) {
                    return $q->where("em.unique_code", $query_condition->get("current_entire_model_unique_code"));
                })
                ->when($query_condition->get("current_sub_model_unique_code"), function ($q) use ($query_condition) {
                    return $q->where("sm.unique_code", $query_condition->get("current_sub_model_unique_code"));
                })
                ->when(
                    $query_condition->get("current_factory_unique_code"),
                    function ($query) use ($query_condition) {
                        return $query->where("f.unique_code", $query_condition->get("current_factory_unique_code"));
                    }
                )
                ->when(
                    $query_condition->get("maintain_type"),
                    function ($query) use ($query_condition) {
                        if ($query_condition->get("maintain_type") == "current_station_name") {
                            return $query->where("ei.maintain_station_name", $query_condition->get("current_station_name"));
                        } elseif ($query_condition->get("maintain_type") == "current_station_names") {
                            return $query->whereIn("ei.maintain_station_name", $query_condition->get("current_station_names"));
                        } else {
                            return $query;
                        }
                    }
                )
                ->when(
                    $query_condition->get("current_status_unique_code"),
                    function ($q) use ($query_condition) {
                        return $q->where("ei.status", $query_condition->get("current_status_unique_code"));
                    }
                )
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->orderByDesc('ei.updated_at');

            $entireInstances = ModelBuilderFacade::unionAll($entireSql, $partSql)->paginate();

            return view("Report.Property.propertySubModel", [
                "queryConditions" => $query_condition->toJson(),
                "currentCategoryUniqueCode" => $query_condition->get("current_category_unique_code"),
                "statuses" => $query_condition->get("statuses"),
                "entireInstances" => $entireInstances,
                "dateMadeAtOrigin" => $dateMadeAtOrigin,
                "dateMadeAtFinish" => $dateMadeAtFinish,
                "dateCreatedAtOrigin" => $dateCreatedAtOrigin,
                "dateCreatedAtFinish" => $dateCreatedAtFinish,
                "dateNextFixingDayOrigin" => $dateNextFixingDayOrigin,
                "dateNextFixingDayFinish" => $dateNextFixingDayFinish,
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
