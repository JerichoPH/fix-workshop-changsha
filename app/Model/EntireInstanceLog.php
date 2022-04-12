<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\EntireInstanceLog
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $name
 * @property string|null $description
 * @property string $entire_instance_identity_code
 * @property int $type 0：普通描述
 * 1：出入所
 * 2：检修相关
 * 3:绑定RFID相关
 * 4：上下道
 * @property string $url
 * @property string $material_type ENTIRE整件、PART部件
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLog whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceLog withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceLog withoutTrashed()
 * @mixin \Eloquent
 */
class EntireInstanceLog extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static $ICONS = [
        'fa-envelope-o',  # 0普通消息
        'fa-home',  # 1出入所
        'fa-wrench',  # 2检修
        'fa-link',  # 3RFID绑定
        'fa-map-signs',  # 4上下道
        'fa-exclamation',  # 5现场故障描述或入所故障描述
        'fa-envelope-o', # 6生产日期类说明
    ];

    final public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
