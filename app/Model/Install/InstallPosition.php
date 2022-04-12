<?php

namespace App\Model\Install;

use App\Model\EntireInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallPosition
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $unique_code 位编码
 * @property string $name 位名称
 * @property string $install_tier_unique_code 层关联编码
 * @property-read \App\Model\Install\InstallTier $WithInstallTier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition whereInstallTierUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPosition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InstallPosition extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'unique_code',
        'install_tier_unique_code',
        'name',
    ];

    /**
     * 上道位置
     * @param string|null $real_name
     * @param string|null $maintain_location_code
     * @param string|null $crossroad_number
     * @param string|null $open_direction
     * @return string
     */
    private static function _locationToString(string $real_name = null, string $maintain_location_code = null, string $crossroad_number = null, string $open_direction = null): string
    {
        $location = [];
        if (empty($real_name) && empty($maintain_location_code) && empty($crossroad_number) && empty($open_direction)) {
            return '';
        } else {
            if ((@$real_name ?: @$maintain_location_code)) {
                $location[] = (@$real_name ?: @$maintain_location_code);
            }
            if ($crossroad_number) {
                $location[] = $crossroad_number;
            }
            if ($open_direction) {
                $location[] = $open_direction;
            }
            return '位置：' . implode(' ', $location);
        }
    }

    /**
     * 获取上次或当前上道位置（优先上次上道位置）
     * @param EntireInstance $entire_instance
     * @return string
     */
    public static function lastLocationToString(EntireInstance $entire_instance): string
    {
        return self::_locationToString(
            self::getRealName(@$entire_instance->last_maintain_location_code ?: (@$entire_instance->maintain_location_code ?: '')),
            @$entire_instance->last_maintain_location_code ?: (@$entire_instance->maintain_location_code ?: ''),
            @$entire_instance->last_crossroad_number ?: (@$entire_instance->crossroad_number ?: ''),
            @$entire_instance->last_open_direction ?: (@$entire_instance->open_direction ?: '')
        );
    }

    /**
     * 获取上道位置
     * @param EntireInstance $entire_instance
     * @return string
     */
    public static function locationToString(EntireInstance $entire_instance): string
    {
        return self::_locationToString(
            self::getRealName(@$entire_instance->maintain_location_code ?: ''),
            @$entire_instance->maintain_location_code ?: '',
            @$entire_instance->crossroad_number ?: '',
            @$entire_instance->open_direction ?: ''
        );
    }

    /**
     * 获取真实名称和车间、车站
     * @param InstallPosition|null $position
     * @param string|null $default
     * @return string
     */
    final private static function _getRealNameAndStationName(InstallPosition $position = null, string $default = null): string
    {
        if (!$position) return $default ?? '';
        $tier = @$position->WithInstallTier->name ?? null;
        if (!$tier) return $default ?? '';
        $shelf = @$position->WithInstallTier->WithInstallShelf->name ?? null;
        if (!$shelf) return $default ?? '';
        $platoon = @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->name ?? null;
        if (!$platoon) return $default ?? '';
        $room = @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?? null;
        if (!$room) return $default ?? '';
        $station = @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?? null;
        if(!$station) return $default ?? '';
        $scene_workshop = @$position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name ?? null;
        if(!$scene_workshop) return $default ?? '';
        return "{$scene_workshop} {$station} {$room} {$platoon}-{$shelf}-{$tier}-{$position->name}";
    }

    /**
     * 获取真实名称和车间、车站
     * @param string $unique_code
     * @param string|null $default
     * @return string
     */
    public static function getRealNameAndStationName(string $unique_code, string $default = null)
    {
        $position = self::with([
            'WithInstallTier',  // 层
            'WithInstallTier.WithInstallShelf',  // 柜
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation',  // 车站
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent',  // 车间
        ])
            ->where('unique_code', $unique_code)
            ->first();

        return self::_getRealName($position, $default);
    }

    /**
     * 根据编号获取真实名称和车间、车站
     * @return string
     */
    final public function getRealNameAndStationNameAttribute(): string
    {
        return self::_getRealNameAndStationName($this);
    }

    /**
     * 获取真实名称
     * @param InstallPosition|null $position
     * @param string|null $default
     * @return string
     */
    final private static function _getRealName(InstallPosition $position = null, string $default = null): string
    {
        if (!$position) return $default ?? '';
        $tier = $position->WithInstallTier->name ?? null;
        if (!$tier) return $default ?? '';
        $shelf = $position->WithInstallTier->WithInstallShelf->name ?? null;
        if (!$shelf) return $default ?? '';
        $platoon = $position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->name ?? null;
        if (!$platoon) return $default ?? '';
        $room = $position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?? null;
        if (!$room) return $default ?? '';
        // return "{$room}{$platoon}-{$shelf}-{$tier}-{$this->name}";
        return "{$room} {$platoon}-{$shelf}-{$tier}-{$position->name}";
    }

    /**
     * 通过编号获取真实名称
     * @param string $unique_code
     * @param string|null $default
     * @return string
     */
    final public static function getRealName(string $unique_code, string $default = null): string
    {
        $position = self::with([
            'WithInstallTier',  // 层
            'WithInstallTier.WithInstallShelf',  // 柜
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
        ])
            ->where('unique_code', $unique_code)
            ->first();

        return self::_getRealName($position, $default);
    }

    /**
     * 根据编号获取真实名称
     * @return string
     */
    final public function getRealNameAttribute(): string
    {
        return self::_getRealName($this);
    }

    /**
     * @param string $install_tier_unique_code
     * @param int $count
     * @return array
     */
    public static function generateUniqueCodes(string $install_tier_unique_code, int $count): array
    {
        $installPosition = DB::table('install_positions')->where('install_tier_unique_code', $install_tier_unique_code)->orderByDesc('id')->select('unique_code')->first();
        if (empty($installPosition)) {
            $start = '00';
        } else {
            $start = substr($installPosition->unique_code, -2);
        }
        $uniqueCodes = [];
        for ($i = 1; $i <= $count; $i++) {
            $start += 1;
            $uniqueCodes[] = [
                'unique_code' => $install_tier_unique_code . str_pad($start, 2, '0', STR_PAD_LEFT),
                'name' => $start,
                'install_tier_unique_code' => $install_tier_unique_code,
            ];
        }
        return $uniqueCodes;
    }

    /**
     * 获取编码
     * @param string $installTierUniqueCode
     * @param int $count
     * @return array
     */
    public static function getUniqueCodes(string $installTierUniqueCode, int $count): array
    {
        return self::generateUniqueCodes($installTierUniqueCode, $count);
    }

    /**
     * 获取最后一位unique_code
     * @param string $installTierUniqueCode
     * @return string
     */
    final public function getLastUniqueCode(string $installTierUniqueCode): string
    {
        $installPosition = DB::table('install_positions')->where('install_tier_unique_code', $installTierUniqueCode)->orderByDesc('id')->select('unique_code')->first();
        return empty($installPosition->unique_code) ? '00' : substr($installPosition->unique_code, -2);
    }

    /**
     * 层
     * @return BelongsTo
     */
    final public function WithInstallTier(): BelongsTo
    {
        return $this->belongsTo(InstallTier::class, 'install_tier_unique_code', 'unique_code');
    }

    /**
     * 设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'maintain_location_code', 'unique_code');
    }
}
