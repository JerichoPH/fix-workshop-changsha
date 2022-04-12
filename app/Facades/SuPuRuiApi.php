<?php

namespace App\Facades;

use App\Services\SuPuRuiApiService;
use Illuminate\Support\Facades\Facade;

/**
 * Class SuPuRuiSdk
 * @package App\Facades
 * @method static debug(bool $isDebug = true): self
 * @method static test(): array
 * @method static password(string $newPwd): array
 * @method static returnType(string $returnType = 'prototype'): self
 * @method static wholeZip(): array
 * @method static insertEntireInstances_S(array $entireInstances): array
 * @method static insertEntireInstances_Q(array $entireInstances): array
 * @method static insertEntireModels_S(array $entireModels): array
 * @method static insertEntireModels_Q(array $entireModels): array
 * @method static insertFixWorkflows_S(array $fixWorkflows): array
 * @method static insertFixWorkflows_Q(array $fixWorkflows): array
 * @method static findFixWorkflow_S(string $entireInstanceIdentityCode): array
 * @method static findFixWorkflow_Q(string $entireInstanceIdentityCode): array
 * @method static findEntireInstance(string $entireInstanceIdentityCode): array
 */
class SuPuRuiApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SuPuRuiApiService::class;
    }
}
