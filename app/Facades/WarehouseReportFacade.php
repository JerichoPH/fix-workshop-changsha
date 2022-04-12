<?php

namespace App\Facades;

use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use App\Services\WarehouseReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Class WarehouseReport
 * @method static buyInOnce(Request $request): string
 * @method static fixWorkflowOutOnce(Request $request, FixWorkflow $fixWorkflow)
 * @method static returnFactoryOutOnce(Request $request, FixWorkflow $fixWorkflow)
 * @method static factoryReturnInOnce(Request $request, FixWorkflow $fixWorkflow)
 * @method static fixingInOnce(Request $request, EntireInstance $entireInstance): bool
 * @method static fixWorkflowInOnce(Request $request, FixWorkflow $fixWorkflow)
 * @method static inBatch(Collection $warehouseBatchReports, string $status = 'FIXING')
 * @method static inOnce(Request $request, EntireInstance $entireInstance)
 * @method static outOnce(Request $request, EntireInstance $entireInstance)
 * @method static batch(int $processorId, string $processedAt, string $connectionName, string $connectionPhone, string $type, Collection $newEntireInstances): bool
 * @method static fixingInOnceWithEntireInstanceIdentityCode(Request $request, string $entireInstanceIdentityCode): bool
 * @method static batchInWithEntireInstanceIdentityCodes(array $entireInstanceIdentityCodes, int $processorId, string $processedAt, string $type = 'NORMAL', string $connectionName = null, string $connectionPhone = null)
 * @method static batchOutWithEntireInstanceIdentityCodes(array $entire_instance_identity_codes, int $processor_id, string $processed_at, string $type = 'NORMAL', string $connection_name = null, string $connection_phone = null)
 * @method static batchInWithBreakdownOrderTempEntireInstances(Collection $breakdown_order_temp_entire_instances, int $processor_id, string $processed_at, string $connection_name = null, string $connection_phone = null)
 * @method static generateStatisticsFor7Days(): array
 * @package App\Facades
 */
class WarehouseReportFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WarehouseReportService::class;
    }
}
