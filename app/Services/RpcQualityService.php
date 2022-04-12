<?php

namespace App\Services;

use Carbon\Carbon;
use Chumper\Zipper\Facades\Zipper;
use Hprose\Http\Server;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class RpcQualityService
{
    final public function init()
    {
        $serve = new Server();
        $serve->addMethod('dateList', $this);
        $serve->addMethod('withYear', $this);
        $serve->addMethod('allFactory', $this);
        $serve->addMethod('factory', $this);
        $serve->start();
    }


    /**
     * 获取范围日期
     * @return mixed|null
     */
    final public function dateList()
    {
        $fileDir = storage_path("app/质量报告");
        return is_file("{$fileDir}/dateList.json") ?
            Texthelper::parseJson(file_get_contents("{$fileDir}/dateList.json")) :
            null;
    }

    /**
     * 获取年度质量报告统计
     * @param int $year
     * @return array
     */
    final public function withYear(int $year)
    {
        $quality = function () use ($year): array {
            $fileDir = storage_path("app/质量报告");

            $qualityDateList = is_file("{$fileDir}/dateList.json") ?
                Texthelper::parseJson(file_get_contents("{$fileDir}/dateList.json")) :
                [];

            $qualityFactories = file_get_contents("{$fileDir}/{$year}/供应商.json");
            $qualityRateWithFactory = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/返修率-供应商.json")));
            $qualityFactoryNames = $qualityRateWithFactory->keys()->toJson();

            return [
                $qualityFactories,
                $qualityFactoryNames,
                $qualityDateList,
                $year,
                $qualityRateWithFactory,
            ];
        };

        list(
            $qualityFactories,
            $qualityFactoryNames,
            $qualityDateList,
            $qualityYear,
            $qualityRateWithFactory,
            ) = $quality();

        return [
            'qualityFactories' => $qualityFactories,
            'qualityFactoryNames' => $qualityFactoryNames,
            'qualityDateList' => $qualityDateList,
            'qualityYear' => $qualityYear,
            'qualityRateWithFactory' => $qualityRateWithFactory,
        ];
    }

    /**
     * 所有供应商
     * @param int $year
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    final public function allFactory(int $year)
    {
        $fileDir = storage_path("app/质量报告");

        if (request('download') == '1') {
            $zipName = "{$year}年质量报告.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$year}年质量报告.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        $qualityDateList = is_file("{$fileDir}/dateList.json") ?
            Texthelper::parseJson(file_get_contents("{$fileDir}/dateList.json")) :
            [];

        $qualityRateWithFactory = TextHelper::parseJson(file_get_contents("{$fileDir}/{$year}/返修率-供应商.json"));

        return [
            'qualityRateWithFactory' => $qualityRateWithFactory,
            'qualityDateList' => $qualityDateList,
        ];
    }

    /**
     * 指定供应商
     * @param string $factoryName
     * @return array
     */
    final public function factory(string $factoryName)
    {
        $now = Carbon::now();
        $fileDir = storage_path("app/质量报告");

        # 获取返修率
        $qualityRateWithFactory = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$now->year}/返修率-类型.json")));
        $qualityRateWithEntireModel = $qualityRateWithFactory[$factoryName];
        $factoryNames = $qualityRateWithFactory->keys()->toArray();
        $qualityBreakdownType = collect(TextHelper::parseJson(file_get_contents("{$fileDir}/{$now->year}/故障类型-供应商.json"))[$factoryName]);

        return [
            'factories' => $factoryNames,
            'currentFactory' => $factoryName,
            'rate' => $qualityRateWithEntireModel,
            'breakdownType' => $qualityBreakdownType
        ];
    }
}
