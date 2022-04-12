<?php

namespace App\Services;

use Carbon\Carbon;
use Chumper\Zipper\Facades\Zipper;
use Hprose\Http\Server;
use Jericho\TextHelper;

class RpcPlanAndFinishService
{
    public function init()
    {
        $serve = new Server();
        $serve->addMethod('dateList', $this);
        $serve->addMethod('withMonth', $this);
        $serve->addMethod('withSketch', $this);
        $serve->addMethod('allCategory', $this);
        $serve->addMethod('category', $this);
        $serve->addMethod('entireModel', $this);
        $serve->addMethod('subModel', $this);
        $serve->start();
    }

    /**
     * 获取可选周期修列表
     * @return mixed|null
     */
    public function dateList()
    {
        $filePath = storage_path("app/周期修计划和完成情况");

        return is_file("{$filePath}/dateList.json") ?
            Texthelper::parseJson(file_get_contents("{$filePath}/dateList.json")) :
            null;
    }

    /**
     * 获取月度周期修统计
     * @param string $date
     * @return array
     */
    public function withMonth(string $date)
    {
        if ($date != null) {
            $carbon = Carbon::createFromFormat('Y-m', $date);
            $year = $carbon->year;
            $month = $carbon->format('m');
        }

        $currentPlanAndFinishDate = "{$year}-{$month}";
        $filePath = storage_path("app/周期修计划和完成情况");

        if (!is_dir("{$filePath}/{$year}")) return null;

        $planAndFixedCategories = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/种类.json")));
        $planWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/计划-种类.json")));
        $planWithCategoryAsValues = $planWithCategory->values();
        $fixedWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/完成-种类.json")));
        $fixedWithCategoryAsValues = $fixedWithCategory->values();
        $realWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/实际-种类.json")));
        $realWithCategoryAsValues = $realWithCategory->values();

        return [
            'categories' => $planAndFixedCategories->toArray(),
            'categoryNames' => $planAndFixedCategories->values()->toArray(),
            'planWithCategory' => $planWithCategoryAsValues,
            'fixedWithCategory' => $fixedWithCategoryAsValues,
            'realWithCategory' => $realWithCategoryAsValues,
        ];
    }

    /**
     * 获取段简述
     * @param string $date
     * @return array|null
     */
    public function withSketch(string $date)
    {
        if ($date) {
            $carbon = Carbon::createFromFormat('Y-m', $date);
            $year = $carbon->year;
            $month = $carbon->format('m');
        }

        $currentPlanAndFinishDate = "{$year}-{$month}";
        $filePath = storage_path("app/周期修计划和完成情况");

        if (!is_dir("{$filePath}/{$year}")) return null;

        $planAndFixedCategories = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/种类.json")));
        $planWithCategoryAsNames = $planAndFixedCategories->values()->toArray();
        $planWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/计划-种类.json")));
        $planWithCategoryAsValues = $planWithCategory->values();
        $fixedWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/完成-种类.json")));
        $fixedWithCategoryAsValues = $fixedWithCategory->values();
        $realWithCategory = collect(TextHelper::parseJson(file_get_contents("{$filePath}/{$year}/{$currentPlanAndFinishDate}/实际-种类.json")));
        $realWithCategoryAsValues = $realWithCategory->values();

        $totalWithPlan = $totalWithFixed = $totalWithReal = 0;

        foreach ($planWithCategory as $item) $totalWithPlan += $item;
        foreach ($fixedWithCategory as $item) $totalWithFixed += $item;
        foreach ($realWithCategory as $item) $totalWithReal += $item;


        return [
            'totalWithPlan' => $totalWithPlan,
            'totalWithFixed' => $totalWithFixed,
            'totalWithReal' => $totalWithReal,
        ];
    }

    /**
     * 获取全部种类
     * @param string $date
     * @return array|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function allCategory(string $date)
    {
        if ($date) {
            list($year, $month) = explode('-', $date);
        } else {
            $year = strval(Carbon::now()->year);
        }
        $fileDir = storage_path("app/周期修计划和完成情况/{$year}");

        if (request('download') == '1') {
            $zipName = "{$year}年种类统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}年种类统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        $planAndFixedCategories = TextHelper::parseJson(file_get_contents("{$fileDir}/种类.json"));
        $planWithCategoryAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-计划-种类.json"));
        $fixedWithCategoryAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-完成-种类.json"));
        $realWithCategoryAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/列-实际-种类.json"));
        $planWithCategoryAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-计划-种类.json"));
        $fixedWithCategoryAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-完成-种类.json"));
        $realWithCategoryAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/行-实际-种类.json"));
        $planWithCategoryAsMonth = [];
        $fixedWithCategoryAsMonth = [];
        $realWithCategoryAsMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $m = str_pad($m, 2, '0', STR_PAD_LEFT);
            $planWithCategoryAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/计划-种类.json"));
            $fixedWithCategoryAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/完成-种类.json"));
            $realWithCategoryAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}-{$m}/实际-种类.json"));
        }

        return [
            'cycleFixCategories' => $planAndFixedCategories,
            'planWithCategoryAsNames'=>collect($planAndFixedCategories)->values()->toJson(),
            'planWithCategoryAsColumn' => $planWithCategoryAsColumn,
            'fixedWithCategoryAsColumn' => $fixedWithCategoryAsColumn,
            'realWithCategoryAsColumn' => $realWithCategoryAsColumn,
            'planWithCategoryAsRow' => $planWithCategoryAsRow,
            'fixedWithCategoryAsRow' => $fixedWithCategoryAsRow,
            'realWithCategoryAsRow' => $realWithCategoryAsRow,
            'planWithCategoryAsMonth' => $planWithCategoryAsMonth,
            'fixedWithCategoryAsMonth' => $fixedWithCategoryAsMonth,
            'realWithCategoryAsMonth' => $realWithCategoryAsMonth
        ];
    }

    /**
     * 指定种类
     * @param string $categoryUniqueCode
     * @param string $date
     * @return array|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function category(string $categoryUniqueCode, string $date)
    {
        list($year, $month) = explode('-', $date);
        $currentPlanAndFinishDate = request('date', "{$year}-{$month}");
        $fileDir = storage_path("app/周期修计划和完成情况");

        if (request('download') == '1') {
            $zipName = "{$year}年类型统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$year}年类型统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        $planAndFixedCategories = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/种类.json"));
        $planAndFixedEntireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/类型-种类.json"));
        $planWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-计划-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $fixedWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-完成-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $realWithEntireModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-实际-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $planWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-计划-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $fixedWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-完成-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $realWithEntireModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-实际-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        $planWithEntireModelAsMonth = [];
        $fixedWithEntireModelAsMonth = [];
        $realWithEntireModelAsMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $m = str_pad($m, 2, '0', STR_PAD_LEFT);
            $planWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/计划-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
            $fixedWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/完成-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
            $realWithEntireModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/实际-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]];
        }

        # 准备图表数据
        $planWithEntireModelAsNames = collect($planAndFixedEntireModels[$planAndFixedCategories[$categoryUniqueCode]])->values()->toArray();
        $planWithEntireModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/计划-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]]);
        $planWithEntireModelAsValues = $planWithEntireModel->values();
        $fixedWithEntireModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/完成-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]]);
        $fixedWithEntireModelAsValues = $fixedWithEntireModel->values();
        $realWithEntireModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/实际-类型.json"))[$planAndFixedCategories[$categoryUniqueCode]]);
        $realWithEntireModelAsValues = $realWithEntireModel->values();

        return [
            'year' => $year,
            'date' => Carbon::now()->format('Y-m'),
            'cycleFixCategories' => $planAndFixedCategories,
            'planAndFixedEntireModels' => $planAndFixedEntireModels[$planAndFixedCategories[$categoryUniqueCode]],
            'planWithEntireModelAsColumn' => $planWithEntireModelAsColumn,
            'fixedWithEntireModelAsColumn' => $fixedWithEntireModelAsColumn,
            'realWithEntireModelAsColumn' => $realWithEntireModelAsColumn,
            'planWithEntireModelAsRow' => $planWithEntireModelAsRow,
            'fixedWithEntireModelAsRow' => $fixedWithEntireModelAsRow,
            'realWithEntireModelAsRow' => $realWithEntireModelAsRow,
            'planWithEntireModelAsMonth' => $planWithEntireModelAsMonth,
            'fixedWithEntireModelAsMonth' => $fixedWithEntireModelAsMonth,
            'realWithEntireModelAsMonth' => $realWithEntireModelAsMonth,
            'planWithEntireModelAsNames' => TextHelper::toJson($planWithEntireModelAsNames),
            'planWithEntireModelAsValues' => TextHelper::toJson($planWithEntireModelAsValues),
            'fixedWithEntireModelAsValues' => TextHelper::toJson($fixedWithEntireModelAsValues),
            'realWithEntireModelAsValues' => TextHelper::toJson($realWithEntireModelAsValues)
        ];
    }

    /**
     * 指定类型
     * @param string $entireModelUniqueCode
     * @param string $date
     * @return array|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function entireModel(string $entireModelUniqueCode, string $date)
    {
        if ($date) {
            list($year, $month) = explode('-', $date);
        } else {
            $carbon = Carbon::now();
            $year = strval($carbon->year);
            $month = str_pad($carbon->month, 2, '0', 0);
        }
        $currentPlanAndFinishDate = request('date', "{$year}-{$month}");
        $fileDir = storage_path("app/周期修计划和完成情况");

        if (request('download') == '1') {
            $zipName = "{$year}年型号和子类统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$year}年型号和子类统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        $planAndFixedEntireModels = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/类型.json"));
        $planAndFixedSubModels = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/型号和子类-类型.json"));
        $planWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-计划-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $fixedWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-完成-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $realWithSubModelAsColumn = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/列-实际-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $planWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-计划-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $fixedWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-完成-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $realWithSubModelAsRow = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/行-实际-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        $planWithSubModelAsMonth = [];
        $fixedWithSubModelAsMonth = [];
        $realWithSubModelAsMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $m = str_pad($m, 2, '0', STR_PAD_LEFT);
            $planWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/计划-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
            $fixedWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/完成-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
            $realWithSubModelAsMonth[] = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$year}-{$m}/实际-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]];
        }

        # 准备图表数据
        $planWithSubModelAsNames = collect($planAndFixedSubModels[$planAndFixedEntireModels[$entireModelUniqueCode]])->values()->toArray();
        $planWithSubModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/计划-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]]);
        $planWithSubModelAsValues = $planWithSubModel->values();
        $fixedWithSubModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/完成-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]]);
        $fixedWithSubModelAsValues = $fixedWithSubModel->values();
        $realWithSubModel = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/{$currentPlanAndFinishDate}/实际-型号和子类.json"))[$planAndFixedEntireModels[$entireModelUniqueCode]]);
        $realWithSubModelAsValues = $realWithSubModel->values();

        return [
            'planAndFixedEntireModels' => $planAndFixedEntireModels,
            'planAndFixedSubModels' => $planAndFixedSubModels[$planAndFixedEntireModels[$entireModelUniqueCode]],
            'planWithSubModelAsColumn' => $planWithSubModelAsColumn,
            'fixedWithSubModelAsColumn' => $fixedWithSubModelAsColumn,
            'realWithSubModelAsColumn' => $realWithSubModelAsColumn,
            'planWithSubModelAsRow' => $planWithSubModelAsRow,
            'fixedWithSubModelAsRow' => $fixedWithSubModelAsRow,
            'realWithSubModelAsRow' => $realWithSubModelAsRow,
            'planWithSubModelAsMonth' => $planWithSubModelAsMonth,
            'fixedWithSubModelAsMonth' => $fixedWithSubModelAsMonth,
            'realWithSubModelAsMonth' => $realWithSubModelAsMonth,
            'planWithSubModelAsNames' => TextHelper::toJson($planWithSubModelAsNames),
            'planWithSubModelAsValues' => TextHelper::toJson($planWithSubModelAsValues),
            'fixedWithSubModelAsValues' => TextHelper::toJson($fixedWithSubModelAsValues),
            'realWithSubModelAsValues' => TextHelper::toJson($realWithSubModelAsValues),
        ];
    }

    /**
     * 指定型号或子类
     * @param string $subModelUniqueCode
     * @param string $date
     */
    public function subModel(string $subModelUniqueCode, string $date)
    {
        if ($date) {
            list($year, $month) = explode('-', $date);
        } else {
            $carbon = Carbon::now();
            $year = strval($carbon->year);
            $month = str_pad($carbon->month, 2, '0', 0);
        }
    }
}
