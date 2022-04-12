<?php

namespace App\Facades;

use App\Services\QrCodeService;
use Illuminate\Support\Facades\Facade;

/**
 * Class QrCodeFacade
 * @package App\Facades
 * @method static generateBase64ByEntireInstanceStatus(string $entire_instance_identity_code = '', int $size = 140): string
 * @method static generateColorsByEntireInstanceStatus(string $entire_instance_identity_code = ''): array
 */
class QrCodeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QrCodeService::class;
    }
}
