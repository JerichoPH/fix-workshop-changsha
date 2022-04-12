<?php

namespace App\Facades;

use App\Services\FixWorkflowInputService;
use Illuminate\Support\Facades\Facade;

/**
 * Class FixWorkflowInputFacade
 * @package App\Facades
 * @method static input_66345(string $name)
 * @method static input_70240(string $name)
 * @method static input_k4railroad(string $name)
 * @method static input_baiMaLong(string $name)
 */
class FixWorkflowInputFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FixWorkflowInputService::class;
    }
}
