<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $warehouse_report_serial_number
 * @property string $entire_instance_identity_code
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string $maintain_station_name 车站名称
 * @property string $maintain_location_code 组合位置
 * @property string $crossroad_number 道岔号
 * @property string $traction 牵引
 * @property string $line_name 线制
 * @property string $crossroad_type 道岔类型
 * @property int $extrusion_protect 防挤压保护罩
 * @property string $point_switch_group_type 转辙机分组类型
 * @property string $open_direction 开向
 * @property string $said_rod 表示杆特征
 * @property int $is_out 是否已经完成出所
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PivotWarehouseReportEntireInstanceAndBreakdownTypes[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\WarehouseReport $WarehouseReport
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportEntireInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereInWarehouseBreakdownExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereIsOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportEntireInstance whereWarehouseReportSerialNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportEntireInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportEntireInstance withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportEntireInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    final public function WarehouseReport()
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'warehouse_report_serial_number');
    }

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    final public function BreakdownTypes()
    {
        return $this->hasMany(PivotWarehouseReportEntireInstanceAndBreakdownTypes::class, 'warehouse_report_entire_instance_id', 'id');
    }
}
