<?php

namespace App\Facades;

use App\Services\WechatMiniAppService;
use Illuminate\Support\Facades\Facade;

/**
 * Class WechatMiniAppFacade
 * @package App\Facades
 * @method static getAccessToken(): string
 * @method static getJsApiTicket(): string
 * @method static getAppId(): string
 */
class WechatMiniAppFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WechatMiniAppService::class;
    }
}
