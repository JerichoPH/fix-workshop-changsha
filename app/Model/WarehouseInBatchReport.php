<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\WarehouseInBatchReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_instance_identity_code
 * @property string|null $fix_workflow_serial_number
 * @property int $processor_id
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
 * @property string $direction 扫码类型
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseInBatchReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WarehouseInBatchReport extends Model
{
    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
