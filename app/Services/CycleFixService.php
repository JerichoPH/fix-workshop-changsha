<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CycleFixService
{
    private $_rootDir = null;  # 基础路径

    /**
     * CycleFixService constructor.
     */
    public function __construct()
    {
        $this->_rootDir = storage_path('app/cycleFix');
    }

    /**
     * 获取所有种类周期修任务统计
     * @param string|null $dateType
     * @param string|null $date
     * @return mixed
     */
    final public function getMissionStatisticsAllCategory(string $dateType = null, string $date = null)
    {
        # 计算时间起点和终点
        switch ($dateType) {
            default:
            case 'year':
                $originAt = Carbon::now()->year($date)->startOfYear()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($date)->endOfYear()->format('Y-m-d H:i:s');
                break;
            case 'month':
                list($year, $month) = explode('-', $date);
                $originAt = Carbon::now()->year($year)->month($month)->startOfMonth()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($year)->month($month)->endOfMonth()->format('Y-m-d H:i:s');
                break;
        }

        $sub_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('entire_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('m.deleted_at', null)
            ->where('m.is_sub_model', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();

        $part_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('part_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('part_categories as pc'), 'pc.id', '=', 'm.part_category_id')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.entire_model_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('m.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('pc.is_main', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();


        $statistics = [];
        foreach ($sub_model_statistics->merge($part_model_statistics)->toArray() as $item) {
            if (!array_key_exists($item->cu, $statistics)) $statistics[$item->cu] = ['parent' => [], 'name' => $item->cn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->emu, $statistics[$item->cu]['subs'])) $statistics[$item->cu]['subs'][$item->emu] = ['parent' => ['unique_code' => $item->cu, 'name' => $item->cn], 'name' => $item->emn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->mu, $statistics[$item->cu]['subs'][$item->emu]['subs'])) $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu] = ['parent' => ['unique_code' => $item->emu, 'name' => $item->emn], 'name' => $item->mn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu]['statistics']['mission_device_count'] += $item->mission_device_count;
        }

        return $statistics;
    }

    /**
     * 获取种类周期修任务统计
     * @param string $uniqueCode
     * @param string|null $dateType
     * @param string|null $date
     * @return mixed
     */
    final public function getMissionStatisticsWithCategory(string $uniqueCode, string $dateType = null, string $date = null)
    {
        # 计算时间起点和终点
        switch ($dateType) {
            default:
            case 'year':
                $originAt = Carbon::now()->year($date)->startOfYear()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($date)->endOfYear()->format('Y-m-d H:i:s');
                break;
            case 'month':
                list($year, $month) = explode('-', $date);
                $originAt = Carbon::now()->year($year)->month($month)->startOfMonth()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($year)->month($month)->endOfMonth()->format('Y-m-d H:i:s');
                break;
        }

        $sub_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('entire_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('m.deleted_at', null)
            ->where('m.is_sub_model', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->where('c.unique_code', $uniqueCode)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();

        $part_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('part_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('part_categories as pc'), 'pc.id', '=', 'm.part_category_id')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.entire_model_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('c.unique_code', $uniqueCode)
            ->where('m.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('pc.is_main', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();


        $statistics = [];
        foreach ($sub_model_statistics->merge($part_model_statistics)->toArray() as $item) {
            if (!array_key_exists($item->cu, $statistics)) $statistics[$item->cu] = ['parent' => [], 'name' => $item->cn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->emu, $statistics[$item->cu]['subs'])) $statistics[$item->cu]['subs'][$item->emu] = ['parent' => ['unique_code' => $item->cu, 'name' => $item->cn], 'name' => $item->emn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->mu, $statistics[$item->cu]['subs'][$item->emu]['subs'])) $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu] = ['parent' => ['unique_code' => $item->emu, 'name' => $item->emn], 'name' => $item->mn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu]['statistics']['mission_device_count'] += $item->mission_device_count;
        }

        return $statistics;
    }

    /**
     * 获取类型周期修任务统计
     * @param string $uniqueCode
     * @param string|null $dateType
     * @param string|null $date
     * @return mixed
     */
    final public function getMissionStatisticsWithEntireModel(string $uniqueCode, string $dateType = null, string $date = null)
    {
        # 计算时间起点和终点
        switch ($dateType) {
            default:
            case 'year':
                $originAt = Carbon::now()->year($date)->startOfYear()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($date)->endOfYear()->format('Y-m-d H:i:s');
                break;
            case 'month':
                list($year, $month) = explode('-', $date);
                $originAt = Carbon::now()->year($year)->month($month)->startOfMonth()->format('Y-m-d H:i:s');
                $finishAt = Carbon::now()->year($year)->month($month)->endOfMonth()->format('Y-m-d H:i:s');
                break;
        }

        $sub_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('entire_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('m.deleted_at', null)
            ->where('m.is_sub_model', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->where('c.unique_code', $uniqueCode)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();

        $part_model_statistics = DB::table('repair_base_cycle_fix_missions as rbcfm')
            ->select([
                'm.unique_code as mu',
                'm.name as mn',
                'em.unique_code as emu',
                'em.name as emn',
                'c.unique_code as cu',
                'c.name as cn',
                'rbcfm.number as mission_device_count',
                'a.id as ai',
                'a.nickname as an',
            ])
            ->join(DB::raw('part_models as m'), 'm.unique_code', '=', 'rbcfm.model_unique_code')
            ->join(DB::raw('part_categories as pc'), 'pc.id', '=', 'm.part_category_id')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'm.entire_model_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('accounts as a'), 'a.id', '=', 'rbcfm.account_id')
            ->whereBetween('rbcfm.created_at', [$originAt, $finishAt])
            ->where('c.unique_code', $uniqueCode)
            ->where('m.deleted_at', null)
            ->where('pc.deleted_at', null)
            ->where('pc.is_main', true)
            ->where('em.deleted_at', null)
            ->where('em.is_sub_model', false)
            ->where('c.deleted_at', null)
            ->groupBy(['m.unique_code', 'm.name', 'em.unique_code', 'em.name', 'c.unique_code', 'c.name', 'rbcfm.number', 'a.id', 'a.nickname'])
            ->get();


        $statistics = [];
        foreach ($sub_model_statistics->merge($part_model_statistics)->toArray() as $item) {
            if (!array_key_exists($item->cu, $statistics)) $statistics[$item->cu] = ['parent' => [], 'name' => $item->cn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->emu, $statistics[$item->cu]['subs'])) $statistics[$item->cu]['subs'][$item->emu] = ['parent' => ['unique_code' => $item->cu, 'name' => $item->cn], 'name' => $item->emn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['statistics']['mission_device_count'] += $item->mission_device_count;
            if (!array_key_exists($item->mu, $statistics[$item->cu]['subs'][$item->emu]['subs'])) $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu] = ['parent' => ['unique_code' => $item->emu, 'name' => $item->emn], 'name' => $item->mn, 'subs' => [], 'statistics' => ['mission_device_count' => 0]];
            $statistics[$item->cu]['subs'][$item->emu]['subs'][$item->mu]['statistics']['mission_device_count'] += $item->mission_device_count;
        }

        return $statistics;
    }
}
