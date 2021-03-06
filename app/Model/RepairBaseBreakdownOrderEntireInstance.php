<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\RepairBaseBreakdownOrderEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $old_entire_instance_identity_code 设备唯一编号
 * @property string $new_entire_instance_identity_code 新设备唯一编号
 * @property string $maintain_location_code 旧组合位置
 * @property string $crossroad_number 旧道岔号
 * @property string $in_sn 故障修出入计划单流水号
 * @property string $out_sn 故障修出所计划流水号
 * @property int $in_scan 入所计划扫码
 * @property int $out_scan 出所计划扫码
 * @property string $in_warehouse_sn 入所单号
 * @property string $out_warehouse_sn 出所单号
 * @property string|null $source 来源
 * @property string|null $source_traction 来源牵引
 * @property string|null $source_crossroad_number 来源道岔号
 * @property string|null $traction 牵引
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property string $crossroad_type 道岔类型
 * @property string $point_switch_group_type 转辙机分组类型：单双机
 * @property int $extrusion_protect 挤压保护罩
 * @property string $scene_workshop_name 现场车间名称
 * @property string $maintain_station_name 车站名称
 * @property string|null $breakdown_types 故障描述
 * @property string|null $entire_instance_log_ids 设备日志编号组
 * @property int|null $breakdown_log_id 故障日志编号组
 * @property-read \App\Model\BreakdownLog $BreakdownLog
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\BreakdownReportFile[] $BreakdownReportFiles
 * @property-read int|null $breakdown_report_files_count
 * @property-read \App\Model\RepairBaseBreakdownOrder $InOrder
 * @property-read \App\Model\WarehouseReport $InWarehouse
 * @property-read \App\Model\EntireInstance $NewEntireInstance
 * @property-read \App\Model\EntireInstance $OldEntireInstance
 * @property-read \App\Model\RepairBaseBreakdownOrder $OutOrder
 * @property-read \App\Model\WarehouseReport $OutWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereBreakdownLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereBreakdownTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereEntireInstanceLogIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereInScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereInSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereInWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereNewEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereOldEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereOutScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereOutSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereSceneWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereSourceCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereSourceTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RepairBaseBreakdownOrderEntireInstance extends Model
{
    protected $guarded = [];

    final public function InOrder():HasOne
    {
        return $this->hasOne(RepairBaseBreakdownOrder::class, 'serial_number', 'in_sn');
    }

    public function OutOrder(): HasOne
    {
        return $this->hasOne(RepairBaseBreakdownOrder::class, 'serial_number', 'out_sn');
    }

    final public function OldEntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'old_entire_instance_identity_code');
    }

    final public function NewEntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'new_entire_instance_identity_code');
    }

    final public function InWarehouse(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'in_warehouse_sn');
    }

    final public function OutWarehouse(): HasOne
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'out_warehouse_sn');
    }

    final public function BreakdownLog(): HasOne
    {
        return $this->hasOne(BreakdownLog::class, 'id', 'breakdown_log_id');
    }

    final public function BreakdownReportFiles(): HasMany
    {
        return $this->hasMany(BreakdownReportFile::class, 'breakdown_order_entire_instance_id', 'id');
    }

}
