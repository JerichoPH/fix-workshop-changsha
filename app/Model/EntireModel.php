<?php

namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\EntireModel
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 设备型号名称
 * @property string $unique_code 设备型号开放代码
 * @property string $category_unique_code 类型唯一代码
 * @property string $fix_cycle_unit 维修周期时长单位
 * @property int $fix_cycle_value 维修周期时长
 * @property int $is_sub_model 是否是子类
 * @property string|null $parent_unique_code 父级代码
 * @property-read \App\Model\Category $Category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModelIdCode[] $EntireModelIdCodes
 * @property-read int|null $entire_model_id_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Factory[] $Factories
 * @property-read int|null $factories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Measurement[] $Measurements
 * @property-read int|null $measurements_count
 * @property-read \App\Model\EntireModel $Parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartModel[] $PartModels
 * @property-read int|null $part_models_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireModel[] $Subs
 * @property-read int|null $subs_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireModel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereFixCycleUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereFixCycleValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereIsSubModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereParentUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireModel withoutTrashed()
 * @mixin \Eloquent
 */
class EntireModel extends Model
{
    use SoftDeletes;

    public static $FIX_CYCLE_UNIT = [
        'YEAR' => '年',
        'MONTH' => '月',
        'WEEK' => '周',
        'DAY' => '日',
    ];

    protected $guarded = [];

    /**
     * 生成类型代码
     * @param string $category_unique_code
     * @return string
     */
    final static public function generateEntireModelUniqueCode(string $category_unique_code): string
    {
        $entire_model = self::with([])
            ->orderByDesc('unique_code')
            ->where('category_unique_code', $category_unique_code)
            ->where('is_sub_model', false)
            ->first();
        if (!$entire_model) {
            $max = 0;
        } else {
            $max = intval(substr($entire_model->unique_code, -2));
        }
        return $category_unique_code . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 生成子类代码
     * @param string $parent_unique_code
     * @return string
     */
    final static public function generateSubModelUniqueCode(string $parent_unique_code):string
    {
        $sub_model = self::with([])
            ->orderByDesc('unique_code')
            ->where('parent_unique_code', $parent_unique_code)
            ->where('is_sub_model', true)
            ->first();
        if(!$sub_model){
            $max = 0;
        }else{
            $max = intval(TextFacade::from36(substr($sub_model->unique_code, -2)));
        }
        return $parent_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    public static function flipFixCycleUnit($value)
    {
        return array_flip(self::$FIX_CYCLE_UNIT)[$value];
    }

    final public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    final public function getFixCycleUnitAttribute($value)
    {
        return self::$FIX_CYCLE_UNIT[$value];
    }

    final public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function Parent()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'parent_unique_code');
    }

    final public function EntireModel()
    {
        return $this->hasOne(self::class, 'unique_code', 'parent_unique_code');
    }

    final public function Subs()
    {
        return $this->hasMany(EntireModel::class, 'parent_unique_code', 'unique_code');
    }

    final public function EntireInstances()
    {
        return $this->hasMany(EntireInstance::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function Measurements()
    {
        return $this->hasMany(Measurement::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function PartModels()
    {
        return $this->hasMany(PartModel::class, 'entire_model_unique_code', 'unique_code');
    }

    // final public function PartModels()
    // {
    //     return $this->belongsToMany(
    //         'App\Model\PartModel',
    //         'pivot_entire_model_and_part_models',
    //         'entire_model_unique_code',
    //         'part_model_unique_code'
    //     );
    // }

    final public function EntireModelIdCodes()
    {
        return $this->hasMany(EntireModelIdCode::class, 'entire_model_unique_code', 'unique_code');
    }

    final public function Factories()
    {
        return $this->belongsToMany(
            Factory::class,
            'pivot_entire_model_and_factories',
            'entire_model_unique_code',
            'factory_name'
        );
    }
}
