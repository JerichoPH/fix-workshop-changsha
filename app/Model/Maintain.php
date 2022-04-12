<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Maintain
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $unique_code 统一标识
 * @property string $name 名称
 * @property string|null $location_code 位置编码
 * @property string|null $explain 说明
 * @property string|null $parent_unique_code 父级
 * @property string $type 类型
 * @property string|null $lon
 * @property string|null $lat
 * @property string|null $contact
 * @property string|null $contact_phone
 * @property string|null $contact_address
 * @property int $is_show 1：显示
 * 0：不显示
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @property-read \App\Model\Maintain|null $Parent
 * @property-read \App\Model\Maintain $ParentLine
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Maintain[] $Subs
 * @property-read int|null $subs_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Maintain onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereContactAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereParentUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Maintain whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Maintain withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Maintain withoutTrashed()
 * @mixin \Eloquent
 */
class Maintain extends Model
{
    use SoftDeletes;

    public static $TYPES = [
        'WORKSHOP' => '车间',
        'SCENE_WORKSHOP' => '现场车间',
        'STATION' => '车站',
    ];

    protected $guarded = [];

    final public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    final public function getTypeAttribute($value)
    {
        return self::$TYPES[$value];
    }

    final public function EntireInstances()
    {
        return $this->hasMany(EntireInstance::class, 'maintain_identity_code', 'identity_code');
    }

    final public function Parent()
    {
        return $this->belongsTo(self::class, 'parent_unique_code', 'unique_code');
    }

    final public function ParentLine()
    {
        return $this->belongsTo(self::class, 'parent_line_code', 'line_unique_code');
    }

    final public function Subs()
    {
        return $this->hasMany(self::class, 'parent_unique_code', 'unique_code');
    }

    /**
     * 电子图纸
     * @return HasMany
     */
    final public function ElectricImages(): HasMany
    {
        return $this->hasMany(StationElectricImage::class, 'maintain_station_unique_code', 'unique_code');
    }

    /**
     * 机柜
     * @return HasMany
     */
    final public function EquipmentCabinets(): HasMany
    {
        return $this->hasMany(EquipmentCabinet::class, 'maintain_station_unique_code', 'unique_code');
    }
}
