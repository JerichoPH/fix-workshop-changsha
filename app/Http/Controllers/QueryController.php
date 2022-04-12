<?php

namespace App\Http\Controllers;

use App\Facades\ModelBuilderFacade;
use App\Model\EntireInstance;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\Model\Log;

class QueryController extends Controller
{
    final public function index()
    {
        $query_keys = [
            'identity_code',
            'factory_device_code',
            'serial_number',
            'status',
            'category_unique_code',
            'entire_model_unique_code',
            'sub_model_unique_code',
            'scene_workshop_name',
            'station_name',
            'maintain_location_code',
            'crossroad_number',
            'created_at',
            'made_at',
            'out_at',
            'installed_at',
            'scarping_at',
            'next_fixing_day',
            'fixed_at',
            'behavior_type',
            'work_area',
            'account_id',
            'warehousein_at',
        ];  // 查询条件字段名
        $fields = [
            'ei.identity_code',
            'ei.factory_name',
            'ei.factory_device_code',
            'ei.serial_number',
            'ei.status',
            'ei.category_name',
            'ei.last_installed_time',
            'ei.maintain_station_name',
            'ei.maintain_location_code',
            'ei.line_unique_code',
            'ei.line_name',
            'ei.open_direction',
            'ei.to_direction',
            'ei.traction',
            'ei.crossroad_number',
            'ei.said_rod',
            'ei.warehouse_name',
            'ei.location_unique_code',
            'ei.work_area',
            'ei.next_fixing_day',
            'ei.next_fixing_time',
            'ei.scarping_at',
            'ei.model_name',
            'ei.warehousein_at',
            'ei.fix_cycle_value as ei_fix_cycle_value',
            'ei.last_fix_workflow_at as fw_updated_at',
            'ei.bind_device_code as bind_device_code',  // 绑定设备编号
            'ei.bind_crossroad_number as bind_crossroad_number',  // 绑定设备道岔号
            'ei.bind_device_type_name as bind_device_type_name',  // 绑定设备名称
            'position.name as position_name',  // 位
            'tier.name          as tier_name',  // 层
            'shelf.name         as shelf_name',  // 架
            'platoon.name       as platoon_name',  // 排
            'area.name          as area_name',  // 区
            'storehous.name     as storehous_name',  // 仓
            'ei.last_fix_workflow_at',  // 上次检修时间
        ];  // 查询字段

        $entire_instances = [];
        $breakdownCounts = [];
        $factories = DB::table('factories')->where('deleted_at', null)->pluck('name');
        $categories = DB::table('categories')->where('deleted_at', null)->pluck('name', 'unique_code');
        $categoryUniqueCodes = $categories->keys()->toJson();
        $sceneWorkshops = DB::table('maintains')->where('deleted_at', null)->where('parent_unique_code', env('ORGANIZATION_CODE'))->pluck('name', 'unique_code');
        $lines = DB::table('lines')->where('deleted_at', null)->pluck('name', 'unique_code');
        $workAreas = [
            '未入库',
            '转辙机',
            '继电器',
            '综合',
        ];
        // 初始化车间条件
        $stations = null;
        if (request('scene_workshop_unique_code'))
            $stations = DB::table("maintains as m")->where('m.deleted_at', null)->where('m.parent_unique_code', request('scene_workshop_unique_code'))->pluck('name');
        if (request('station_name'))
            $stations = [request('station_name')];

        /**
         * 组合搜索条件
         * @param Builder $query
         * @return Builder
         */
        $make_condition = function (Builder $query) use ($stations): Builder {
            return $query
                ->when(
                    request('code_value'),
                    function ($query) {
                        $code_value = request('code_value');
                        $code_type = request('code_type');
                        $is_code_indistinct = boolval(request('ici', false));
                        $operator = $is_code_indistinct ? 'like' : '=';
                        $code_value = $is_code_indistinct ? "%{$code_value}%" : $code_value;
                        $query->where("ei.{$code_type}", $operator, $code_value);
                    }
                )
                ->when(
                    request('status'),
                    function ($query) {
                        $query->where('ei.status', request('status'));
                    }
                )
                ->when(
                    request('category_unique_code'),
                    function ($query) {
                        $query->where('c.unique_code', request('category_unique_code'));
                    }
                )
                ->when(
                    request('entire_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        $query->where('em.unique_code', request('entire_model_unique_code'));
                    }
                )
                ->when(
                    request('sub_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        $query->where('ei.model_unique_code', request('sub_model_unique_code'));
                    }
                )
                ->when(
                    request('factory'),
                    function ($query) {
                        $query->where('ei.factory_name', request('factory'));
                    }
                )
                ->when(
                    $stations,
                    function ($query) use ($stations) {
                        $query->whereIn('ei.maintain_station_name', $stations);
                    }
                )
                ->when(
                    request('maintain_location_code'),
                    function ($query) {
                        request('maintain_location_code_use_indistinct')
                            ? $query->where('ei.maintain_location_code', 'like', '%' . request('maintain_location_code') . '%')
                            : $query->where('ei.maintain_location_code', request('maintain_location_code'));
                    }
                )
                ->when(
                    request('crossroad_number'),
                    function ($query) {
                        $crossroad_number = request('crossroad_number');
                        request('crossroad_number_use_indistinct')
                            ? $query->where(function ($q) use ($crossroad_number) {
                            $q->where('ei.crossroad_number', 'like', "%{$crossroad_number}%")->orWhere('ei.bind_crossroad_number','like', "%{$crossroad_number}%");
                        })
                            : $query->where(function ($q) {
                            $q->where('ei.crossroad_number', request('crossroad_number'))->orWhere('ei.bind_crossroad_number', request('crossroad_number'));
                        });
                    }
                )
                ->when(
                    request('use_created_at') == '1',
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('created_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.created_at')
                            ->whereBetween('ei.created_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('use_made_at'),
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('made_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.made_at')
                            ->whereBetween('ei.made_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('use_out_at'),
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('out_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.last_out_at')
                            ->whereBetween('ei.last_out_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('use_installed_at'),
                    function ($query) {
                        list($origin, $finish) = explode('~', request('installed_at'));
                        $originTimestamp = Carbon::createFromFormat('Y-m-d', $origin)->setTime(0, 0, 0)->timestamp;
                        $finishTimestamp = Carbon::createFromFormat('Y-m-d', $finish)->setTime(23, 59, 59)->timestamp;
                        $query
                            ->orderByDesc('ei.last_installed_time')
                            ->whereBetween('ei.last_installed_time', [$originTimestamp, $finishTimestamp]);
                    }
                )
                ->when(
                    request('use_scarping_at'),
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('scarping_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.scarping_at')
                            ->whereBetween('ei.scarping_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('use_next_fixing_day'),
                    function ($query) {
                        list($originTimestamp, $finishTimestamp) = explode('~', request('next_fixing_day'));
                        $originTimestamp = Carbon::createFromFormat('Y-m-d', $originTimestamp)->setTime(0, 0, 0)->timestamp;
                        $finishTimestamp = Carbon::createFromFormat('Y-m-d', $finishTimestamp)->setTime(23, 59, 59)->timestamp;
                        $query
                            ->orderByDesc('ei.next_fixing_time')
                            ->whereBetween('ei.next_fixing_time', [$originTimestamp, $finishTimestamp]);
                    }
                )
                ->when(
                    request('use_fixed_at'),
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('fixed_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.last_fix_workflow_at')
                            ->whereBetween('ei.last_fix_workflow_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('use_warehousein_at') == '1',
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('warehousein_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        $query
                            ->orderByDesc('ei.warehousein_at')
                            ->whereBetween('ei.warehousein_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('is_scraped') !== 'all',
                    function ($query) {
                        switch (strtoupper(request('is_scraped'))) {
                            case 'OUT':
                                // 只搜索未超期
                                $query->where('ei.scarping_at', '>', date('Y-m-d'));
                                break;
                            case 'IN':
                                // 只搜索超期
                                $query->where('ei.scarping_at', '<', date('Y-m-d'));
                                break;
                        }
                    }
                )
                ->orderByDesc('ei.id');
        };

        if (!empty(array_intersect(request()->keys(), $query_keys))) {
            // 搜索器材
            $sql_Q = $make_condition(
                DB::table('entire_instances as ei')
                    ->select($fields)
                    ->addSelect(['sm.fix_cycle_value as model_fix_cycle_value'])
                    ->distinct()
                    ->leftJoin(DB::raw('entire_models as sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->leftJoin(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->leftJoin(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->leftJoin(DB::raw('maintains as s'), 's.name', '=', 'ei.maintain_station_name')
                    ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                    ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                    ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                    ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                    ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                    ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                    ->where('ei.deleted_at', null)
                    ->where('sm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('c.deleted_at', null)
                    ->where('sm.is_sub_model', true)
                    ->where('em.is_sub_model', false)
            );

            // 搜索设备
            $sql_S = $make_condition(
                DB::table('entire_instances as ei')
                    ->select($fields)
                    ->addSelect(['em.fix_cycle_value as model_fix_cycle_value'])
                    ->distinct()
                    ->leftJoin(DB::raw('part_models as pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                    ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'ei.category_unique_code')
                    ->leftJoin(DB::raw('maintains as s'), 's.name', '=', 'ei.maintain_station_name')
                    ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
                    ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
                    ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
                    ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
                    ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
                    ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
                    ->where('ei.deleted_at', null)
                    ->where('pm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('c.deleted_at', null)
                    ->where('em.is_sub_model', false)
            );

            $query_db = ModelBuilderFacade::unionAll($sql_Q, $sql_S);
            if (request('d') == 1) {
                // 搜索下载
                $entire_instances = $query_db->get();
                ExcelWriteHelper::download(function ($excel) use ($entire_instances) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    // 字体颜色
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');

                    // 首行
                    $currentSheet->setCellValueExplicit('A1', '唯一编号', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('B1', '供应商', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('C1', '状态', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('D1', '种类型', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('E1', '安装日期', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('F1', '位置', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('G1', '开向', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('H1', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('I1', '仓库位置', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('J1', '检修日期', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('K1', '下次周期修', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('L1', '到期日期', \PHPExcel_Cell_DataType::TYPE_STRING);

                    $row = 1;

                    foreach ($entire_instances as $entire_instance) {
                        $row++;
                        $currentSheet->setCellValueExplicit("A{$row}", $entire_instance->identity_code, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("B{$row}", $entire_instance->factory_name, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("C{$row}", EntireInstance::$STATUSES[$entire_instance->status], \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("D{$row}", $entire_instance->category_name . $entire_instance->model_name, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("E{$row}", $entire_instance->last_installed_time ? date('Y-m-d', $entire_instance->last_installed_time) : '', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("F{$row}", $entire_instance->maintain_station_name . $entire_instance->maintain_location_code . $entire_instance->crossroad_number, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("G{$row}", $entire_instance->open_direction, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("H{$row}", $entire_instance->said_rod, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit(
                            "I{$row}",
                            $entire_instance->storehous_name .
                            $entire_instance->area_name .
                            $entire_instance->platoon_name .
                            $entire_instance->shelf_name .
                            $entire_instance->tier_name .
                            $entire_instance->position_name,
                            \PHPExcel_Cell_DataType::TYPE_STRING
                        );
                        $currentSheet->setCellValueExplicit("J{$row}", $entire_instance->last_fix_workflow_at ? date('Y-m-d', strtotime($entire_instance->last_fix_workflow_at)) : '', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("K{$row}", $entire_instance->next_fixing_time ? date('Y-m-d', $entire_instance->next_fixing_time) : '', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit("L{$row}", date('Y-m-d', strtotime($entire_instance->scarping_at)), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }

                    // 定义列宽
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(25);  // A
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(25);  // B
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(8);  // C
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(21);  // D
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(11);  // E
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(15);  // F
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(6))->setWidth(8);  // G
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(11);  // H
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(8);  // I
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(8))->setWidth(35);  // J
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(9))->setWidth(11);  // K
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(10))->setWidth(11);  // L
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(11))->setWidth(11);  // M

                    return $excel;
                }, '设备列表');
            } else {
                // 搜索
                $entire_instances = $query_db->paginate(100);
                // $entire_instances = $query_db->get();
                $maintain_station_names = $entire_instances->pluck('maintain_station_name')->unique()->toArray();
                // 获取故障数量
                if (!empty($maintain_station_names))
                    $breakdownCounts = DB::table('breakdown_logs')->selectRaw("count(*) as count, concat(maintain_station_name, maintain_location_code, crossroad_number) as install_location")->whereIn('maintain_station_name', $maintain_station_names)->groupBy('maintain_station_name', 'maintain_location_code', 'crossroad_number')->pluck('count', 'install_location')->toArray();
            }
        }

        // 获取未来一年的年月
        for ($i = 0; $i <= 12; $i++) {
            $dates[] = date('Y-m', strtotime("+$i months"));
        }

        return view('Query.index', [
            'entireInstances' => $entire_instances,
            'statuses' => EntireInstance::$STATUSES,
            'factories' => $factories,
            'categories' => $categories,
            'categoryUniqueCodes' => $categoryUniqueCodes,
            'categoriesAsJson' => $categories->toJson(),
            'sceneWorkshops' => $sceneWorkshops,
            'lines' => $lines,
            'workAreas' => $workAreas,
            'dates' => $dates,
            'breakdownCounts' => $breakdownCounts
        ]);
    }

    /**
     * 根据种类获取类型
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function entireModels(string $categoryUniqueCode)
    {
        return response()->json(
            DB::table('entire_models')
                ->where('deleted_at', null)
                ->where('category_unique_code', $categoryUniqueCode)
                ->where('is_sub_model', false)
                ->pluck('name', 'unique_code')
        );
    }

    /**
     * 根据类型获取型号
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function subModels(string $entireModelUniqueCode)
    {
        return response()->json(
            array_merge(
                DB::table('entire_models')
                    ->where('deleted_at', null)
                    ->where('parent_unique_code', $entireModelUniqueCode)
                    ->where('is_sub_model', true)
                    ->pluck('name', 'unique_code')
                    ->toArray(),
                DB::table('part_models')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModelUniqueCode)
                    ->pluck('name', 'unique_code')
                    ->toArray()
            )
        );
    }

    /**
     * 根据车间获取车站
     * @return \Illuminate\Http\JsonResponse
     */
    final public function stations()
    {
        return response()->json(
            DB::table('maintains')
                ->where('deleted_at', null)
                ->where('type', '=', 'STATION')
                ->when(
                    request('sceneWorkshopUniqueCode'),
                    function ($query) {
                        return $query->where('parent_unique_code', request('sceneWorkshopUniqueCode'));
                    }
                )
                ->when(
                    request('lineUniqueCode'),
                    function ($query) {
                        $line = DB::table('lines')->where('unique_code', request('lineUniqueCode'))->first();
                        return $line ? $query->whereIn('id', DB::table('lines_maintains')->where('lines_id', $line->id)->pluck('maintains_id')->toArray()) : null;
                    }
                )
                ->pluck('name', 'unique_code')
        );
    }

    /**
     * 根据工区编号获取员工列表
     * @param int $workArea
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @param int $workArea
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function accounts(int $workArea)
    {
        $organizationCode = env('ORGANIZATION_CODE');

        return response()->json(
            DB::table('accounts')
                ->where('deleted_at', null)
                ->where('workshop_code', $organizationCode)
                ->where('work_area', '<>', null)
                ->when(
                    $workArea > 0,
                    function ($query) use ($workArea) {
                        return $query->where('work_area', $workArea);
                    },
                    function ($query) {
                        return $query->whereIn('work_area', [1, 2, 3]);
                    }
                )
                ->pluck('nickname', 'id')
        );
    }
}
