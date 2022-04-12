<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class JsonResponseService
{
    /**
     * 响应简单类型数字
     * @param array $data
     * @return JsonResponse
     */
    final public static function dump(...$data): JsonResponse
    {
        return response()->json([
            'msg' => 'dump response',
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
        ],
            200);
    }

    /**
     * 响应数据别名
     * @param array $data
     * @param string $msg
     * @param ...$details
     * @return JsonResponse
     */
    final public static function dict($data = [], string $msg = '', ...$details): JsonResponse
    {
        return self::data($data, $msg, $details);
    }

    /**
     * 响应数据
     * @param $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function data($data = [], string $msg = '', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 操作成功
     * @param string $msg
     * @return JsonResponse
     */
    final public function ok(string $msg = '操作成功'): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => [],
            'details' => [],
        ], 200);
    }

    /**
     * 新建成功
     * @param $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function created($data = [], string $msg = '添加成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 更新成功
     * @param $data
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function updated($data = [], string $msg = '编辑成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details
        ],
            200);
    }

    /**
     * 删除成功
     * @param string $msg
     * @param array $data
     * @param array $details
     * @return JsonResponse
     */
    final public static function deleted($data = [], string $msg = '删除成功', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 200,
            'errorCode' => 0,
            'data' => $data,
            'details' => $details,
        ],
            200);
    }

    /**
     * 空资源
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorEmpty(string $msg = '数据不存在', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 404,
            'errorCode' => 1,
            'details' => $details,
        ],
            404);
    }

    /**
     * 禁止操作
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorForbidden(string $msg, ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 403,
            'errorCode' => 1,
            'details' => $details,
        ],
            403);
    }

    /**
     * 授权失败
     * @param string $msg
     * @param array $details
     * @return JsonResponse
     */
    final public static function errorUnauthorized(string $msg = '授权失败', ...$details): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 401,
            'errorCode' => 1,
            'details' => $details
        ],
            401);
    }

    /**
     * 表单验证失败
     * @param string $msg
     * @return JsonResponse
     */
    final public static function errorValidate(string $msg): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 422,
            'errorCode' => 1,
        ],
            422);
    }

    /**
     * 异常错误
     * @param \Throwable $e
     * @param string $msg
     * @param int $errorCode
     * @return JsonResponse
     */
    final public static function errorException(\Throwable $e, string $msg = '意外错误', int $errorCode = 1): JsonResponse
    {
        return response()->json([
            'msg' => $msg,
            'status' => 500,
            'errorCode' => $errorCode,
            'details' => [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ],
            500);
    }
}
