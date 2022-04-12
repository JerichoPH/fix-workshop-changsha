<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SuPuRuiSdkService
{
    /**
     * 生成设备单元参数
     * @param string $sysId 表ID，设备识别码，长度为：14
     * @param string $speciesNId 设备单元型号代码，长度为：8
     * @param string $productionNumber 出厂编号
     * @param int $status 使用状态：0，在用；1，停用；2，备用；3，入所；4，出所；
     * @param string $railwayId 局ID
     * @param string|null $sectionId 段ID
     * @param int $deleteFlag 删除标志，0：正常；1：已删除；
     * @param string|null $stationId 站场ID，与区间ID二填一
     * @param string|null $intervalId 区间ID，与站场ID二填一
     * @param string|null $coordinate 坐标；公里标或坐标
     * @param string|null $locationOne 安装位置:室外设备:0-线路左侧；1-线路右侧；2-线路中心；车载设备：0-0端；1-1端
     * @param string|null $locationTwo 安装位置:0-心轨；1-尖轨
     * @param string|null $positionId 位置ID（位置码），长度为：14
     * @param string|null $factoryId 生产厂家(厂家代码)
     * @param string|null $productionDate 出厂日期，格式为：2019-07-10 00:00:00
     * @param string|null $useDate 上道日期，格式为：2019-07-10 00:00:00
     * @param int|null $overhaul 检修周期:1,月检;3,季检；6，半年检；12，年检
     * @param int|null $middleRepair 中修周期，以年为单位
     * @param int|null $largeRepair 大修周期，以年为单位
     * @return array
     */
    final public function makeEntireInstance_S(
        string $sysId,
        string $speciesNId,
        string $productionNumber,
        int $status,
        string $railwayId,
        string $sectionId = null,
        int $deleteFlag = 0,
        string $stationId = null,
        string $intervalId = null,
        string $coordinate = null,
        string $locationOne = null,
        string $locationTwo = null,
        string $positionId = null,
        string $factoryId = null,
        string $productionDate = null,
        string $useDate = null,
        int $overhaul = null,
        int $middleRepair = null,
        int $largeRepair = null
    ): array
    {
        return [
            'SysId' => $sysId,
            'DeleteFlag' => $deleteFlag,
            'SpeciesNId' => $speciesNId,
            'ProductionNumber' => $productionNumber,
            'Status' => $status,
            'RailwayId' => $railwayId,
            'SectionId' => $sectionId,
            'StationId' => $stationId,
            'IntervalId' => $intervalId,
            'Coordinate' => $coordinate,
            'LocationOne' => $locationOne,
            'LocationTwo' => $locationTwo,
            'PositionId' => $positionId,
            'FactoryId' => $factoryId,
            'ProductionDate' => $productionDate,
            'UseDate' => $useDate,
            'Overhaul' => $overhaul,
            'MiddleRepair' => $middleRepair,
            'LargeRepair' => $largeRepair,
        ];
    }

    /**
     * 生成型号或分类参数
     * @param string $sysId
     * @param string $pid
     * @param string $speciesName
     * @param int $speciesLevel
     * @param int $deleteFlag
     * @return Collection
     */
    public function makeEntireModel(
        string $sysId,
        string $pid,
        string $speciesName,
        int $speciesLevel = 3,
        int $deleteFlag = 0
    ): Collection
    {
        return collect([
            'SysId' => $sysId,
            'DeleteFlag' => $deleteFlag,
            'PId' => $pid,
            'SpeciesLevel' => $speciesLevel,
            'SpeciesName' => $speciesName,
        ]);
    }

    /**
     * 关键器件
     * @param string $sysId 表ID，关键器件识别码，长度为：19
     * @param string $speciesNId 三级代码，长度为：7
     * @param string $productionNumber 出厂编号
     * @param string $exitNumber 出所编号
     * @param int $status 使用状态：0:在用；1:停用；2:备用；3:入所
     * @param string $railwayId 局ID
     * @param string $sectionId 段ID
     * @param int $deleteFlag 删除标志，0：正常；1：已删除
     * @param string|null $stationId 站场ID，与区间ID二填一
     * @param string|null $intervalId 区间ID，与站场ID二填一
     * @param string|null $positionId 位置ID（位置码），长度为：14
     * @param string|null $location 安装位置
     * @param string|null $equipmentId 归属设备ID
     * @param string|null $factoryId 生产厂家ID
     * @param string|null $productionDate 出厂日期，格式为：2019-07-10 00:00:00
     * @param string|null $useDate 上道日期，格式为：2019-07-10 00:00:00
     * @return array
     */
    public function makeEntireInstance_Q(
        string $sysId,
        string $speciesNId,
        string $productionNumber,
        string $exitNumber,
        int $status,
        string $railwayId,
        string $sectionId,
        int $deleteFlag = 0,
        string $stationId = null,
        string $intervalId = null,
        string $positionId = null,
        string $location = null,
        string $equipmentId = null,
        string $factoryId = null,
        string $productionDate = null,
        string $useDate = null
    ): array
    {
        return [
            'SysId' => $sysId,
            'SpeciesNId' => $speciesNId,
            'ProductionNumber' => $productionNumber,
            'ExitNumber' => $exitNumber,
            'Status' => $status,
            'RailwayId' => $railwayId,
            'SectionId' => $sectionId,
            'DeleteFlag' => $deleteFlag,
            'StationId' => $stationId,
            'IntervalId' => $intervalId,
            'PositionId' => $positionId,
            'Location' => $location,
            'EquipmentId' => $equipmentId,
            'FactoryId' => $factoryId,
            'ProductionDate' => $productionDate,
            'UseDate' => $useDate,
        ];
    }

    /**
     * 生成设备单元维修元素
     * @param string $sysId 表ID，非标，最长为36位，建议使用随机码
     * @param string $equipmentId 设备ID
     * @param string $repairDate 维修日期，格式为：2019-07-10 00:00:00
     * @param int $repairProcess 修程:地面设备 0:故障修,1:检修,2:中修,3:大修,4:更换；车载设备:5:二级修,6:三级,7:四级
     * @param int $deleteFlag 删除标志，0：正常；1：已删除；
     * @param string|null $problem 维修问题
     * @param string|null $overhaulUser 检修人姓名
     * @param string|null $overhaulTime 检修时间，格式为：2019-07-10 00:00:00
     * @param string|null $exitNumber 出所编号
     * @param string|null $softwareVersion 软件版本
     * @param string|null $alarmInformation 报警信息
     * @param string|null $disorderSpeciesId 信号障碍代码
     * @param string|null $repairRecords 维修记录，HTML格式，可嵌入于网页内显示
     * @return Collection
     */
    final public function makeFixWorkflow_S(
        string $sysId,
        string $equipmentId,
        string $repairDate,
        int $repairProcess,
        int $deleteFlag = 0,
        string $problem = null,
        string $overhaulUser = null,
        string $overhaulTime = null,
        string $exitNumber = null,
        string $softwareVersion = null,
        string $alarmInformation = null,
        string $disorderSpeciesId = null,
        string $repairRecords = null
    ): Collection
    {
        return Collection::make([
            'SysId' => $sysId,
            'DeleteFlag' => $deleteFlag,
            'EquipmentId' => $equipmentId,
            'RepairDate' => $repairDate,
            'RepairProcess' => $repairProcess,
            'Problem' => $problem,
            'OverhaulUser' => $overhaulUser,
            'OverhaulTime' => $overhaulTime,
            'ExitNumber' => $exitNumber,
            'SoftwareVersion' => $softwareVersion,
            'AlarmInformation' => $alarmInformation,
            'DisorderSpeciesId' => $disorderSpeciesId,
            'RepairRecords' => $repairRecords,
        ]);
    }

    /**
     * 生成关键器件元素
     * @param string $sysId 表ID，关键器件识别码，长度为：19
     * @param string $deviceId 关键器件ID
     * @param int $repairNumber 第几次入所
     * @param string $repairDate 维修日期，格式为：2019-07-10 00:00:00
     * @param int $deleteFlag 删除标志，0：正常；1：已删除；
     * @param string $overhaulUser 检修人
     * @param string $overhaulTime 维修日期，格式为：2019-07-10 00:00:00
     * @param string $checkUser 验收人
     * @param string $checkTime 验收日期，格式为：2019-07-10 00:00:00
     * @param string $recheckUser 复验人
     * @param string $recheckTime 复验日期，格式为：2019-07-10 00:00:00
     * @param string $spotCheckUser 抽查人
     * @param string $spotCheckTime 抽查时间，格式为：2019-07-10 00:00:00
     * @param string $repairRecords 历史记录表
     * @return Collection
     */
    final public function makeFixWorkflow_Q(
        string $sysId,
        string $deviceId,
        int $repairNumber,
        string $repairDate,
        int $deleteFlag = 0,
        string $overhaulUser = null,
        string $overhaulTime = null,
        string $checkUser = null,
        string $checkTime = null,
        string $recheckUser = null,
        string $recheckTime = null,
        string $spotCheckUser = null,
        string $spotCheckTime = null,
        string $repairRecords = null
    ): Collection
    {
        return Collection::make([
            'SysId' => $sysId,
            'DeleteFlag' => $deleteFlag,
            'DeviceId' => $deviceId,
            'RepairNumber' => $repairNumber,
            'RepairDate' => $repairDate,
            'OverhaulUser' => $overhaulUser,
            'OverhaulTime' => $overhaulTime,
            'CheckUser' => $checkUser,
            'CheckTime' => $checkTime,
            'RecheckUser' => $recheckUser,
            'RecheckTime' => $recheckTime,
            'SpotCheckUser' => $spotCheckUser,
            'SpotCheckTime' => $spotCheckTime,
            'RepairRecords' => $repairRecords,
        ]);
    }
}
