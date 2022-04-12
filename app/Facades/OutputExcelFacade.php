<?php

namespace App\Facades;

use App\Services\OutputExcelService;
use Illuminate\Support\Facades\Facade;

/**
 * Class OutputExcelFacade
 * @package App\Facades
 * @method static do_q01()
 */
class OutputExcelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OutputExcelService::class;
    }
}
