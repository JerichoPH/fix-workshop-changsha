<?php

namespace App\Facades;

use App\Services\JsonResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Class JsonResponseFacade
 * @package App\Facades
 * @method static dump(...$data): JsonResponse
 * @method static dict($data = [], string $msg = '', ...$details): JsonResponse
 * @method static data($data = [], string $msg = '', ...$details): JsonResponse
 * @method static created($data = [], string $msg = '添加成功', ...$details): JsonResponse
 * @method static updated($data = [], string $msg = '编辑成功', ...$details): JsonResponse
 * @method static deleted($data = [], string $msg = '删除成功', ...$details): JsonResponse
 * @method static errorEmpty(string $msg = '数据不存在', ...$details): JsonResponse
 * @method static errorForbidden(string $msg, ...$details): JsonResponse
 * @method static errorUnauthorized(string $msg = '授权失败', ...$details): JsonResponse
 * @method static errorValidate(string $msg): JsonResponse
 * @method static errorException(\Throwable $e, string $msg = '意外错误', $errorCode = 1): JsonResponse
 */
class JsonResponseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return JsonResponseService::class;
    }
}
