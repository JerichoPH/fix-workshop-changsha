<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StatisticsService
{

    /**
     * 生成车站缓存
     * @param bool $is_array
     * @return array|false|string
     */
    final public function makeStation(bool $is_array = false)
    {
        $stations = [];
        $maintains = DB::table('maintains as m1')
            ->select([
                'm1.name as station_name',
                'm1.unique_code as station_unique_code',
                'm2.name as scene_workshop_name'
            ])
            ->join(DB::raw('maintains m2'), 'm2.unique_code', '=', 'm1.parent_unique_code')
            ->where('m1.deleted_at', null)
            ->where('m2.deleted_at', null)
            ->get();
        foreach ($maintains as $maintain) $stations[$maintain->scene_workshop_name][$maintain->station_name] = $maintain->station_unique_code;

        return $is_array ? $stations : json_encode($stations, 256);
    }

    /**
     * 生成种类
     * @param bool $is_array
     * @return array|string
     */
    final public function makeCategories(bool $is_array = false)
    {
        $categories = DB::table('categories as c')->where('c.deleted_at', null)->pluck('unique_code', 'name');
        return $is_array ? $categories->toJson() : $categories->toJson(256);
    }

    /**
     * 生成种类缓存
     * @param bool $is_array
     * @return array|false|string
     */
    final public function makeCategoryAndEntireModel(bool $is_array = false)
    {
        $ce = [];
        foreach (DB::table('entire_models as em')
                     ->select([
                         'c.name as category_name',
                         'em.name as entire_model_name',
                         'em.unique_code as entire_model_unique_code'
                     ])
                     ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                     ->where('em.deleted_at', null)
                     ->where('c.deleted_at', null)
                     ->where('em.is_sub_model', false)
                     ->get() as $item) {
            $ce[$item->category_name][$item->entire_model_name] = $item->entire_model_unique_code;
        }
        return $is_array ? $ce : json_encode($ce, 256);
    }

    /**
     * 生成型号缓存
     * @param bool $is_array
     * @return array|string
     */
    final public function makeSubModel(bool $is_array = false)
    {
        $partQuery = DB::table('part_models as pm')->select('unique_code', 'name')->where('pm.deleted_at', null);
        $subModels = DB::table('entire_models as em')->where('em.is_sub_model', true)->where('em.deleted_at', null)
            ->union($partQuery)
            ->pluck('unique_code', 'name');
        return $is_array ? $subModels->toArray() : $subModels->toJson(256);
    }
}
