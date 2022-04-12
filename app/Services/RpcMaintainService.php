<?php

namespace App\Services;

use Hprose\Http\Server;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class RpcMaintainService
{
    final public function init()
    {
        $serve = new Server();
        $serve->addMethod('total', $this);
        $serve->addMethod('sceneWorkshopWithAllCategory', $this);
        $serve->addMethod('sceneWorkshop', $this);
        $serve->start();
    }

    /**
     * 获取即时统计（全部）
     * @return array
     */
    final public function total()
    {
        $python = env('PYTHON_SHELL_NAME');
        $organizationCode = env('ORGANIZATION_CODE');
        $maintainDir = public_path('statistics/maintain.py');
        $fileDir = storage_path("app/台账");
        shell_exec("{$python} {$maintainDir} -oc {$organizationCode}");
        $sceneWorkshops = TextHelper::parseJson(file_get_contents("{$fileDir}/现场车间.json"));
        $maintainWithWorkshops = TextHelper::parseJson(file_get_contents("{$fileDir}/状态统计-现场车间.json"));
        $stations = TextHelper::parseJson(file_get_contents("{$fileDir}/车站.json"));
        $stationNames = collect($stations)->values()->toArray();

        return [
            'sceneWorkshops' => $sceneWorkshops,
            'maintainWithWorkshops' => $maintainWithWorkshops,
            'stations' => $stations,
            'stationNames' => $stationNames
        ];
    }

    /**
     * 获取全部种类
     * @param string $sceneWorkshopUniqueCode
     * @param string|null $status
     * @return array
     */
    final public function sceneWorkshopWithAllCategory(string $sceneWorkshopUniqueCode, string $status = null)
    {
        # 获取所有该现场车间下所有车站
        $stations = DB::connection('mysql_as_rpc')->table('maintains')
            ->where('deleted_at', null)
            ->where('type', 'STATION')
            ->where('parent_unique_code', $sceneWorkshopUniqueCode)
            ->get();

        $stationNames = $stations->pluck('name')->toArray();
        $stationNameStr = "";
        foreach ($stationNames as $stationName) $stationNameStr .= "'{$stationName}',";
        $stationNameStr = rtrim($stationNameStr, ',');

        $legendSelected = ['上道' => true, '成品' => true, '在修' => true, '送修' => true, '备品' => true];
        if ($status != null) foreach ($legendSelected as $key => $item) if ($key != $status) $legendSelected[$key] = false;

        # 获取当前现场车间数据
        $sceneWorkshop = DB::connection('mysql_as_rpc')->table('maintains')->where('deleted_at', null)->where('unique_code', $sceneWorkshopUniqueCode)->first();

        # 获取所有种类的统计
        $categoryStatistics = [];

        # 获取所有种类
        $categories = DB::connection('mysql_as_rpc')->table('categories')->where('deleted_at', null)->get();
        foreach ($categories as $category) $categoryStatistics[$category->name] = [0, 0, 0, 0, 0];  # 制作空数据

        # 获取统计模型
        $getBuilderWithCategory = function (array $status) use ($stationNameStr): array {
            $statusStr = "";
            foreach ($status as $item) $statusStr .= "'{$item}',";
            $statusStr = rtrim($statusStr, ',');

            return collect(DB::connection('mysql_as_rpc')
                ->select("select c.name, count(c.name) as count
from entire_instances ei
         join categories c on ei.category_unique_code = c.unique_code
where ei.deleted_at is null
  and ei.status in ({$statusStr})
  and ei.maintain_station_name in ({$stationNameStr})
group by c.name"))
                ->pluck('count', 'name')
                ->toArray();
        };

        # 统计：上道
        foreach ($getBuilderWithCategory(['INSTALLED']) as $key => $item) $categoryStatistics[$key][0] = $item;
        # 统计：成品
        foreach ($getBuilderWithCategory(['FIXED']) as $key => $item) $categoryStatistics[$key][1] = $item;
        # 统计：在修
        foreach ($getBuilderWithCategory(['FIXING', 'FACTORY_RETURN']) as $key => $item) $categoryStatistics[$key][2] = $item;
        # 统计：返厂
        foreach ($getBuilderWithCategory(['RETURN_FACTORY']) as $key => $item) $categoryStatistics[$key][3] = $item;
        # 统计：备品
        foreach ($getBuilderWithCategory(['INSTALLING']) as $key => $item) $categoryStatistics[$key][4] = $item;

        return [
            'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
            'legendSelected' => TextHelper::toJson($legendSelected),
            'sceneWorkshop' => $sceneWorkshop,
            'categoryStatistics' => $categoryStatistics,
            'categoryNames' => collect($categories)->pluck('name')->toJson(),
            'categories' => collect($categories)->pluck('unique_code', 'name')->toJson()
        ];
    }

    /**
     * 现场车间统计
     * INSTALLED 上道
     * FIXED 成品
     * FIXING FACTORY_RETURN 在修
     * RETURN_FACTORY 送修
     * INSTALLING 备用
     * @param string $sceneWorkshopUniqueCode
     * @param null $categoryUniqueCode
     * @param null $entireModelUniqueCode
     * @param null $status
     * @return array
     */
    final public function sceneWorkshop(string $sceneWorkshopUniqueCode,
                                        $categoryUniqueCode = null,
                                        $entireModelUniqueCode = null,
                                        $status = null)
    {
        # 获取所有该现场车间下所有车站
        $stations = DB::connection('mysql_as_rpc')
            ->table('maintains')
            ->where('deleted_at', null)
            ->where('type', 'STATION')
            ->where('parent_unique_code', $sceneWorkshopUniqueCode)
            ->get();
        $stationNames = $stations->pluck('name')->toArray();
        $stationNameStr = "";
        foreach ($stationNames as $stationName) $stationNameStr .= "'{$stationName}',";
        $stationNameStr = rtrim($stationNameStr, ',');

        $legendSelected = ['上道' => true, '成品' => true, '在修' => true, '送修' => true, '备品' => true];
        if ($status != null) foreach ($legendSelected as $key => $item) if ($key != $status) $legendSelected[$key] = false;

        # 获取当前现场车间数据
        $sceneWorkshops = DB::connection('mysql_as_rpc')->table('maintains')->where('deleted_at', null)->where('unique_code', $sceneWorkshopUniqueCode)->first();

        # 获取所有种类
        $categories = DB::connection('mysql_as_rpc')->table('categories')->where('deleted_at', null)->get();

        # 当前选中的种类
        $currentCategoryUniqueCode = $categoryUniqueCode ? $categoryUniqueCode : $categories->first()->unique_code;

        # 当前选中的类型
        $currentEntireModelName = $entireModelUniqueCode ?
            DB::connection('mysql_as_rpc')->table('entireModels')->where('unique_code', $entireModelUniqueCode)->first(['name'])->name :
            DB::connection('mysql_as_rpc')->table('entireModels')->where('deleted_at', null)->where('category_unique_code', $currentCategoryUniqueCode)->first(['name'])->name;
        $entireModels = DB::connection('mysql_as_rpc')->table('entireModels')->where('deleted_at', null)->where('category_unique_code', $currentCategoryUniqueCode)->pluck('unique_code', 'name');

        # 制作类型空数据
        $entireModelsStatistics = [];
        # 制作型号和子类空数据
        $subModelsStatistics = [];
        foreach (DB::connection('mysql_as_rpc')
                     ->table('entireModels')
                     ->where('deleted_at', null)
                     ->where('is_sub_model', false)
                     ->where('category_unique_code', $currentCategoryUniqueCode)
                     ->pluck('name', 'unique_code') as $parentUniqueCode => $parentName) {
            $entireModelsStatistics[$parentName] = [0, 0, 0, 0, 0];
        }

        foreach (DB::connection('mysql_as_rpc')->select("select em.name, em2.name as parent_name
from entireModels em
         join entireModels em2 on em2.unique_code = em.parent_unique_code
where em.deleted_at is null
  and em.category_unique_code = '{$currentCategoryUniqueCode}'") as $item) {
            $entireModelsStatistics[$item->parent_name] = [0, 0, 0, 0, 0];
            $subModelsStatistics[$item->parent_name][$item->name] = [0, 0, 0, 0, 0];
        }

        foreach (DB::connection('mysql_as_rpc')->select("select pm.name, em.name as parent_name
from part_models pm
         join entireModels em on em.unique_code = pm.entire_model_unique_code
where em.deleted_at is null
  and pm.deleted_at is null
  and pm.category_unique_code = '{$currentCategoryUniqueCode}'") as $item) {
            $entireModelsStatistics[$item->parent_name] = [0, 0, 0, 0, 0];
            $subModelsStatistics[$item->parent_name][$item->name] = [0, 0, 0, 0, 0];
        }

        $getBuilder = function ($status) use ($currentCategoryUniqueCode, $stationNameStr): array {
            $statusStr = "";
            foreach ($status as $s) $statusStr .= "'{$s}',";
            $statusStr = rtrim($statusStr, ',');

            # 统计型号
            $a = DB::connection('mysql_as_rpc')->select("select pm.name as name, count(pm.name) as count, em.name as parent_name
from entire_instances ei
         join part_instances pi on pi.entire_instance_identity_code = ei.identity_code
         join part_models pm on pm.unique_code = pi.part_model_unique_code
         join entireModels em on em.unique_code = pm.entire_model_unique_code
where ei.category_unique_code = '{$currentCategoryUniqueCode}'
  and ei.maintain_station_name in ({$stationNameStr})
  and ei.status in ({$statusStr})
  and pm.deleted_at is null
  and em.name is not null
  and em.name <> ''
group by pm.name, pm.unique_code, em.name");

            # 统计子类
            $b = DB::connection('mysql_as_rpc')->select("select em.name as name, count(em.name) as count, em2.name as parent_name
from entire_instances ei
         join entireModels em on em.unique_code = ei.entire_model_unique_code
         left join entireModels em2 on em2.unique_code = em.parent_unique_code
where ei.category_unique_code = '{$currentCategoryUniqueCode}'
  and ei.maintain_station_name in ({$stationNameStr})
  and ei.status in ({$statusStr})
  and em.is_sub_model is true
  and em.deleted_at is null
  and em2.name is not null
  and em2.name <> ''
group by em.name, em.unique_code, em2.name");

            return array_merge($a, $b);
        };

        # 获取上道统计
        $installedStatistics = $getBuilder(['INSTALLED']);  # 统计上道
        $fixedStatistics = $getBuilder(['FIXED']);  # 统计成品
        $fixingAndFactoryReturnStatistics = $getBuilder(['FIXING', 'FACTORY_RETURN']);  # 统计在修
        $returnFactoryStatistics = $getBuilder(['RETURN_FACTORY']);  # 统计送修
        $installingStatistics = $getBuilder(['INSTALLING']);  # 统计备品
        foreach ($installedStatistics as $installedStatistic) {
            $entireModelsStatistics[$installedStatistic->parent_name][0] += $installedStatistic->count;
            $subModelsStatistics[$installedStatistic->parent_name][$installedStatistic->name][0] += $installedStatistic->count;
        }
        foreach ($fixedStatistics as $fixedStatistic) {
            $entireModelsStatistics[$fixedStatistic->parent_name][1] += $fixedStatistic->count;
            $subModelsStatistics[$fixedStatistic->parent_name][$fixedStatistic->name][1] += $fixedStatistic->count;
        }
        foreach ($fixingAndFactoryReturnStatistics as $fixingAndFactoryReturnStatistic) {
            $entireModelsStatistics[$fixingAndFactoryReturnStatistic->parent_name][2] += $fixingAndFactoryReturnStatistic->count;
            $subModelsStatistics[$fixingAndFactoryReturnStatistic->parent_name][$fixingAndFactoryReturnStatistic->name][2] += $fixingAndFactoryReturnStatistic->count;
        }
        foreach ($returnFactoryStatistics as $returnFactoryStatistic) {
            $entireModelsStatistics[$returnFactoryStatistic->parent_name][3] += $returnFactoryStatistic->count;
            $subModelsStatistics[$returnFactoryStatistic->parent_name][$returnFactoryStatistic->name][3] += $returnFactoryStatistic->count;
        }
        foreach ($installingStatistics as $installingStatistic) {
            $entireModelsStatistics[$installingStatistic->parent_name][4] += $installingStatistic->count;
            $subModelsStatistics[$installingStatistic->parent_name][$installingStatistic->name][4] += $installingStatistic->count;
        }

        $series = [
            ['name' => '上道', 'type' => 'bar', 'data' => []],
            ['name' => '成品', 'type' => 'bar', 'data' => []],
            ['name' => '在修', 'type' => 'bar', 'data' => []],
            ['name' => '送修', 'type' => 'bar', 'data' => []],
            ['name' => '备品', 'type' => 'bar', 'data' => []],
        ];

        $subModelNames = [];
        if ($subModelsStatistics != []) {
            if (key_exists($currentEntireModelName, $subModelsStatistics)) {
                foreach ($subModelsStatistics[$currentEntireModelName] as $subModelName => $subModelsStatistic) {
                    $subModelNames[] = $subModelName;
                    $series[0]['data'][] = $subModelsStatistic[0];
                    $series[1]['data'][] = $subModelsStatistic[1];
                    $series[2]['data'][] = $subModelsStatistic[2];
                    $series[3]['data'][] = $subModelsStatistic[3];
                    $series[4]['data'][] = $subModelsStatistic[4];
                }
            }
        }

        return [
            'sceneWorkshop' => $sceneWorkshops,
            'categories' => $categories,
            'categoryNames' => collect($categories)->pluck('name')->toJson(),
            'currentEntireModelName' => $currentEntireModelName,
            'entireModels' => $entireModels->toArray(),
            'entireModelsStatistics' => $entireModelsStatistics,
            'subModelNames' => TextHelper::toJson($subModelNames),
            'subModelsStatistics' => key_exists($currentEntireModelName, $subModelsStatistics) ? $subModelsStatistics[$currentEntireModelName] : [],
            'series' => TextHelper::toJson($series),
            'legendSelected' => TextHelper::toJson($legendSelected)
        ];
    }
}
