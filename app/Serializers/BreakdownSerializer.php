<?php

namespace App\Serializers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BreakdownSerializer
{
    public static $_INS = null;
    public static $DEFAULT_TABLE_NAME = 'default_table_name';
    public static $IS_HARD_FACTORY_RELATIONSHIP = 'is_hard_factory_relationship';
    public static $IS_INCLUDE_STATION = 'is_include_station';
    public static $IS_HARD_SCENE_WORKSHOP_RELATIONSHIP = 'is_hard_scene_workshop_relationship';
    public static $IS_HARD_STATION_RELATIONSHIP = 'is_hard_station_relationship';
    public static $IS_PART = 'is_part';

    private $_options = [
        'default_table_name' => 'repair_base_breakdown_order_entire_instances as rbboei',
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

    final private function _generateCondition(Builder $builder): Builder
    {
        return $builder
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
                request('station_unique_code'),
                function ($query, $station_unique_code) {
                    $query->where('s.unique_code', $station_unique_code);
                }
            )
            ->when(
                request('status'),
                function ($query, $status) {
                    $query->where('ei.status', $status);
                }
            );
    }

    final public function generateStandardRelationshipS(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->join(DB::raw('repair_base_breakdown_orders rbbo'), 'rbbo.serial_number', '=', 'rbboei.in_sn')
            ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbboei.old_entire_instance_identity_code')
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'rbboei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'rbboei.scene_workshop_name')
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
            ->where('rbbo.direction', '=', 'IN')
            ->where('rbbo.status', '=', 'DONE')
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('ei.deleted_at')
            ->whereNull('sc.deleted_at')
            ->where('sc.type', 'SCENE_WORKSHOP')
            ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
            ->where('s.type', 'STATION')
            ->whereNull('s.deleted_at')
            ->when(
                !is_null($this->_options[self::$IS_PART]),
                function ($query) {
                    $query->where('ei.is_part', $this->_options[self::$IS_PART]);
                }
            );

        return $this->_generateCondition($db);
    }

    final public function generateStandardRelationshipQ(): Builder
    {
        $db = DB::table($this->_options[self::$DEFAULT_TABLE_NAME])
            ->join(DB::raw('repair_base_breakdown_orders rbbo'), 'rbbo.serial_number', '=', 'rbboei.in_sn')
            ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'rbboei.old_entire_instance_identity_code')
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->{$this->_options[self::$IS_HARD_FACTORY_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('factories f'), 'f.name', '=', 'ei.factory_name')
            ->{$this->_options[self::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains s'), 's.name', '=', 'rbboei.maintain_station_name')
            ->{$this->_options[self::$IS_HARD_STATION_RELATIONSHIP] ? 'join' : 'leftJoin'}(DB::raw('maintains sc'), 'sc.name', '=', 'rbboei.scene_workshop_name')
            ->where('rbbo.direction', '=', 'IN')
            ->where('rbbo.status', '=', 'DONE')
            ->where('ei.status', '<>', 'SCRAP')
            ->whereNull('ei.deleted_at')
            ->whereNull('sm.deleted_at')
            ->where('sm.is_sub_model', true)
            ->whereNull('em.deleted_at')
            ->where('em.is_sub_model', false)
            ->whereNull('c.deleted_at')
            ->whereNull('sc.deleted_at')
            ->where('sc.type', 'SCENE_WORKSHOP')
            ->where('sc.parent_unique_code', '=', env('ORGANIZATION_CODE'))
            ->where('s.type', 'STATION')
            ->whereNull('s.deleted_at')
            ->when(
                request('model_unique_code'),
                function ($query, $model_unique_code) {
                    $query->where('sm.unique_code', $model_unique_code);
                }
            );
        return $this->_generateCondition($db);
    }
}
