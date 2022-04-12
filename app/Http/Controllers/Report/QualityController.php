<?php

namespace App\Http\Controllers\Report;

use App\Facades\ReportFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\FileSystem;
use Jericho\TextHelper;

class QualityController extends Controller
{
    private $_organizationCode = null;
    private $_organizationName = null;
    private $_qualityDir = '';
    private $_qualityTypeDir = '';
    private $_deviceDir = '';
    private $_propertyDir = '';
    private $_basicInfoDir = '';
    private $_quarters = [];
    private $_current_year = '';


    public function __construct()
    {
        $this->_organizationCode = env('ORGANIZATION_CODE');
        $this->_organizationName = env('ORGANIZATION_NAME');
        $this->_qualityDir = 'app/quality/breakdownDevice';
        $this->_qualityTypeDir = 'app/quality/breakdownType';
        $this->_basicInfoDir = 'app/basicInfo';
        $this->_deviceDir = 'app/basicInfo/deviceTotal';
        $this->_propertyDir = 'app/property';

        $this->_quarters = [
            '1季度' => 'Q1',
            '2季度' => 'Q2',
            '3季度' => 'Q3',
            '4季度' => 'Q4',
        ];
        $this->_current_year = date('Y');
    }

    /**
     * 质量报告
     * @param Request $request
     * @return Factory|Application|RedirectResponse|Redirector|View|string
     */
    final public function quality(Request $request)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityYear = $dirs['qualityYear'];
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $qualityTypeDir = $dirs['qualityTypeDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityYearList = $dirs['qualityYearList'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $deviceWithCategories = $file->setPath($deviceDir)->join('devicesAsKind.json')->fromJson();
            $breakdownWithCategories = $file->setPath($qualityDir)->join('kind.json')->fromJson();
            $deviceWithMaintains = $file->setPath($deviceDir)->join('devicesAsMaintain.json')->fromJson();
            $breakdownWithMaintains = $file->setPath($qualityDir)->join('maintain.json')->fromJson();
            $breakdownTypeWithCategories = $file->setPath($qualityTypeDir)->join('kind.json')->fromJson();

            return view("Report.Quality.quality", [
                'qualityDateType' => $qualityDateType,
                'qualityYearList' => $qualityYearList,
                'qualityYear' => $qualityYear,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'deviceWithCategories' => TextHelper::toJson($deviceWithCategories),
                'breakdownWithCategories' => TextHelper::toJson($breakdownWithCategories),
                'deviceWithMaintains' => TextHelper::toJson($deviceWithMaintains),
                'breakdownWithMaintains' => TextHelper::toJson($breakdownWithMaintains),
                'breakdownTypeWithCategories' => TextHelper::toJson($breakdownTypeWithCategories),
            ]);
        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 获取质量报告所需路径
     * @param string $qualityDateType
     * @return array
     * @throws Exception
     */
    public function getQualityDirs(string $qualityDateType)
    {
        $qualityDate = '';
        $qualityDir = '';
        $qualityTypeDir = '';
        $deviceDir = '';
        $propertyDir = '';
        $qualityYearList = [];
        $qualityDateList = [];
        $file = FileSystem::init('');
        if ($qualityDateType == 'year') {
            $qualityYear = request("qualityYear", date("Y"));
            $year = $qualityDate = request("qualityDate", date("Y"));
            $qualityYearList = $file->setPath(storage_path($this->_qualityDir))->join("yearList.json")->fromJson();
            $qualityDir = storage_path($this->_qualityDir . "/{$year}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$year}");
            $deviceDir = storage_path($this->_deviceDir . "/{$year}");
            $propertyDir = storage_path($this->_propertyDir . "/{$year}");
        }
        if ($qualityDateType == 'month') {
            $qualityYear = request("qualityYear", date("Y"));
            $qualityDateList = $file->setPath(storage_path($this->_qualityDir))->join("dateList.json")->fromJson();
            $qualityDate = request("qualityDate", date("Y-m"));
            list($year, $month) = explode("-", $qualityDate);
            $qualityDir = storage_path($this->_qualityDir . "/{$year}/{$year}-{$month}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$year}/{$year}-{$month}");
            $deviceDir = storage_path($this->_deviceDir . "/{$year}/{$year}-{$month}");
            $propertyDir = storage_path($this->_propertyDir . "/{$year}/{$year}-{$month}");
        }
        if ($qualityDateType == 'quarter') {
            $qualityYear = request("qualityYear", date("Y"));
            $qualityYearList = $file->setPath(storage_path($this->_qualityDir))->join("yearList.json")->fromJson();
            $qualityDate = request("qualityDate", ceil(date('n') / 3) . '季度');
            $qualityDateList = array_keys($this->_quarters);
            $qualityDir = storage_path($this->_qualityDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $deviceDir = storage_path($this->_deviceDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $propertyDir = storage_path($this->_propertyDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
        }
        return [
            'qualityYear' => $qualityYear,
            'qualityDate' => $qualityDate,
            'qualityDir' => $qualityDir,
            'qualityTypeDir' => $qualityTypeDir,
            'deviceDir' => $deviceDir,
            'propertyDir' => $propertyDir,
            'qualityYearList' => $qualityYearList,
            'qualityDateList' => $qualityDateList,
            'file' => $file
        ];

    }

    /**
     * 质量报告 - 种类
     * @param Request $request
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityCategory(Request $request, string $categoryUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityYear = $dirs['qualityYear'];
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $propertyDir = $dirs['propertyDir'];
            $qualityYearList = $dirs['qualityYearList'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $kinds = $file->setPath(storage_path($this->_basicInfoDir))->join('kinds.json')->fromJson();
            $deviceWithCategories = $file->setPath($propertyDir)->join('devicesAsKind.json')->fromJson();
            $breakdownWithCategories = $file->setPath($qualityDir)->join('kind.json')->fromJson();
            $deviceWithFactories = $deviceWithCategories[$categoryUniqueCode]['factories'] ?? [];
            $breakdownWithFactories = $breakdownWithCategories[$categoryUniqueCode]['factories'] ?? [];

            return view('Report.Quality.qualityCategory', [
                'categoryUniqueCode' => $categoryUniqueCode,
                'categoryName' => $kinds[$categoryUniqueCode]['name'],
                'qualityDateType' => $qualityDateType,
                'qualityYearList' => $qualityYearList,
                'qualityYear' => $qualityYear,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'deviceWithFactories' => TextHelper::toJson($deviceWithFactories),
                'breakdownWithFactories' => TextHelper::toJson($breakdownWithFactories),
            ]);
        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 现场车间
     * @param Request $request
     * @param string $sceneWorkshopUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualitySceneWorkshop(Request $request, string $sceneWorkshopUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $stations = $file->setPath(storage_path($this->_basicInfoDir))->join('stations.json')->fromJson();
            $deviceWithMaintains = $file->setPath($deviceDir)->join('devicesAsMaintain.json')->fromJson();
            $breakdownWithMaintains = $file->setPath($qualityDir)->join('maintain.json')->fromJson();
            $deviceWithStations = $deviceWithMaintains[$sceneWorkshopUniqueCode]['subs'] ?? [];
            $breakdownWithStations = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['subs'] ?? [];
            $deviceWithFactories = $deviceWithMaintains[$sceneWorkshopUniqueCode]['factories'] ?? [];
            $breakdownWithFactories = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualitySceneWorkshop", [
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'sceneWorkshopName' => $stations[$sceneWorkshopUniqueCode]['name'],
                'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
                'deviceWithStations' => TextHelper::toJson($deviceWithStations),
                'breakdownWithStations' => TextHelper::toJson($breakdownWithStations),
                'deviceWithFactories' => TextHelper::toJson($deviceWithFactories),
                'breakdownWithFactories' => TextHelper::toJson($breakdownWithFactories),
            ]);

        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 现场车间 - 车站
     * @param Request $request
     * @param string $sceneWorkshopUniqueCode
     * @param string $stationUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityStation(Request $request, string $sceneWorkshopUniqueCode, string $stationUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $stations = $file->setPath(storage_path($this->_basicInfoDir))->join('stations.json')->fromJson();
            $deviceWithMaintains = $file->setPath($deviceDir)->join('devicesAsMaintain.json')->fromJson();
            $breakdownWithMaintains = $file->setPath($qualityDir)->join('maintain.json')->fromJson();
            $deviceWithFactories = $deviceWithMaintains[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['factories'] ?? [];
            $breakdownWithFactories = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualityStation", [
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'sceneWorkshopName' => $stations[$sceneWorkshopUniqueCode]['name'],
                'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
                'stationUniqueCode' => $stationUniqueCode,
                'stationName' => $stations[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['name'],
                'deviceWithFactories' => TextHelper::toJson($deviceWithFactories),
                'breakdownWithFactories' => TextHelper::toJson($breakdownWithFactories),
            ]);
        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 种类 - 故障类型
     * @param Request $request
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityBreakdownTypeWithCategory(Request $request, string $categoryUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityTypeDir = $dirs['qualityTypeDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $kinds = $file->setPath(storage_path($this->_basicInfoDir))->join('kinds.json')->fromJson();
            $breakdownTypeWithCategories = $file->setPath($qualityTypeDir)->join('kind.json')->fromJson();
            $breakdownTypeWithFactories = $breakdownTypeWithCategories[$categoryUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualityBreakdownTypeWithCategory", [
                'categoryUniqueCode' => $categoryUniqueCode,
                'categoryName' => $kinds[$categoryUniqueCode]['name'],
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'breakdownTypeWithFactories' => TextHelper::toJson($breakdownTypeWithFactories)
            ]);

        } catch (Exception $exception) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 设备列表
     * @param Request $request
     * @return Factory|Application|RedirectResponse|View
     */
    final public function qualityEntireInstance(Request $request)
    {
        try {
            $basicInfoDir = storage_path($this->_basicInfoDir);
            if (!is_dir($basicInfoDir)) return back()->with('info', "{$basicInfoDir}，文件夹不存在");
            $file = FileSystem::init(__FILE__);
            $factories = $file->setPath($basicInfoDir)->join('factories.json')->fromJson();
            $models = $file->setPath($basicInfoDir)->join('models.json')->fromJson();
            $stations = $file->setPath($basicInfoDir)->join('stations.json')->fromJson();
            $status = [
                'INSTALLED' => '上道',
                'INSTALLING' => '备品',
                'FIXED' => '成品',
                'FIXING' => '在修',
                'RETURN_FACTORY' => '送修',
            ];
            $qualityDateType = $request->get('qualityDateType', '');
            $qualityDate = $request->get('qualityDate', '');
            $repairAt = ReportFacade::handleDateWithType($qualityDateType, $qualityDate);
            $lines = DB::table('lines as l')->where('l.deleted_at', null)->get();

            $currentFactoryUniqueCode = $request->get('factoryUniqueCode', '');
            $currentCategoryUniqueCode = $request->get('categoryUniqueCode', '');
            $currentSubModelUniqueCode = $request->get('subModelUniqueCode', '');
            $currentSceneWorkshopUniqueCode = $request->get('sceneWorkshopUniqueCode', '');
            $currentStationUniqueCode = $request->get('stationUniqueCode', '');
            $currentStatusCode = $request->get('statusCode', '');

            $currentSelRepairAt = $request->get('selRepairAt', '');
            $currentRepairAt = $request->get('repairAt', '');
            if (!empty($repairAt)) {
                $currentSelRepairAt = 1;
                $currentRepairAt = $repairAt;
            }
            list($currentRepairAtOrigin, $currentRepairAtFinish) = explode("~", empty($currentRepairAt) ? Carbon::now()->startOfDay()->toDateTimeString() . '~' . Carbon::now()->endOfDay()->toDateTimeString() : $currentRepairAt);

            switch (request('type')) {
                case 'breakdown':
                    $partEntireInstanceSql = DB::table('repair_base_breakdown_order_entire_instances as rbboei')
                        ->select([
                            'rbboei.old_entire_instance_identity_code as identity_code',
                            'c.name as category_name',
                            'pm.name as sub_model_name',
                            'rbbo.updated_at as repair_updated_at',
                            'ei.status as status',
                            'f.name as factory_name',
                            'rbboei.maintain_station_name as maintain_station_name',
                            'rbboei.open_direction as open_direction',
                            'rbboei.said_rod as said_rod',
                            'rbboei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'rbboei.maintain_location_code as maintain_location_code',
                        ])
                        ->join(DB::raw('repair_base_breakdown_orders rbbo'), 'rbbo.serial_number', '=', 'rbboei.in_sn')
                        ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbboei.old_entire_instance_identity_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'rbboei.maintain_station_name')
                        ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                        ->where('rbbo.direction', '=', 'IN')
                        ->where('rbbo.status', '=', 'DONE')
                        ->where('ei.made_at', '<>', null)
                        ->where('ei.deleted_at', null)
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('pm.deleted_at', null)
                        ->where('pc.deleted_at', null)
                        ->where('pc.is_main', true)
                        ->where('c.deleted_at', null)
                        ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
                        ->where('sc.deleted_at', null)
                        ->where('sc.type', '=', 'SCENE_WORKSHOP')
                        ->where('s.deleted_at', null)
                        ->where('s.type', '=', 'STATION')
                        ->when(
                            $currentSelRepairAt == 1,
                            function ($query) use ($currentRepairAtOrigin, $currentRepairAtFinish) {
                                return $query->whereBetween('rbbo.updated_at', [$currentRepairAtOrigin, $currentRepairAtFinish]);
                            }
                        )
                        ->when(
                            !empty($currentFactoryUniqueCode),
                            function ($query) use ($currentFactoryUniqueCode) {
                                return $query->where('f.unique_code', '=', $currentFactoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentCategoryUniqueCode),
                            function ($query) use ($currentCategoryUniqueCode) {
                                return $query->where('c.unique_code', '=', $currentCategoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSubModelUniqueCode),
                            function ($query) use ($currentSubModelUniqueCode) {
                                return $query->where('pm.unique_code', '=', $currentSubModelUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSceneWorkshopUniqueCode),
                            function ($query) use ($currentSceneWorkshopUniqueCode) {
                                return $query->where('sc.unique_code', '=', $currentSceneWorkshopUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStationUniqueCode),
                            function ($query) use ($currentStationUniqueCode) {
                                return $query->where('s.unique_code', '=', $currentStationUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStatusCode),
                            function ($query) use ($currentStatusCode) {
                                return $query->where('ei.status', '=', $currentStatusCode);
                            }
                        )
                        ->groupBy('rbboei.old_entire_instance_identity_code')
                        ->orderByDesc('rbboei.updated_at');

                    $entireInstanceSql = DB::table('repair_base_breakdown_order_entire_instances as rbboei')
                        ->select([
                            'rbboei.old_entire_instance_identity_code as identity_code',
                            'c.name as category_name',
                            'sm.name as sub_model_name',
                            'rbbo.updated_at as repair_updated_at',
                            'ei.status as status',
                            'f.name as factory_name',
                            'rbboei.maintain_station_name as maintain_station_name',
                            'rbboei.open_direction as open_direction',
                            'rbboei.said_rod as said_rod',
                            'rbboei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'rbboei.maintain_location_code as maintain_location_code',
                        ])
                        ->join(DB::raw('repair_base_breakdown_orders rbbo'), 'rbbo.serial_number', '=', 'rbboei.in_sn')
                        ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbboei.old_entire_instance_identity_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                        ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'rbboei.maintain_station_name')
                        ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                        ->where('rbbo.direction', '=', 'IN')
                        ->where('rbbo.status', '=', 'DONE')
                        ->where('ei.made_at', '<>', null)
                        ->where('ei.deleted_at', null)
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('sm.deleted_at', null)
                        ->where('sm.is_sub_model', true)
                        ->where('c.deleted_at', null)
                        ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
                        ->where('sc.deleted_at', null)
                        ->where('sc.type', '=', 'SCENE_WORKSHOP')
                        ->where('s.deleted_at', null)
                        ->where('s.type', '=', 'STATION')
                        ->when(
                            $currentSelRepairAt == 1,
                            function ($query) use ($currentRepairAtOrigin, $currentRepairAtFinish) {
                                return $query->whereBetween('rbbo.updated_at', [$currentRepairAtOrigin, $currentRepairAtFinish]);
                            }
                        )
                        ->when(
                            !empty($currentFactoryUniqueCode),
                            function ($query) use ($currentFactoryUniqueCode) {
                                return $query->where('f.unique_code', '=', $currentFactoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentCategoryUniqueCode),
                            function ($query) use ($currentCategoryUniqueCode) {
                                return $query->where('c.unique_code', '=', $currentCategoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSubModelUniqueCode),
                            function ($query) use ($currentSubModelUniqueCode) {
                                return $query->where('sm.unique_code', '=', $currentSubModelUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSceneWorkshopUniqueCode),
                            function ($query) use ($currentSceneWorkshopUniqueCode) {
                                return $query->where('sc.unique_code', '=', $currentSceneWorkshopUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStationUniqueCode),
                            function ($query) use ($currentStationUniqueCode) {
                                return $query->where('s.unique_code', '=', $currentStationUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStatusCode),
                            function ($query) use ($currentStatusCode) {
                                return $query->where('ei.status', '=', $currentStatusCode);
                            }
                        )
                        ->groupBy('rbboei.old_entire_instance_identity_code')
                        ->orderByDesc('rbboei.updated_at');
                    $entireInstances = ModelBuilderFacade::unionAll($entireInstanceSql, $partEntireInstanceSql)->paginate();
                    return view("Report.Quality.qualityEntireInstance_breakdown", [
                        'lines' => $lines->toJson(),
                        'factories' => TextHelper::toJson($factories),
                        'models' => TextHelper::toJson($models),
                        'stations' => TextHelper::toJson($stations),
                        'status' => TextHelper::toJson($status),
                        'currentFactoryUniqueCode' => $currentFactoryUniqueCode,
                        'currentCategoryUniqueCode' => $currentCategoryUniqueCode,
                        'currentSubModelUniqueCode' => $currentSubModelUniqueCode,
                        'currentSceneWorkshopUniqueCode' => $currentSceneWorkshopUniqueCode,
                        'currentStationUniqueCode' => $currentStationUniqueCode,
                        'currentStatusCode' => $currentStatusCode,
                        'currentRepairAt' => $currentRepairAt,
                        'currentSelRepairAt' => $currentSelRepairAt,
                        'currentRepairAtOrigin' => $currentRepairAtOrigin,
                        'currentRepairAtFinish' => $currentRepairAtFinish,
                        "entireInstances" => $entireInstances,
                    ]);
                    break;
                case 'device':
                    $partEntireInstanceSql = DB::table('entire_instances as ei')
                        ->select([
                            'ei.identity_code as identity_code',
                            'c.name as category_name',
                            'pm.name as sub_model_name',
                            'ei.status as status',
                            'f.name as factory_name',
                            'ei.maintain_station_name as maintain_station_name',
                            'ei.open_direction as open_direction',
                            'ei.said_rod as said_rod',
                            'ei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'ei.maintain_location_code as maintain_location_code',
                        ])
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                        ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                        ->where('ei.made_at', '<>', null)
                        ->where('ei.deleted_at', null)
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('pm.deleted_at', null)
                        ->where('pc.deleted_at', null)
                        ->where('pc.is_main', true)
                        ->where('c.deleted_at', null)
                        ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
                        ->where('sc.deleted_at', null)
                        ->where('sc.type', '=', 'SCENE_WORKSHOP')
                        ->where('s.deleted_at', null)
                        ->where('s.type', '=', 'STATION')
                        ->when(
                            !empty($currentFactoryUniqueCode),
                            function ($query) use ($currentFactoryUniqueCode) {
                                return $query->where('f.unique_code', '=', $currentFactoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentCategoryUniqueCode),
                            function ($query) use ($currentCategoryUniqueCode) {
                                return $query->where('c.unique_code', '=', $currentCategoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSubModelUniqueCode),
                            function ($query) use ($currentSubModelUniqueCode) {
                                return $query->where('pm.unique_code', '=', $currentSubModelUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSceneWorkshopUniqueCode),
                            function ($query) use ($currentSceneWorkshopUniqueCode) {
                                return $query->where('sc.unique_code', '=', $currentSceneWorkshopUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStationUniqueCode),
                            function ($query) use ($currentStationUniqueCode) {
                                return $query->where('s.unique_code', '=', $currentStationUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStatusCode),
                            function ($query) use ($currentStatusCode) {
                                return $query->where('ei.status', '=', $currentStatusCode);
                            }
                        )
                        ->groupBy('ei.identity_code')
                        ->orderByDesc('ei.updated_at');

                    $entireInstanceSql = DB::table('entire_instances as ei')
                        ->select([
                            'ei.identity_code as identity_code',
                            'c.name as category_name',
                            'sm.name as sub_model_name',
                            'ei.status as status',
                            'f.name as factory_name',
                            'ei.maintain_station_name as maintain_station_name',
                            'ei.open_direction as open_direction',
                            'ei.said_rod as said_rod',
                            'ei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'ei.maintain_location_code as maintain_location_code',
                        ])
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                        ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                        ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                        ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                        ->where('ei.made_at', '<>', null)
                        ->where('ei.deleted_at', null)
                        ->where('ei.status', '<>', 'SCRAP')
                        ->where('sm.deleted_at', null)
                        ->where('sm.is_sub_model', true)
                        ->where('c.deleted_at', null)
                        ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
                        ->where('sc.deleted_at', null)
                        ->where('sc.type', '=', 'SCENE_WORKSHOP')
                        ->where('s.deleted_at', null)
                        ->where('s.type', '=', 'STATION')
                        ->when(
                            !empty($currentFactoryUniqueCode),
                            function ($query) use ($currentFactoryUniqueCode) {
                                return $query->where('f.unique_code', '=', $currentFactoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentCategoryUniqueCode),
                            function ($query) use ($currentCategoryUniqueCode) {
                                return $query->where('c.unique_code', '=', $currentCategoryUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSubModelUniqueCode),
                            function ($query) use ($currentSubModelUniqueCode) {
                                return $query->where('sm.unique_code', '=', $currentSubModelUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentSceneWorkshopUniqueCode),
                            function ($query) use ($currentSceneWorkshopUniqueCode) {
                                return $query->where('sc.unique_code', '=', $currentSceneWorkshopUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStationUniqueCode),
                            function ($query) use ($currentStationUniqueCode) {
                                return $query->where('s.unique_code', '=', $currentStationUniqueCode);
                            }
                        )
                        ->when(
                            !empty($currentStatusCode),
                            function ($query) use ($currentStatusCode) {
                                return $query->where('ei.status', '=', $currentStatusCode);
                            }
                        )
                        ->groupBy('ei.identity_code')
                        ->orderByDesc('ei.updated_at');

                    $entireInstances = ModelBuilderFacade::unionAll($entireInstanceSql, $partEntireInstanceSql)->paginate();
                    return view("Report.Quality.qualityEntireInstance_device", [
                        'lines' => $lines->toJson(),
                        'factories' => TextHelper::toJson($factories),
                        'models' => TextHelper::toJson($models),
                        'stations' => TextHelper::toJson($stations),
                        'status' => TextHelper::toJson($status),
                        'currentFactoryUniqueCode' => $currentFactoryUniqueCode,
                        'currentCategoryUniqueCode' => $currentCategoryUniqueCode,
                        'currentSubModelUniqueCode' => $currentSubModelUniqueCode,
                        'currentSceneWorkshopUniqueCode' => $currentSceneWorkshopUniqueCode,
                        'currentStationUniqueCode' => $currentStationUniqueCode,
                        'currentStatusCode' => $currentStatusCode,
                        'currentRepairAt' => $currentRepairAt,
                        'currentSelRepairAt' => $currentSelRepairAt,
                        'currentRepairAtOrigin' => $currentRepairAtOrigin,
                        'currentRepairAtFinish' => $currentRepairAtFinish,
                        "entireInstances" => $entireInstances,
                    ]);
                    break;
                default:
                    return back()->with('danger', '链接错误');
                    break;
            }
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
