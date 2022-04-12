<?php

namespace App\Model\Install;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallTier
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 层编码
 * @property string $install_shelf_unique_code 架关联编码
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Install\InstallPosition[] $WithInstallPositions
 * @property-read int|null $with_install_positions_count
 * @property-read \App\Model\Install\InstallShelf $WithInstallShelf
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereInstallShelfUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallTier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InstallTier extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'name',
        'unique_code',
        'install_shelf_unique_code',
    ];

    final public static function generateUniqueCode(string $install_shelf_unique_code): string
    {
        $installTier = DB::table('install_tiers')->where('install_shelf_unique_code', $install_shelf_unique_code)->orderByDesc('id')->first();
        $prefix = $install_shelf_unique_code;
        if (empty($installTier)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installTier->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * @param string $installShelfUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installShelfUniqueCode): string
    {
        return self::generateUniqueCode($installShelfUniqueCode);
    }

    /**
     * 获取真实名称
     * @param InstallTier|null $tier
     * @return string
     */
    final private static function _getRealName(InstallTier $tier = null): string
    {
        if (!$tier) return '';
        $shelf = $tier->WithInstallShelf->name ?? null;
        if (!$shelf) return '';
        $platoon = $tier->WithInstallShelf->WithInstallPlatoon->name ?? null;
        if (!$platoon) return '';
        $room = $tier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?? null;
        if (!$room) return '';
        // return "{$room}{$platoon}-{$shelf}-{$tier}-{$this->name}";
        return "{$room}{$platoon}排{$shelf}柜{$tier->name}层";
    }

    /**
     * 通过编号获取真实名称
     * @param string $unique_code
     * @return string
     */
    final public static function getRealName(string $unique_code): string
    {
        $tier = self::with([
            'WithInstallShelf',  // 柜
            'WithInstallShelf.WithInstallPlatoon',  // 排
            'WithInstallShelf.WithInstallPlatoon.WithInstallRoom',  // 室
        ])
            ->where('unique_code', $unique_code)
            ->first();

        return self::_getRealName($tier);
    }

    /**
     * 根据编号获取真实名称
     * @return string
     */
    final public function getRealNameAttribute(): string
    {
        return self::_getRealName($this);
    }

    final public function WithInstallPositions(): HasMany
    {
        return $this->hasMany(InstallPosition::class, 'install_tier_unique_code', 'unique_code');
    }

    public function install_positions(): HasMany
    {
        return $this->hasMany(InstallPosition::class, 'install_tier_unique_code', 'unique_code');
    }

    final public function WithInstallShelf(): BelongsTo
    {
        return $this->belongsTo(InstallShelf::class, 'install_shelf_unique_code', 'unique_code');
    }

}
