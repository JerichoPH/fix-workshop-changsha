<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Warehouse
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $state 状态：'START'开始, 'END'结束, 'CANCEL'取消
 * @property string $unique_code
 * @property string $direction 入库（绑定位置）：IN
 * 出库（确定出库）：OUT
 * 报废（选择报废）：SCRAP
 * 报损（选择报损）：FRMLOSS
 * @property int $account_id 操作人id
 * @property string $receiver 领取人
 * @property string $go_direction 去向
 * @property string|null $connection_phone
 * @property string|null $maintain_unique_code 车站车间关联编码
 * @property-read \App\Model\Account $WithAccount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseMaterial[] $WithWarehouseMaterials
 * @property-read int|null $with_warehouse_materials_count
 * @property-read mixed $s_t_a_t_e
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Warehouse onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereConnectionPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereGoDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereMaintainUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Warehouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Warehouse withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Warehouse withoutTrashed()
 * @mixin \Eloquent
 */
class Warehouse extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static $DIRECTION = [
        'IN_WAREHOUSE' => '入库',
        'OUT_WAREHOUSE' => '出库',
        'SCRAP' => '报废',
        'FRMLOSS' => '报损',
    ];

    public static $STATE = [
        'START' => '开始',
        'END' => '结束',
        'CANCEL' => '作废'
    ];

    public function getDirectionAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$DIRECTION[$value],
        ];
    }

    public function getSTATEAttribute($value)
    {
        return self::$STATE[$value];
    }

    /**
     * 生成编号
     * @param string $direction
     * @return string
     */
    final public function getUniqueCode(string $direction)
    {
        $time = date("Ymd", time());
        $ware = Warehouse::where('direction', $direction)->orderby('unique_code', 'desc')->select('unique_code')->first();
        if (empty($ware)) {
            $unique_code = $direction . $time . '0001';
        } else {
            if (strstr($ware->unique_code, $time)) {
                $suffix = sprintf("%04d", substr($ware->unique_code, -4) + 1);
                $unique_code = $direction . $time . $suffix;
            } else {
                $unique_code = $direction . $time . '0001';
            }
        }
        return $unique_code;
    }

    public function WithAccount()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function WithWarehouseMaterials()
    {
        return $this->hasMany(WarehouseMaterial::class, 'warehouse_unique_code', 'unique_code');
    }

}
