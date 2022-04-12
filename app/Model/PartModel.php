<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\PartModel
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 配件名称
 * @property string $unique_code 型号
 * @property string $category_unique_code 种类编号
 * @property string $entire_model_unique_code 整件代码
 * @property int|null $part_category_id 所属部件种类
 * @property int|null $fix_cycle_value
 * @property-read \App\Model\Category $Category
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $EntireModels
 * @property-read int|null $entire_models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Measurement[] $Measurements
 * @property-read int|null $measurements_count
 * @property-read \App\Model\PartCategory $PartCategory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartInstance[] $PartInstances
 * @property-read int|null $part_instances_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartModel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereFixCycleValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel wherePartCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartModel withoutTrashed()
 * @mixin \Eloquent
 */
class PartModel extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 生成型号代码
     * @param string $entire_model_unique_code
     * @return string
     */
    final static public function generateUniqueCode(string $entire_model_unique_code): string
    {
        $part_model = self::with([])->orderByDesc('unique_code')->where('entire_model_unique_code', $entire_model_unique_code)->first();
        $max = intval(substr($part_model->unique_code, -2));
        return $entire_model_unique_code . 'N' . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    public function EntireModels()
    {
        return $this->belongsToMany(
            EntireModel::class,
            'pivot_entire_model_and_part_models',
            'part_model_unique_code',
            'entire_model_unique_code'
        );
    }

    public function PartInstances()
    {
        return $this->hasMany(PartInstance::class, 'part_model_unique_code', 'unique_code');
    }

    public function PartCategory()
    {
        return $this->hasOne(PartCategory::class, "id", "part_category_id");
    }

    public function Measurements()
    {
        return $this->hasMany(Measurement::class, 'part_model_unique_code', 'unique_code');
    }
}
