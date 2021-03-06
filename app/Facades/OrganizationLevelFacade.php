<?php

namespace App\Facades;

use App\Services\OrganizationLevelService;
use Illuminate\Support\Facades\Facade;

class OrganizationLevelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OrganizationLevelService::class;
    }
}