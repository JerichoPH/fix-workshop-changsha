<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Jericho\Model\Log;

class MaintainLocationService
{

    private $_root = null;
    private $_type = 'fromSceneWorkshopUniqueCode';
    private $_connectionName = 'mysql';
    private $_stations = null;

    /**
     * 设置连接名称
     * @param string $connectionName
     * @return MaintainLocationService
     */
    final public function connectionName(string $connectionName): self
    {
        $this->_connectionName = $connectionName;
        return $this;
    }

    /**
     * 通过现场车间代码初始化
     * @param string $sceneWorkshopUniqueCode
     * @return MaintainLocationService
     */
    final public function fromSceneWorkshopUniqueCode(string $sceneWorkshopUniqueCode): self
    {
        $this->_root = $sceneWorkshopUniqueCode;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * 通过现场车间名称初始化
     * @param string $sceneWorkshopName
     * @return MaintainLocationService
     */
    final public function fromSceneWorkshopName(string $sceneWorkshopName): self
    {
        $this->_root = $sceneWorkshopName;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * 通过站场名称初始化
     * @param string $stationName
     * @return MaintainLocationService
     */
    final public function fromMaintainStationName(string $stationName): self
    {
        $this->_root = $stationName;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * 通过站场代码初始化
     * @param string $stationUniqueCode
     * @return MaintainLocationService
     */
    final public function fromMaintainStationUniqueCode(string $stationUniqueCode): self
    {
        $this->_root = $stationUniqueCode;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * 获取所有种类下统计
     * @return array
     * @throws Exception
     */
    final public function withAllCategory()
    {
        $stationUniqueCodes = $this->getStationUniqueCodes();

        # 获取所有种类
        $categories = DB::table('categories')->where('deleted_at', null)->get();

        # 获取所有种类下设备状态
        $entireInstancesWithCategory = [];

        $getBuilder = function ($uniqueCode) {
            return DB::table('entire_instances')
                ->select(['entire_instances.status'])
                ->where('entire_instances.deleted_at', null)
                ->where('entire_instances.category_unique_code', $uniqueCode)
                ->whereIn('entire_instances.maintain_station_name', $this->_stations->pluck('name'));
        };

        $categories->pluck('unique_code')->each(
            function ($uniqueCode)
            use (&$entireInstancesWithCategory, $getBuilder) {

                $entireInstancesWithCategory[$uniqueCode] = [
                    $getBuilder($uniqueCode)->where('status', 'INSTALLED')->count('entire_instances.status'),  # 上道
                    $getBuilder($uniqueCode)->where('status', 'FIXED')->count('entire_instances.status'),  # 成品
                    $getBuilder($uniqueCode)->where('status', 'FIXING')->orWhere('status', 'FACTORY_RETURN')->count('entire_instances.status'),  # 在修
                    $getBuilder($uniqueCode)->where('status', 'RETURN_FACTORY')->count('entire_instances.status'),  # 送修
                    $getBuilder($uniqueCode)->where('status', 'INSTALLING')->count('entire_instances.status'),  # 备用
                ];
            });

        return [$categories, $entireInstancesWithCategory];
    }

    /**
     * 统计某一种类下所有的型号统计数据
     * @param string $categoryUniqueCode
     * @return array
     * @throws Exception
     */
    final public function withEntireModelByCategoryUniqueCode(string $categoryUniqueCode)
    {
        $stationUniqueCodes = $this->getStationUniqueCodes();

        # 统计某一种类下所有的型号统计数据
        $entireModelWithCategory = DB::table('entire_models')->where('deleted_at', null)->where('is_sub_model', false)->where('category_unique_code', $categoryUniqueCode)->get();
        $entireInstancesWithEntireModel = [];
        $getBuilder = function ($uniqueCode) {
            return DB::table('entire_instances')
                ->select(['entire_instances.status'])
                ->join('entireModels', 'entire_models.unique_code', '=', 'entire_instances.entire_model_unique_code')
                ->where('entire_instances.deleted_at', null)
                ->where('entireModels.deleted_at', $uniqueCode)
                ->where('entireModels.parent_unique_code', $uniqueCode)
                ->whereIn('entire_instances.maintain_station_name', $this->_stations->pluck('name'));
        };
        foreach ($entireModelWithCategory->pluck('unique_code') as $uniqueCode) {
            $entireInstancesWithEntireModel[$uniqueCode] = [
                $getBuilder($uniqueCode)->where('status', 'INSTALLED')->count('entire_instances.status'),  # 上道
                $getBuilder($uniqueCode)->where('status', 'FIXED')->count('entire_instances.status'),  # 成品
                $getBuilder($uniqueCode)->where('status', 'FIXING')->orWhere('status', 'FACTORY_RETURN')->count('entire_instances.status'),  # 在修
                $getBuilder($uniqueCode)->where('status', 'RETURN_FACTORY')->count('entire_instances.status'),  # 送修
                $getBuilder($uniqueCode)->where('status', 'INSTALLING')->count('entire_instances.status'),  # 备用
            ];
        }
        return [$entireModelWithCategory, $entireInstancesWithEntireModel];
    }

    /**
     * 根据类型获取统计数据
     * @param string $entireModelUniqueCode
     * @return array
     * @throws Exception
     */
    final public function withEntireModelByEntireModelUniqueCode(string $entireModelUniqueCode)
    {
        $stationUniqueCodes = $this->getStationUniqueCodes();
        switch (substr($entireModelUniqueCode, 0, 1)) {
            case 'Q':
                $sub = DB::table('entire_models')->where('deleted_at', null)->where('parent_unique_code', request('entireModelUniqueCode'))->where('is_sub_model', true)->get();
                break;
            case 'S':
                $sub = DB::table('part_models')->where('deleted_at', null)->where('entire_model_unique_code', request('entireModelUniqueCode'))->get();
                break;
        }
        # 统计某一类型下所有的型号统计数据
        $entireInstancesWithSub = [];
        $getBuilder = function ($uniqueCode) {
            return DB::table('entire_instances')
                ->select(['entire_instances.status'])
                ->where('entire_instances.deleted_at', null)
                ->where('entire_instances.entire_model_unique_code', 'like', "{$uniqueCode}%")
                ->whereIn('entire_instances.maintain_station_name', $this->_stations->pluck('name'));
        };
        foreach ($sub->pluck('unique_code') as $uniqueCode) {
            $entireInstancesWithSub[$uniqueCode] = [
                $getBuilder($uniqueCode)->where('status', 'INSTALLED')->count('entire_instances.status'),  # 上道
                $getBuilder($uniqueCode)->where('status', 'FIXED')->count('entire_instances.status'),  # 成品
                $getBuilder($uniqueCode)->where('status', 'FIXING')->orWhere('status', 'FACTORY_RETURN')->count('entire_instances.status'),  # 在修
                $getBuilder($uniqueCode)->where('status', 'RETURN_FACTORY')->count('entire_instances.status'),  # 送修
                $getBuilder($uniqueCode)->where('status', 'INSTALLING')->count('entire_instances.status'),  # 备用
            ];
        }
        return [$sub, $entireInstancesWithSub];
    }

    /**
     * 获取现场车间下对应战场
     * @return mixed
     * @throws Exception
     */
    final public function getStationUniqueCodes()
    {
        switch ($this->_type) {
            case 'fromSceneWorkshopUniqueCode':
                if (!DB::table('maintains')
                    ->where('deleted_at', null)
                    ->where('type', 'SCENE_WORKSHOP')
                    ->where('unique_code', $this->_root)->first()) throw new Exception('没有对应现场车间');

                $this->_stations = DB::table('maintains')
                    ->where('deleted_at', null)
                    ->where('type', 'STATION')
                    ->where('parent_unique_code', $this->_root)
                    ->get();
                $stationUniqueCodes = $this->_stations ? $this->_stations->pluck('unique_code') : [];

                break;
            case 'fromSceneWorkshopName':
                $sceneWorkshopUniqueCode = DB::table('maintains')
                    ->where('deleted_at', null)
                    ->where('type', 'SCENE_WORKSHOP')
                    ->where('name', $this->_root)
                    ->first(['unique_code']);
                if (!$sceneWorkshopUniqueCode) throw new Exception('没有对应现场车间');
                $this->_stations = DB::table('maintains')
                    ->where('deleted_at', null)
                    ->where('type', 'STATION')
                    ->where('parent_unique_code', $sceneWorkshopUniqueCode->unique_code)
                    ->get();
                $stationUniqueCodes = $this->_stations ? $this->_stations->pluck('unique_code') : [];
                break;
            case 'fromMaintainStationName':
            case 'fromMaintainStationUniqueCode':
                $this->_stations = collect([]);
                $stationUniqueCodes = [];
                break;
            default:
                throw new Exception('初始化参数错误');
                break;
        }

        return $stationUniqueCodes;
    }

    /**
     * 获取统计数据
     * @return array
     * @throws Exception
     */
    final public function getCount(): array
    {
        $stationUniqueCodes = $this->getStationUniqueCodes();

        $getDB = function () use ($stationUniqueCodes): Builder {
            return DB::connection($this->_connectionName)
                ->table('maintains')
                ->join('entire_instances', 'entire_instances.maintain_station_name', '=', 'maintains.name')
                ->whereIn('maintains.unique_code', $stationUniqueCodes);
        };
        # 统计在用设备
        $usingCount = $getDB()->where('entire_instances.status', 'INSTALLED')->count('entire_instances.id');

        # 统计备品
        $installingCount = $getDB()->where('entire_instances.status', 'INSTALLING')->count('entire_instances.id');

        # 统计在修设备
        $fixingCount = $getDB()->where('entire_instances.status', 'FIXING')->count('entire_instances.id');

        # 统计成品
        $fixedCount = $getDB()->where('entire_instances.status', 'FIXED')->count('entire_instances.id');

        # 统计送修
        $returnFactory = $getDB()->where('entire_instances.status', 'RETURN_FACTORY')->count('entire_instances.id');

        return [
            '上道' => $usingCount,
            '备品' => $installingCount,
            '在修' => $fixingCount,
            '成品' => $fixedCount,
            '送修' => $returnFactory,
        ];
    }
}
