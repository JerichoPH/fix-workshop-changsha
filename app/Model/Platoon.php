<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Platoon
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 排编码
 * @property string $area_unique_code 区关联编码
 * @property-read \App\Model\Area $WithArea
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Shelf[] $WithShelfs
 * @property-read int|null $with_shelfs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Shelf[] $subset
 * @property-read int|null $subset_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereAreaUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Platoon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Platoon extends Model
{
    protected $guarded = [];

    /**
     * 获取排编码
     * @param string $areaUniqueCode
     * @return string
     */
    public function getUniqueCode(string $areaUniqueCode)
    {
        $platoon = Platoon::with([])->where('area_unique_code', $areaUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $areaUniqueCode;
        if (empty($platoon)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $platoon->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithShelfs()
    {
        return $this->hasMany(Shelf::class, 'platoon_unique_code', 'unique_code');
    }

    public function WithArea()
    {
        return $this->belongsTo(Area::class, 'area_unique_code', 'unique_code');
    }

    public function subset()
    {
        return $this->hasMany(Shelf::class, 'platoon_unique_code', 'unique_code');
    }

}
