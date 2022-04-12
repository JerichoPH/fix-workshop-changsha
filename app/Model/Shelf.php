<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Shelf
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 架编码
 * @property string $platoon_unique_code 排关联编码
 * @property string $location_img 位置图片
 * @property-read \App\Model\Platoon $WithPlatoon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Tier[] $WithTiers
 * @property-read int|null $with_tiers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Tier[] $subset
 * @property-read int|null $subset_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereLocationImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf wherePlatoonUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Shelf whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Shelf extends Model
{
    protected $guarded = [];

    /**
     * 获取架编码
     * @param string $platoonUniqueCode
     * @return string
     */
    public function getUniqueCode(string $platoonUniqueCode)
    {
        $shelf = Shelf::with([])->where('platoon_unique_code', $platoonUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $platoonUniqueCode;
        if (empty($shelf)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $shelf->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithTiers()
    {
        return $this->hasMany(Tier::class, 'shelf_unique_code', 'unique_code');
    }

    public function WithPlatoon()
    {
        return $this->belongsTo(Platoon::class, 'platoon_unique_code', 'unique_code');
    }

    public function subset()
    {
        return $this->hasMany(Tier::class, 'shelf_unique_code', 'unique_code');
    }

}
