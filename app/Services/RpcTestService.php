<?php

namespace App\Services;

use Hprose\Http\Server;

class RpcTestService
{
    final public function init()
    {
        $serve = new Server();
        $serve->addMethod('test', $this);
        $serve->addMethod('params', $this);
        $serve->start();
    }

    final public function test()
    {
        return 'here is rpc test';
    }

    final public function params(string $params)
    {
        return "here is u params: {$params}";
    }
}
