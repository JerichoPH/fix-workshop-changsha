<?php

namespace App\Services;

use App\Exceptions\WechatException;
use App\Facades\JsonResponseFacade;
use Curl\Curl;
use Jericho\FileSystem;

class WechatMiniAppService
{
    private $_root_dir = null;
    private $_curl = null;
    private $_app_id = null;
    private $_app_secret = null;

    public function __construct()
    {
        $this->_root_dir = storage_path('wechat');
        if (!is_dir($this->_root_dir)) FileSystem::init($this->_root_dir)->makeDir();
        $this->_curl = new Curl();
        $this->_app_id = env('WECHAT_APP_ID_' . env('ORGANIZATION_CODE'));
        $this->_app_secret = env('WECHAT_APP_SECRET_' . env('ORGANIZATION_CODE'));
    }

    /**
     * 获取app_id
     * @return string
     */
    final public function getAppId(): string
    {
        return $this->_app_id;
    }

    /**
     * 获取js_api_ticket
     * @return string
     */
    final public function getJsApiTicket(): string
    {
        $filename = "{$this->_root_dir}/jsApiTicket.txt";

        $_getJsApiTicket = function () use ($filename) {
            $this->_curl->get('https://api.weixin.qq.com/cgi-bin/ticket/getticket', [
                'access_token' => $this->getAccessToken(),
                'type' => 'jsapi',
            ]);

            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->errorMessage);

            if (@$this->_curl->response->errcode != 0) throw new WechatException($this->_curl->response->errmsg);
            if (!@$this->_curl->response->ticket) throw new WechatException('获取js_api_ticket失败');

            file_put_contents($filename, $this->_curl->response->ticket);
            return $this->_curl->response->ticket;
        };

        if (file_exists($filename) && time() - filemtime($filename) < 7200) {
            $js_api_ticket = file_get_contents($filename);
        } else {
            $js_api_ticket = $_getJsApiTicket();
        }

        return $js_api_ticket;
    }

    /**
     * 获取access_token
     * @return false|string
     */
    final public function getAccessToken(): string
    {
        $filename = "{$this->_root_dir}/accessToken.txt";

        $_getAccessToken = function () use ($filename) {
            $this->_curl->get('https://api.weixin.qq.com/cgi-bin/token', [
                'grant_type' => 'client_credential',
                'appid' => $this->_app_id,
                'secret' => $this->_app_secret,
            ]);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->errorMessage);
            if (@$this->_curl->response->errcode > 0) throw new WechatException($this->_curl->response->errmsg);
            if (!@$this->_curl->response->access_token) throw new WechatException('获取access_token失败');

            file_put_contents($filename, $this->_curl->response->access_token);
            return $this->_curl->response->access_token;
        };

        if (file_exists($filename) && time() - filemtime($filename) < 7200) {
            $access_token = file_get_contents($filename);
        } else {
            $access_token = $_getAccessToken();
        }
        return $access_token;
    }
}
