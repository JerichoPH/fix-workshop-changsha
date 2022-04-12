<?php

namespace App\Services;

use App\Model\BreakdownLog;
use App\Model\BreakdownType;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\PivotBreakdownLogAndBreakdownType;

class BreakdownLogService
{
    /**
     * 入所故障描述
     * @param EntireInstance $entire_instance
     * @param string $explain
     * @param string $submitted_at
     * @param array $breakdown_type_ids
     * @param string $submitter_name
     * @return array
     */
    final public function createWarehouseIn(
        EntireInstance $entire_instance,
        string $explain = '',
        string $submitted_at = '',
        array $breakdown_type_ids = [],
        string $submitter_name = ''
    ): array
    {
        # 记录故障描述（补充）
        $breakdown_log = BreakdownLog::with([])->create([
            'entire_instance_identity_code' => $entire_instance->identity_code,
            'explain' => $explain ? "补充：{$explain}" : '',
            'scene_workshop_name' => @$entire_instance->Station->Parent->name ?: '',
            'maintain_station_name' => @$entire_instance->Station->name ?: '',
            'maintain_location_code' => @$entire_instance->maintain_location_code ?: '',
            'crossroad_number' => @$entire_instance->crossroad_number ?: '',
            'traction' => @$entire_instance->traction ?: '',
            'line_name' => @$entire_instance->line_name ?: '',
            'open_direction' => @$entire_instance->open_direction ?: '',
            'said_rod' => @$entire_instance->said_rod ?: '',
            'crossroad_type' => @$entire_instance->crossroad_type ?: '',
            'point_switch_group_type' => @$entire_instance->point_switch_group_type ?: '',
            'extrusion_protect' => @$entire_instance->extrusion_protect ?: '',
            'type' => 'WAREHOUSE_IN',
            'submitter_name' => @$submitter_name ?: session('account.nickname'),
            'submitted_at' => $submitted_at,
        ]);

        # 记录设备日志
        # 故障类型转字符串
        $breakdown_type_names = BreakdownType::with([])->whereIn('id', $breakdown_type_ids)->pluck('name');
        $breakdown_type_names_msg = '';
        if ($breakdown_type_names->isNotEmpty())
            $breakdown_type_names_msg = "<ol><li>{$breakdown_type_names->implode('</li><li>')}</li></ol>";
        $submitter_msg = "&emsp;&emsp;上报人：" . @$submitter_name ?: session('account.nickname') . "&emsp;上报时间：{$submitted_at}";
        $explain = $explain ? "&emsp;&emsp;补充：{$explain}" : '';
        $entire_instance_log = EntireInstanceLog::with([])->create([
            'name' => $breakdown_log->type,
            'description' => $breakdown_type_names_msg . $submitter_msg . $explain,
            'entire_instance_identity_code' => $entire_instance->identity_code,
            'type' => 5,
        ]);

        # 多对多故障日志和类型关联
        $pivot_breakdown_log_and_breakdown_types = [];
        foreach ($breakdown_type_ids as $breakdown_type_id) {
            $pivot_breakdown_log_and_breakdown_types[] = [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'breakdown_log_id' => $breakdown_log->id,
                'breakdown_type_id' => $breakdown_type_id,
            ];
        }
        PivotBreakdownLogAndBreakdownType::with([])->insert($pivot_breakdown_log_and_breakdown_types);

        return ['entire_instance_log_id'=>$entire_instance_log->id,'breakdown_log_id'=>$breakdown_log->id];
    }

    /**
     * 现场故障描述
     * @param EntireInstance $entire_instance
     * @param string $explain
     * @param string $submitted_at
     * @param string $crossroad_number
     * @param string $submitter_name
     * @return bool
     */
    final public function createStation(
        EntireInstance $entire_instance,
        string $explain = '',
        string $submitted_at = '',
        string $crossroad_number = '',
        string $submitter_name = ''
    ): bool
    {
        # 记录故障描述
        $breakdown_log = BreakdownLog::with([])->create([
            'entire_instance_identity_code' => $entire_instance->identity_code,
            'explain' => $explain ? "内容：{$explain}" : '',
            'scene_workshop_name' => @$entire_instance->Station->Parent->name ?: '',
            'maintain_station_name' => @$entire_instance->Station->name ?: '',
            'maintain_location_code' => @$entire_instance->maintain_location_code ?: '',
            'crossroad_number' => $crossroad_number ?: '',
            'traction' => @$entire_instance->traction ?: '',
            'line_name' => @$entire_instance->line_name ?: '',
            'open_direction' => @$entire_instance->open_direction ?: '',
            'said_rod' => @$entire_instance->said_rod ?: '',
            'crossroad_type' => @$entire_instance->crossroad_type ?: '',
            'point_switch_group_type' => @$entire_instance->point_switch_group_type ?: '',
            'extrusion_protect' => @$entire_instance->extrusion_protect ?: '',
            'type' => 'STATION',
            'submitter_name' => @$submitter_name ?: session('account.nickname'),
            'submitted_at' => $submitted_at,
        ]);

        # 记录设备日志
        $explain = "{$explain}&emsp;道岔号：{$crossroad_number}&emsp;上报人：" . @$submitter_name ?: session('account.nickname') . "&emsp;上报时间：{$submitted_at}";
        EntireInstanceLog::with([])->create([
            'name' => $breakdown_log->type,
            'description' => $explain,
            'entire_instance_identity_code' => $entire_instance->identity_code,
            'type' => 5,
        ]);
        return true;
    }

    /**
     * 原样记载故障类型
     * @param string $identityCode
     * @param string $explain
     * @param string $submittedAt
     * @param string $crossroadNumber
     * @param string $submitterName
     * @param string $stationName
     * @param string $locationCode
     * @return bool
     */
    final public function createStationAsOriginal(
        string $identityCode,
        string $explain = '',
        string $submittedAt = '',
        string $crossroadNumber = '',
        string $submitterName = '',
        string $stationName = '',
        string $locationCode = ''
    ): bool
    {
        # 记录故障描述
        $breakdown_log = BreakdownLog::with([])->create([
            'entire_instance_identity_code' => $identityCode,
            'explain' => $explain ? "内容：{$explain}" : '',
            'scene_workshop_name' => '',
            'maintain_station_name' => $stationName,
            'maintain_location_code' => $locationCode,
            'crossroad_number' => $crossroadNumber,
            'traction' => '',
            'line_name' => '',
            'open_direction' => '',
            'said_rod' => '',
            'crossroad_type' => '',
            'point_switch_group_type' => '',
            'extrusion_protect' => '',
            'type' => 'STATION',
            'submitter_name' => $submitterName,
            'submitted_at' => $submittedAt,
        ]);

        # 记录设备日志
        $explain = "{$explain}&emsp;道岔号：{$crossroadNumber}&emsp;上报人：" . @$submitterName ?: "未登记" . "&emsp;上报时间：{$submittedAt}";
        EntireInstanceLog::with([])->create([
            'name' => $breakdown_log->type,
            'description' => $explain,
            'entire_instance_identity_code' => $identityCode,
            'type' => 5,
        ]);
        return true;
    }
}
