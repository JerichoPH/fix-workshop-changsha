<?php

namespace App\Services;

use App\Model\EntireInstance;
use Hprose\Http\Server;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class RpcPropertyService
{
    final public function init()
    {
        $serve = new Server();
        $serve->addMethod('categoryNames', $this);
        $serve->addMethod('allCategoryAsFactory', $this);
        $serve->addMethod('allCategory', $this);
        $serve->addMethod('withCategory', $this);
        $serve->addMethod('totalWithCategory', $this);
        $serve->addMethod('withSubModel', $this);
        $serve->start();
    }

    /**
     * 获取全部种类名称
     */
    final public function categoryNames()
    {
        $fileDir = storage_path('app/资产管理');
        $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
        return collect($categories)->values()->toArray();
    }

    /**
     * 获取设备总数
     */
    final public function totalWithCategory()
    {
        $fileDir = storage_path('app/资产管理');
        $totalWithCategory = TextHelper::parseJson(file_get_contents("{$fileDir}/总数-种类.json"));
        return $totalWithCategory;
    }

    /**
     * 根据厂家获取全种类数据
     * @return array
     */
    final public function allCategoryAsFactory()
    {
        $fileDir = storage_path('app/资产管理');

        if (!is_dir($fileDir)) return [[], [], [], []];

        $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
        $withFactory = TextHelper::parseJson(file_get_contents("{$fileDir}/统计-供应商.json"));
        $categoryNames = collect($categories)->values()->toArray();
        $factoryNames = collect($withFactory)->keys()->toArray();
        # 资产管理颜色
        $propertyColors = [
            '#37A2DA',
            '#9FE6B8',
            '#FFDB5C',
            '#FF9F7F',
            '#FB7293',
            '#8378EA',
        ];

        $tmp = [];
        foreach ($withFactory as $factoryName => $statistics) {
            foreach ($categoryNames as $categoryName) $tmp[$factoryName][] = key_exists($categoryName, $statistics) ? $statistics[$categoryName] : 0;
        }

        return [
            'propertyCategories' => $categories,
            'propertyWithFactory' => $tmp,
            'propertyCategoryNames' => $categoryNames,
            'propertyFactoryNames' => $factoryNames,
            'propertyColors' => $propertyColors,
        ];
    }

    /**
     * 指定种类
     * @param string $categoryUniqueCode
     * @return array
     */
    final public function withCategory(string $categoryUniqueCode)
    {
        $property = function () use ($categoryUniqueCode) {
            # 资产管理颜色
            $propertyColors = [
                '#37A2DA',
                '#9FE6B8',
                '#FFDB5C',
                '#FF9F7F',
                '#FB7293',
                '#8378EA',
            ];

            $fileDir = storage_path('app/资产管理');

            if (!is_dir($fileDir)) return [[], [], [], []];

            $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
            $subModels = TextHelper::parseJson(file_get_contents("{$fileDir}/型号和子类-种类.json"))[$categories[$categoryUniqueCode]];
            $withFactory = TextHelper::parseJson(file_get_contents("{$fileDir}/统计-供应商.json"));
            $withSubModel = TextHelper::parseJson(file_get_contents("{$fileDir}/统计-供应商-型号和子类.json"))[$categories[$categoryUniqueCode]];

            $subModelNames = collect($subModels)->values()->toArray();
            $factoryNames = collect($withFactory)->keys()->toArray();

            $tmp = [];
            foreach ($withFactory as $factoryName => $statistics) {
                foreach ($subModelNames as $subModelName) $tmp[$factoryName][] = key_exists($subModelName, $statistics) ? $statistics[$subModelName] : 0;
            }

            $tmp2 = [];
            foreach ($withSubModel as $subModelName => $statistics) {
                $tmp2[$subModelName] = [];
                foreach ($factoryNames as $factoryName) {
                    if (!key_exists($factoryName, $tmp2[$subModelName])) {
                        $tmp2[$subModelName][$factoryName] = key_exists($factoryName, $statistics) ? $statistics[$factoryName] : 0;
                    } else {
                        $tmp2[$subModelName][$factoryName] += key_exists($factoryName, $statistics) ? $statistics[$factoryName] : 0;
                    }
                }
            }

            return [$subModels, $tmp, $tmp2, $subModelNames, $factoryNames, $propertyColors];
        };

        list($propertySubModels, $propertyWithFactory, $propertyWithSubModel, $propertySubModelNames, $propertyFactoryNames, $propertyColors) = $property();

        return [
            'propertySubModels' => $propertySubModels,
            'propertyWithFactory' => $propertyWithFactory,
            'propertyWithSubModel' => $propertyWithSubModel,
            'propertySubModelNames' => $propertySubModelNames,
            'propertyFactoryNames' => $propertyFactoryNames,
            'propertyColors' => $propertyColors,
        ];
    }

    /**
     * 获取所有种类数据
     * @return array
     */
    final public function allCategory()
    {
        $fileDir = storage_path('app/资产管理');

        if (!is_dir($fileDir)) return [[], [], [], []];

        $categories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
        $withFactory = TextHelper::parseJson(file_get_contents("{$fileDir}/统计-供应商-种类.json"));
        $categoryNames = collect($categories)->values()->toArray();

        return [
            'categories' => $categories,
            'withFactory' => $withFactory,
            'categoryNames' => $categoryNames,
        ];
    }

    /**
     * 指定型号
     * @param string $subModelUniqueCode
     * @return array
     */
    final public function withSubModel(string $subModelUniqueCode)
    {
        switch (substr($subModelUniqueCode, 0, 1)) {
            case 'Q':
                $entireInstances = DB::connection('mysql_as_rpc')
                    ->table('entire_instances')
                    ->select([
                        'entire_instances.created_at',  # 创建时间
                        'entire_instances.updated_at',  # 修改时间
                        'entire_instances.identity_code',  # 唯一编号
                        'entire_instances.factory_name',  # 供应商名称
                        'entire_instances.factory_device_code',  # 出厂编号
                        'entire_instances.serial_number',  # 出所编号
                        'entire_instances.maintain_station_name',  # 站场名称
                        'entire_instances.maintain_location_code',  # 安装位置
                        'entire_instances.crossroad_number', # 道岔
                        'entire_instances.line_name', # 线制
                        'entire_instances.to_direction', # 去向
                        'entire_instances.open_direction', # 开向
                        'entire_instances.traction', # 牵引
                        'entire_instances.said_rod', # 表示杆
                        'entire_instances.last_installed_time',  # 最后安装时间
                        'entire_instances.category_name as category_name',  # 种类名称
                        'entireModels.name as entire_model_name', # 子类名称
                        'entire_instances.status', # 状态
                    ])
                    ->leftJoin('entireModels', 'entireModels.unique_code', '=', 'entire_instances.entire_model_unique_code')
                    ->where('entire_instances.deleted_at', null)
                    ->where('entireModels.deleted_at', null)
                    ->where('entire_instances.factory_name', request('factoryName'))
                    ->where('entire_instances.entire_model_unique_code', $subModelUniqueCode)
                    ->paginate();
                break;
            case 'S':
                $entireInstances = DB::connection('mysql_as_rpc')
                    ->table('entire_instances')
                    ->select([
                        'entire_instances.created_at',  # 创建时间
                        'entire_instances.updated_at',  # 修改时间
                        'entire_instances.identity_code',  # 唯一编号
                        'entire_instances.factory_name',  # 供应商名称
                        'entire_instances.factory_device_code',  # 出厂编号
                        'entire_instances.serial_number',  # 出所编号
                        'entire_instances.maintain_station_name',  # 站场名称
                        'entire_instances.maintain_location_code',  # 安装位置
                        'entire_instances.crossroad_number', # 道岔
                        'entire_instances.line_name', # 线制
                        'entire_instances.to_direction', # 去向
                        'entire_instances.open_direction', # 开向
                        'entire_instances.traction', # 牵引
                        'entire_instances.said_rod', # 表示杆
                        'entire_instances.last_installed_time',  # 最后安装时间
                        'entire_instances.category_name as category_name',  # 种类名称
                        'part_models.name as part_model_name',  # 型号名称
                        'entire_instances.status', # 状态
                    ])
                    ->leftJoin('part_instances', 'part_instances.entire_instance_identity_code', '=', 'entire_instances.identity_code')
                    ->leftJoin('part_models', 'part_models.unique_code', '=', 'part_instances.part_model_unique_code')
                    ->where('entire_instances.deleted_at', null)
                    ->where('part_models.deleted_at', null)
                    ->where('entire_instances.factory_name', request('factoryName'))
                    ->where('part_models.unique_code', $subModelUniqueCode)
                    ->paginate();
                break;
        }

        return [
            'subModelUniqueCode' => $subModelUniqueCode,
            'entireInstances' => $entireInstances,
            'entireInstanceStatuses' => EntireInstance::$STATUSES,
            'lastPage' => $entireInstances->lastPage(),
        ];
    }
}
