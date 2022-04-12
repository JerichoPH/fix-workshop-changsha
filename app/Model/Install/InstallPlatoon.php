<?php

namespace App\Model\Install;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\Install\InstallPlatoon
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $unique_code 排编码
 * @property string $install_room_unique_code 机房关联编码
 * @property-read \App\Model\Install\InstallRoom $WithInstallRoom
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Install\InstallShelf[] $WithInstallShelves
 * @property-read int|null $with_install_shelves_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereInstallRoomUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallPlatoon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InstallPlatoon extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'name',
        'unique_code',
        'install_room_unique_code',
    ];

    /**
     * @param string $install_room_unique_code
     * @return string
     */
    final public static function generateUniqueCode(string $install_room_unique_code):string
    {
        $installPlatoon = DB::table('install_platoons')->where('install_room_unique_code', $install_room_unique_code)->orderByDesc('id')->first();
        $prefix = $install_room_unique_code;
        if (empty($installPlatoon)) {
            $uniqueCode = $prefix . '01';
        } else {
            $lastUniqueCode = $installPlatoon->unique_code;
            $suffix = sprintf("%02d", substr($lastUniqueCode, strlen($prefix)) + 1);
            $uniqueCode = $prefix . $suffix;
        }
        return $uniqueCode;
    }

    /**
     * @param string $installRoomUniqueCode
     * @return string
     */
    final public function getUniqueCode(string $installRoomUniqueCode): string
    {
        return self::generateUniqueCode($installRoomUniqueCode);
    }

    final public function WithInstallShelves()
    {
        return $this->hasMany(InstallShelf::class, 'install_platoon_unique_code', 'unique_code');
    }

    final public function WithInstallRoom()
    {
        return $this->belongsTo(InstallRoom::class, 'install_room_unique_code', 'unique_code');
    }

}
