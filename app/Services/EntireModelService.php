<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EntireModelService
{
    /**
     * 根据型号名称列表获取已经存在和不存在的
     * @param array $entireModelNames
     * @return array
     */
    public static function isExistsByName(array $entireModelNames): array
    {
        $exists = [];
        $noExists = [];
        $entireModelNames = array_unique($entireModelNames);
        foreach ($entireModelNames as $entireModelName) {
            if ($entireModelName) {
                $entireModel = DB::table('entire_models')->where('name', $entireModelName)->first();
                if ($entireModel) {
                    $exists[] = $entireModelName;
                } else {
                    $noExists[] = $entireModelName;
                }
            }
        }
        return [$exists, $noExists];
    }

    /**
     * 根据型号名称获取型号是否存在
     * @param $entireModelName
     * @return bool
     */
    public static function isExistByName($entireModelName): bool
    {
        return DB::table('entire_models')->where('name', $entireModelName)->first() == null;
    }
}
