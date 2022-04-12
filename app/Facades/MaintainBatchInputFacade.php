<?php

namespace App\Facades;

use App\Services\MaintainBatchInputService;
use Illuminate\Http\Request;
use Overtrue\LaravelWeChat\Facade;

/**
 * Class MaintainBatchInputFacade
 * @package App\Facades
 * @method static FROM_REQUEST(Request $request, string $filename): self
 * @method static FROM_STORAGE(string $diskName, string $filename): self
 * @method static withSheetIndex(int $sheetIndex = 0)
 * @method static withSheetName(string $sheetName)
 */
class MaintainBatchInputFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return MaintainBatchInputService::class;
    }
}
