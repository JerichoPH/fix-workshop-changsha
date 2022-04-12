<?php

namespace App\Model;

use App\Facades\TextFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * App\Model\WorkArea
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $workshop_unique_code
 * @property string $name
 * @property string $unique_code
 * @property string $type
 * @property string $paragraph_unique_code 所属电务段代码
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Account[] $Accounts
 * @property-read int|null $accounts_count
 * @property-read \App\Model\Maintain $Workshop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereParagraphUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WorkArea whereWorkshopUniqueCode($value)
 * @mixin \Eloquent
 */
class WorkArea extends Model
{

    public static $WORK_AREA_TYPES = [
        '转辙机工区' => 'pointSwitch',
        '继电器工区' => 'relay',
        '综合工区' => 'synthesize',
        '现场工区' => 'scene',
        '电源屏工区' => 'powerSupplyPanel',
    ];

    protected $guarded = [];

    /**
     * 生成唯一编号
     * @return string
     */
    final public static function generateUniqueCode(): string
    {
        $last_work_area = self::with([])->orderByDesc('id')->first();
        $last_unique_code = $last_work_area ? TextFacade::from36(Str::substr($last_work_area->unique_code, -2)) : 0;
        return env('ORGANIZATION_CODE') . 'D' . str_pad(TextFacade::to36($last_unique_code + 1), 2, '0', 0);
    }

    /**
     * 所属车间
     * @return HasOne
     */
    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    /**
     * 所属用户
     * @return HasMany
     */
    final public function Accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'work_area_unique_code', 'unique_code');
    }
}
