<?php

namespace App\Model\Install;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallShelf
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 架编码
 * @property string $install_platoon_unique_code 排关联编码
 * @property-read \App\Model\Install\InstallPlatoon $WithInstallPlatoon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Install\InstallTier[] $WithInstallTiers
 * @property-read int|null $with_install_tiers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereInstallPlatoonUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallShelf whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InstallShelf extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'name',
        'unique_code',
        'install_platoon_unique_code',
    ];

    /**
     * @param string $install_platoon_unique_code
     * @return string
     */
    final public static function generateUniqueCode(string $install_platoon_unique_code): string
    {
        $installShelf = DB::table('install_shelves')->where('install_platoon_unique_code', $install_platoon_unique_code)->orderByDesc('id')->first();
        $prefix = $install_platoon_unique_code;
        if (empty($installShelf)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installShelf->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * @param string $installPlatoonUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installPlatoonUniqueCode): string
    {
        return self::generateUniqueCode($installPlatoonUniqueCode);
    }

    final public function WithInstallTiers(): HasMany
    {
        return $this->hasMany(InstallTier::class, 'install_shelf_unique_code', 'unique_code');
    }

    final public function install_tiers(): HasMany
    {
        return $this->hasMany(InstallTier::class, 'install_shelf_unique_code', 'unique_code');
    }

    final public function WithInstallPlatoon(): BelongsTo
    {
        return $this->belongsTo(InstallPlatoon::class, 'install_platoon_unique_code', 'unique_code');
    }
}
