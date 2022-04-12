<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResponseService
{
    private $_msg = '';
    private $_error_code = 0;
    private $_status = 200;
    private $_data = [];
    private $_details = [];

    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    final public function setData(array $data = []):self
    {
        $this->_data = $data;
        return $this;
    }

    final public function setDetails(...$details):self
    {
        $this->_details = $details;
        return $this;
    }

    final public function setErrorCode(int $error_code): self
    {
        $this->_error_code = $error_code;
        return $this;
    }

    final public function created(string $msg = '添加成功')
    {
        $this->_msg = $msg;
        $this->_status = 201;
        return $this;
    }

    final public function updated(string $msg = '编辑成功')
    {
        $this->_msg = $msg;
        $this->_status = 202;
        return $this;
    }

    final public function deleted(string $msg = '删除成功')
    {
        $this->_msg = $msg;
        $this->_status = 204;
        return $this;
    }

    final public function empty(string $msg = '数据不存在', ...$details)
    {
        $this->_msg = $msg;
        $this->_status = 404;
        $this->_details = $details;
        return $this;
    }

    final public function forbidden(string $msg = '禁止操作', ...$details)
    {
        $this->_msg = $msg;
        $this->_status = 403;
        $this->_details = $details;
        return $this;
    }

    final public function authorization(string $msg = '无权操作', ...$details)
    {
        $this->_msg = $msg;
        $this->_status = 401;
        $this->_details = $details;
        return $this;
    }

    final public function validate(string $msg = '', ...$details)
    {
        $this->_msg = $msg;
        $this->_status = 422;
        $this->_details = $details;
        return $this;
    }

    final public function exception(string $msg = '意外错误', \Throwable $e): self
    {
        $this->_msg = $msg;
        $this->_status = 500;
        $this->_details = [
            'class_name' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        return $this;
    }

    private function _generate(): array
    {
        return [
            'msg' => $this->_msg,
            'error_code' => $this->_error_code,
            'data' => $this->_data,
            'details' => $this->_details,
        ];
    }

    final public function json(): JsonResponse
    {
        return response()->json($this->_generate(), $this->_status);
    }

    public function http(): Response
    {
        return response()->make($this->_msg, $this->_status);
    }
}
