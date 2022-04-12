<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Tier
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name 层名称
 * @property string $unique_code 层代码
 * @property string $shelf_unique_code 所属架
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Position[] $WithPositions
 * @property-read int|null $with_positions_count
 * @property-read \App\Model\Shelf $WithShelf
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Position[] $subset
 * @property-read int|null $subset_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereShelfUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Tier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Tier extends Model
{
    protected $guarded = [];

    /**
     * 获取层编码
     * @param string $shelfUniqueCode
     * @return string
     */
    public function getUniqueCode(string $shelfUniqueCode)
    {
        $tier = Tier::where('shelf_unique_code', $shelfUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $shelfUniqueCode;
        if (empty($tier)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $tier->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithShelf()
    {
        return $this->belongsTo(Shelf::class, 'shelf_unique_code', 'unique_code');
    }

    public function WithPositions()
    {
        return $this->hasMany(Position::class, 'tier_unique_code', 'unique_code');
    }

    public function subset()
    {
        return $this->hasMany(Position::class, 'tier_unique_code', 'unique_code');
    }

}
