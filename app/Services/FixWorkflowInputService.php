<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\TextHelper;

class FixWorkflowInputService
{
    private $_stage = [
        '检前' => 'FIX_BEFORE',
        '检修' => 'FIX_AFTER',
        '验收' => 'CHECKED',
    ];
    private $_entireModels = null;
    private $_accounts = null;


    final public function init()
    {
        $this->_entireModels = DB::table('entire_models')->where('deleted_at', null)->pluck('unique_code', 'name');
        $this->_accounts = DB::table('accounts')->where('deleted_at', null)->where(function ($q) {
            $q->where('workshop_code', null)->orWhere('workshop_code', env('ORGANIZATION_CODE'));
        })->pluck('id', 'nickname');
        return $this;
    }

    /**
     * 读取JSON文件
     * @param string $filename
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    final public function jsonFileRead(string $filename)
    {
        if (!Storage::disk('fixWorkflowInput')->exists($filename)) throw new \Exception("{$filename}不存在");
        return TextHelper::parseJson(Storage::disk('fixWorkflowInput')->get($filename))["Sheet1"];
    }

    /**
     * 获取现有数据库里存在的流水号设备
     * @param array $sheet
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    final public function getExistBySerialNumbers(array $sheet)
    {
        $serialNumbers = [];
        foreach ($sheet as $row) $serialNumbers[] = $row[1];

        $entireInstanceExists = DB::table('entire_instances')->where('deleted_at', null)->whereIn('serial_number', $serialNumbers)->get();
        if ($entireInstanceExists->isEmpty()) throw new \Exception('数据为空');

        return $entireInstanceExists;
    }

    /**
     * 解析66345
     * @param mixed $row
     * @return mixed
     */
    final public function parse_66345($row)
    {
        list($stage, $serialNumber, $entireModelName, $factoryDeviceCode, $maintainStationName, $maintainLocationCode, $fixerName, $checkerName, $fixedAt, $checkedAt, $temperature,
            $record1, $record2, $record3, $record4, $record5, $record6, $record7, $record8, $record9, $record10,
            $record11, $record12, $record13, $record14, $record15, $record16, $record17, $record18, $record19, $record20,
            $record21, $record22, $record23, $record24, $record25, $record26, $note) = $row;
        return $record1;
    }

    /**
     * 66345：Q011301
     * @param string $name
     * @throws \Exception
     */
    final public function input_66345(string $name)
    {
        # 读取文件
        $sheet = $this->jsonFileRead($name);
        # 获取存在的设备
        $this->getExistBySerialNumbers($sheet);

        foreach ($sheet as $row) {
            dump($this->parse_66345($row));
        }
        dd('ok');
    }

    /**
     * 解析70240
     * @param mixed $row
     * @return mixed
     */
    final public function parse_70240($row)
    {
        list($stage, $serialNumber, $entireModelName, $factoryDeviceCode, $maintainStationName, $maintainLocationCode, $fixerName, $checkerName, $fixedAt, $checkedAt, $temperature,
            $record1, $record2, $record3, $record4, $record5, $record6, $record7, $record8, $record9, $record10,
            $record11, $record12, $record13, $record14, $record15, $record16, $record17, $record18, $record19, $record20,
            $record21, $record22, $record23, $record24, $record25, $record26, $note) = $row;
        return $record1;
    }

    /**
     * 70240：Q011302
     * @param string $name
     * @throws \Exception
     */
    final public function input_70240(string $name)
    {
        # 读取文件
        $sheet = $this->jsonFileRead($name);
        # 获取存在的设备
        $entireInstances = $this->getExistBySerialNumbers($sheet);
        dd($entireInstances);

//        foreach ($sheet as $row) {
//            dump($this->parse_66345($row));
//        }
    }

    /**
     * 解析JWXC-1700
     * @param mixed $row
     * @return mixed
     */
    final public function parse_JWXC_1700(array $row)
    {
        list($stage, $serialNumber, $entireModelName, $factoryDeviceCode, $maintainStationName, $maintainLocationCode, $fixerName, $checkerName, $fixedAt, $checkedAt,
            $record1, $record2, $record3, $record4, $record5, $record6, $record7, $record8, $record9, $record10,
            $record11, $record12, $record13, $record14, $record15, $record16, $record17, $record18, $record19, $record20,
            $record21, $record22, $record23, $record24, $record25, $record26, $record27, $record28, $record29, $record30,
            $record31, $record32, $record33, $record34, $record35, $record36, $record37, $record38) = $row;
        # 基础信息
        $entireModel = $this->_entireModels[$entireModelName];
        $stage = $this->_stage[$stage];
        $fixerName = $this->_accounts[$fixerName];
        $checkerName = $this->_accounts[$checkerName];
        $fixedAt = Carbon::createFromFormat('Y/m/d H:i:s', $fixedAt)->format('Y-m-d');
        $checkedAt = Carbon::createFromFormat('Y/m/d H:i:s', $checkedAt)->format('Y-m-d');
        # 检测信息
        $record1 = floatval($record1);
        $record3 = floatval($record3);
        $record5 = floatval($record5);
        $record25 = floatval($record25);

        return [$record1, $record3, $record5, $record25];
    }

    /**
     * 解析k4（JWXC-1700：Q010102）
     * @param string $name
     * @throws \Exception
     */
    final public function input_k4railroad(string $name)
    {
        $sheet = $this->jsonFileRead($name);
        $entireInstances = $this->getExistBySerialNumbers($sheet);
        foreach ($sheet as $row) {
            $a = $this->parse_JWXC_1700($row);
            dd($a);
        }
    }

    final public function input_baiMaLong(string $name)
    {
        $sheet = $this->jsonFileRead($name);
        $entireInstances = $this->getExistBySerialNumbers($sheet);
        foreach ($entireInstances as $entireInstance) {

        }
    }
}
