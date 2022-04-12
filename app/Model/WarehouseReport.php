<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $processor_id 经手人编号
 * @property string|null $processed_at 执行时间
 * @property string|null $connection_name 联系人
 * @property string|null $connection_phone 联系电话
 * @property string $type 类型
 *     BUY_IN新购
 *     FIXING检修
 *     ROTATE轮换中
 *   FACTORY_RETURN返厂入所
 *     INSTALL安装
 *     RETURN_FACTORY返厂
 *     SCRAP报废
 *     BATCH_WITH_OLD旧设备批量导入
 *     HIGH_FREQUENCY高频修
 * @property string $direction 方向
 * @property string $serial_number 流水号
 * @property string|null $scene_workshop_name
 * @property string|null $station_name
 * @property int|null $work_area_id 1转辙机工区
 * 2继电器工区
 * 3综合工区
 * @property string $scene_workshop_unique_code
 * @property string $maintain_station_unique_code 车站
 * @property string $status 状态
 * @property string $v250_task_order_sn
 * @property-read \App\Model\Account $Processor
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseReportEntireInstance[] $WarehouseReportEntireInstances
 * @property-read int|null $warehouse_report_entire_instances_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereConnectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereConnectionPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereSceneWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereSceneWorkshopUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereV250TaskOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReport whereWorkAreaId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReport withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReport withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReport extends Model
{
    use SoftDeletes;

    public static $TYPE = [
        'NORMAL' => '通用',
        'BUY_IN' => '采购入所',
        'INSTALLING' => '备品',
        'INSTALLED' => '已安装',
        'FIXING' => '入所检修',
        'ROTATE' => '轮换中',
        'FACTORY_RETURN' => '返厂中',
        'INSTALL' => '安装出所',
        'RETURN_FACTORY' => '返厂回所',
        'SCRAP' => '报废',
        'BATCH_WITH_OLD' => '批量导入旧设备',
        'HIGH_FREQUENCY' => '高频/状态修',
        'BREAKDOWN' => '故障修',
        'EXCHANGE_MODEL' => '更换型号',
        'NEW_STATION' => '新站',
        'FILL_FIX' => '大修',
        'EXCHANGE_STATION' => '站改',
        'STATION_REMOULD' => '站改',
        'TECHNOLOGY_REMOULD' => '技改',
        'SCENE_BACK_IN' => '现场退回',
    ];

    public static $DIRECTION = [
        'IN' => '入所',
        'OUT' => '出所',
    ];

    protected $guarded = [];

    public function prototype($attributeKey)
    {
        return @$this->attributes[$attributeKey];
    }

    public function getTypeAttribute($value)
    {
        return @self::$TYPE[$value] ?: '无';
    }

    public function getDirectionAttribute($value)
    {
        return @self::$DIRECTION[$value] ?: '无';
    }

    public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    public function WarehouseReportEntireInstances()
    {
        return $this->hasMany(WarehouseReportEntireInstance::class, 'warehouse_report_serial_number', 'serial_number');
    }
}
