<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseMaterial
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $material_unique_code
 * @property string $warehouse_unique_code
 * @property string $material_type
 * @property-read \App\Model\EntireInstance $WithEntireInstance
 * @property-read \App\Model\PartInstance $WithPartInstance
 * @property-read \App\Model\Warehouse $WithWarehouse
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseMaterial onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereMaterialUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseMaterial whereWarehouseUniqueCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseMaterial withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseMaterial withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseMaterial extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static $MATERIAL_TYPES = [
        'ENTIRE' => '整件',
        'PART' => '部件'
    ];

    public function getMaterialTypeAttribute($value)
    {
        return self::$MATERIAL_TYPES[$value];
    }

    public function WithEntireInstance()
    {
        return $this->belongsTo(EntireInstance::class, 'material_unique_code', 'identity_code');
    }

    public function WithWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_unique_code', 'unique_code');
    }

    public function WithPartInstance()
    {
        return $this->belongsTo(PartInstance::class, 'material_unique_code', 'identity_code');
    }

}
