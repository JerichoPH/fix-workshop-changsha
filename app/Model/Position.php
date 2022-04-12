<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\Position
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code
 * @property string $tier_unique_code 层关联编码
 * @property string|null $img_url 图片路径
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $WithEntireInstances
 * @property-read int|null $with_entire_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartInstance[] $WithPartInstances
 * @property-read int|null $with_part_instances_count
 * @property-read \App\Model\Tier $WithTier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereImgUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereTierUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Position whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Position extends Model
{
    protected $guarded = [];

    /**
     * 获取位编码
     * @param string $tierUniqueCode
     * @return string
     */
    public function getUniqueCode(string $tierUniqueCode)
    {
        $position = Position::where('tier_unique_code', $tierUniqueCode)->orderBy('id', 'desc')->first();
        $prefix = $tierUniqueCode;
        if (empty($position)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $position->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithTier()
    {
        return $this->belongsTo(Tier::class, 'tier_unique_code', 'unique_code');
    }

    public function WithEntireInstances()
    {
        return $this->hasMany(EntireInstance::class, 'location_unique_code', 'unique_code');
    }

    public function WithPartInstances()
    {
        return $this->hasMany(PartInstance::class, 'location_unique_code', 'unique_code');
    }


}
