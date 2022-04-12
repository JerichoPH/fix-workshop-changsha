<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseBreakdownOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number 流水号
 * @property string $scene_workshop_code 现场车间代码
 * @property string $station_code 车站代码
 * @property string $status 状态：
 * UNDONE未完成
 * UNSATISFIED未满足
 * SATISFY满足
 * DONE已完成
 * @property string $direction 方向：
 * IN入所
 * OUT出所
 * @property int $work_area_id 所属工区:
 * 1转辙机
 * 2继电器
 * 3综合
 * @property string $in_sn 高频修入所计划
 * @property int|null $processor_id 经办人
 * @property string|null $processed_at 处理时间
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseBreakdownOrderEntireInstance[] $InEntireInstances
 * @property-read int|null $in_entire_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RepairBaseBreakdownOrderEntireInstance[] $OutEntireInstances
 * @property-read int|null $out_entire_instances_count
 * @property-read \App\Model\Account $Processor
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereInSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereSceneWorkshopCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereStationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrder whereWorkAreaId($value)
 * @mixin \Eloquent
 */
class RepairBaseBreakdownOrder extends Model
{
    public static $STATUSES = [
        'UNDONE' => '未完成',
        'UNSATISFIED' => '未满足',
        'SATISFY' => '满足',
        'DONE' => '已完成',
    ];

    public static $DIRECTIONS = [
        'IN' => '入所',
        'OUT' => '出所',
    ];

    public static $WORK_ARES = [
        1 => '转辙机工区',
        2 => '继电器工区',
        3 => '综合工区'
    ];

    protected $guarded = [];

    final public function getDirectionAttribute($value)
    {
        return self::$DIRECTIONS[$value];
    }

    final public function getWorkAreaIdAttribute($value)
    {
        return @self::$WORK_ARES[$value] ?: '无';
    }

    final public function getStatusAttribute($value)
    {
        return self::$STATUSES[$value];
    }

    final public function InEntireInstances()
    {
        return $this->hasMany(RepairBaseBreakdownOrderEntireInstance::class, 'in_sn', 'serial_number');
    }

    final public function OutEntireInstances()
    {
        return $this->hasMany(RepairBaseBreakdownOrderEntireInstance::class, 'out_sn', 'serial_number');
    }

    final public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
