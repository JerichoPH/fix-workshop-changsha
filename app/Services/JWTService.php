<?php

namespace App\Services;
use App\Model\Account;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JWTService
{
    /**
     * 生成jwt
     * @param $payload
     * @return string
     */
    final public function generate($payload):string
    {
        // 生成jwt
        $time = time();
        $nbf = env('JWT_NBF');
        $exp = env('JWT_EXP');
        $token = [
            'iss' => env('JWT_ISS', null),  // 发送人
            'aud' => $payload['account'],  // 接收人
            'iat' => $time,  // 签发时间
            'nbf' => $time + $nbf,  // 立即可用
            'exp' => $time + $exp,  // 14天后过期
            'payload' => $payload,
        ];
        return JWT::encode($token, env('JWT_KEY'));  // 转译JWT
    }
}
