<?php

namespace App\Http\Controllers\Report;

use App\Facades\QueryConditionFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use Jericho\Excel\ExcelWriteHelper;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;

class ScrapedController extends Controller
{
    /**
     * 超期使用
     * @return Factory|RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    final public function scraped()
    {
        try {
            $currentYear = date("Y");
            $propertyDevicesAsKind = json_decode(file_get_contents(storage_path("app/property/{$currentYear}/devicesAsKind.json")), true);
            $scrapedDevicesAsKind = json_decode(file_get_contents(storage_path("app/scraped/scrapedDevicesAsKind.json")), true);

            return view('Report.Scraped.scraped', [
                'propertyDevicesAsKindAsJson' => json_encode($propertyDevicesAsKind),
                'scrapedDevicesAsKindAsJson' => json_encode($scrapedDevicesAsKind),
            ]);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 超期使用（指定种类）
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function scrapedWithCategory(string $categoryUniqueCode)
    {
        try {
            $currentYear = date("Y");
            $propertyDevicesAsKind = json_decode(file_get_contents(storage_path("app/property/{$currentYear}/devicesAsKind.json")), true)[$categoryUniqueCode]['subs'];
            $scrapedDevicesAsKind = json_decode(file_get_contents(storage_path("app/scraped/scrapedDevicesAsKind.json")), true)[$categoryUniqueCode]['subs'];

            return view('Report.Scraped.scrapedWithCategory', [
                'propertyDevicesAsKindAsJson' => json_encode($propertyDevicesAsKind),
                'scrapedDevicesAsKindAsJson' => json_encode($scrapedDevicesAsKind),
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 超期使用（指定类型）
     * @param string $entireModelUniqueCode
     * @return Factory|View
     */
    final public function scrapedWithEntireModel(string $entireModelUniqueCode)
    {
        $currentYear = date("Y");
        $propertyDevicesAsKind = json_decode(file_get_contents(storage_path("app/property/{$currentYear}/devicesAsKind.json")), true)[substr($entireModelUniqueCode, 0, 3)]['subs'][$entireModelUniqueCode]['subs'];
        $scrapedDevicesAsKind = json_decode(file_get_contents(storage_path("app/scraped/scrapedDevicesAsKind.json")), true)[substr($entireModelUniqueCode, 0, 3)]['subs'][$entireModelUniqueCode]['subs'];

        return view('Report.Scraped.scrapedWithEntireModel', [
            'propertyDevicesAsKindAsJson' => json_encode($propertyDevicesAsKind),
            'scrapedDevicesAsKindAsJson' => json_encode($scrapedDevicesAsKind),
        ]);
    }

    /**
     * 超期使用（指定型号）
     * @param string $modelUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function scrapedWithSubModel(string $modelUniqueCode)
    {
        try {
            $now = Carbon::now()->format('Y-m-d');
            $root_dir = storage_path('app/scraped');
            if (!is_dir($root_dir)) return back()->with('danger', '数据不存在');
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode('~', request('date_made_at', "{$now}~{$now}"));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode('~', request('date_created_at', "{$now}~{$now}"));
            list($dateScarpingAtOrigin, $dateScarpingAtFinish) = explode('~', request('date_scarping_at', "{$now}~{$now}"));

            $query_condition = QueryConditionFacade::init($root_dir)
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB();

            $category_unique_code = "";
            $entire_model_unique_code = "";
            $sub_model_unique_code = "";

            if ($modelUniqueCode !== "NONE") {
                $category_unique_code = substr($modelUniqueCode, 0, 3);
                $entire_model_unique_code = strlen($modelUniqueCode) > 4 ? substr($modelUniqueCode, 0, 5) : "";
                $sub_model_unique_code = strlen($modelUniqueCode) > 5 ? $modelUniqueCode : "";
            }

            $query_condition->make(
                strval($category_unique_code),
                strval($entire_model_unique_code),
                strval($sub_model_unique_code),
                strval(request("factory_name")),
                "",
                strval(request("scene_workshop_unique_code")),
                strval(request("station_name")),
                strval(request("status_unique_code"))
            );

            $partSql = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.serial_number',
                    'ei.category_name',
                    'ei.factory_name',
                    'ei.status',
                    'ei.scarping_at',
                    'ei.maintain_station_name',
                    'ei.maintain_location_code',
                    'ei.open_direction',
                    'ei.to_direction',
                    'ei.crossroad_number',
                    'ei.line_name',
                    'ei.said_rod',
                    'ei.model_unique_code',
                    'ei.model_name',
                ])
                ->leftJoin(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->leftJoin(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->leftJoin(DB::raw('entire_models em'), 'pm.entire_model_unique_code', '=', 'em.unique_code')
                ->leftJoin(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->leftJoin(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                ->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->leftJoin(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('ei.made_at', '<>', null)
                ->where('ei.scarping_at', '<', date('Y-m-d 00:00:00'))
                ->where('ei.deleted_at', null)
                ->where('ei.status', '<>', 'SCRAP')
                ->where('pm.deleted_at', null)
                ->where('pc.deleted_at', null)
                ->where('pc.is_main', true)
                ->where('em.deleted_at', null)
                ->where('em.is_sub_model', false)
                ->where('c.deleted_at', null)
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('sc.deleted_at', null)
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where('s.deleted_at', null)
                ->where('s.type', 'STATION')
                ->when(
                    $query_condition->get("current_status_unique_code"),
                    function ($query) use ($query_condition) {
                        return $query->where("ei.status", $query_condition->get("current_status_unique_code"));
                    }
                )
                ->when(
                    $query_condition->get("current_factory_name"),
                    function ($query) use ($query_condition) {
                        return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
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
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->when($modelUniqueCode, function ($query) use ($modelUniqueCode) {
                    switch (strlen($modelUniqueCode)) {
                        case 7:
                            # 通过型号
                            return $query->where('pm.unique_code', $modelUniqueCode);
                        case 5:
                            # 通过类型
                            return $query->where('em.unique_code', $modelUniqueCode);
                        case 3:
                            # 通过种类
                            return $query->where('c.unique_code', $modelUniqueCode);
                    }
                    return $query;
                })
                ->orderBy('ei.scarping_at');

            $entireSql = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.serial_number',
                    'ei.category_name',
                    'ei.factory_name',
                    'ei.status',
                    'ei.scarping_at',
                    'ei.maintain_station_name',
                    'ei.maintain_location_code',
                    'ei.open_direction',
                    'ei.to_direction',
                    'ei.crossroad_number',
                    'ei.line_name',
                    'ei.said_rod',
                    'ei.model_unique_code',
                    'ei.model_name',
                ])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('ei.made_at', '<>', null)
                ->where('ei.scarping_at', '<', date('Y-m-d 00:00:00'))
                ->where('ei.deleted_at', null)
                ->where('ei.status', '<>', 'SCRAP')
                ->where('sm.deleted_at', null)
                ->where('sm.is_sub_model', true)
                ->where('em.deleted_at', null)
                ->where('em.is_sub_model', false)
                ->where('c.deleted_at', null)
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('sc.deleted_at', null)
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where('s.deleted_at', null)
                ->where('s.type', 'STATION')
                ->when(
                    $query_condition->get("current_status_unique_code"),
                    function ($query) use ($query_condition) {
                        return $query->where("ei.status", $query_condition->get("current_status_unique_code"));
                    }
                )
                ->when(
                    $query_condition->get("current_factory_name"),
                    function ($query) use ($query_condition) {
                        return $query->where("ei.factory_name", $query_condition->get("current_factory_name"));
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
                ->when($modelUniqueCode, function ($query) use ($modelUniqueCode) {
                    switch (strlen($modelUniqueCode)) {
                        case 8:
                            # 通过子类
                            return $query->where('sm.unique_code', $modelUniqueCode);
                            break;
                        case 5:
                            # 通过类型
                            return $query->where('em.unique_code', $modelUniqueCode);
                            break;
                        case 3:
                            # 通过种类
                            return $query->where('ei.category_unique_code', $modelUniqueCode);
                            break;
                    }
                })
                ->when(request("use_made_at") == "1", function ($q) {
                    return $q->whereBetween("ei.made_at", explode("~", request("date_made_at")));
                })
                ->when(request("use_created_at") == "1", function ($q) {
                    return $q->whereBetween("ei.created_at", explode("~", request("date_created_at")));
                })
                ->when(request("use_next_fixing_day") == "1", function ($q) {
                    return $q->whereBetween("ei.next_fixing_day", explode("~", request("date_next_fixing_day")));
                })
                ->orderBy('ei.scarping_at');

            if (request('download') == '1') {
                # 下载Excel
                # 下载数据
                $entireInstances = DB::table(DB::raw("({$entireSql->toSql()}) as a"))->mergeBindings($entireSql)->get();

                ExcelWriteHelper::download(function ($excel) use ($entireInstances, $query_condition) {
                    $currentSheet = $excel->getActiveSheet();
                    # 字体颜色
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');

                    # 首行
                    $currentSheet->setCellValueExplicit('A1', '唯一编号', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('B1', '型号', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('C1', '状态', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('D1', '到期时间', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('D1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('E1', '安装位置', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->setCellValueExplicit('F1', '供应商', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $i = 2;
                    foreach ($entireInstances as $entireInstance) {
                        $currentSheet->setCellValueExplicit('A' . $i, $entireInstance->identity_code, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B' . $i, $entireInstance->model_name, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C' . $i, $query_condition->get("statuses")[$entireInstance->status], \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D' . $i, Carbon::parse($entireInstance->scarping_at)->format('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->getStyle('D' . $i)->getFont()->setColor($red);
                        $currentSheet->setCellvalueExplicit('E' . $i, $entireInstance->maintain_station_name . $entireInstance->maintain_location_code, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('F' . $i, $entireInstance->factory_name, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $i++;
                    }

                    # 定义列宽
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(25);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(17);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(25);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(25);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(6))->setWidth(18);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(8))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(9))->setWidth(10);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(10))->setWidth(11);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(11))->setWidth(10);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(12))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(13))->setWidth(13);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(14))->setWidth(15);

                    return $excel;
                }, '超期使用');
            } else {
                $entireInstances = ModelBuilderFacade::unionAll($partSql, $entireSql)->paginate();
//                $entireInstances = DB::table(DB::raw("({$entireSql->toSql()}) as a"))->mergeBindings($entireSql)->paginate();
            }

            return view('Report.Scraped.scrapedWithSub', [
                'entireInstances' => $entireInstances,
                'queryConditions' => $query_condition->toJson(),
                'statuses' => $query_condition->get("statuses"),
                'dateMadeAtOrigin' => $dateMadeAtOrigin,
                'dateMadeAtFinish' => $dateMadeAtFinish,
                'dateCreatedAtOrigin' => $dateCreatedAtOrigin,
                'dateCreatedAtFinish' => $dateCreatedAtFinish,
                'dateScarpingAtOrigin' => $dateScarpingAtOrigin,
                'dateScarpingAtFinish' => $dateScarpingAtFinish,
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
