<?php

namespace App\Facades;

use App\Services\ExcelWriterService;
use Illuminate\Support\Facades\Facade;

/**
 * Class ExcelWriter
 * @package App\Facades
 * @method static download(\Closure $closure, string $filename)
 * @method static save(\Closure $closure, string $filename)
 */
class ExcelWriter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ExcelWriterService::class;
    }
}
