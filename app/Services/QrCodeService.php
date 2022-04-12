<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * 根据设备器材生成颜色
     * @param string $entire_instance_identity_code
     * @return int[]
     */
    final public function generateColorsByEntireInstanceStatus(string $entire_instance_identity_code = ''): array
    {
        $entire_instance = DB::table('entire_instances as ei')->where('ei.identity_code', $entire_instance_identity_code)->first();
        $has_alarm = DB::table('entire_instance_alarm_logs as eial')->where('eial.status', 'WARNING')->where('eial.entire_instance_identity_code', $entire_instance_identity_code)->exists();

        switch ($entire_instance->status) {
            case 'FIXING':
            case 'TRANSFER_IN':
            case 'SEND_REPAIR':
                // yellow
                $red = 255;
                $green = 255;
                $blue = 0;
                break;
            default:
            case 'INSTALLING':
            case 'INSTALLED':
            case 'FIXED':
            case 'TRANSFER_OUT':
                // green
                $red = 59;
                $green = 180;
                $blue = 108;
                break;
        }
        if ($has_alarm) {
            $red = 255;
            $green = 0;
            $blue = 0;
        }

        return ['red' => $red, 'green' => $green, 'blue' => $blue];
    }

    /**
     * 生成健康码
     * @param string $entire_instance_identity_code
     * @param int $size
     * @return string
     */
    final public function generateBase64ByEntireInstanceStatus(
        string $entire_instance_identity_code = ''
        , int $size = 140
    ): string
    {
        ['red' => $red, 'green' => $green, 'blue' => $blue,] = $this->generateColorsByEntireInstanceStatus($entire_instance_identity_code);


        $gen = base64_encode(QrCode::format('png')->color($red, $green, $blue)->size($size)->generate($entire_instance_identity_code));
        return 'data:image/png;base64,' . $gen;
    }
}
