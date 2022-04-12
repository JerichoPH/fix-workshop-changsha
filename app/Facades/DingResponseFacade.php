<?php

namespace App\Facades;

use App\Services\DingResponseService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * Class DingResponseFacade
 * @package App\Facades
 * @method static created(string $msg): Response
 * @method static data($data): Response
 * @method static error(string $msg): Response
 * @method static errorEmpty(string $msg): Response
 * @method static errorValidate(string $msg): Response
 * @method static errorForbidden(string $msg): Response
 * @method static errorUnauthorized(string $msg = '授权失败'): Response
 */
class DingResponseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DingResponseService::class;
    }
}
