<?php

namespace App\Services;

use Carbon\Carbon;
use Jericho\TextHelper;

class ReportService
{
    public function cycleFixWithCategory(
        string $fileDir,
        string $year,
        string $currentCycleFixDate,
        string $categoryUniqueCode = null): array
    {
        $cycleFixWithCategories = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/种类.json"));
        $cycleFixEntireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/类型-种类.json"));
        $missionWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-任务-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $fixedWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-完成-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $realWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-实际-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $planWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-计划-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $missionWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-任务-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $fixedWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-完成-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $realWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-实际-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $planWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-计划-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $missionWithEntireModelAsMonth = [];
        $fixedWithEntireModelAsMonth = [];
        $realWithEntireModelAsMonth = [];
        $planWithEntireModelAsMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $m = str_pad($m, 2, '0', STR_PAD_LEFT);
            $missionWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/任务-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
            $fixedWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/完成-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
            $realWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/实际-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
            $planWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/计划-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        }

        # 准备图表数据
        $missionWithEntireModelAsNames = array_values($cycleFixEntireModels[$cycleFixWithCategories[$categoryUniqueCode]]);
        $missionWithEntireModel = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentCycleFixDate}/任务-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $missionWithEntireModelAsValues = array_values($missionWithEntireModel);
        $fixedWithEntireModel = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentCycleFixDate}/完成-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $fixedWithEntireModelAsValues = array_values($fixedWithEntireModel);
        $realWithEntireModel = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentCycleFixDate}/实际-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $realWithEntireModelAsValues = array_values($realWithEntireModel);
        $planWithEntireModel = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentCycleFixDate}/计划-类型.json"))[$cycleFixWithCategories[$categoryUniqueCode]];
        $planWithEntireModelAsValues = array_values($planWithEntireModel);

        return [
            'year' => $year,
            'date' => Carbon::now()->format('Y-m'),
            'cycleFixWithCategories' => $cycleFixWithCategories,
            'cycleFixEntireModels' => $cycleFixEntireModels[$cycleFixWithCategories[$categoryUniqueCode]],
            'missionWithEntireModelAsColumn' => $missionWithEntireModelAsColumn,
            'fixedWithEntireModelAsColumn' => $fixedWithEntireModelAsColumn,
            'realWithEntireModelAsColumn' => $realWithEntireModelAsColumn,
            'planWithEntireModelAsColumn' => $planWithEntireModelAsColumn,
            'missionWithEntireModelAsRow' => $missionWithEntireModelAsRow,
            'fixedWithEntireModelAsRow' => $fixedWithEntireModelAsRow,
            'realWithEntireModelAsRow' => $realWithEntireModelAsRow,
            'planWithEntireModelAsRow' => $planWithEntireModelAsRow,
            'missionWithEntireModelAsMonth' => $missionWithEntireModelAsMonth,
            'fixedWithEntireModelAsMonth' => $fixedWithEntireModelAsMonth,
            'realWithEntireModelAsMonth' => $realWithEntireModelAsMonth,
            'planWithEntireModelAsMonth' => $planWithEntireModelAsMonth,
            'missionWithEntireModelAsNames' => TextHelper::toJson($missionWithEntireModelAsNames),
            'missionWithEntireModelAsValues' => TextHelper::toJson($missionWithEntireModelAsValues),
            'fixedWithEntireModelAsValues' => TextHelper::toJson($fixedWithEntireModelAsValues),
            'realWithEntireModelAsValues' => TextHelper::toJson($realWithEntireModelAsValues),
            'planWithEntireModelAsValues' => TextHelper::toJson($planWithEntireModelAsValues),
        ];
    }

    /**
     * 处理时间 成为开始结束时间 - 根据年、月份、季度
     * @param string $type
     * @param string $date
     * @return string
     */
    final public function handleDateWithType(string $type, string $date): string
    {
        $t = '';
        $currentYear = date('Y');
        if (!empty($type) && !empty($date)) {
            if ($type == 'year') {
                $start = Carbon::parse($date)->startOfYear()->toDateTimeString();
                $end = Carbon::parse($date)->endOfYear()->toDateTimeString();
                $t = "{$start}~{$end}";
            }
            if ($type == 'month') {
                $start = Carbon::parse($date)->startOfMonth()->toDateTimeString();
                $end = Carbon::parse($date)->endOfMonth()->toDateTimeString();
                $t = "{$start}~{$end}";
            }
            if ($type == 'quarter') {
                $quarter = substr($date, 0, 1);
                $start = Carbon::create($currentYear, $quarter * 3 - 2, 1)->firstOfMonth()->toDateTimeString();
                $end = Carbon::create($currentYear, $quarter * 3, 1)->endOfMonth()->toDateTimeString();

                $t = "{$start}~{$end}";
            }
        }
        return $t;
    }

}
