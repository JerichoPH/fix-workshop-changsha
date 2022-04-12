<?php

namespace App\Services;

use App\Model\EntireInstanceLog;
use Illuminate\Support\Facades\DB;

class EntireInstanceLogService
{

    /**
     * 生成单个操作日志
     * @param string $name
     * @param string $entireInstanceIdentityCode
     * @param string $description
     * @param int $type
     * @param string $url
     * @param string $materialType
     * @return bool
     */
    final public function makeOne(string $name, string $entireInstanceIdentityCode, int $type = 0, string $url = '', string $description = '', string $materialType = 'ENTIRE'): bool
    {
        # 插入整件操作日志
        return DB::table('entire_instance_logs')
            ->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $name,
                'description' => $description,
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'type' => $type,
                'url' => $url,
                'material_type' => $materialType
            ]);
    }

    /**
     * 通过整件对象列表批量添加
     * @param string $name
     * @param array $entireInstances
     * @param string $description
     * @param int $type
     * @param string $url
     * @return bool
     */
    final public function makeBatchUseEntireInstances(string $name, array $entireInstances, int $type = 0, string $url = '', string $description = ''): bool
    {
        $entireInstanceLogs = [];
        foreach ($entireInstances as $entireInstance) {
            $entireInstanceLogs[] = [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $name,
                'description' => $description,
                'entire_instance_identity_code' => $entireInstance['identity_code'],
                'type' => $type,
                'url' => $url
            ];
        }
        return DB::table('entire_instance_logs')->insert($entireInstanceLogs);
    }

    /**
     * 通过唯一编号列表批量添加
     * @param string $name
     * @param array $entireInstanceIdentityCode
     * @param string $description
     * @param int $type
     * @param string $url
     * @return bool
     */
    final public function makeBatchUseEntireInstanceIdentityCodes(string $name, array $entireInstanceIdentityCode, int $type = 0, string $url = '', string $description = ''): bool
    {
        $entireInstanceLogs = [];
        foreach ($entireInstanceIdentityCode as $identityCode) {
            $entireInstanceLogs[] = [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $name,
                'description' => $description,
                'entire_instance_identity_code' => $identityCode,
                'type' => $type,
                'url' => $url
            ];
        }
        return DB::table('entire_instance_logs')->insert($entireInstanceLogs);
    }

    /**
     * 通过数组批量生成日志
     * @param array $data
     * @return bool
     */
    final public function makeBatchUseArray(array $data): bool
    {
        return DB::table('entire_instance_logs')->insert($data);
    }
}
