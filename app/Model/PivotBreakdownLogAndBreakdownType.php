<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotBreakdownLogAndBreakdownType
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $breakdown_log_id
 * @property int|null $breakdown_type_id
 * @property-read \App\Model\BreakdownLog $BreakdownLog
 * @property-read \App\Model\BreakdownType $BreakdownType
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType whereBreakdownLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType whereBreakdownTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotBreakdownLogAndBreakdownType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PivotBreakdownLogAndBreakdownType extends Model
{
    protected $guarded = [];

    final public function BreakdownLog()
    {
        return $this->hasOne(BreakdownLog::class, 'id', 'breakdown_log_id');
    }

    final public function BreakdownType()
    {
        return $this->hasOne(BreakdownType::class, 'id', 'breakdown_type_id');
    }
}
