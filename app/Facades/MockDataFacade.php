<?php

namespace App\Facades;

use App\Services\MockDataService;
use Illuminate\Support\Facades\Facade;

/**
 * Class MockDataFacade
 * @package App\Facades
 * @method static runMockFixWorkflow()
 * @method static runReplenishMadeAtForLogs()
 * @method static tmpReplenishPartInstances()
 */
class MockDataFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MockDataService::class;
    }
}
