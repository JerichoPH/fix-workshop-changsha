<?php

namespace App\Services;

use App\Facades\EntireInstanceFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class CodeService
{
    private $_currentDatetime;
    private $_currentDate;
    private $_currentYearMonth;
    private $_currentTime;
    private $_serialNumberType = [
        'IN' => '01',  # 入所单
        'OUT' => '02',  # 出所单
        'WAREHOUSE_IN' => '01',  # 入所单
        'WAREHOUSE_OUT' => '02',  # 出所单
        'FIX_WORKFLOW' => '03',  # 工单
        'FIX_WORKFLOW_PROCESS' => '04',  # 检测单
        'FIX_WORKFLOW_PROCESS_PART' => '05',  # 部件检测单
        'FIX_WORKFLOW_PROCESS_ENTIRE' => '06',  # 整件检测单
        'HIGH_FREQUENCY_IN' => '07',  # 高频修入所
        'HIGH_FREQUENCY_OUT' => '08',  # 高频修出所
        'BREAKDOWN_IN' => '09',  # 故障修入所
        'BREAKDOWN_OUT' => '10',  # 故障修出所
        'EXCHANGE_MODEL_IN' => '11',  # 更换型号入所
        'EXCHANGE_MODEL_OUT' => '12',  # 更换型号出所
        'NEW_STATION_IN' => '13',  # 新站入所
        'NEW_STATION_OUT' => '14',  # 新站出所
        'FULL_FIX_IN' => '15',  # 大修入所
        'FULL_FIX_OUT' => '16',  # 大修出所
        'FULL_FIX_SCRAP' => '17',  # 大修报废
        'STATION_REFORM_IN' => '18', # 站改入所
        'STATION_REFORM_OUT' => '19', # 站改出所
        'FIX_MISSION_NEW_STATION' => '20', # 检修任务：新站
        'FIX_MISSION_EXCHANGE_MODEL' => '21',  # 检修任务：技改
        'FIX_MISSION_HIGH_FREQUENCY' => '22',  # 检修任务：高频修
        'FIX_MISSION_FULL_FIX' => '23',  # 检修任务：大修
        'FIX_MISSION_STATION_REFORM' => '24',  # 检修任务：站改
        'SCENE_BACK_IN' => '25',  # 新站任务：现场退回
    ];

    final public function __construct()
    {
        $this->_currentDatetime = date('YmdHis');
        $this->_currentDate = date('Ymd');
        $this->_currentYearMonth = date('Ym');
        $this->_currentTime = time();
    }

    /**
     * 360唯一编码转十六进制
     * @param string $identityCode
     * @return string
     * @throws \Exception
     */
    final public function identityCodeToHex(string $identityCode): string
    {
        $len = strlen($identityCode);
        switch ($len) {
            case 14:
                $length = str_pad(dechex($len), 2, '0', STR_PAD_LEFT);  # 长度位（1位）
                $asciiHex = str_pad(dechex(intval(TextHelper::strToAscii(substr($identityCode, 0, 1)))), 2, '0', STR_PAD_LEFT);  # 字母位（1位）
                $categoryUniqueCode = str_pad(dechex(intval(substr($identityCode, 1, 2))), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 3, 2))), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $sectionUniqueCode = str_pad(dechex(intval(substr($identityCode, 6, 3))), 4, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberLength = "05";
                $serialNumberCode = str_pad(dechex(intval(substr($identityCode, 9, 5))), 8, '0', STR_PAD_LEFT);  # 序码位（4位）
                return strtoupper("13{$length}{$asciiHex}{$categoryUniqueCode}{$entireModelUniqueCode}{$sectionUniqueCode}{$serialNumberLength}{$serialNumberCode}");  # 组合RFID EPC CODE
                break;
            case 19:
                $length = str_pad(dechex($len), 2, '0', STR_PAD_LEFT);  # 长度位（1位）
                $asciiHex = str_pad(dechex(intval(TextHelper::strToAscii(substr($identityCode, 0, 1)))), 2, '0', STR_PAD_LEFT);  # 字母位（1位）
                $categoryUniqueCode = str_pad(dechex(intval(substr($identityCode, 1, 2))), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 3, 2))), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $partModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 5, 2))), 2, '0', STR_PAD_LEFT);  # 形码位（1位）
                $sectionUniqueCode = str_pad(dechex(intval(substr($identityCode, 8, 3))), 4, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberLength = "08";
                $serialNumberCode = str_pad(dechex(intval(substr($identityCode, 11, 8))), 8, '0', STR_PAD_LEFT);  # 序码位（4位）
                return strtoupper("{$length}{$asciiHex}{$categoryUniqueCode}{$entireModelUniqueCode}{$partModelUniqueCode}{$sectionUniqueCode}{$serialNumberLength}{$serialNumberCode}");  # 组合RFID EPC CODE
                break;
            default:
                throw new \Exception("参数错误");
                break;
        }
    }

    /**
     * 十六进制转360唯一编码
     * @param string $hex
     * @return string
     * @throws \Exception
     */
    final public function hexToIdentityCode(string $hex)
    {
        $hex = $this->hexCodeCheck($hex);
        if (empty($hex)) throw new \Exception("代码格式错误");
        switch ($hex[0] . $hex[1]) {
            case '130E':
                $char = TextHelper::asciiToStr(hexdec($hex[2]));  # 字母位（1位）
                $categoryUniqueCode = str_pad(hexdec($hex[3]), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(hexdec($hex[4]), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $sectionUniqueCode = 'B' . str_pad(hexdec($hex[5] . $hex[6]), 3, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberCode = str_pad(hexdec($hex[8] . $hex[9] . $hex[10] . $hex[11]), hexdec($hex[7]), '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$char}{$categoryUniqueCode}{$entireModelUniqueCode}{$sectionUniqueCode}{$serialNumberCode}";
                break;
            case '130F':
                $char = TextHelper::asciiToStr(hexdec($hex[1]));  # 字母位（1位）
                $categoryUniqueCode = str_pad(hexdec($hex[2]), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(hexdec($hex[3]), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $partModelUniqueCode = str_pad(hexdec($hex[4]), 2, '0', STR_PAD_LEFT);  # 形码位（1位）
                $sectionUniqueCode = 'B' . str_pad(hexdec($hex[5] . $hex[6]), 3, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberCode = str_pad(hexdec($hex[8] . $hex[9] . $hex[10] . $hex[11]), hexdec($hex[7]), '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$char}{$categoryUniqueCode}{$entireModelUniqueCode}{$partModelUniqueCode}{$sectionUniqueCode}{$serialNumberCode}";
                break;
            default:
                throw new \Exception("代码格式错误");
                break;
        }
    }

    /**
     * 验证十六进制代码是否合法
     * @param string $hex
     * @return array
     * @throws \Exception
     */
    final public function hexCodeCheck(string $hex): array
    {
        $hex = str_split($hex, 2);
        if (count($hex) < 11) throw new \Exception("代码长度错误");
        $first = $hex[0] . $hex[1];
        return ($first == "130E" || $first == "130F") ? $hex : [];
    }

    /**
     * 生成整件身份码
     * @param string $entireModelUniqueCode
     * @param string $factoryUniqueCode
     * @return string
     */
    final public function makeEntireInstanceIdentityCode(string $entireModelUniqueCode, string $factoryUniqueCode = null)
    {
        $entireModel = EntireModel::with([
            'Category',
            'Category.Race',
        ])
            ->where('unique_code', $entireModelUniqueCode)
            ->firstOrFail();

        return "{$entireModel->unique_code}"
            . env('ORGANIZATION_CODE')
            . str_pad(
                strtoupper(EntireInstanceFacade::incCount($entireModelUniqueCode)),
                $entireModel->Category->Race->serial_number_length,
                '0',
                STR_PAD_LEFT
            );
    }

    /**
     * 批量生成整件唯一编号
     * @param string $entire_model_unique_code
     * @param array $new_entire_instances
     * @return array
     * @throws \Exception
     */
    final public function generateEntireInstanceIdentityCodes(string $entire_model_unique_code, array $new_entire_instances): array
    {
        $first = substr($entire_model_unique_code, 0, 1);

        $lengths = collect(['S' => 5, 'Q' => 7,]);
        $length = $lengths->get($first, 0);

        $v = DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.entire_model_unique_code as eu'])
            ->where('ei.entire_model_unique_code', $entire_model_unique_code)
            ->orderByDesc('ei.id')
            ->orderByDesc('ei.created_at')
            ->orderByDesc('ei.identity_code')
            ->first();
        if ($v) {
            $tmp_identity_code = rtrim($v->identity_code, 'H');
            $count = intval(substr($tmp_identity_code, (strlen($entire_model_unique_code) + 4)));
        } else {
            $count = 0;
        }

        $new_entire_instances = array_map(
            function ($new_entire_instance) use (
                $entire_model_unique_code,
                &$count,
                $length,
                $first
            ) {
                $count++;

                switch ($length) {
                    case 5:
                    case 7:
                        $new_entire_instance['identity_code'] = $entire_model_unique_code
                            . env('ORGANIZATION_CODE')
                            . str_pad(
                                $count,
                                $length,
                                '0',
                                STR_PAD_LEFT
                            )
                            . ($first === 'Q' ? 'H' : '');
                        break;
                    default:
                        throw new \Exception("设备赋码时，当前型号不属于设备也不属于型号：{$first}{$length}");
                }

                return $new_entire_instance;
            },
            $new_entire_instances
        );

        return [$new_entire_instances, $count];
    }

    /**
     * 生成部件身份码
     * @param string $partModelUniqueCode
     * @return string
     */
    final public function makePartInstanceIdentityCode(string $partModelUniqueCode): string
    {
        $code = $this->_currentDate . strval(rand(1000, 9999));
        $code = TextHelper::to32($code);
        $repeat = DB::table('part_instances as pi')
            ->select(['id'])
            ->where('pi.identity_code', $code)
            ->first();
        return $repeat ? $this->makePartInstanceIdentityCode($partModelUniqueCode) : $code;
    }

    /**
     * 生成新的整件设备流水号
     * @param string $entireModelUniqueCode
     * @return string
     */
    final public function makeEntireInstanceSerialNumber(string $entireModelUniqueCode): string
    {
        $entireInstanceCount = EntireInstanceFacade::incFixedCount($entireModelUniqueCode);
        $entireInstanceCount = str_pad($entireInstanceCount, 5, '0', STR_PAD_LEFT);
        return env('ORGANIZATION_CODE') . "{$this->_currentYearMonth}{$entireModelUniqueCode}{$entireInstanceCount}";
    }

    /**
     * 生成流水单号
     * @param string $type
     * @param string|null $date
     * @return string
     */
    final public function makeSerialNumber(string $type, string $date = null): string
    {
        $now = $date ?? $this->_currentDate;
        $new_code = env('ORGANIZATION_CODE') . "{$now}{$this->_serialNumberType[$type]}";
        return $new_code . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        switch ($type) {
            case 'IN':
            case 'OUT':
            case 'WAREHOUSE_IN':
            case 'WAREHOUSE_OUT':
            case 'HIGH_FREQUENCY_IN':
            case 'HIGH_FREQUENCY_OUT':
            case 'BREAKDOWN_IN':
            case 'BREAKDOWN_OUT':
            case 'EXCHANGE_MODEL_IN':
            case 'EXCHANGE_MODEL_OUT':
            case 'NEW_STATION_IN':
            case 'NEW_STATION_OUT':
            case 'FULL_FIX_IN':
            case 'FULL_FIX_OUT':
            case 'FULL_FIX_SCRAP':
            case 'STATION_REFORM_IN':
            case 'STATION_REFORM_OUT':
                $db = DB::table('warehouse_reports');
                break;
            case 'FIX_WORKFLOW':
                $db = DB::table('fix_workflows');
                break;
            case 'FIX_WORKFLOW_PROCESS':
            case 'FIX_WORKFLOW_PROCESS_PART':
            case 'FIX_WORKFLOW_PROCESS_ENTIRE':
                $db = DB::table('fix_workflow_processes');
                break;
            default:
                return $new_code . rand(100000, 999999);
        }

        $last = $db->whereBetween('created_at', [Carbon::parse($date)->startOfDay(), Carbon::parse($date)->endOfDay()])->orderByDesc('created_at')->first();
        $new_sn = $last ? (@$last->serial_number ? intval(str_replace($new_code, '', $last->serial_number)) : 0) : 0;
        return $new_code . str_pad($new_sn + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * 生成测试模板身份码
     * @param string $entireModelUniqueCode
     * @param string|null $partModelUniqueCode
     * @return string
     */
    final public function makeMeasurementIdentityCode(string $entireModelUniqueCode, string $partModelUniqueCode = null): string
    {
        $header = $partModelUniqueCode ? 'MP' : 'ME';
        $uniqueCode = $partModelUniqueCode ? $partModelUniqueCode : $entireModelUniqueCode;
        $code = "{$header}{$this->_currentDatetime}{$uniqueCode}" . strval(rand(1000, 9999));
        $repeat = DB::table('measurements')->where('identity_code', $code)->first(['id']);
        return $repeat ? self::makeMeasurementIdentityCode($entireModelUniqueCode, $partModelUniqueCode) : $code;
    }
}
