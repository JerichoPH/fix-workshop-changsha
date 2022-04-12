<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Line
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $unique_code 线别编码
 * @property string $name 线别名称
 * @property-read \App\Model\Organization $organization
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Line whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Line withoutTrashed()
 * @mixin \Eloquent
 */
class Line extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function organization()
    {
        return $this->hasOne(Organization::class, 'id', 'organization_id');
    }
}
