<?php

namespace App\Http\Controllers\Report;

use App\Facades\CommonFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Model\Log;
use Jericho\TextHelper;

class MaintainController extends Controller
{
    /**
     * 现场车间下车站统计
     * @param string $sceneWorkshopUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function getStationsWithSceneWorkshop(string $sceneWorkshopUniqueCode)
    {
        try {
            $fileDir = storage_path("app/maintain/{$sceneWorkshopUniqueCode}.json");
            if (!is_file($fileDir)) return back()->with('danger', '没有找到对应的数据');

            $file = json_decode(file_get_contents($fileDir), true);
            if (!array_key_exists('subs', $file)) return back()->with('danger', '没有找到对应的数据');
            if (empty($file['subs'])) return back()->with('danger', '没有找到对应的数据');

            $stations = [];
            foreach ($file['subs'] as $su => $item) $stations[$su] = $item['name'];

            return view('Report.Maintain.stationsWithSceneWorkshop', [
                'stationAsJson' => json_encode($file['subs']),
                'categoryAsJson' => json_encode($file['categories']),
                'stations' => $stations,
                'sceneWorkshopName' => $file['name'],
                'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 获取台账设备列表
     */
    final public function gatMaintainEntireInstances()
    {
        try {
            $rootDir = storage_path("app/basicInfo");
            // 获取现场车间和车站信息
            if (!is_file("{$rootDir}/stations.json")) return back()->with('danger', '现场车间车站缓存不存在');
            // 获取种类型信息
            if (!is_file("{$rootDir}/kinds.json")) return back()->with('danger', '种类型缓存不存在');

            $lines = DB::table('lines as l')->where('l.deleted_at', null)->get();
            $maintains = json_decode(file_get_contents("{$rootDir}/stations.json"), true);
            $kinds = json_decode(file_get_contents("{$rootDir}/kinds.json"), true);
            $statuses = EntireInstance::$STATUSES;
            $checkAllMaintain = false;

            // 初始化台账选择
            if (request('station_unique_code')) {
                // 选择车站
                $tmp = collect($maintains)->pluck('subs')->collapse()->get(request('station_unique_code'));
                $currentSceneWorkshopUniqueCode = $tmp['parent']['unique_code'];
                // dd($currentSceneWorkshopUniqueCode);
                $currentSceneWorkshopName = $tmp['parent']['name'];
                $currentStationUniqueCode = request('station_unique_code');
                $currentStationName = $tmp['name'];
                if (!is_file(storage_path("app/maintain/{$currentSceneWorkshopUniqueCode}.json"))) return back()->with('danger', '该车站没有数据');
                $stations_with_file = json_decode(file_get_contents(storage_path("app/maintain/{$currentSceneWorkshopUniqueCode}.json")), true)['subs'];
                if (!array_key_exists($currentStationUniqueCode, $stations_with_file)) return back()->with('danger', '该车站没有数据');
                $statistics = [$stations_with_file[$currentStationUniqueCode]];
            } elseif (!request('station_unique_code') && request('scene_workshop_unique_code')) {
                // 选择现场车间
                $currentSceneWorkshopUniqueCode = request('scene_workshop_unique_code');
                $currentSceneWorkshopName = $maintains[$currentSceneWorkshopUniqueCode]['name'];
                $currentStationUniqueCode = '';
                $currentStationName = '全部';
                if (!is_file(storage_path("app/maintain/{$currentSceneWorkshopUniqueCode}.json"))) return back()->with('danger', '该车间没有数据');
                $statistics = [json_decode(file_get_contents(storage_path("app/maintain/{$currentSceneWorkshopUniqueCode}.json")), true)];
            } else {
                // 全选
                $currentSceneWorkshopUniqueCode = '';
                $currentSceneWorkshopName = '全部';
                $currentStationUniqueCode = '';
                $currentStationName = '全部';
                $checkAllMaintain = true;
                $statistics = [];
                foreach ($maintains as $scu => $item)
                    $statistics[] = json_decode(file_get_contents(storage_path("app/maintain/{$scu}.json")), true);
            }

            /**
             * 组合种类型
             * @return array
             */
            $generate_kinds = function () use ($statistics) {
                $categories = [];
                $entire_models = [];
                $sub_models = [];

                foreach ($statistics as $statistic) {
                    if (array_key_exists('categories', $statistic)) {
                        foreach ($statistic['categories'] as $cu => $category) {
                            $categories[$cu] = $category['name'];
                            if (!array_key_exists($cu, $entire_models)) $entire_models[$cu] = [];

                            foreach ($category['subs'] as $emu => $entire_model) {
                                $entire_models[$cu][$emu] = $entire_model['name'];
                                if (!array_key_exists($emu, $sub_models)) $sub_models[$emu] = [];

                                foreach ($entire_model['subs'] as $smu => $sub_model) {
                                    $sub_models[$emu][$smu] = $sub_model['name'];
                                }
                            }
                        }
                    }
                }

                $categories = collect($categories);
                $entire_models = collect($entire_models);
                $sub_models = collect($sub_models);

                // 默认种代码
                $current_category_unique_code = $categories->flip()->get($categories->first());
                $current_category_unique_code = request('category_unique_code', $current_category_unique_code) ?? $current_category_unique_code;
                $current_category_name = $categories->get($current_category_unique_code, '');

                // 默认类代码
                $current_entire_model_unique_code =
                    $entire_models->get($current_category_unique_code)
                        ? (array_first(array_flip($entire_models->get($current_category_unique_code))))
                        : '';
                $current_entire_model_unique_code = request('entire_model_unique_code', $current_entire_model_unique_code) ?? $current_entire_model_unique_code;
                $current_entire_model_name = $entire_models->get($current_category_unique_code)[$current_entire_model_unique_code] ?? '';

                // 默认型代码
                $current_sub_model_unique_code =
                    $sub_models->get($current_entire_model_unique_code)
                        ? (array_first(array_flip($sub_models->get($current_entire_model_unique_code))))
                        : '';
                $current_sub_model_name = $sub_models->get($current_entire_model_unique_code)[$current_sub_model_unique_code] ?? '';

                return [
                    $categories,
                    $entire_models,
                    $sub_models,
                    $current_category_unique_code,
                    $current_entire_model_unique_code,
                    $current_sub_model_unique_code,
                    $current_category_name,
                    $current_entire_model_name,
                    $current_sub_model_name
                ];
            };

            list(
                $categories,
                $entire_models,
                $sub_models,
                $current_category_unique_code,
                $current_entire_model_unique_code,
                $current_sub_model_unique_code,
                $current_category_name,
                $current_entire_model_name,
                $current_sub_model_name
                ) = $generate_kinds();

            // 设备列表
            $sql_Q = function () {
                return DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'ei.identity_code as identity_code',
                        'ei.identity_code as serial_number',
                        'ei.status        as eis',
                        'ei.maintain_location_code as maintain_location_code',
                        'ei.crossroad_number as crossroad_number',
                        'ei.next_fixing_time as next_fixing_time',
                        'sm.unique_code   as smu',
                        'sm.name          as smn',
                        'em.unique_code   as emu',
                        'em.name          as emn',
                        'c.unique_code    as cu',
                        'c.name           as cn',
                        'f.unique_code    as fu',
                        'f.name           as fn',
                        's.unique_code    as su',
                        's.name           as sn',
                        'sc.unique_code   as scu',
                        'sc.name          as scn',
                    ]))
                    ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->leftJoin(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->when(
                        request('station_unique_code'),
                        function ($query) {
                            $query->where('s.unique_code', request('station_unique_code'));
                        }
                    )
                    ->when(request('category_unique_code'),function($query){
                        $query->where('c.unique_code',request('category_unique_code'));
                    })
                    ->when(request('entire_model_unique_code'),function($query){
                        $query->where('em.unique_code',request('entire_model_unique_code'));
                    })
                    ->when(request('model_unique_code'),function($query){
                        $query->where('sm.unique_code',request('model_unique_code'));
                    })
                    ->where('ei.deleted_at', null)
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
                    ->orderByDesc('ei.updated_at');
            };
            $sql_S = function () {
                return DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'ei.identity_code as identity_code',
                        'ei.identity_code as serial_number',
                        'ei.status        as eis',
                        'ei.maintain_location_code as maintain_location_code',
                        'ei.crossroad_number as crossroad_number',
                        'ei.next_fixing_time as next_fixing_time',
                        'pm.unique_code   as smu',
                        'pm.name          as smn',
                        'em.unique_code   as emu',
                        'em.name          as emn',
                        'c.unique_code    as cu',
                        'c.name           as cn',
                        'f.unique_code    as fu',
                        'f.name           as fn',
                        's.unique_code    as su',
                        's.name           as sn',
                        'sc.unique_code   as scu',
                        'sc.name          as scn',
                    ]))
                    ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->leftJoin(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->when(
                        request('station_unique_code'),
                        function ($query) {
                            $query->where('s.unique_code', request('station_unique_code'));
                        }
                    )
                    ->when(request('category_unique_code'),function($query){
                        $query->where('c.unique_code',request('category_unique_code'));
                    })
                    ->when(request('entire_model_unique_code'),function($query){
                        $query->where('em.unique_code',request('entire_model_unique_code'));
                    })
                    ->when(request('model_unique_code'),function($query){
                        $query->where('pm.unique_code',request('model_unique_code'));
                    })
                    ->where('ei.deleted_at', null)
                    ->where('pm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('em.is_sub_model', false)
                    ->where('c.deleted_at', null)
                    ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                    ->where('sc.deleted_at', null)
                    ->where('sc.type', 'SCENE_WORKSHOP')
                    ->where('s.deleted_at', null)
                    ->where('s.type', 'STATION')
                    ->orderByDesc('ei.updated_at');
            };

            $entire_instances = ModelBuilderFacade::unionAll($sql_Q(), $sql_S())->paginate(10);

            return view('Report.Maintain.maintainEntireInstances', [
                'entireInstances' => $entire_instances,
                'checkAllMaintain' => $checkAllMaintain,
                'linesAsJson' => $lines->toJson(),
                'maintainsAsJson' => json_encode($maintains),
                'kindsAsJson' => json_encode($kinds),
                'statuses' => $statuses,
                'statusesAsJson' => json_encode($statuses),
                'categoriesAsJson' => json_encode($categories),
                'entireModelsAsJson' => json_encode($entire_models),
                'subModelsAsJson' => json_encode($sub_models),
                'currentCategoryUniqueCode' => $current_category_unique_code,
                'currentCategoryName' => $current_category_name,
                'currentEntireModelUniqueCode' => $current_entire_model_unique_code,
                'currentEntireModelName' => $current_entire_model_name,
                'currentModelUniqueCode' => $current_sub_model_unique_code,
                'currentModelName' => $current_sub_model_name,
                'currentSceneWorkshopUniqueCode' => $currentSceneWorkshopUniqueCode,
                'currentSceneWorkshopName' => $currentSceneWorkshopName,
                'currentStationUniqueCode' => $currentStationUniqueCode,
                'currentStationName' => $currentStationName,
                'statisticsAsJson' => json_encode($statistics),
            ]);
        } catch (\Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取现场车间具体设备列表
     * @param string $sceneWorkshopUniqueCode
     * @return Factory|RedirectResponse|View
     */
    final public function sceneWorkshopEntireInstances(string $sceneWorkshopUniqueCode)
    {
        try {
            $fileDir = storage_path("app/台账");
            if (!is_dir($fileDir)) return back()->with('danger', '数据不存在');
            $sceneWorkshops = TextHelper::parseJson(file_get_contents("{$fileDir}/现场车间.json"));
            $stationsWithSceneWorkshop = TextHelper::parseJson(file_get_contents("{$fileDir}/车站-现场车间.json"));
            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[request('categoryUniqueCode')];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            $currentEntireModel = $entireModels[request('entireModelUniqueCode')];
            $subModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-类型.json"))[$currentEntireModel];
            $currentSubModel = $subModels[request('subModelUniqueCode')];
            $entireInstanceStatuses = EntireInstance::$STATUSES;
            $currentStatus = array_flip($entireInstanceStatuses)[request('status')];

            $sceneWorkshopName = $sceneWorkshops[$sceneWorkshopUniqueCode];
            $stations = $stationsWithSceneWorkshop[$sceneWorkshopName];

            $getDB = function () use ($stations, $currentStatus): array {
                $stationNames = '';
                foreach ($stations as $station) $stationNames .= "'{$station}',";
                $stationNames = rtrim($stationNames, ',');

                $categoryUniqueCode = request('categoryUniqueCode') ? "and ei.category_unique_code = '" . request('categoryUniqueCode') . "'" : '';
                $entireModelUniqueCode = request('entireModelUniqueCode') ? "and em.unique_code = '" . request('entireModelUniqueCode') . "'" : '';
                $subModelUniqueCode = request('subModelUniqueCode') ? "and sm.unique_code = '" . request('subModelUniqueCode') . "'" : '';
                $status = request('status') ? "and ei.status = '{$currentStatus}'" : '';

                $sqlSm = "
select ei.identity_code,
       ei.category_name,
       em.name as entire_model_name,
       sm.name as sub_model_name,
       ei.status,
       ei.maintain_station_name,
       ei.maintain_location_code,
       ei.crossroad_number,
       ei.open_direction,
       ei.to_direction,
       ei.line_name,
       ei.said_rod,
       ei.next_fixing_day
from entire_instances ei
         inner join entire_models sm on sm.unique_code = ei.entire_model_unique_code
         left join entire_models em on em.unique_code = sm.parent_unique_code
where ei.deleted_at is null
  and ei.maintain_station_name in ({$stationNames})
  {$categoryUniqueCode}
  {$entireModelUniqueCode}
  {$subModelUniqueCode}
  {$status}";

                $subModelUniqueCode = request('subModelUniqueCode') ? "and pm.unique_code = '" . request('subModelUniqueCode') . "'" : '';

                $sqlPm = "
select ei.identity_code,
       ei.category_name,
       em.name as entire_model_name,
       pm.name as sub_model_name,
       ei.status,
       ei.maintain_station_name,
       ei.maintain_location_code,
       ei.crossroad_number,
       ei.open_direction,
       ei.to_direction,
       ei.line_name,
       ei.said_rod,
       ei.next_fixing_day
from entire_instances ei
inner join part_instances pi on pi.entire_instance_identity_code = ei.identity_code
inner join part_models pm on pm.unique_code = pi.part_model_unique_code
inner join entire_models em on em.unique_code = pm.entire_model_unique_code
where ei.deleted_at is null
  and ei.maintain_station_name in ({$stationNames})
  {$categoryUniqueCode}
  {$entireModelUniqueCode}
  {$subModelUniqueCode}
  {$status}";

                return array_merge(DB::select($sqlSm), DB::select($sqlPm));
            };

            return view('Report.Maintain.sceneWorkshopEntireInstances', [
                'entireInstances' => $getDB(),
                'sceneWorkshops' => $sceneWorkshops,
                'currentSceneWorkshop' => $sceneWorkshopUniqueCode,
                'statuses' => EntireInstance::$STATUSES,
                'currentStatus' => $currentStatus,
                'categories' => $categories,
                'currentCategory' => $currentCategory,
                'entire_models' => $entireModels,
                'currentEntireModel' => $currentEntireModel,
                'subModels' => $subModels,
                'currentSubModel' => $currentSubModel,
            ]);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据种类获取类型列表（现场车间-设备列表）
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function entireModelsWithSceneWorkshopEntireInstances(string $categoryUniqueCode)
    {
        try {
            $fileDir = storage_path('app/台账');
            if (!is_dir($fileDir)) return response()->make('数据不存在', 404);

            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[$categoryUniqueCode];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            return response()->json($entireModels);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }

    /**
     * 根据类型获取型号和子类列表（现场车间-设备列表）
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function subModelsWithSceneWorkshopEntireInstances(string $entireModelUniqueCode)
    {
        try {
            $fileDir = storage_path('app/台账');
            if (!is_dir($fileDir)) return response()->make('数据不存在', 404);

            $categoryUniqueCode = substr($entireModelUniqueCode, 0, 3);
            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $currentCategory = $categories[$categoryUniqueCode];
            $entireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/类型-种类.json"))[$currentCategory];
            $currentEntireModel = $entireModels[$entireModelUniqueCode];
            $subModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-类型.json"))[$currentEntireModel];
            return response()->json($subModels);
        } catch (\Exception $exception) {
            return back()->with('info', '暂无数据');
        }
    }
}
