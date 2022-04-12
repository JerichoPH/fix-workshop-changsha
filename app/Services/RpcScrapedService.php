<?php

namespace App\Services;

use App\Model\EntireInstance;
use App\Model\EntireModel;
use Carbon\Carbon;
use Hprose\Http\Server;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class RpcScrapedService
{
    final public function init()
    {
        $serve = new Server();
        $serve->addMethod('dynamic', $this);
        $serve->addMethod('allCategory', $this);
        $serve->addMethod('category', $this);
        $serve->addMethod('entireModel', $this);
        $serve->addMethod('withSubModel', $this);
        $serve->start();
    }

    /**
     * 获取即时统计
     * @return array|null
     */
    final public function dynamic()
    {
        $fileDir = storage_path('app/超期使用');
        if (!is_dir($fileDir)) return null;

        $categories = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/种类.json')));
        $scrapedWithCategory = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/种类统计.json')));

        return [
            'categories' => $categories,
            'categoryNames' => collect($categories)->values(),
            'scrapedWithCategory' => $scrapedWithCategory
        ];
    }

    /**
     * 全部种类
     * @return array
     */
    final public function allCategory()
    {
        $categories = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/种类.json')));
        $scrapedWithCategory = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/种类统计.json')));

        return [
            'categories' => $categories,
            'categoryNames' => collect($categories)->values()->toJson(),
            'scrapedWithCategory' => $scrapedWithCategory
        ];
    }

    /**
     * 指定种类
     * @param string $categoryUniqueCode
     * @return array
     */
    final public function category(string $categoryUniqueCode)
    {
        $categoryName = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/种类.json')))[$categoryUniqueCode];
        $entireModels = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/类型.json')))[$categoryName];
        $scrapedWithEntireModel = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/类型统计.json')))[$categoryName];

        return [
            'entireModels' => $entireModels,
            'entireModelNames' => collect($entireModels)->values()->toJson(),
            'scrapedWithEntireModel' => $scrapedWithEntireModel
        ];
    }

    /**
     * 指定类型
     * @param string $entireModelUniqueCode
     * @return array
     */
    final public function entireModel(string $entireModelUniqueCode)
    {
        $entireModelName = DB::connection('mysql_as_rpc')->table('entire_models')->where('is_sub_model', false)->where('unique_code', $entireModelUniqueCode)->first(['name'])->name;
        $subModels = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/型号和子类.json')))[$entireModelName];
        $scrapedWithSubModel = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/型号和子类统计.json')))[$entireModelName];

        return [
            'subModels' => $subModels,
            'subModelNames' => collect($subModels)->values()->toJson(),
            'scrapedWithSubModel' => $scrapedWithSubModel
        ];
    }

    /**
     * 指定型号
     * @param string $subModelUniqueCode
     * @return array|\Illuminate\Http\RedirectResponse
     */
    final public function withSubModel(string $subModelUniqueCode)
    {
        $now = Carbon::now()->format('Y-m-d');

        switch (substr($subModelUniqueCode, 0, 1)) {
            case 'Q':
                $sub = DB::connection('mysql_as_rpc')->table('entire_models')->where('is_sub_model', true)->where('unique_code', $subModelUniqueCode)->first(['parent_unique_code', 'name']);
                $parentName = DB::connection('mysql_as_rpc')->table('entire_models')->where('is_sub_model', false)->where('unique_code', $sub->parent_unique_code)->first(['name'])->name;
                $subName = $sub->name;
                $entireInstances = DB::connection('mysql_as_rpc')
                    ->table('entire_instances')
                    ->select([
                        'entire_instances.identity_code',
                        'entire_instances.serial_number',
                        'entire_instances.category_name',
                        'entire_models.name as model_name',
                        'entire_instances.status',
                        'entire_instances.scarping_at',
                        'entire_instances.maintain_station_name',
                        'entire_instances.maintain_location_code',
                    ])
                    ->join('entire_models', 'entire_models.unique_code', '=', 'entire_instances.entire_model_unique_code')
                    ->where('entire_instances.deleted_at', null)
                    ->where('entire_instances.status', '<>', 'SCRAP')
                    ->where('entire_instances.entire_model_unique_code', $subModelUniqueCode)
                    ->where('entire_instances.scarping_at', '<>', null)
                    ->where('entire_instances.scarping_at', '<', $now)
                    ->paginate();
                break;
            case 'S':
                $sub = DB::connection('mysql_as_rpc')
                    ->table('part_models')
                    ->select(['entire_models.name as entire_model_name', 'part_models.name as part_model_name'])
                    ->join('entire_models', 'entire_models.unique_code', '=', 'part_models.entire_model_unique_code')
                    ->where('part_models.unique_code', $subModelUniqueCode)
                    ->first();
                $subName = $sub->part_model_name;
                $parentName = $sub->entire_model_name;
                $entireInstances = DB::connection('mysql_as_rpc')
                    ->table('entire_instances')
                    ->select([
                        'entire_instances.identity_code',
                        'entire_instances.serial_number',
                        'entire_instances.category_name',
                        'part_models.name as model_name',
                        'entire_instances.status',
                        'entire_instances.maintain_station_name',
                        'entire_instances.scarping_at',
                        'entire_instances.open_direction',
                        'entire_instances.to_direction',
                        'entire_instances.crossroad_number',
                        'entire_instances.line_name',
                        'entire_instances.said_rod'
                    ])
                    ->join('part_instances', 'part_instances.entire_instance_identity_code', '=', 'entire_instances.identity_code')
                    ->join('part_models', 'part_models.unique_code', '=', 'part_instances.part_model_unique_code')
                    ->where('part_instances.part_model_unique_code', $subModelUniqueCode)
                    ->where('entire_instances.scarping_at', '<>', null)
                    ->where('entire_instances.scarping_at', '<', $now)
                    ->paginate();
                break;
            default:
                return back()->with('danger', '参数错误');
                break;
        }

        $statistics = TextHelper::parseJson(file_get_contents(storage_path('app/超期使用/型号和子类统计.json')))[$parentName][$subName];

        return [
            'entireInstances' => $entireInstances,
            'statistics' => $statistics,
            'lastPage' => $entireInstances->lastPage(),
            'entireInstanceStatuses'=>EntireInstance::$STATUSES
        ];
    }

}
