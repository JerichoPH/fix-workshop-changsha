<?php

namespace App\Services;

use Hprose\Http\Server;
use Illuminate\Support\Facades\DB;

class RpcDeviceStatusService
{
    public function init()
    {
        $serve = new Server();
        $serve->addMethod('dynamic', $this);
        $serve->start();
    }

    /**
     * 设备动态统计
     * @param string|null $categoryUniqueCode
     * @return array
     */
    public function dynamic(string $categoryUniqueCode = null)
    {
        $getBuilder = function () use ($categoryUniqueCode) {
            return $categoryUniqueCode ?
                DB::connection('mysql_as_rpc')->table('entire_instances')->where("deleted_at", null)->where('category_unique_code', $categoryUniqueCode) :
                DB::connection('mysql_as_rpc')->table('entire_instances')->where("deleted_at", null);
        };

        $categories = DB::connection('mysql_as_rpc')->table('categories')->where('deleted_at', null)->pluck('name', 'unique_code');

        $using = $getBuilder()->whereIn("status", ["INSTALLING", "INSTALLED"])->count("id");
        $fixed = $getBuilder()->where("status", "FIXED")->count("id");
        $returnFactory = $getBuilder()->where("status", "RETURN_FACTORY")->count("id");
        $fixing = $getBuilder()->whereIn("status", ["FIXING", "FACTORY_RETURN", "BUY_IN"])->count("id");
        $total = $getBuilder()->where("status", "<>", "SCRAP")->count("id");

        return [
            $total,
            [
                ["name" => "在用", "value" => $using],
                ["name" => "维修", "value" => $fixing],
                ["name" => "送检", "value" => $returnFactory],
                ["name" => "备用", "value" => $fixed]
            ],
            $categories->toArray()
        ];
    }
}
