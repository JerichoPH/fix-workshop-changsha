<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\ExcelInException;
use App\Exceptions\FuncNotFoundException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\NewStationFacade;
use App\Facades\QueryConditionFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\SuPuRuiApi;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\EntireInstanceRequest;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\FixWorkflowProcess;
use App\Model\Maintain;
use App\Model\PivotEntireModelAndPartModel;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Throwable;

/**
 * Class InstanceController
 * @example 01：入库单 02：出库单 03：检修工单
 * @package App\Http\Controllers\Entire
 */
class InstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $now = now()->format('Y-m-d');
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode("~", request("date_made_at", "{$now} 00:00:00~{$now} 23:59:59"));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode("~", request("date_created_at", "{$now} 00:00:00~{$now} 23:59:59"));
            list($dateNextFixingDayOrigin, $dateNextFixingDayFinish) = explode("~", request("date_next_fixing_day", "{$now} 00:00:00~{$now} 23:59:59"));

            $rootDir = storage_path("app/property");
            if (!is_dir($rootDir)) back()->with("danger", "数据不存在");

            $queryCondition = QueryConditionFacade::init($rootDir)
                ->setCategoriesWithDB()
                ->setEntireModelsWithDB()
                ->setSubModelsWithDB();

            $queryCondition->make(
                strval(request("category_unique_code")),
                strval(request("entire_model_unique_code")),
                strval(request("sub_model_unique_code")),
                strval(request("factory_name")),
                strval(request("factory_unique_code")),
                strval(request("scene_workshop_unique_code")),
                strval(str_replace(' ', '+', request("station_name"))),
                strval(request("status_unique_code"))
            );

            # 初始化车间条件
            $stations = null;
            if (request('scene_workshop_unique_code')) $stations = DB::table("maintains as m")->where('m.deleted_at', null)->where('m.parent_unique_code', request('scene_workshop_unique_code'))->pluck('name');
            if (request('station_name')) $stations = [request('station_name')];

            $builderQ = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.open_direction',
                    'ei.said_rod',
                    'ei.last_installed_time',
                    'ei.last_fix_workflow_at',
                    'ei.next_fixing_time',
                    'ei.scarping_at',
                    'ei.model_name',
                    'ei.model_unique_code',
                    'em.unique_code as entire_model_unique_code',
                    'em.category_unique_code as category_unique_code',
                    'ei.status',
                    'ei.fix_cycle_value as ei_fix_cycle_value',
                    'sm.fix_cycle_value as em_fix_cycle_value',
                ])
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->where('ei.deleted_at', null)
                ->where('sm.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('c.deleted_at', null)
                ->when(
                    request('status'),
                    function ($query) {
                        return is_array(request('status')) ?
                            $query->whereIn('ei.status', request('status')) :
                            $query->where('ei.status', request('status'));
                    }
                )
                ->when(
                    request('category_unique_code'),
                    function ($query) {
                        return $query->where('c.unique_code', request('category_unique_code'));
                    }
                )
                ->when(
                    request('entire_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        return $query->where('em.unique_code', request('entire_model_unique_code'));
                    }
                )
                ->when(
                    request('sub_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        return $query->where('sm.unique_code', request('sub_model_unique_code'));
                    }
                )
                ->when(
                    request('factory'),
                    function ($query) {
                        return $query->where('ei.factory_name', request('factory'));
                    }
                )
                ->when(
                    $stations,
                    function ($query) use ($stations) {
                        return $query->whereIn("ei.maintain_station_name", $stations);
                    }
                )
                ->when(
                    request('scene_workshop_unique_code'),
                    function ($query) {
                        return $query->where('sc.unique_code', request('scene_workshop_unique_code'));
                    }
                )
                ->when(
                    request('maintain_location_code'),
                    function ($query) {
                        return request('maintain_location_code_use_indistinct') ?
                            $query->where('ei.maintain_location_code', 'like', '%' . request('maintain_location_code') . '%') :
                            $query->where('ei.maintain_location_code', request('maintain_location_code'));
                    }
                )
                ->when(
                    request('crossroad_number'),
                    function ($query) {
                        return request('crossroad_number_use_indistinct')
                            ? $query->where('crossroad_number', 'like', '%' . request('crossroad_number') . '%')->orWhere('bind_crossroad_number', 'like', '%' . request('crossroad_number') . '%')
                            : $query->where('crossroad_number', request('crossroad_number'))->orWhere('bind_crossroad_number', request('crossroad_number'));
                    }
                )
                ->when(
                    request('use_created_at') == '1',
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('created_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
                            ->orderByDesc('ei.last_fix_workflow_at')
                            ->whereBetween('ei.last_fix_workflow_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('is_scraped') !== 'all',
                    function ($query) {
                        if (request('is_scraped') === 'out') {
                            # 只搜索未超期设备
                            return $query->where('ei.scarping_at', '>', date('Y-m-d'));
                        } elseif (request('is_scraped') === 'in') {
                            # 只搜索超期设备
                            return $query->where('ei.scarping_at', '<', date('Y-m-d'));
                        } else {
                            return $query;
                        }
                    }
                )
                ->orderByDesc('ei.identity_code')
                ->groupBy('ei.identity_code');

            $builderS = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.open_direction',
                    'ei.said_rod',
                    'ei.last_installed_time',
                    'ei.last_fix_workflow_at',
                    'ei.next_fixing_time',
                    'ei.scarping_at',
                    'ei.model_name',
                    'ei.model_unique_code',
                    'em.unique_code as entire_model_unique_code',
                    'em.category_unique_code as category_unique_code',
                    'ei.status',
                    'ei.fix_cycle_value as ei_fix_cycle_value',
                    'em.fix_cycle_value as em_fix_cycle_value',
                ])
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->where('ei.deleted_at', null)
                ->where('pm.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('c.deleted_at', null)
                ->when(
                    request('status'),
                    function ($query) {
                        return is_array(request('status')) ?
                            $query->whereIn('ei.status', request('status')) :
                            $query->where('ei.status', request('status'));
                    }
                )
                ->when(
                    request('category_unique_code'),
                    function ($query) {
                        return $query->where('c.unique_code', request('category_unique_code'));
                    }
                )
                ->when(
                    request('entire_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        return $query->where('em.unique_code', request('entire_model_unique_code'));
                    }
                )
                ->when(
                    request('sub_model_unique_code') and request('category_unique_code'),
                    function ($query) {
                        return $query->where('pm.unique_code', request('sub_model_unique_code'));
                    }
                )
                ->when(
                    request('factory'),
                    function ($query) {
                        return $query->where('ei.factory_name', request('factory'));
                    }
                )
                ->when(
                    $stations,
                    function ($query) use ($stations) {
                        return $query->whereIn("ei.maintain_station_name", $stations);
                    }
                )
                ->when(
                    request('scene_workshop_unique_code'),
                    function ($query) {
                        return $query->where('sc.unique_code', request('scene_workshop_unique_code'));
                    }
                )
                ->when(
                    request('maintain_location_code'),
                    function ($query) {
                        return request('maintain_location_code_use_indistinct') ?
                            $query->where('ei.maintain_location_code', 'like', '%' . request('maintain_location_code') . '%') :
                            $query->where('ei.maintain_location_code', request('maintain_location_code'));
                    }
                )
                ->when(
                    request('crossroad_number'),
                    function ($query) {
                        return request('crossroad_number_use_indistinct')
                            ? $query->where('crossroad_number', 'like', '%' . request('crossroad_number') . '%')->orWhere('bind_crossroad_number', 'like', '%' . request('crossroad_number') . '%')
                            : $query->where('crossroad_number', request('crossroad_number'))->orWhere('bind_crossroad_number', request('crossroad_number'));
                    }
                )
                ->when(
                    request('use_created_at') == '1',
                    function ($query) {
                        list($originAt, $finishAt) = explode('~', request('created_at'));
                        $originAt = Carbon::createFromFormat('Y-m-d', $originAt)->setTime(0, 0, 0)->toDateTimeString();
                        $finishAt = Carbon::createFromFormat('Y-m-d', $finishAt)->setTime(23, 59, 59)->toDateTimeString();
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
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
                        return $query
                            ->orderByDesc('ei.last_fix_workflow_at')
                            ->whereBetween('ei.last_fix_workflow_at', [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request('is_scraped') !== 'all',
                    function ($query) {
                        if (request('is_scraped') === 'out') {
                            # 只搜索未超期设备
                            return $query->where('ei.scarping_at', '>', date('Y-m-d'));
                        } elseif (request('is_scraped') === 'in') {
                            # 只搜索超期设备
                            return $query->where('ei.scarping_at', '<', date('Y-m-d'));
                        } else {
                            return $query;
                        }
                    }
                )
                ->orderByDesc('ei.identity_code')
                ->groupBy('ei.identity_code');

            $entireInstances = ModelBuilderFacade::unionAll($builderS, $builderQ)->paginate();

            return view("Entire.Instance.index", [
                "queryConditions" => $queryCondition->toJson(),
                "currentCategoryUniqueCode" => $queryCondition->get("current_category_unique_code"),
                "statuses" => EntireInstance::$STATUSES,
                "entireInstances" => $entireInstances,
                "dateMadeAtOrigin" => $dateMadeAtOrigin,
                "dateMadeAtFinish" => $dateMadeAtFinish,
                "dateCreatedAtOrigin" => $dateCreatedAtOrigin,
                "dateCreatedAtFinish" => $dateCreatedAtFinish,
                "dateNextFixingDayOrigin" => $dateNextFixingDayOrigin,
                "dateNextFixingDayFinish" => $dateNextFixingDayFinish,
            ]);
        } catch (\Exception $e) {
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '数据异常');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    final public function create()
    {
        try {
            $categories = Category::with([])->get();
            $entire_models = EntireModel::with([])->where('is_sub_model', false)->get();
            /*
             * 必填
             * 种类型
             * 供应商
             * 所编号
             */
            /*
             * 非必填
             * 出厂日期
             * 寿命（年）：计算报废日期，如不填则不计算
             * 上次出所日期
             * 上次安装日期（如果没有上次安装日期，将以上次出所日期为准）：计算下次周期修时间
             * 周期修（年）：计算下次周期修时间，如不填则按照类型周期修为准
             */
            return view('Entire.Instance.create', [
                'categories' => $categories,
                'entire_models' => $entire_models,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * 整件检修入所页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getFixing()
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", request("entireInstanceIdentityCode"))->firstOrFail();
            $accounts = Account::orderByDesc("id")->pluck("nickname", "id");
            return view($this->view("fixing"))
                ->with("accounts", $accounts)
                ->with("entireInstanceIdentityCode", request("entireInstanceIdentityCode"))
                ->with("entireInstance", $entireInstance);
        } catch (ModelNotFoundException $exception) {
            return back()->with("danger", "数据不存在");
        } catch (\Exception $exception) {
            return back()->with("danger", "意外错误");
        }
    }

    final public function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Entire.Instance.{$viewName}";
    }

    /**
     * 整件检修入所
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    final public function postFixing(Request $request)
    {
        try {
            DB::transaction(function () {
                DB::table('entire_instances')
                    ->where('identity_code', request('entireInstanceIdentityCode'))
                    ->update([
                        'updated_at' => date('Y-m-d'),
                        'fix_workflow_serial_number' => null,
                        'status' => 'FIXING',
                        'in_warehouse' => false
                    ]);

                $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN');
                $warehouseReport = [
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'processor_id' => session('account.id'),
                    'processed_at' => date('y-m-d'),
                    'connection_name' => '',
                    'connection_phone' => '',
                    'type' => 'FIXING',
                    'direction' => 'IN',
                    'serial_number' => $newWarehouseReportSerialNumber,
                ];
                DB::table('warehouse_reports')->insert($warehouseReport);

                $warehouseReportEntireInstance = [
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                    'entire_instance_identity_code' => request('entireInstanceIdentityCode'),
                ];
                DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstance);

                EntireInstanceLogFacade::makeOne('入所检修', request('entireInstanceIdentityCode'), 1, "/warehouse/report/{$newWarehouseReportSerialNumber}");
            });

            return Response::make("入所成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 入库单个设备（新入所）
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $newEntireInstanceIdentityCode = WarehouseReportFacade::buyInOnce($request);
            return back()->with("success", '<h3>入所成功&nbsp;&nbsp;[<a href="' . url('search', $newEntireInstanceIdentityCode) . '">点击查看</a>]</h3>');
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with("danger", "数据不存在");
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return back()->withInput()->with("danger", "{$eMsg}<br>{$eCode}<br>{$eLine}<br>{$eFile}");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show(string $entireModelUniqueCode)
    {
        if (request()->has('date')) {
            list($originAt, $finishAt) = explode('~', request('date'));
        } else {
            $originAt = Carbon::now()->startOfMonth()->toDateString();
            $finishAt = Carbon::now()->endOfMonth()->toDateString();
        }

        try {
            $statuses = EntireInstance::$STATUSES;

            $entireInstances = DB::table("entire_instances as ei")
                ->select([
                    "ei.id",
                    "fw.id as fw_id",
                    "ei.identity_code",
                    "ei.model_name",
                    "ei.status",
                    "ei.factory_name",
                    "ei.factory_device_code",
                    "ei.maintain_station_name",
                    "ei.maintain_location_code",
                    "ei.crossroad_number",
                    "ei.traction",
                    "ei.open_direction",
                    "ei.line_name",
                    "ei.said_rod",
                    "ei.is_main",
                    "ei.last_installed_time",
                    "ei.fix_workflow_serial_number",
                    "fw.status as fw_status",
                ])
                ->leftJoin(DB::raw("warehouse_report_entire_instances wre"), "wre.entire_instance_identity_code", "=", "ei.identity_code")
                ->leftJoin(DB::raw("warehouse_reports wr"), "wr.serial_number", "=", "wre.warehouse_report_serial_number")
                ->leftJoin(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "ei.fix_workflow_serial_number")
                ->where("ei.deleted_at", null)
                ->where("ei.status", "<>", "SCRAP")
                ->where(function ($query) use ($entireModelUniqueCode) {
                    $query->where('ei.model_unique_code', $entireModelUniqueCode)
                        ->orWhere('ei.entire_model_unique_code', $entireModelUniqueCode);
                })
                ->when(
                    request("status", null) !== null,
                    function ($query) {
                        return $query->where("ei.status", request("status"));
                    }
                )
                ->when(
                    request("date_type") == "create",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("ei.created_at", [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request("date_type") == "in",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("ei.created_at", [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request("date_type") == "fix",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("fw.created_at", [$originAt, $finishAt]);
                    }
                )
                ->groupBy("ei.id")
                ->orderByDesc("ei.id")
                ->paginate();

            $entireModel = EntireModel::where("unique_code", $entireModelUniqueCode)->firstOrFail(["name", "unique_code", "category_unique_code"]);

            session()->put("currentCategoryUniqueCode", $entireModel->category_unique_code);

            return view("Entire.Instance.show", [
                "entireInstances" => $entireInstances,
                "statuses" => $statuses,
                "entireModel" => $entireModel,
                "originAt" => $originAt,
                "finishAt" => $finishAt,
            ]);
        } catch (\Exception $exception) {
            return back()->with("danger", $exception->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $identityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($identityCode)
    {
        try {
            $entireInstance = EntireInstance::with([
                'Category',
                'EntireModel',
                'PartInstances',
                'PartInstances.PartModel',
                'FixWorkflow',
                'Station',
                'Station.Parent',
                'WarehouseReportByOut',
            ])
                ->where('identity_code', $identityCode)
                ->firstOrFail();

            # 获取同所编号数量
            $sameSerialNumberCount = $entireInstance->serial_number ? EntireInstance::with([])->where('serial_number', $entireInstance->serial_number)->where('deleted_at', null)->count() : 0;

            # 获取同组合位置数量
            $sameMaintainLocationCodeCount = $entireInstance->maintain_location_code ? EntireInstance::with([])
                ->where('maintain_location_code', $entireInstance->maintain_location_code)
                ->where('maintain_station_name', $entireInstance->maintain_station_name)
                ->count() : 0;

            # 获取同道岔号数量
            $sameCrossroadNumberCount = $entireInstance->crossroad_number ? EntireInstance::with([])
                ->where('crossroad_number', $entireInstance->crossroad_number)
                ->where('maintain_station_name', $entireInstance->maintain_station_name)
                ->count() : 0;

            # 获取现场车间、站场
            $stations = DB::table('maintains as s')
                ->join(DB::raw('maintains sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                ->select([
                    's.name as station_name',
                    's.unique_code as station_unique_code',
                    'sw.name as scene_workshop_name',
                    'sw.unique_code as scene_workshop_unique_code',
                ])
                ->where('s.deleted_at', null)
                ->where('sw.deleted_at', null)
                ->get();

            if ($entireInstance->category_unique_code) switch ($entireInstance->category_unique_code) {
                case 'S03':
                    $work_area_id = 1;
                    break;
                case 'Q01':
                    $work_area_id = 2;
                    break;
                default:
                    $work_area_id = 3;
                    break;
            }

            $fixers = Account::with([])->where('work_area', $work_area_id)->pluck('nickname', 'id');
            $checkers = Account::with([])->where('work_area', $work_area_id)->pluck('nickname', 'id');

            # 获取最后一次检修人
            $fixer = FixWorkflowProcess::with([])->select('processor_id')->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'FIX_AFTER')->first();
            # 获取最后一次验收人
            $checker = FixWorkflowProcess::with([])->select('processor_id')->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'CHECKED')->first();

            # 供应商列表
            $statisticsRootDir = storage_path('app/basicInfo');
            $factories = file_exists("{$statisticsRootDir}/factories.json") ?
                array_pluck(json_decode(file_get_contents("{$statisticsRootDir}/factories.json")), 'name') :
                Factory::with([])->get()->pluck('name');

            return view('Entire.Instance.edit', [
                'entireInstance' => $entireInstance,
                'statuses' => EntireInstance::$STATUSES,
                'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                'fixers' => $fixers,
                'checkers' => $checkers,
                'fixer' => $fixer->processor_id ?? 0,
                'checker' => $checker->processor_id ?? 0,
                'sameSerialNumberCount' => $sameSerialNumberCount ?? 0,
                'sameMaintainLocationCodeCount' => $sameMaintainLocationCodeCount ?? 0,
                'sameCrossroadNumberCount' => $sameCrossroadNumberCount ?? 0,
                'factories' => $factories,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with("danger", $exception->getMessage());
        } catch (\Exception $exception) {
            return back()->with("danger", $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $identity_code
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function update(Request $request, $identity_code)
    {
        try {
            $v = Validator::make($request->all(), EntireInstanceRequest::$RULES, EntireInstanceRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $entireInstance = EntireInstance::with(['EntireModel', 'FixWorkflow'])
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            $entireInstance->fill($request->except('last_installed_at', 'fixer', 'checker'));
            if ($request->get('last_installed_at')) {
                $entireInstance->last_installed_time = $request->get('last_installed_at') ? Carbon::parse($request->get('last_installed_at'))->timestamp : null;
            }

            if ($request->get('next_fixing_day')) {
                $next_fixing_day = Carbon::parse($request->get('next_fixing_day'));
                $entireInstance->next_fixing_time = $next_fixing_day->timestamp;
                $entireInstance->next_fixing_month = $next_fixing_day->firstOfMonth()->toDateString();
                $entireInstance->next_fixing_day = $next_fixing_day->toDateString();
            }

            # 如果有检修人和检测人，则创建空检测单
            if ($request->get('fixer') && $request->get('checker') && $request->get('last_fix_workflow_at')) {
                DB::table('fix_workflows')
                    ->insert([
                        'created_at' => $request->get('last_fix_workflow_at'),
                        'updated_at' => $request->get('last_fix_workflow_at'),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'status' => 'FIXED',
                        'processor_id' => $request->get('acceptor'),
                        'serial_number' => $new_fix_workflow_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW'),
                        'stage' => 'CHECKED',
                        'type' => 'FIX',
                    ]);
                $insert['fix_workflow_serial_number'] = $new_fix_workflow_sn;
                # 新建检测人
                DB::table('fix_workflow_processes')
                    ->insert([
                        'created_at' => $request->get('last_fix_workflow_at'),
                        'updated_at' => $request->get('last_fix_workflow_at'),
                        'fix_workflow_serial_number' => $new_fix_workflow_sn,
                        'stage' => 'FIX_AFTER',
                        'type' => 'ENTIRE',
                        'auto_explain' => '手工修改检测人',
                        'serial_number' => $new_fix_workflow_processes_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS'),
                        'numerical_order' => 1,
                        'is_allow' => 1,
                        'processor_id' => $request->get('fixer'),
                        'processed_at' => $request->get('last_fix_workflow_at'),
                    ]);
                # 新建验收人
                DB::table('fix_workflow_processes')
                    ->insert([
                        'created_at' => $request->get('last_fix_workflow_at'),
                        'updated_at' => $request->get('last_fix_workflow_at'),
                        'fix_workflow_serial_number' => $new_fix_workflow_sn,
                        'stage' => 'CHECKED',
                        'type' => 'ENTIRE',
                        'auto_explain' => '手工修改验收人',
                        'serial_number' => $new_fix_workflow_processes_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS'),
                        'numerical_order' => 1,
                        'is_allow' => 1,
                        'processor_id' => $request->get('checker'),
                        'processed_at' => $request->get('last_fix_workflow_at'),
                    ]);
                $entireInstance->fix_workflow_serial_number = $new_fix_workflow_sn;
                $entireInstance->last_fix_workflow_at = $request->get('last_fix_workflow_at');
                $fixer = Account::with([])->where('id', $request->get('fixer'))->first();
                if (!$fixer) return response()->json(['message' => '检修人不存在'], 403);
                $checker = Account::with([])->where('id', $request->get('checker'))->first();
                if (!$checker) return response()->json(['message' => '验收人不存在'], 403);
                $entireInstance->fixer_name = $fixer->nickname;
                $entireInstance->fixed_at = $request->get('last_fix_workflow_at');
                $entireInstance->checker_name = $checker->nickname;
                $entireInstance->checked_at = $request->get('last_fix_workflow_at');
            } elseif ($request->get('fixer') || $request->get('checker') || $request->get('last_fix_workflow_at')) {
                return response()->json(['message' => '检修人、验收人、检修时间必须都选择']);
            } else {
                $entireInstance->fixer_name = '';
                $entireInstance->fixed_at = null;
                $entireInstance->checker_name = '';
                $entireInstance->checked_at = null;
                $entireInstance->spot_checker_name = '';
                $entireInstance->spot_checked_at = null;
            }
            $entireInstance->saveOrFail();

            return response()->json(['message' => '编辑成功']);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $identityCode
     * @return \Illuminate\Http\Response
     */
    final public function destroy($identityCode)
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", $identityCode)->firstOrFail();
            $entireInstance->fill(["status" => "SCRAP"])->saveOrFail();

            return Response::make("报废成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 报废
     * @param $identityCode
     * @return \Illuminate\Http\Response
     */
    final public function scrap($identityCode)
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", $identityCode)->firstOrFail();
            $entireInstance->fill(["status" => "SCRAP"])->saveOrFail();

            return Response::make("报废成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 入所页面
     * @param $entireInstanceIdentityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getFixingIn($entireInstanceIdentityCode)
    {
        $accounts = DB::table("accounts")
            ->orderByDesc("id")
            ->where("deleted_at", null)
            ->where(function ($query) {
                $query->where("workshop_code", env("ORGANIZATION_CODE"))->orWhere("workshop_code", null);
            })
            ->pluck("nickname", "id");
        return view($this->view("fixingIn_ajax"))
            ->with("entireInstanceIdentityCode", $entireInstanceIdentityCode)
            ->with("accounts", $accounts);
    }

    /**
     * 入所
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return \Illuminate\Http\Response
     */
    final public function postFixingIn(Request $request, string $entireInstanceIdentityCode)
    {
        try {
            # 获取检修单数据
            WarehouseReportFacade::inOnce($request, EntireInstance::where("identity_code", $entireInstanceIdentityCode)->firstOrFail());
            return Response::make("入所成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
    }

    /**
     * 出库安装页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function getInstall()
    {
        try {
            $organizationCode = env("ORGANIZATION_CODE");
            $entireInstance = EntireInstance::where("identity_code", request("entireInstanceIdentityCode"))->firstOrFail();
            # 获取该检修车间下的现场车间
            $workshops = Maintain::where("parent_unique_code", $organizationCode)->where("type", "SCENE_WORKSHOP")->get();
            # 获取该检修车间下的人员
            //            $accounts = DB::select(DB::raw("select * from accounts where workshop_code is null or workshop_code="{$organizationCode}""));
            $accounts = DB::table("accounts")->where("deleted_at", null)->where(function ($query) use ($organizationCode) {
                $query->where("workshop_code", null)->orWhere("workshop_code", $organizationCode);
            })->get();

            return view($this->view("install_ajax"))
                ->with("entireInstance", $entireInstance)
                ->with("workshops", $workshops)
                ->with("accounts", $accounts);
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 出库安装
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return \Illuminate\Http\Response
     */
    final public function postInstall(Request $request, string $entireInstanceIdentityCode)
    {
        try {
            $entireInstance = EntireInstance::with(["EntireModel"])->where("identity_code", $entireInstanceIdentityCode)->firstOrFail();
            $ret = WarehouseReportFacade::outOnce($request, $entireInstance);
            //            return response()->json($ret);

            return Response::make("出库成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 批量导入页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatch()
    {
        $accounts = Account::orderByDesc("id")->pluck("nickname", "id");
        return view("Entire.Instance.batch")
            ->with("accounts", $accounts);
    }

    /**
     * 批量导入
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Exception
     */
    final public function postBatch(Request $request)
    {
        if (!$request->hasFile("file")) return back()->with("error", "上传文件不存在");

        try {
            DB::beginTransaction();

            list($entireInstances, $partInstances, $suPuRuiInputs)
                = \App\Facades\EntireInstanceFacade::batchFromExcelWithNew($request, "file", "0");
            $entireInstanceCount = count($entireInstances);
            DB::table("entire_instances")->insert($entireInstances);  # 导入设备实例
            $entireInstanceIdentityCodes = collect($entireInstances)->pluck("identity_code")->toArray();  # 获取全部整件实例的唯一编号
            //            if ($request->get("auto_insert_fix_workflow")) FixWorkflowFacade::batchByEntireInstanceIdentityCodes($entireInstanceIdentityCodes);  # 如果需要自动创建检修单
            # 创建入所记录
            WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                $entireInstanceIdentityCodes,
                $request->get("processor_id"),
                $request->get("processed_at"),
                "BUY_IN",
                $request->get("connection_name"),
                $request->get("connection_phone")
            );

            # 如果存在部件，导入部件部分
            if ($request->get("has_part") && $partInstances != null) DB::table("part_instances")->insert($partInstances);

            # 调用速普瑞接口
            if (env("SUPURUI_API", 0) == 1) {
                if (array_key_exists("S", $suPuRuiInputs)) $suPuRuiInputResult_S = SuPuRuiApi::debug(true)->returnType("x2a")->insertEntireInstances_S($suPuRuiInputs["S"]);
                if (array_key_exists("Q", $suPuRuiInputs)) $suPuRuiInputResult_Q = SuPuRuiApi::debug(true)->returnType("x2a")->insertEntireInstances_Q($suPuRuiInputs["Q"]);
            }

            DB::commit();
            return back()->with("success", "成功导入：{$entireInstanceCount}条数据");
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            return $request->ajax() ?
                response()->make(env("APP_DEBUG") ?
                    "数据不存在：" . $exception->getMessage() :
                    "数据不存在", 404) :
                back()->withInput()->with("danger", env("APP_DEBUG") ?
                    "数据不存在：" . $exception->getMessage() :
                    "数据不存在");
        } catch (\Exception $exception) {
            DB::rollback();
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            //            dd("{$eMsg}\r\n{$eFile}\r\n{$eLine}");
            return $request->ajax() ?
                response()->make(env("APP_DEBUG") ?
                    "{$eMsg}\r\n{$eFile}\r\n{$eLine}" :
                    "意外错误", 500) :
                back()->withInput()->with("danger", env("APP_DEBUG") ?
                    "{$eMsg}<br>{$eFile}<br>{$eLine}" :
                    "意外错误");
        }
    }

    /**
     * 旧编号批量转新编号页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getOldNumberToNew()
    {
        $entireInstances = EntireInstance::with(["Category"])->where("old_number", null)->paginate();
        return view($this->view("oldNumberToNew"))
            ->with("entireInstances", $entireInstances);
    }

    /**
     * 旧编号批量转新编号
     */
    final public function postOldNumberToNew()
    {
        $entireInstances = EntireInstance::with(["Category"])->where("old_number", null)->get();
        $count = \App\Facades\EntireInstanceFacade::makeNewCode($entireInstances);
        return response()->make("成功转码：" . $count);
    }

    /**
     * 通过类型或子类获取供应商列表
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getFactoryByEntireModelUniqueCode(string $entireModelUniqueCode)
    {
        return response()->json(
            DB::table("pivot_entire_model_and_factories")
                ->join("factories", "name", "=", "factory_name")
                ->where("entire_model_unique_code", $entireModelUniqueCode)
                ->pluck("factories.name")
        );
    }

    /**
     * 批量上传页面
     */
    final public function getUpload()
    {
        try {
            $workAreas = array_flip([
                0 => '全部',
                1 => '转辙机工区',
                2 => '继电器工区',
                3 => '综合工区',
            ]);
            $currentWorkArea = $workAreas[session('account.work_area')];
            $downloadTypes = [
                '0101' => '转辙机上道、备品',
                '0102' => '转辙机成品、待修',
                '0201' => '继电器、综合上道、备品',
                '0202' => '继电器、综合成品、待修',
            ];

            # 下载
            if (request('download')) {
                # 下载Excel模板
                ExcelWriteHelper::download(function ($excel) use ($downloadTypes) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    # 字体颜色
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');

                    # 转辙机工区（转辙机上道、备品）
                    $makeExcel0101 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '车站*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('H1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('I1', '出所日期/上道日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('I1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('J1', '道岔号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('J1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('K1', '开向*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('K1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('L1', '线制*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('L1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('M1', '表示杆特征*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('M1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('N1', '道岔类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('N1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('O1', '转辙机组合类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('O1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('P1', '防挤压保护罩*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('P1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('Q1', '牵引*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('Q1')->getFont()->setColor($red);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('F2', 'ZD6-D', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', '常德', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('J2', '4#', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K2', '左/右', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('L2', '4线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('M2', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('N2', '道岔类型', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('O2', '转辙机组合类型', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('P2', '是/否', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('Q2', '2牵引', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 转辙机工区（转辙机成品、待修）
                    $makeExcel0102 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '检修日期', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I1', '线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('J1', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K1', '防挤压保护罩', \PHPExcel_Cell_DataType::TYPE_STRING);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('F2', 'ZD6-D', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('I2', '4线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('J2', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K2', '是/否', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 继电器综合（继电器综合上道、备品）
                    $makeExcel0201 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '车站*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('H1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('I1', '出所日期/上道日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('I1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('J1', '上道位置*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('J1')->getFont()->setColor($red);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('F2', 'JWXC-1000', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', '常德', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('J2', '1-2-3-4', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 继电器综合（继电器综合成品、待修）
                    $makeExcel0202 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '检修日期', \PHPExcel_Cell_DataType::TYPE_STRING);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('F2', 'JWXC-1000', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);

                        return $excel;
                    };

                    # 定义列宽
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(17);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(17);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(14);
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

                    $func = "makeExcel" . request('download');
                    return $$func();
                }, "批量导入模板(" . $downloadTypes[request('download')] . ")");
            }
            # 上传
            $accounts = Account::with([])
                ->when(session('account.work_area'), function ($query) use ($currentWorkArea) {
                    $query->where('work_area', $currentWorkArea);
                })
                ->get();
            return view('Entire.Instance.upload', [
                'currentWorkArea' => $currentWorkArea,
                'accounts' => $accounts,
            ]);
        } catch (\Exception $e) {
            //            dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return redirect('/warehouse/report/scanBatch?direction=IN')->with('danger', '意外错误');
        }
    }

    /**
     * 批量上传
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUpload(Request $request)
    {
        try {
            # 导入类型
            switch ($request->get('type')) {
                case 'INSTALLED':
                case 'INSTALLING':
                    $type = '01';
                    break;
                case 'FIXED':
                case 'FIXING':
                    $type = '02';
                    break;
                default:
                    throw new ExcelInException("导入类型错误");
                    break;
            }
            $factoryNames = Factory::with([])->pluck('name')->toArray();  # 供应商列表
            $r = 1;  # 当前行
            $entireInstanceLogs = [];

            # 转辙机上道、备品
            $insert0101 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 车站* 出所日期/上道日期* 道岔号* 开向* 线制* 表示杆特征* 道岔类型* 转辙机组合类型* 防挤压保护罩* 牵引*
                        list(
                            $factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $maintainStationName,
                            $lastOutAt, $crossroadNumber, $openDirection, $lineName, $saidRod, $crossroadType, $pointSwitchGroupType, $extrusionProtect, $traction
                            ) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('part_models as pm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'pm.unique_code as mu',
                                'pm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('pm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('pm.name', $modelName)
                            ->where('em.is_sub_model', true)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$lastOutAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($lastOutAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($lastOutAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }
                        if (!$maintainStationName) throw new ExcelInException("第{$r}行错误：车站不能为空");
                        $maintain = DB::table('maintains as s')
                            ->select(['sc.name as scn', 'sc.unique_code as scu', 's.name as sn', 's.unique_code as su'])
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                            ->where('s.deleted_at', null)
                            ->where('sc.deleted_at', null)
                            ->where('s.name', $maintainStationName)
                            ->where('s.type', 'STATION')
                            ->where('sc.type', 'SCENE_WORKSHOP')
                            ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                            ->first();
                        if (!$maintain) throw new ExcelInException("第{$r}行错误：没有找到车站");

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->emu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $lastOutAt,
                            'last_installed_time' => $lastInstalledTime,
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'crossroad_number' => $crossroadNumber,
                            'open_direction' => $openDirection,
                            'line_name' => $lineName,
                            'said_rod' => $saidRod,
                            'crossroad_type' => $crossroadType,
                            'point_switch_group_type' => $pointSwitchGroupType,
                            'extrusion_protect' => $extrusionProtect == '是',
                            'traction' => $traction,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                            'maintain_workshop_name' => $maintain->scn,
                            'maintain_station_name' => $maintain->sn,
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::getNextCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；到期日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            # 转辙机成品、待修
            $insert0102 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 检修日期 线制 表示杆特征 防挤压保护罩
                        list(
                            $factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName,
                            $cycleFixYear, $fixedAt, $lineName, $saidRod, $extrusionProtect
                            ) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $partModel = DB::table('part_models as pm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'pm.unique_code as mu',
                                'pm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('pm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('pm.name', $modelName)
                            ->where('em.is_sub_model', true)
                            ->first();
                        if (!$partModel) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($fixedAt) {
                            # 计算下次周期修时间
                            $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($partModel->cycle_fix_year ? $partModel->cycle_fix_year : 0);
                            if ($cycleFixYear) {
                                $time = Carbon::parse($fixedAt)->addYears($cycleFixYear);
                                $nextFixingTime = $time->timestamp;
                                $nextFixingMonth = $time->format('Y-m-d');
                                $nextFixingDay = $time->format('Y-m-d');
                            }
                        }
                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $partModel->mu,
                            'model_name' => $partModel->mn,
                            'entire_model_unique_code' => $partModel->emu,
                            'category_unique_code' => $partModel->cu,
                            'category_name' => $partModel->cn,
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'line_name' => $lineName,
                            'said_rod' => $saidRod,
                            'extrusion_protect' => $extrusionProtect == '是',
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                        ];

                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::getNextCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；到期日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];
                        return $entireInstance;
                    });
            };

            # 继电器上道、备品
            $insert0201 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 出所日期/上道日期* 上道位置*
                        list($factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $maintainStationName, $lastOutAt, $maintainLocationCode) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('entire_models as sm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'sm.unique_code as mu',
                                'sm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('sm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.is_sub_model', false)
                            ->where('sm.name', $modelName)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$lastOutAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($lastOutAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($lastOutAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }
                        if (!$maintainStationName) throw new ExcelInException("第{$r}行错误：车站不能为空");
                        $maintain = DB::table('maintains as s')
                            ->select(['sc.name as scn', 'sc.unique_code as scu', 's.name as sn', 's.unique_code as su'])
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                            ->where('s.deleted_at', null)
                            ->where('sc.deleted_at', null)
                            ->where('s.name', $maintainStationName)
                            ->where('s.type', 'STATION')
                            ->where('sc.type', 'SCENE_WORKSHOP')
                            ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                            ->first();
                        if (!$maintain) throw new ExcelInException("第{$r}行错误：没有找到车站");
                        if (!$maintainLocationCode) throw new ExcelInException("第{$r}行错误：上道位置不能为空");

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->mu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $lastOutAt,
                            'last_installed_time' => $lastInstalledTime,
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                            'maintain_workshop_name' => $maintain->scn,
                            'maintain_station_name' => $maintain->sn,
                            'maintain_location_code' => $maintainLocationCode,
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::getNextCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；到期日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            # 继电器成品、待修
            $insert0202 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 出所日期/上道日期* 上道位置*
                        list($factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $fixedAt) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('entire_models as sm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'sm.unique_code as mu',
                                'sm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('sm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.is_sub_model', false)
                            ->where('sm.name', $modelName)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$fixedAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($fixedAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($fixedAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->mu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $fixedAt,
                            'last_installed_time' => $lastInstalledTime,
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::getNextCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；到期日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            $func = "insert{$request->get('workArea')}{$type}";
            $excel = $$func();
            if ($excel['fail']) throw new ExcelInException("Excel中存在：" . count($excel['fail']) . "条错误");

            DB::beginTransaction();
            DB::table('entire_instances')->insert($excel['success']);  # 写入设备
            if ($entireInstanceLogs) DB::table('entire_instance_logs')->insert($entireInstanceLogs);  # 写入日志
            $entireInstanceLogs = [];
            DB::commit();

            return back()->with('success', '成功写入：' . count($excel['success']) . '条');
        } catch (ExcelInException $e) {
            return back()->withInput()->with('danger', $e->getMessage());
        } catch (\Throwable $th) {
            return CommonFacade::ddExceptionWithAppDebug($th);
        }
    }

    /**
     * 批量删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postDelete(Request $request)
    {
        try {
            $deletedCount = EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identityCodes'))
                ->delete();
            return response()->json(['message' => "成功删除{$deletedCount}条"]);
        } catch (\Exception $e) {
            return response()->json(['message' => '异常错误', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 回收站
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getTrashed()
    {
        try {
            $entireInstances = EntireInstance::with([])
                ->onlyTrashed()
                ->orderByDesc('deleted_at')
                ->paginate();

            return view('Entire.Instance.trashed', [
                'entireInstances' => $entireInstances,
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 恢复删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postRefresh(Request $request)
    {
        try {
            $refreshCount = EntireInstance::with([])->whereIn('identity_code', $request->get('identityCodes'))->restore();
            return response()->json(['message' => "成功恢复{$refreshCount}条"]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 下载批量修改设备模板
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getDownloadUploadEditDeviceExcelTemplate()
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', request('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            return EntireInstanceFacade::downloadUploadEditDeviceExcelTemplate($work_area);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量编辑设备页面
     */
    final public function getUploadEditDevice()
    {
        try {
            $work_areas = WorkArea::with([])->get();
            $accounts = Account::with([])
                ->where('workshop_code', env('ORGANIZATION_CODE'))
                ->where('work_area_unique_code', session('account.work_area_by_unique_code.unique_code'))
                ->get();

            return view('Entire.Instance.uploadEditDevice', [
                'accounts' => $accounts,
                'work_areas' => $work_areas,
            ]);
        } catch (Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 上传批量编辑设备
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadEditDevice(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

            $work_area = WorkArea::with([])->where('unique_code', $request->get('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            return EntireInstanceFacade::uploadEditDevice($request, $work_area->type);
        } catch (ExcelInException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量修改
     * @param Request $request
     * @return array
     */
    final public function putUpdateBatch(Request $request)
    {
        try {
            if (!$request->get('identity_codes')) return JsonResponseFacade::errorValidate('请勾选设备');
            $params = collect($request->except('identity_codes'))->filter(function ($value) {
                return !empty($value);
            });
            if ($params->isEmpty()) return JsonResponseFacade::errorValidate('没有需要修改的内容');
            if ($params->get('scene_workshop_unique_code')) {
                $scene_workshop = Maintain::with([])->where('type', 'SCENE_WORKSHOP')->where('unique_code', $params->get('scene_workshop_unique_code'))->first();
                if (!$scene_workshop) return JsonResponseFacade::errorValidate('所选现场车间不存在');
                $params->forget('scene_workshop_unique_code');
                $params['maintain_workshop_name'] = $scene_workshop->name;
            }
            if ($params->get('maintain_station_unique_code')) {
                $station = Maintain::with([])->where('type', 'STATION')->where('unique_code', $params->get('maintain_station_unique_code'))->first();
                if (!$station) return JsonResponseFacade::errorValidate('所选车站不存在');
                $params->forget('maintain_station_unique_code');
                $params['maintain_station_name'] = $station->name;
            }
            if ($params->get('last_installed_time')) {
                $params['last_installed_time'] = strtotime($params->get('last_installed_time'));
            }
            if ($params->get('next_fixing_time')) {
                $params['next_fixing_day'] = $params->get('next_fixing_time');
                $params['next_fixing_time'] = strtotime($params->get('next_fixing_time'));
                $params['next_fixing_month'] = Carbon::createFromTimestamp($params->get('next_fixing_time'))->startOfMonth()->format('Y-m-d');
            }
            if ($params->get('scraping_at')) {
                $params['scarping_at'] = $params->get('scraping_at');
                $params->forget('scraping_at');
            }
            DB::begintransaction();
            // 执行批量修改
            EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identity_codes'))
                ->update(array_merge(['updated_at' => now(),], $params->toArray()));
            DB::commit();

            return JsonResponseFacade::ok('批量修改成功');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

}
