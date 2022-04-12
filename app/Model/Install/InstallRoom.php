<?php

namespace App\Model\Install;

use App\Model\Maintain;
use App\Model\Station;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\Install\InstallRoom
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $unique_code 机房编码
 * @property string $station_unique_code 车站编码
 * @property string $type 机房类型：11微机房
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Install\InstallPlatoon[] $WithInstallPlatoons
 * @property-read int|null $with_install_platoons_count
 * @property-read \App\Model\Station $WithStation
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Install\InstallRoom whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InstallRoom extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'unique_code',
        'station_unique_code',
        'type',
    ];

    public static $TYPES = [
        '10' => '机械室',
        '11' => '微机室',
        '12' => '电源室',
        '13' => '防雷分线室',
        '14' => '备品间',
        '15' => '运转室',
        '16' => '仿真实验室',
        '17' => 'SAM调度大厅',
        '18' => 'SAM联络室',
    ];

    public function getTypeAttribute($value)
    {
        return (object)[
            'value' => $value,
            'text' => self::$TYPES[$value],
        ];
    }

    public function WithInstallPlatoons()
    {
        return $this->hasMany(InstallPlatoon::class, 'install_room_unique_code', 'unique_code');
    }

    public function WithStation()
    {
        return $this->belongsTo(Maintain::class, 'station_unique_code', 'unique_code');
    }


}
