<?php

namespace App\Facades;

use App\Model\EntireInstance;
use App\Model\V250TaskOrder;
use App\Model\WorkArea;
use App\Services\EntireInstanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class EntireInstance
 * @method static incCount(string $entireModelUniqueCode): int
 * @method static incFixedCount(string $entireModelUniqueCode): int
 * @method static nextFixingTime(EntireInstance $entireInstance): array
 * @method static nextFixingTimeForRelative(EntireInstance $entireInstance, int $fixCycleValue = 0, string $fixCycleUnit = 'YEAR'): array
 * @method static nextFixingTimeWithIdentityCode(string $entireInstanceIdentityCode): array
 * @method static batchFromExcelWithNew(Request $request, string $filename, string $sheetName = '导入'): array
 * @method static makeNewCode($entireInstances): int
 * @method static getEntireInstanceIdentityCodeByCodeForPda(string $code): string
 * @method static toDecode(string $code): string
 * @method static copyLocation(string $from, string $to)
 * @method static clearLocation(string $identity_code)
 * @method static copyLocations(array $entire_instances)
 * @method static clearLocations(array $identity_codes)
 * @method static downloadUploadCreateDeviceExcelTemplate(WorkArea $work_area)
 * @method static uploadCreateDevice(Request $request, string $work_area_type, string $work_area_unique_code)
 * @method static downloadUploadEditDeviceExcelTemplate(WorkArea $work_area)
 * @method static uploadEditDevice(Request $request, string $work_area_type)
 * @package App\Facades
 */
class EntireInstanceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return EntireInstanceService::class;
    }
}
