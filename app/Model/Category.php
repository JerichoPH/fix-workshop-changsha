<?php

namespace App\Model;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Category
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 设备类型名称
 * @property string $unique_code 统一代码
 * @property string|null $race_unique_code 种型
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $EntireModels
 * @property-read int|null $entire_models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartCategory[] $PartCategories
 * @property-read int|null $part_categories_count
 * @property-read \App\Model\Race $Race
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $Subs
 * @property-read int|null $subs_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereRaceUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Category withoutTrashed()
 * @mixin \Eloquent
 */
class Category extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 生成唯一编号
     */
    final static public function generateUniqueCode(string $type = '')
    {
        if (!$type) throw new \Exception('种类类型错误');
        $category = self::with([])->where('unique_code', 'like', "{$type}%")->orderByDesc('unique_code')->first();
        $max = intval(substr($category->unique_code, -2));
        return $type . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 该类目下所有实例
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function EntireModels()
    {
        return $this->hasMany(EntireModel::class, 'category_unique_code', 'unique_code');
    }

    public function Race()
    {
        return $this->hasOne(Race::class, 'unique_code', 'race_unique_code');
    }

    public function PartCategories()
    {
        return $this->hasMany(PartCategory::class, "category_unique_code", "unique_code");
    }

    public function Subs()
    {
        return $this->hasMany(EntireModel::class, 'category_unique_code', 'unique_code')->where('is_sub_model', false);
    }
}
