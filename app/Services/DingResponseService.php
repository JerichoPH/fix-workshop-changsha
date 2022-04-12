<?php

namespace App\Services;

use Illuminate\Http\Response;

class DingResponseService
{
    final public function data($data): Response
    {
        return response()->make(['status' => 200, 'data' => $data], 200);
    }

    final public function created(string $msg): Response
    {
        return response()->make(['status' => 200, 'message' => $msg], 200);
    }

    final public function error(string $msg): Response
    {
        return response()->make(['status' => 500, 'message' => $msg], 500);
    }

    final public function errorForbidden(string $msg): Response
    {
        return response()->make(['status' => 403, 'message' => $msg], 500);
    }

    final public function errorUnauthorized(string $msg = '授权失败'): Response
    {
        return response()->make(['status' => 401, 'message' => $msg], 500);
    }

    final public function errorEmpty(string $msg): Response
    {
        return response()->make(['status' => 404, 'msg' => $msg], 500);
    }

    final public function errorValidate(string $msg): Response
    {
        return response()->make(['status' => 421, 'msg' => $msg], 500);
    }

}
