<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Area
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $storehouse_unique_code 仓关联编码
 * @property string $unique_code 区编码
 * @property string $type 仓库类型：成品FIXED，待修品FIXING，报废 SCRAP
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Platoon[] $WithPlatoons
 * @property-read int|null $with_platoons_count
 * @property-read \App\Model\Storehouse $WithStorehouse
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Platoon[] $subset
 * @property-read int|null $subset_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereStorehouseUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Area whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Area extends Model
{
    protected $guarded = [];

    public static $TYPE = [
        'FIXED' => '成品库',
        'FIXING' => '待修品库',
        'SCRAP' => '报废库',
    ];

    public function getTypeAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$TYPE[$value],
        ];
    }

    /**
     * 获取区编码
     * @param string $storehouseUniqueCode
     * @return string
     */
    public function getUniqueCode(string $storehouseUniqueCode)
    {
        $area = Area::with([])->where('storehouse_unique_code', $storehouseUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $storehouseUniqueCode;
        if (empty($area)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $area->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithPlatoons()
    {
        return $this->hasMany(Platoon::class, 'area_unique_code', 'unique_code');
    }

    public function WithStorehouse()
    {
        return $this->belongsTo(Storehouse::class, 'storehouse_unique_code', 'unique_code');
    }

    public function subset()
    {
        return $this->hasMany(Platoon::class, 'area_unique_code', 'unique_code');
    }
}
