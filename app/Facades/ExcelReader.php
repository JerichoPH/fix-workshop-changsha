<?php

namespace App\Facades;

use App\Services\ExcelReaderService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;

/**
 * Class ExcelReadHelper
 * @package App\Facades
 * @method static init(Request $request, string $fileName): ExcelReaderService
 * @method static readAll(int $originRow, int $finishRow = 0, \Closure $closure = null): array
 * @method static readSheetByName(string $sheetName, int $originRow, int $finishRow = 0, \Closure $closure = null): array
 * @method static readSheetByIndex(int $sheetIndex, \Closure $closure = null): array
 * @method static file(string $file): ExcelReaderService
 */
class ExcelReader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ExcelReaderService::class;
    }
}
