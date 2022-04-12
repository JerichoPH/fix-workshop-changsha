<?php

namespace App\Facades;

use App\Services\CodeService;
use Illuminate\Support\Facades\Facade;

/**
 * Class Code
 * @method static makeEntireInstanceIdentityCode(string $entireModelUniqueCode, string $factoryUniqueCode = null): string
 * @method static generateEntireInstanceIdentityCodes(string $entire_model_unique_code, array $new_entire_instances): array
 * @method static makeMeasurementIdentityCode(string $entireModelUniqueCode, string $partModelUniqueCode = null): string
 * @method static makePartInstanceIdentityCode(string $partModelUniqueCode): string
 * @method static makeSerialNumber(string $type, string $date = null): string
 * @method static makeEntireInstanceSerialNumber(string $entireModelUniqueCode): string
 * @method static identityCodeToHex(string $identityCode): string
 * @method static hexToIdentityCode(string $hex): string
 * @method static hexCodeCheck(string $hex): array
 * @package App\Facades
 */
class CodeFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CodeService::class;
    }
}
