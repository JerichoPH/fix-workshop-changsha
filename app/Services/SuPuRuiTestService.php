<?php

namespace App\Services;

use App\Facades\SuPuRuiApi;
use App\Facades\SuPuRuiSdk;
use Jericho\CommonHelper;

class SuPuRuiTestService
{
    final public function api1()
    {
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->test());
    }

    final public function api2()
    {
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->password(''));
    }

    final public function api3()
    {
        # 接口3：写入设备分类说明
        CommonHelper::printRDie(SuPuRuiApi::debug()
            ->returnType('x2a')
            ->insertEntireModels_S([
                SuPuRuiSdk::makeEntireModel(
                    'S0301N01',
                    'S0301',
                    'ZD6转辙机'
                )
            ])
        );
    }

    final public function api4()
    {
        # 接口4：写入设备单元信息
        CommonHelper::printRDie(SuPuRuiApi::debug()
            ->returnType('x2a')
            ->insertEntireInstances_S([
                SuPuRuiSdk::makeEntireInstance_S(
                    'S0301B05000001',
                    'S0301N01',
                    'ZZJ0000001',
                    3,
                    'A12',
                    'B050',
                    0,
                    'G00001'
                )
            ])
        );
    }

    final public function api5()
    {
        # 接口5：写入设备单元维修记录
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->insertFixWorkflows_S([SuPuRuiSdk::makeFixWorkflow_S(
            md5(env('ORGANIZATION_CODE') . time() . rand(1000, 9999)),
            'S0301B05000001',
            date('Y-m-d'),
            1
        )]));
    }

    final public function api6()
    {
        # 接口6：写入关键器件分类说明
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->insertEntireModels_Q([SuPuRuiSdk::makeEntireModel(
            'Q010101',
            'Q0101',
            'JWXC-1000'
        )]));
    }

    final public function api7()
    {
        # 接口7：写入关键器件信息
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->insertEntireInstances_Q([SuPuRuiSdk::makeEntireInstance_Q(
            'Q010101B04800000001',
            'Q010101',
            'JDQ0000001',
            'JDQ8888881',
            0,
            'A12',
            'B048'
        )]));
    }

    final public function api8()
    {
        # 接口8：写入关键器件维修记录
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->insertFixWorkflows_Q([SuPuRuiSdk::makeFixWorkflow_Q(
            md5(env('ORGANIZATION_CODE') . time() . rand(1000, 9999)),
            'Q010101B04800000001',
            1,
            date('Y-m-d')
        )]));
    }

    final public function api10()
    {
        # 接口10：获取获取设备维修记录（动态）
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->findFixWorkflow_S('S0301B05000001'));
    }

    public function api11()
    {
        # 接口11：获取获取关键器件维修记录（动态）
        CommonHelper::printRDie(SuPuRuiApi::debug()->returnType('x2a')->findFixWorkflow_Q('Q01001B04800000001'));
    }
}
