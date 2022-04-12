<?php

namespace App\Serializers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class EntireInstanceSerializer
{
    public static $_INS = null;
    public static $DEFAULT_TABLE_NAME = 'default_table_name';
    public static $IS_HARD_FACTORY_RELATIONSHIP = 'is_hard_factory_relationship';
    public static $IS_INCLUDE_STATION = 'is_include_station';
    public static $IS_HARD_SCENE_WORKSHOP_RELATIONSHIP = 'is_hard_scene_workshop_relationship';
    public static $IS_HARD_STATION_RELATIONSHIP = 'is_hard_station_relationship';
    public static $IS_PART = 'is_part';
    private $_options = [
        'default_table_name' => 'entire_instances as ei',
        'is_hard_factory_relationship' => false,
        'is_include_station' => false,
        'is_hard_scene_workshop_relationship' => false,
        'is_hard_station_relationship' => false,
        'is_part' => null,
    ];

    public static function INS(array $options = []): self
    {
        if (!self::$_INS) self::$_INS = new self();
        self::$_INS->_options = array_merge(self::$_INS->_options, $options);
        return self::$_INS;
    }

    private function __construct()
    {
    }

    /**
     * 台账统计条件
     * @param Builder $builder
     * @return Builder
     */
    private function _generateConditionForMaintainEntireInstanceStatistics(Builder $builder): Builder
    {
        return $builder
            ->where('is_part', false)
            ->when(
                request('category_unique_code'),
                function ($query, $category_unique_code) {
                    $query->where('c.unique_code', $category_unique_code);
                }
            )
            ->when(
                request('entire_model_unique_code'),
                function ($query, $entire_model_unique_code) {
                    $query->where('em.unique_code', $entire_model_unique_code);
                }
            )
            ->when(
                request('status'),
                function ($query, $status) {
                    return is_array($status) ?
                        $query->whereIn('ei.status', $status) :
                        $query->where('ei.status', $status);
                }
            )
            ->when(
                request('factory_unique_code'),
                function ($query, $factory_unique_code) {
                    return $query->where('f.unique_code', $factory_unique_code);
                }
            )
            ->when(
                request('factory_name'),
                function ($query, $factory_name) {
                    return $query->where('ei.factory_name', $factory_name);
                }
            )
            ->when(
                request('station_unique_code'),
                function ($query, $station_unique_code) {
                    return $query->where('s.unique_code', $station_unique_code);
                }
            )
            ->when(
                request('scene_workshop_unique_code'),
                function ($query, $scene_workshop_unique_code) {
                    return $this->_options[self::$IS_INCLUDE_STATION]
                        ? $query->where('sc.unique_code', $scene_workshop_unique_code)
                            ->orWhere('s.parent_unique_code', $scene_workshop_unique_code)
                        : $query->where('sc.unique_code', $scene_workshop_unique_code);
                }
            )
            ->when(
                request('line_unique_code'),
                function ($query, $line_unique_code) {
                    // $station_unique_codes = DB::table('lines_maintains as lm')
                    //     ->select(['s.unique_code as station_unique_code',])
                    //     ->join(DB::raw('`lines` l'), 'l.id', '=', 'lm.lines_id')
                    //     ->join(DB::raw('maintains s'), 's.id', '=', 'lm.maintains_id')
                    //     ->where('l.unique_code', $line_unique_code)
                    //     ->pluck('station_unique_code')
                    //     ->unique()
                    //     ->toArray();
                    // $query->whereIn('s.unique_code', $station_unique_codes);
                    $query
                        ->join(DB::raw('`lines` l'), 'l.id', '=', 'lm.lines_id')
                        ->join(DB::raw('maintains s'), 's.id', '=', 'lm.maintains_id')
                        ->where('l.unique_code', $line_unique_code);
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
                request('install_room_unique_code'),
                function ($query, $install_room_unique_code) {
                    $query->where('maintain_location_code', 'like', "{$install_room_unique_code}%");
                }
            )
            ->when(
                request('install_platoon_unique_code'),
                function ($query, $install_platoon_unique_code) {
                    $query->where('maintain_location_code', 'like', "{$install_platoon_unique_code}%");
                }
            )
            ->when(
                request('install_shelf_unique_code'),
                function ($query, $install_shelf_unique_code) {
                    $query->where('maintain_location_code', 'like', "{$install_shelf_unique_code}%");
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
            );
    }

    /**
     * 台长统计（器材）
     * @return Builder
     */
    final public function generateRelationShipQForMaintainEntireInstanceStatistics(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
            ->whereNull('ei.deleted_at')
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at');
        return $this->_generateConditionForMaintainEntireInstanceStatistics($db);
    }

    /**
     * 台长统计（设备）
     * @return Builder
     */
    final public function generateRelationShipSForMaintainEntireInstanceStatistics(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
            ->when(
                request('model_unique_code'),
                function ($query, $model_unique_code) {
                    $query->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->whereNull('pm.deleted_at')
                        ->whereNull('pc.deleted_at')
                        ->where('pc.is_main', true)
                        ->where('ei.model_unique_code', $model_unique_code)
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at');
                },
                function ($query) {
                    $query->join(DB::raw('entire_models em'), 'em.unique_code', 'ei.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at');
                }
            )
            ->whereNull('ei.deleted_at')
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at');
        return $this->_generateConditionForMaintainEntireInstanceStatistics($db);
    }

    /**
     * 标准搜索条件
     * @param Builder $builder
     * @return Builder
     */
    private function _generateStandardCondition(Builder $builder): Builder
    {
        return $builder
            ->when(
                request('status'),
                function ($query, $status) {
                    return is_array($status) ?
                        $query->whereIn('ei.status', $status) :
                        $query->where('ei.status', $status);
                }
            )
            ->when(
                request('category_unique_code'),
                function ($query, $category_unique_code) {
                    return $query->where('c.unique_code', $category_unique_code);
                }
            )
            ->when(
                request('entire_model_unique_code'),
                function ($query, $entire_model_unique_code) {
                    return $query->where('em.unique_code', $entire_model_unique_code);
                }
            )
            ->when(
                request('factory_unique_code'),
                function ($query, $factory_unique_code) {
                    return $query->where('f.unique_code', $factory_unique_code);
                }
            )
            ->when(
                request('factory_name'),
                function ($query, $factory_name) {
                    return $query->where('ei.factory_name', $factory_name);
                }
            )
            ->when(
                request('station_unique_code'),
                function ($query, $station_unique_code) {
                    return $query->where('s.unique_code', $station_unique_code);
                }
            )
            ->when(
                request('scene_workshop_unique_code'),
                function ($query, $scene_workshop_unique_code) {
                    return $this->_options[self::$IS_INCLUDE_STATION]
                        ? $query->where('sc.unique_code', $scene_workshop_unique_code)
                            ->orWhere('s.parent_unique_code', $scene_workshop_unique_code)
                        : $query->where('sc.unique_code', $scene_workshop_unique_code);
                }
            )
            ->when(
                request('line_unique_code'),
                function ($query, $line_unique_code) {
                    // $station_unique_codes = DB::table('lines_maintains as lm')
                    //     ->select(['s.unique_code as station_unique_code',])
                    //     ->join(DB::raw('`lines` l'), 'l.id', '=', 'lm.lines_id')
                    //     ->join(DB::raw('maintains s'), 's.id', '=', 'lm.maintains_id')
                    //     ->where('l.unique_code', $line_unique_code)
                    //     ->pluck('station_unique_code')
                    //     ->unique()
                    //     ->toArray();
                    // $query->whereIn('s.unique_code', $station_unique_codes);
                    $query
                        ->join(DB::raw('`lines` l'), 'l.id', '=', 'lm.lines_id')
                        ->join(DB::raw('maintains s'), 's.id', '=', 'lm.maintains_id')
                        ->where('l.unique_code', $line_unique_code);
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
            );
    }

    /**
     * 标准搜索（器材）
     * @return Builder
     */
    final public function generateStandardRelationShipQ(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
            ->whereNull('ei.deleted_at')
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at')
            ->when(
                request('model_unique_code'),
                function ($query, $model_unique_code) {
                    return $query->where('sm.unique_code', $model_unique_code);
                }
            );
            // ->when(
            //     !is_null($this->_options[self::$IS_PART]),
            //     function ($query, $is_part) {
            //         $query->where('is_part', $is_part);
            //     }
            // );
        return $this->_generateStandardCondition($db);
    }

    /**
     * 标准搜索（设备）
     * @return Builder
     */
    final public function generateStandardRelationShipS(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
            ->when(
                request('model_unique_code'),
                function ($query, $model_unique_code) {
                    $query->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                        ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->whereNull('pm.deleted_at')
                        ->whereNull('pc.deleted_at')
                        ->where('pc.is_main', true)
                        ->where('ei.model_unique_code', $model_unique_code)
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at');
                },
                function ($query) {
                    $query->join(DB::raw('entire_models em'), 'em.unique_code', 'ei.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->whereNull('em.deleted_at')
                        ->where('em.is_sub_model', false)
                        ->whereNull('c.deleted_at');
                }
            )
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at');
            // ->when(
            //     !is_null($this->_options[self::$IS_PART]),
            //     function ($query, $is_part) {
            //         $query->where('is_part', $is_part);
            //     }
            // );
        return $this->_generateStandardCondition($db);
    }

    /**
     * 搜索页面条件
     * @param Builder $builder
     * @return Builder
     */
    final public function _generateQueryCondition(Builder $builder): Builder
    {
        return $builder
            ->where('ei.status', '<>', 'SCRAP')
            ->when(
                request('identity_code'),
                function ($query, $identity_code) {
                    $query->where('ei.identity_code', $identity_code);
                }
            )
            ->when(
                request('code_value'),
                function ($query) {
                    $code_value = request('code_value');
                    $code_type = request('code_type');
                    $is_code_indistinct = boolval(request('ici', false));
                    $operator = $is_code_indistinct ? 'like' : '=';
                    $code_value = $is_code_indistinct ? "{$code_value}%" : $code_value;
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
                request('statuses'),
                function ($query, $statuses) {
                    $query->whereIn('ei.status', $statuses);
                }
            )
            ->when(
                request('category_unique_code'),
                function ($query) {
                    $query->where('c.unique_code', request('category_unique_code'));
                }
            )
            ->when(
                request('entire_model_unique_code'),
                function ($query, $entire_model_unique_code) {
                    if (strlen(request('entire_model_unique_code')) == 7) {
                        $query->where('em.unique_code', substr($entire_model_unique_code, 0, 5));
                    } else {
                        $query->where('em.unique_code', $entire_model_unique_code);
                    }
                }
            )
            ->when(
                request('model_unique_code'),
                function ($query) {
                    $query->where('ei.model_unique_code', request('model_unique_code'));
                }
            )
            ->when(
                request('factory'),
                function ($query, $factory_name) {
                    if ($factory_name == '无') {
                        $query->where('ei.factory_name', '');
                    } else {
                        $query->where('ei.factory_name', $factory_name);
                    }
                }
            )
            ->when(
                request('factory_name'),
                function ($query, $factory_name) {
                    $query->where('ei.factory_name', $factory_name);
                }
            )
            ->when(
                request('factory_unique_code'),
                function ($query, $factory_unique_code) {
                    $query->where('f.unique_code', $factory_unique_code);
                }
            )
            ->when(
                request('scene_workshop_unique_code'),
                function ($query, $scene_workshop_unique_code) {
                    return $this->_options[self::$IS_INCLUDE_STATION]
                        ? $query->where('sc.unique_code', $scene_workshop_unique_code)
                            ->orWhere('s.parent_unique_code', $scene_workshop_unique_code)
                        : $query->where('sc.unique_code', $scene_workshop_unique_code);
                }
            )
            ->when(
                request('maintain_station_unique_code'),
                function ($query, $maintain_station_unique_code) {
                    $query->where('s.unique_code', $maintain_station_unique_code);
                }
            )
            ->when(
                request('station_unique_code'),
                function ($query, $station_unique_code) {
                    $query->where('s.unique_code', $station_unique_code);
                }
            )
            ->when(
                request('station_name'),
                function ($query, $station_name) {
                    $query->where('ei.maintain_station_name', $station_name);
                }
            )
            ->when(
                request('line_unique_code'),
                function ($query, $line_unique_code) {
                    $query->where('ei.line_unique_code', $line_unique_code);
                }
            )
            ->when(
                request('maintain_location_code'),
                function ($query, $maintain_location_code) {
                    request('maintain_location_code_use_indistinct')
                        ? $query->where('ei.maintain_location_code', 'like', "%{$maintain_location_code}%")
                        : $query->where('ei.maintain_location_code', $maintain_location_code);
                }
            )
            ->when(
                request('crossroad_number'),
                function ($query, $crossroad_number) {
                    request('crossroad_number_use_indistinct')
                        ? $query->where(function ($q) use ($crossroad_number) {
                        $q->where('ei.crossroad_number', 'like', "%{$crossroad_number}%")->orWhere('ei.bind_crossroad_number', 'like', "%{$crossroad_number}%");
                    })
                        : $query->where(function ($q) use ($crossroad_number) {
                        $q->where('ei.crossroad_number', $crossroad_number)->orWhere('ei.bind_crossroad_number', $crossroad_number);
                    });
                }
            )
            ->when(
                request('storehouse_unique_code'),
                function ($query, $storehouse_unique_code) {
                    $query->where('storehouses.unique_code', $storehouse_unique_code);
                }
            )
            ->when(
                request('area_unique_code'),
                function ($query, $area_unique_code) {
                    $query->where('areas.unique_code', $area_unique_code);
                }
            )
            ->when(
                request('platoon_unique_code'),
                function ($query, $platoon_unique_code) {
                    $query->where('platoons.unique_code', $platoon_unique_code);
                }
            )
            ->when(
                request('shelf_unique_code'),
                function ($query, $shelf_unique_code) {
                    $query->where('shelves.unique_code', $shelf_unique_code);
                }
            )
            ->when(
                request('tier_unique_code'),
                function ($query, $tier_unique_code) {
                    $query->where('tiers.unique_code', $tier_unique_code);
                }
            )
            ->when(
                request('position_unique_code'),
                function ($query, $position_unique_code) {
                    $query->where('positions.unique_code', $position_unique_code);
                }
            )
            ->when(
                request('install_room_unique_code'),
                function ($query, $install_root_unique_code) {
                    $query->where('ei.maintain_location_code', 'like', '%' . $install_root_unique_code . '%');
                }
            )
            ->when(
                request('install_platoon_unique_code'),
                function ($query, $install_platoon_unique_code) {
                    $query->where('ei.maintain_location_code', 'like', '%' . $install_platoon_unique_code . '%');
                }
            )
            ->when(
                request('install_shelf_unique_code'),
                function ($query, $install_shelf_unique_code) {
                    $query->where('ei.maintain_location_code', 'like', '%' . $install_shelf_unique_code . '%');
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
            ->when(
                request('source_type_code'),
                function ($query, $source_type_code) {
                    $query->where('ei.source_type', $source_type_code);
                }
            )
            ->when(
                request('source_name'),
                function ($query, $source_name) {
                    $query->where('ei.source_name', 'like', "%{$source_name}%");
                }
            )
            ->when(
                request('work_area_unique_code'),
                function ($query, $work_area_unique_code) {
                    $query->where('ei.work_area_unique_code', $work_area_unique_code);
                }
            )
            ->when(
                request('note'),
                function ($query, $note) {
                    $query->where('ei.note', 'like', "%{$note}%");
                }
            )
            // ->when(
            //     !is_null($this->_options[self::$IS_PART]),
            //     function ($query) {
            //         $query->where('ei.is_part', $this->_options[self::$IS_PART]);
            //     }
            // )
            ->orderByDesc('ei.identity_code');
    }

    /**
     * 搜索页面（器材）
     * @return Builder
     */
    final public function generateQueryRelationShipQ(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name')
            ->leftJoin(DB::raw('`lines` l'), 'l.unique_code', '=', 'ei.line_unique_code')
            ->leftJoin(DB::raw('positions'), 'ei.location_unique_code', '=', 'positions.unique_code')
            ->leftJoin(DB::raw('tiers'), 'positions.tier_unique_code', '=', 'tiers.unique_code')
            ->leftJoin(DB::raw('shelves'), 'tiers.shelf_unique_code', '=', 'shelves.unique_code')
            ->leftJoin(DB::raw('platoons'), 'shelves.platoon_unique_code', '=', 'platoons.unique_code')
            ->leftJoin(DB::raw('areas'), 'platoons.area_unique_code', '=', 'areas.unique_code')
            ->leftJoin(DB::raw('storehouses'), 'areas.storehouse_unique_code', '=', 'storehouses.unique_code')
            ->whereNull('ei.deleted_at')
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at');

        return $this->_generateQueryCondition($db);
    }

    /**
     * 搜索页面（设备）
     * @return Builder
     */
    final public function generateQueryRelationShipS(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME]);
        if ($this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP]) {
            $db->join(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name');
        } else {
            $db->leftJoin(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name');
        }
        if ($this->_options[self::$IS_HARD_STATION_RELATIONSHIP]) {
            $db->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name');
        } else {
            $db->leftJoin(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name');
        }
        if ($this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP]) {
            $db->join(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name');
        } else {
            $db->leftJoin(DB::raw('maintains sc'), 'sc.name', '=', 'ei.maintain_workshop_name');
        }

        $db
            ->join(DB::raw('entire_models em'), 'em.unique_code', 'ei.entire_model_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->leftJoin(DB::raw('`lines` l'), 'l.unique_code', '=', 'ei.line_unique_code')
            ->leftJoin(DB::raw('positions'), 'ei.location_unique_code', '=', 'positions.unique_code')
            ->leftJoin(DB::raw('tiers'), 'positions.tier_unique_code', '=', 'tiers.unique_code')
            ->leftJoin(DB::raw('shelves'), 'tiers.shelf_unique_code', '=', 'shelves.unique_code')
            ->leftJoin(DB::raw('platoons'), 'shelves.platoon_unique_code', '=', 'platoons.unique_code')
            ->leftJoin(DB::raw('areas'), 'platoons.area_unique_code', '=', 'areas.unique_code')
            ->leftJoin(DB::raw('storehouses'), 'areas.storehouse_unique_code', '=', 'storehouses.unique_code')
            ->where('em.is_sub_model', false)
            ->whereNull('f.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('sc.deleted_at');

        return $this->_generateQueryCondition($db);
    }

    /**
     * 查询器材备品和临近车站
     * @param string $from_station_unique_code
     * @param string $entire_model_unique_code
     * @param int $limit
     * @return Builder
     */
    final public function generateInstallingRelationShipQ(
        string $from_station_unique_code
        , string $entire_model_unique_code
        , int $limit = 2
    ): Builder
    {
        return DB::table('entire_instances as ei')
            ->selectRaw(implode(',', [
                'count(sm.unique_code) as aggregate'
                , 'sm.unique_code as unique_code'
                , 'sm.unique_code as name'
                , 'd.from_unique_code'
                , 'd.to_unique_code'
                , 'd.distance'
                , 's.name as station_name'
                , 's.unique_code as station_unique_code'
                , 'ws.name as workshop_name'
                , 'ws.unique_code as workshop_unique_code'
                , 's.contact'
                , 's.contact_phone'
                , 's.lon'
                , 's.lat'
            ]))
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->join(DB::raw('maintains ws'), 'ws.unique_code', '=', 's.parent_unique_code')
            ->join(DB::raw('distance d'), 's.unique_code', '=', 'd.to_unique_code')
            ->where('d.from_unique_code', $from_station_unique_code)
            ->where('d.to_unique_code', '<>', $from_station_unique_code)
            ->where('d.from_type', 'STATION')
            ->where('d.to_type', 'STATION')
            ->orderBy(DB::raw('d.distance+0'))
            ->whereNull('ei.deleted_at')
            ->where('ei.status', 'INSTALLING')
            ->where('ei.entire_model_unique_code', $entire_model_unique_code)
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            // ->when(
            //     !is_null($this->_options[self::$IS_PART]),
            //     function ($query) {
            //         $query->where('ei.is_part', $this->_options[self::$IS_PART]);
            //     }
            // )
            ->orderBy('d.distance')
            ->groupBy(['s.unique_code'])
            ->limit($limit);
    }

    /**
     * 查询设备临近车站备品
     * @param string $from_station_unique_code
     * @param string $entire_model_unique_code
     * @param int $limit
     * @return Builder
     */
    final public function generateInstallingRelationShipS(
        string $from_station_unique_code
        , string $entire_model_unique_code
        , int $limit = 2
    ): Builder
    {
        return DB::table('entire_instances as ei')
            ->selectRaw(implode(',', [
                'count(ei.model_unique_code) as aggregate'
                , 'ei.entire_model_unique_code as unique_code'
                , 'ei.model_name as name'
                , 'd.from_unique_code'
                , 'd.to_unique_code'
                , 'd.distance'
                , 's.name as station_name'
                , 's.unique_code as station_unique_code'
                , 'wd.name as workshop_name'
                , 'ws.unique_code as workshop_unique_code'
                , 's.contact'
                , 's.contact_phone'
                , 's.lon'
                , 's.lat'
            ]))
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
            ->join(DB::raw('maintains ws'), 'ws.unique_code', '=', 's.parent_unique_code')
            ->join(DB::raw('distance d'), 's.unique_code', '=', 'd.to_unique_code')
            ->where('d.from_unique_code', $from_station_unique_code)
            ->where('d.to_unique_code', '<>', $from_station_unique_code)
            ->where('d.from_type', 'STATION')
            ->where('d.to_type', 'STATION')
            ->whereNull('ei.deleted_at')
            ->where('ei.status', 'INSTALLING')
            ->where('ei.entire_model_unique_code', $entire_model_unique_code)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            // ->when(
            //     !is_null($this->_options[self::$IS_PART]),
            //     function ($query) {
            //         $query->where('ei.is_part', $this->_options[self::$IS_PART]);
            //     }
            // )
            ->orderBy('d.distance')
            ->groupBy(['s.unique_code'])
            ->limit($limit);
    }

}
