<?php

namespace App\Services;

use App\Model\Area;
use App\Model\EntireInstance;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\Shelf;
use App\Model\Storehouse;
use App\Model\Tier;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;

class QueryConditionService
{
    private $_file = null;
    private $_root_dir = null;
    private $_categories = null;
    private $_entire_models = null;
    private $_sub_models = null;
    private $_current_models = null;
    private $_maintains = [];
    private $_maintains_by_line = [];
    private $_scene_workshops = [];
    private $_lines = [];
    private $_statuses = [];
    private $_factories = [];
    private $_storehouses = [];
    private $_areas = [];
    private $_platoons = [];
    private $_shelves = [];
    private $_tiers = [];
    private $_positions = [];

    private $_ret = [
        "categories" => [],
        "current_category_unique_code" => "",
        "current_category_name" => "",
        "entire_models" => [],
        "current_entire_model_unique_code" => "",
        "current_entire_model_name" => "",
        "sub_models" => [],
        "current_sub_model_unique_code" => "",
        "current_sub_model_name" => "",
        "factories" => [],
        "current_factory_name" => [],
        "current_factory_unique_code" => [],
        "maintains" => [],
        "maintains_by_line" => [],
        "scene_workshops" => [],
        "current_scene_workshop_unique_code" => "",
        "current_station_name" => "",
        "current_station_names" => [],
        "maintain_type" => "all",
        "statuses" => [],
        "current_status_unique_code" => "",
        "storehouses" => [],
        "areas" => [],
        "platoons" => [],
        "shelves" => [],
        "tiers" => [],
        "current_storehouse_unique_code" => "",
        "current_area_unique_code" => "",
        "current_platoon_unique_code" => "",
        "current_shelf_unique_code" => "",
        "current_tier_unique_code" => "",
        "current_position_unique_code" => "",
    ];

    /**
     * 初始化
     * @param string $root_dir
     * @return $this
     * @throws \Exception
     */
    final public function init(string $root_dir): self
    {
        $this->_root_dir = $root_dir;
        $this->_file = FileSystem::init($this->_root_dir);
        if (!is_dir($this->_root_dir)) throw new \Exception('目录不存在：' . $this->_root_dir);

        $scene_workshops = DB::table("maintains as m")->where("m.deleted_at", null)->where("m.parent_unique_code", env("ORGANIZATION_CODE"))->where("m.type", "SCENE_WORKSHOP")->pluck("name", "unique_code");
        $maintains = DB::table("maintains as m")
            ->select(["m.name as station_name", "m2.name as scene_workshop_name"])
            ->join(DB::raw("maintains as m2"), "m2.unique_code", "=", "m.parent_unique_code")
            ->where("m.deleted_at", null)
            ->where("m2.deleted_at", null)
            ->where("m2.parent_unique_code", env("ORGANIZATION_CODE"))
            ->get();
        if ($scene_workshops) $this->_scene_workshops = $scene_workshops;
        foreach ($maintains as $maintain) $this->_maintains[$maintain->scene_workshop_name][] = $maintain->station_name;

        $lines = DB::table('lines as l')->where('l.deleted_at', null)->pluck('name', 'unique_code');
        if ($lines) $this->_lines = $lines;
        $this->_maintains_by_line = DB::table('lines_maintains as lm')
            ->select(['l.name as line_name', 's.name as station_name'])
            ->join(DB::raw('`lines` as l'), 'l.id', '=', 'lm.lines_id')
            ->join(DB::raw('maintains as s'), 's.id', '=', 'lm.maintains_id')
            ->join(DB::raw('maintains as sc'), 'sc.unique_code', '=', 's.parent_unique_code')
            ->where('s.deleted_at', null)
            ->where('l.deleted_at', null)
            ->where('s.type', 'STATION')
            ->where('sc.type', 'SCENE_WORKSHOP')
            ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
            ->get()
            ->groupBy(['line_name']);

        $factories = DB::table("factories as f")->where("f.deleted_at", null)->pluck("name", "unique_code");
        if ($factories) $this->_factories = $factories->toArray();

        $this->_statuses = EntireInstance::$STATUSES;

        return $this;
    }

    /**
     * 设置状态
     * @param array $status
     * @return $this
     */
    final public function setStatus(array $status = []): self
    {
        if (empty($status)) {
            $this->_statuses = EntireInstance::$STATUSES;
        } else {
            $this->_statuses = $status;
        }
        return $this;
    }

    /**
     * 设置仓
     * @return $this
     */
    final public function setStorehouses(): self
    {
        $this->_storehouses = Storehouse::with([])->pluck('name', 'unique_code')->toArray();
        return $this;
    }

    /**
     * 设备区
     * @return $this
     */
    final public function setAreas(): self
    {
        foreach (Area::all() as $area) $this->_areas[$area->storehouse_unique_code][$area->unique_code] = $area->name;
        return $this;
    }

    /**
     * 设置排
     * @return $this
     */
    final public function setPlatoons(): self
    {
        foreach (Platoon::all() as $platoon) $this->_platoons[$platoon->area_unique_code][$platoon->unique_code] = $platoon->name;
        return $this;
    }

    /**
     * 设置架
     * @return $this
     */
    final public function setShelves(): self
    {
        foreach (Shelf::all() as $shelf) $this->_shelves[$shelf->platoon_unique_code][$shelf->unique_code] = $shelf->name;
        return $this;
    }

    /**
     * 设置层
     * @return $this
     */
    final public function setTiers(): self
    {
        foreach (Tier::all() as $tier) $this->_tiers[$tier->shelf_unique_code][$tier->unique_code] = $tier->name;
        return $this;
    }

    /**
     * 设置位
     * @return $this
     */
    public function setPositions(): self
    {
        foreach (Position::all() as $position) $this->_positions[$position->tier_unique_code][$position->unique_code] = $position->name;
        return $this;
    }

    /**
     * 设置种类
     * @param array $categories
     * @return $this
     * @throws \Exception
     */
    final public function setCategories(array $categories): self
    {
        $this->_categories = $categories;
        return $this;
    }

    /**
     * 通过文件加载种类
     * @param array $filename
     * @return $this
     */
    final public function setCategoriesWithFile(array $filename): self
    {
        $this->_categories = $this->_file->setPath($this->_root_dir)->joins($filename)->fromJson();
        return $this;
    }

    /**
     * 通过数据库加载种类
     * @return $this
     */
    final public function setCategoriesWithDB(): self
    {
        $this->_categories = DB::table('categories')->where('deleted_at', null)->pluck('name', 'unique_code')->toArray();
        return $this;
    }

    /**
     * 设置种类
     * @param string $category_unique_code
     * @return $this
     * @throws \Exception
     */
    final public function setCategory(string $category_unique_code): self
    {
        if (!$this->_categories) throw new \Exception("未设置种类基础信息");
        $this->_ret["current_category_unique_code"] = $category_unique_code;
        $this->_ret["current_category_name"] = $this->_categories[$category_unique_code];
        return $this;
    }

    /**
     * 设置类型
     * @param array $entire_models
     * @return $this
     */
    final public function setEntireModels(array $entire_models): self
    {
        $this->_entire_models = $entire_models;
        return $this;
    }

    /**
     * 通过文件设置类型
     * @param array $filename
     * @return $this
     */
    final public function setEntireModelsWithFile(array $filename): self
    {
        $this->_entire_models = $this->_file->setPath($this->_root_dir)->joins($filename)->fromJson();
        return $this;
    }

    /**
     * 通过数据库加载类型
     * @return $this
     */
    final public function setEntireModelsWithDB(): self
    {
        $this->_entire_models = DB::table('entire_models as em')
            ->select([
                'c.name as cn',
                'em.unique_code as emu',
                'em.name as emn',
            ])
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->where('em.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->get()
            ->groupBy('cn')
            ->map(function ($val) {
                return array_pluck($val, 'emn', 'emu');
            })
            ->toArray();
        return $this;
    }

    /**
     * 设置类型
     * @param string $entire_model_unique_code
     * @return $this
     * @throws \Exception
     */
    final public function setEntireModel(string $entire_model_unique_code): self
    {
        if (!$this->_categories) throw new \Exception("未设置类型基础信息");
        $this->_ret["current_entire_model_unique_code"] = $entire_model_unique_code;
        $this->_ret["current_entire_model_name"] = $this->_entire_models[$entire_model_unique_code];
        return $this;
    }

    /**
     * 设置型号和子类
     * @param array $sub_models
     * @return $this
     */
    final public function setSubModels(array $sub_models = null): self
    {
        $this->_sub_models = $sub_models;
        return $this;
    }

    /**
     * 通过文件设置型号和子类
     * @param array $filename
     * @return $this
     */
    final public function setSubModelsWithFile(array $filename): self
    {
        $this->_sub_models = $this->_file->setPath($this->_root_dir)->joins($filename)->fromJson();
        return $this;
    }

    /**
     * 通过数据库加载子类和型号
     * @return $this
     */
    final public function setSubModelsWithDB(): self
    {
        $this->_sub_models = DB::table('entire_models as sm')
            ->select([
                'em.name as emn',
                'sm.unique_code as smu',
                'sm.name as smn',
            ])
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->where('sm.deleted_at', null)
            ->where('em.deleted_at', null)
            ->where('sm.is_sub_model', true)
            ->where('em.is_sub_model', false)
            ->get()
            ->groupBy('emn')
            ->map(function ($val) {
                return array_pluck($val, 'smn', 'smu');
            })
            ->merge(DB::table('part_models as pm')
                ->select([
                    'em.name as emn',
                    'pm.unique_code as pmu',
                    'pm.name as pmn',
                ])
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->where('pm.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('pc.deleted_at', null)
                ->where('pc.is_main', true)
                ->get()
                ->groupBy('emn')
                ->map(function ($val) {
                    return array_pluck($val, 'pmn', 'pmu');
                })->toArray())
            ->toArray();
        return $this;
    }

    /**
     * 设置型号和子类
     * @param string $sub_model_unique_code
     * @return $this
     * @throws \Exception
     */
    final public function setSubModel(string $sub_model_unique_code): self
    {
        if (!$this->_categories) throw new \Exception("未设置类型基础信息");
        $this->_ret["current_sub_model_unique_code"] = $sub_model_unique_code;
        $this->_ret["current_sub_model_name"] = $this->_entire_models[$sub_model_unique_code];
        return $this;
    }

    /**
     * 设置供应商
     * @param array $factories
     * @return $this
     */
    final public function setFactories(array $factories): self
    {
        $this->_factories = $factories;
        return $this;
    }

    /**
     * 通过文件设置供应商
     * @param array $filename
     * @return $this
     */
    final public function setFactoriesWithFile(array $filename): self
    {
        $this->_factories = $this->_file->setPath($this->_root_dir)->joins($filename)->fromJson();
        return $this;
    }

    /**
     * 生成搜索条件组
     * @param string $category_unique_code
     * @param string $entire_model_unique_code
     * @param string $sub_model_unique_code
     * @param string $factory_name
     * @param string $factory_unique_code
     * @param string $scene_workshop_unique_code
     * @param string $station_name
     * @param string $status_unique_code
     * @param string $storehouse_unique_code
     * @param string $area_unique_code
     * @param string $platoon_unique_code
     * @param string $shelf_unique_code
     * @param string $tier_unique_code
     * @param string $position_unique_code
     * @param string $line_unique_code
     * @return $this
     * @throws \Exception
     */
    final public function make(
        string $category_unique_code = "",
        string $entire_model_unique_code = "",
        string $sub_model_unique_code = "",
        string $factory_name = "",
        string $factory_unique_code = "",
        string $scene_workshop_unique_code = "",
        string $station_name = "",
        string $status_unique_code = "",
        string $storehouse_unique_code = "",
        string $area_unique_code = "",
        string $platoon_unique_code = "",
        string $shelf_unique_code = "",
        string $tier_unique_code = "",
        string $position_unique_code = "",
        string $line_unique_code = ""
    ): self
    {
        $this->_ret["categories"] = $this->_categories;
        $this->_ret["entire_models"] = $this->_entire_models;
        $this->_ret["sub_models"] = $this->_sub_models;

        # 确定当前选中种类
        $this->_ret["current_category_unique_code"] = $category_unique_code;
        if (!empty($category_unique_code)) {
            if (empty($this->_categories)) throw new \Exception("未设置种类基础信息");
            $this->_ret["current_category_name"] = $this->_categories[$category_unique_code];
        }

        # 确定当前选中类型
        $this->_ret["current_entire_model_unique_code"] = $entire_model_unique_code;
        if (!empty($entire_model_unique_code) && !empty($category_unique_code)) {
            if (empty($this->_entire_models)) throw new \Exception("未设置类型基础信息");
            $this->_ret["current_entire_model_name"] = $this->_entire_models[$this->_ret["current_category_name"]][$entire_model_unique_code];
        }

        # 确定当前选中的子类
        $this->_ret["current_sub_model_unique_code"] = $sub_model_unique_code;
        if (!empty($sub_model_unique_code) && !empty($entire_model_unique_code) && !empty($category_unique_code)) {
            if (empty($this->_sub_models)) throw new \Exception("未设置型号和子类基础信息");
            $this->_ret["current_sub_model_name"] = $this->_ret["current_entire_model_name"] ? $this->_sub_models[$this->_ret["current_entire_model_name"]][$sub_model_unique_code] : "";
        }

        $this->_ret["factories"] = $this->_factories;
        $this->_ret["current_factory_name"] = $factory_name;
        $this->_ret["current_factory_unique_code"] = $factory_unique_code;

        $this->_ret["maintains"] = $this->_maintains;
        $this->_ret["maintains_by_line"] = $this->_maintains_by_line;
        $this->_ret["scene_workshops"] = $this->_scene_workshops;
        $this->_ret["lines"] = $this->_lines;

        if ($line_unique_code) {
            $this->_ret['current_scene_workshop_unique_code'] = '';
            $this->_ret['current_line_unique_code'] = $line_unique_code;
            $this->_ret['current_station_names'] = $this->_ret['maintains_by_line'][$this->_lines[$line_unique_code]];
            $this->_ret["maintain_type"] = "current_station_names";
        }

        if ($scene_workshop_unique_code) {
            if (empty($this->_ret["maintains"])) throw new \Exception("未设置台账信息");
            if (empty($this->_ret["scene_workshops"])) throw new \Exception("未设置现场车间列表");
            $this->_ret["current_scene_workshop_unique_code"] = $scene_workshop_unique_code;
            $this->_ret['current_line_unique_code'] = '';
            $this->_ret["current_station_names"] = $this->_ret["maintains"][$this->_ret["scene_workshops"][$scene_workshop_unique_code]];
            $this->_ret["maintain_type"] = "current_station_names";
        }

        if ($station_name) {
            $this->_ret["current_station_name"] = $station_name;
            $this->_ret["maintain_type"] = "current_station_name";
        }

        $this->_ret["statuses"] = $this->_statuses;
        if ($status_unique_code) {
            $this->_ret['current_scene_workshop_unique_code'] = $scene_workshop_unique_code;
            $this->_ret['current_line_unique_code'] = $line_unique_code;
            $this->_ret["current_status_unique_code"] = $status_unique_code;
            $this->_ret["current_status_name"] = $this->_ret["statuses"][$status_unique_code];
        }
        $this->_ret["storehouses"] = $this->_storehouses;
        if ($storehouse_unique_code) $this->_ret['current_storehouse_unique_code'] = $storehouse_unique_code;
        $this->_ret['areas'] = $this->_areas;
        if ($area_unique_code) $this->_ret['current_area_unique_code'] = $area_unique_code;
        $this->_ret['platoons'] = $this->_platoons;
        if ($platoon_unique_code) $this->_ret['current_platoon_unique_code'] = $platoon_unique_code;
        $this->_ret['shelves'] = $this->_shelves;
        if ($shelf_unique_code) $this->_ret['current_shelf_unique_code'] = $shelf_unique_code;
        $this->_ret['tiers'] = $this->_tiers;
        if ($tier_unique_code) $this->_ret['current_tier_unique_code'] = $tier_unique_code;
        $this->_ret['positions'] = $this->_positions;
        if ($position_unique_code) $this->_ret['current_position_unique_code'] = $position_unique_code;

        return $this;
    }

    /**
     * 获取数据
     * @param string|null $name
     * @return array|mixed
     * @throws \Exception
     */
    final public function get(string $name = null)
    {
        if ($name) {
            if (!array_key_exists($name, $this->_ret)) throw new \Exception("数据不存在：{$name}");
            return $this->_ret[$name];
        } else {
            return $this->_ret;
        }
    }

    /**
     * 获取json格式数据
     * @param string|null $name
     * @param int $option
     * @return string
     * @throws \Exception
     */
    final public function toJson(string $name = null, $option = 256): string
    {
        if ($name) {
            if (!array_key_exists($name, $this->_ret)) throw new \Exception("数据不存在：{$name}");
            $ret = $this->_ret[$name];
        } else {
            $ret = $this->_ret;
        }

        return json_encode($ret, $option);
    }

}
