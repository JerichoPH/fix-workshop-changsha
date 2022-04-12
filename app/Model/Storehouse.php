<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Storehouse
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Area[] $WithAreas
 * @property-read int|null $with_areas_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Area[] $subset
 * @property-read int|null $subset_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Storehouse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Storehouse extends Model
{
    protected $guarded = [];

    /**
     * 获取仓编码
     * @return string
     */
    public function getUniqueCode()
    {
        $storehouse = Storehouse::orderBy('id', 'desc')->first();
        $prefix = env('ORGANIZATION_LOCATION_CODE');
        if (empty($storehouse)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $storehouse->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    public function WithAreas()
    {
        return $this->hasMany(Area::class, 'storehouse_unique_code', 'unique_code');
    }

    public function subset()
    {
        return $this->hasMany(Area::class, 'storehouse_unique_code', 'unique_code');
    }

}
