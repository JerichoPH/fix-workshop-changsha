<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use App\Model\PartInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AutoCollectService
{
    /**
     * 生成自动测试数据
     * @param string $type
     * @param string $factoryDeviceCode
     * @return array|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function makeTestData(string $type, string $factoryDeviceCode)
    {
        switch (strtoupper($type)) {
            case 'ENTIRE':
                return $this->makeEntireTestData($factoryDeviceCode);
                break;
            case 'PART':
                return $this->makePartTestData($factoryDeviceCode);
                break;
        }
    }

    /**
     * 自动生成整件测试数据
     * @param string $entireInstanceFactoryDeviceCode
     * @return array|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function makeEntireTestData(string $entireInstanceFactoryDeviceCode)
    {

    }

    /**
     * 自动生成部件测试数据
     * @param string $partInstanceFactoryDeviceCode
     * @return array|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function makePartTestData(string $partInstanceFactoryDeviceCode)
    {

    }

    /**
     * 保存测试值
     * @param array $testData
     */
    public function saveTestData(array $testData)
    {
        DB::table('fix_workflow_records')->insert();
    }
}
