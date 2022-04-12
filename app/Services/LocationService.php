<?php

namespace App\Services;


class LocationService
{
    private $_organizationLocationCode = null;
    private $_organizationLocationCodeLen = 0;

    public function __construct()
    {
        $this->_organizationLocationCode = env('ORGANIZATION_LOCATION_CODE');
        $this->_organizationLocationCodeLen = strlen(env('ORGANIZATION_LOCATION_CODE'));
    }

    /**
     * 解析位置编码
     * @param string $locationUniqueCode
     * @param int $state 0默认 1位置打印标签
     * @return string
     */
    public function makeLocationUniqueCode(string $locationUniqueCode, int $state = 0): string
    {
        if ($state == 1) {
            return (int)substr($locationUniqueCode, $this->_organizationLocationCodeLen, 2) . '-' . implode('-', str_split(substr($locationUniqueCode, $this->_organizationLocationCodeLen + 4), 2));
        } else {
            return (int)substr($locationUniqueCode, $this->_organizationLocationCodeLen, 2) . '-' . implode('-', str_split(substr($locationUniqueCode, $this->_organizationLocationCodeLen + 2), 2));
        }
    }

}