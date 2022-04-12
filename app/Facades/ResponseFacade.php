<?php

namespace App\Facades;

use App\Services\ResponseService;
use Illuminate\Support\Facades\Facade;

/**
 * 响应
 * @method setData(array $data = []):self
 * @method setDetails(...$details):self
 * @method setErrorCode(int $error_code): self
 * @method created(string $msg = '添加成功')
 * @method updated(string $msg = '编辑成功')
 * @method deleted(string $msg = '删除成功')
 * @method empty(string $msg = '数据不存在', ...$details)
 * @method forbidden(string $msg = '禁止操作', ...$details)
 * @method authorization(string $msg = '无权操作', ...$details)
 * @method validate(string $msg = '', ...$details)
 * @method json(): JsonResponse
 * @method http(): Response
 */
class ResponseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ResponseService::class;
    }
}
