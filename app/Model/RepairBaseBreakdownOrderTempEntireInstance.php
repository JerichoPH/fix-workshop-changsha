<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseBreakdownOrderTempEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $operator_id 操作人
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string $station_breakdown_explain 现场故障描述
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\Account $Operator
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereInWarehouseBreakdownExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereOperatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereStationBreakdownExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseBreakdownOrderTempEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RepairBaseBreakdownOrderTempEntireInstance extends Model
{
    protected $guarded = [];

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class,'identity_code','entire_instance_identity_code');
    }

    final public function Operator()
    {
        return $this->hasOne(Account::class,'id','operator_id');
    }

    final public function BreakdownTypes()
    {
        return $this->hasMany(PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::class,'repair_base_breakdown_order_temp_entire_instance_id','id');
    }
}
