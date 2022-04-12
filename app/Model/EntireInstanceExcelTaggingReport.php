<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * App\Model\EntireInstanceExcelTaggingReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number 上传流水号
 * @property int $is_upload_create_device_excel_error 是否有设备赋码设备错误报告
 * @property int $is_upload_edit_device_excel_error 是否有批量编辑错误报告
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereIsUploadCreateDeviceExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereIsUploadEditDeviceExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceExcelTaggingReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EntireInstanceExcelTaggingReport extends Model
{
    protected $guarded = [];

    /**
     * 生成序列号
     * @return string
     */
    final static public function generateSerialNumber(): string
    {
        $today = self::with([])->orderByDesc('created_at')->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->first();
        $next = $today ? intval(substr($today->serial_number, 12)) + 1 : 1;

        return env('ORGANIZATION_CODE') . now()->format('Ymd') . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 赋码唯一编号
     * @return HasMany
     */
    final public function EntireInstanceIdentityCodes(): HasMany
    {
        return $this->hasMany(EntireInstanceExcelTaggingIdentityCode::class, 'entire_instance_excel_tagging_report_sn', 'serial_number');
    }

    /**
     * 操作人
     * @return HasOne
     */
    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    /**
     * 所属工区类型
     * @param $value
     * @return object
     */
    final public function getWorkAreaTypeAttribute($value)
    {
        return (object)([
            'code' => $value,
            'name' => array_flip(WorkArea::$WORK_AREA_TYPES)[$value] ?? '无',
        ]);
    }
}
