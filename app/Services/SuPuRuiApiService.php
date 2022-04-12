<?php

namespace App\Services;

use Ethansmart\HttpBuilder\Builder\HttpClientBuilder;
use Jericho\HttpRequestHelper;
use Jericho\TextHelper;

class SuPuRuiApiService
{
//    private $_url = "http://spr.f3322.net:81/";
    private $_url = "http://192.168.253.1:8111/";
    private $_isDebug = true;
    private $_userName = 'bjrew';
    private $_userPwd = 'Bjrew.123456789018';
    private $_httpRequest = null;
    private $_data = ['userName' => 'bjrew', 'userPwd' => 'BJrew.123456789018'];
    private $_returnType = 'prototype';

    /**
     * 设置debug开关
     * @param bool $isDebug
     * @return SuPuRuiApiService
     */
    final public function debug(bool $isDebug = true): self
    {
        $this->_isDebug = $isDebug;
        return $this;
    }

    /**
     * 设置返回值格式
     * @param string $returnType
     * @return SuPuRuiApiService
     */
    final public function returnType(string $returnType = 'prototype'): self
    {
        $this->_returnType = $returnType;
        return $this;
    }

    /**
     * 链接测试
     * @return array
     */
    final public function test(): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('ConnectTest'));
//        $this->_httpRequest = HttpClientBuilder::create()->build()->Post([
//            'uri' => $this->_url('ConnectTest')
//        ]);
        return $this->_send();
    }

    /**
     * 生成路由
     * @param string $route
     * @return string
     */
    final private function _url(string $route = null): string
    {
        $url = $this->_isDebug ?
            "{$this->_url}/WebService_Laws.asmx" :
            "{$this->_url}/WebService.asmx";
        if ($route) $url .= "/{$route}";
        return $url;
    }

    /**
     * 发送请求
     * @param array|null $data
     * @return array
     */
    final private function _send(array $data = null): array
    {
        $post = $data ? array_merge($this->_data, $data) : $this->_data;
        $returnType = $this->_returnType;
        return $this->_httpRequest->params($post)->isReturnMap()->isXWwwUrlEncode()->post()->$returnType();
    }

    /**
     * 修改密码
     * @param string $newPwd
     * @return array
     */
    final public function password(string $newPwd): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('ModifyUserPassword'));
        return $this->_send(['newPwd' => $newPwd], $this->_returnType);
    }

    /**
     * 获取全部静态资源
     * @return array
     */
    final public function wholeZip(): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('GetFullDataName'));
        return $this->_send();
    }

    /**
     * 写入设备单元信息
     * @param array $entireInstances
     * @return array
     */
    final public function insertEntireInstances_S(array $entireInstances): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteEquipmentInfo'));
        return $this->_send(['json' => TextHelper::toJson($entireInstances)]);
    }

    /**
     * 写入关键器件信息
     * @param array $entireInstances
     * @return array
     */
    final public function insertEntireInstances_Q(array $entireInstances): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteDeviceInfo'));
        return $this->_send(['json' => TextHelper::toJson($entireInstances)]);
    }

    /**
     * 写入设备单元分类说明
     * @param array $entireModels
     * @return array
     */
    final public function insertEntireModels_S(array $entireModels): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteEquipmentSpecies'));
        return $this->_send(['json' => TextHelper::toJson($entireModels)]);
    }

    /**
     * 写入关键器件分类说明
     * @param array $entireModels
     * @return array
     */
    final public function insertEntireModels_Q(array $entireModels): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteDeviceSpecies'));
        return $this->_send(['json' => TextHelper::toJson($entireModels)]);
    }

    /**
     * 写入设备单元维修记录
     * @param array $fixWorkflows
     * @return array
     */
    final public function insertFixWorkflows_S(array $fixWorkflows): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteEquipmentRepair'));
        return $this->_send(['json' => TextHelper::toJson($fixWorkflows)]);
    }

    /**
     * 写入关键器件维修记录
     * @param array $fixWorkflows
     * @return array
     */
    final public function insertFixWorkflows_Q(array $fixWorkflows): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('WriteDeviceRepair'));
        return $this->_send(['json' => TextHelper::toJson($fixWorkflows)]);
    }

    /**
     * 获取设备单元检修记录
     * @param string $entireInstanceIdentityCode
     * @return array
     */
    public function findFixWorkflow_S(string $entireInstanceIdentityCode): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('GetEquipmentRepair'));
        return $this->_send(['id' => $entireInstanceIdentityCode]);
    }

    /**
     * 获取关键器件检修记录
     * @param string $entireInstanceIdentityCode
     * @return array
     */
    public function findFixWorkflow_Q(string $entireInstanceIdentityCode): array
    {
        $this->_httpRequest = HttpRequestHelper::INS($this->_url('GetDeviceRepair'));
        return $this->_send(['id' => $entireInstanceIdentityCode]);
    }

    /**
     * 读取设备单元或关键器件动态数据
     * @param string $entireInstanceIdentityCode
     * @return array
     */
    final public function findEntireInstance(string $entireInstanceIdentityCode): array
    {
        $route = null;
        switch (strtoupper(substr($entireInstanceIdentityCode, 0, 1))) {
            default:
            case 'Q':
                $route = 'GetEquipmentRepair';
                break;
            case 'S':
                $route = 'GetDeviceRepair';
                break;
        }
        $this->_httpRequest = HttpRequestHelper::INS($this->_url($route));
        return $this->_send(['id' => $entireInstanceIdentityCode]);
    }
}
