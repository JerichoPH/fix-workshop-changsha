<?php

namespace App\Facades;

use App\Model\EntireInstance;
use App\Services\FixWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Class FixWorkflow
 * @method static make(string $fixWorkflowSerialNumber): bool
 * @method static batch(Collection $entireInstances): int
 * @method static makeByFactoryDeviceCode(string $factoryDeviceCode): bool
 * @method static makeByFactoryDeviceCodes(array $factoryDeviceCodes): bool
 * @method static makeByEntireInstanceIdentityCode(string $entireInstanceIdentityCode): bool
 * @method static batchByEntireInstanceIdentityCodes(array $entireInstanceIdentityCodes): int
 * @method static mockEmpty(EntireInstance $entireInstance, string $fixedAt = null, string $checkedAt = null, int $fixerId = null, int $checkerId = null, string $spotCheckedAt = null, int $spotCheckerId = null)
 * @method static mockEmptyWithOutEditFixed(EntireInstance $entireInstance, string $fixedAt = null, string $checkedAt = null, int $fixerId = null, int $checkerId = null, string $spotCheckedAt = null, int $spotCheckerId = null)
 * @package App\Facades
 */
class FixWorkflowFacade
    extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FixWorkflowService::class;
    }
}
