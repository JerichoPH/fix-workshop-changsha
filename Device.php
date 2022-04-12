<?php

namespace Input;

use App\Model\EntireInstanceLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;
use PHPExcel_Worksheet;

class Device
{
    private $_excel = null;
    private $_dir = null;
    private $_filename = null;
    private $_extension = 'xlsx';
    private $_zone = 1;
    private $_originRow = 2;
    private $_finishRow = 0;
    private $_highestRow = 0;
    private $_highestColumn = 0;
    private $_entireInstanceCounts = [];

    /**
     * 通过本地文件打开excel
     * @param string $dir
     * @param string $filename
     * @return Device
     * @throws \Exception
     */
    final public static function fromStorage(string $dir, string $filename): self
    {
        # 检查文件是否存在及获取文件基本信息
        if (!is_file("{$dir}/{$filename}")) throw new \Exception('文件不存在', 1);
        $pathInfo = pathinfo("{$dir}/{$filename}");

        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify("{$dir}/{$filename}");
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $obj = new self;
        $obj->_excel = $objReader->load("{$dir}/{$filename}");
        $obj->_dir = $dir;
        $obj->_filename = $pathInfo['filename'];
        $obj->_extension = $pathInfo['extension'];
        $entireInstanceCounts = DB::table('entire_instance_counts')->pluck('count', 'entire_model_unique_code');
        if ($entireInstanceCounts->isNotEmpty()) $obj->_entireInstanceCounts = $entireInstanceCounts->toArray();
        return $obj;
    }

    /**
     * 通过Sheet名获取Sheet
     * @param string $sheetName
     * @return PHPExcel_Worksheet
     */
    final private function _getSheetByName(string $sheetName = "Sheet1"): PHPExcel_Worksheet
    {
        $sheet = $this->_excel->getSheetByName($sheetName);
        $this->_highestRow = $sheet->getHighestRow();
        $this->_highestColumn = $sheet->getHighestColumn();

        $this->getOriginRow($this->_highestRow);  # 设置起始行
        $this->getFinishRow($this->_highestRow);  # 设置终止行

        return $sheet;
    }

    /**
     * excel时间戳转换日期时间
     * @param int $t
     * @return string
     */
    final private function _getDatetime(int $t): string
    {
        return gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24));
    }

    /**
     * 型号转代码
     * @param string $modelName
     * @return string
     * @throws \Exception
     */
    final private function _getModelUniqueCode(string $modelName): string
    {
        // return gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24));
        $em = DB::table('entire_models')->where('name', $modelName)->first();
        $pm = DB::table('part_models')->where('name', $modelName)->first();

        if (is_null($em) && is_null($pm)) {
            throw new \Exception("没有找到型号：{$modelName}");
        } else {
            return $em ? $em->unique_code : $pm->unique_code;
        }
    }

    /**
     * excel时间戳转换时间戳
     * @param int $t
     * @return int
     */
    final private function _getTimestamp(int $t): int
    {
        return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24)))->timestamp;
    }

    /**
     * 递归创建文件夹
     * @param $dir
     * @param int $mode
     * @return bool
     */
    final private function _makeDirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true;
        if (!$this->_makeDirs(dirname($dir), $mode)) return false;

        return @mkdir($dir, $mode);
    }

    /**
     * 获取和合法化起始行
     * @param int $highestRow
     * @return int
     */
    final public function getOriginRow(int $highestRow): int
    {
        if ($this->_originRow > $highestRow) $this->_originRow = intval($highestRow);
        return $this->_originRow;
    }

    /**
     * 设置起始行
     * @param int $originRow
     * @return Device
     */
    final public function setOriginRow(int $originRow = 2): self
    {
        $this->_originRow = $originRow;
        return $this;
    }

    /**
     * 设置和合法化最大行
     * @param int $highestRow
     * @return int
     */
    final public function getFinishRow(int $highestRow): int
    {
        if (($this->_finishRow == 0) || ($this->_finishRow > $highestRow)) $this->_finishRow = intval($highestRow);
        return $this->_finishRow;
    }

    /**
     * 设置结束行
     * @param int $finishRow
     * @return $this
     */
    final public function setFinishRow(int $finishRow = 0)
    {
        $this->_finishRow = $finishRow;
        return $this;
    }

    /**
     * 设置工区 1：转辙机工区 2：继电器工区 3：综合工区
     * @param int|null $zone
     * @return $this|int
     */
    final public function setZone(int $zone = null)
    {
        $this->_zone = $zone;
        return $this;
    }

    /**
     * 获取转辙机型号数据
     * @param string $modelName
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    final private function _getPointSwitchModel(string $modelName)
    {
        return DB::table('part_models')
            ->select([
                'part_models.name as part_model_name',
                'part_models.unique_code as part_model_unique_code',
                'entire_models.unique_code as entire_model_unique_code',
                'categories.name as category_name',
                'categories.unique_code as category_unique_code'
            ])
            ->join('categories', 'categories.unique_code', '=', 'part_models.category_unique_code')
            ->join('entire_models', 'entire_models.unique_code', '=', 'part_models.entire_model_unique_code')
            ->where('categories.deleted_at', null)
            ->where('entire_models.deleted_at', null)
            ->where('part_models.deleted_at', null)
            ->where('part_models.name', $modelName)
            ->first();
    }

    /**
     * 获取继电器和综合设备型号数据
     * @param string $modelName
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    final private function _getRelayModel(string $modelName)
    {
        return DB::table('entire_models')
            ->select([
                'entire_models.unique_code as entire_model_unique_code',
                'categories.name as category_name',
                'categories.unique_code as category_unique_code'
            ])
            ->join('categories', 'categories.unique_code', '=', 'entire_models.category_unique_code')
            ->where('entire_models.is_sub_model', true)
            ->where('entire_models.deleted_at', null)
            ->where('entire_models.name', $modelName)
            ->first();
    }

    /**
     * 生成整件唯一编号
     * @param string $entireModelUniqueCode
     * @return string
     */
    final private function _makeEntireInstanceIdentityCode(string $entireModelUniqueCode): string
    {
        $races = [
            'Q' => 8,
            'S' => 5,
        ];


        if (key_exists($entireModelUniqueCode, $this->_entireInstanceCounts)) {
            $this->_entireInstanceCounts[$entireModelUniqueCode] += 1;
        } else {
            $this->_entireInstanceCounts[$entireModelUniqueCode] = 1;
        }

        $count = $this->_entireInstanceCounts[$entireModelUniqueCode];

        return $entireModelUniqueCode . env('ORGANIZATION_CODE') . str_pad($count, $races[substr($entireModelUniqueCode, 0, 1)], '0', STR_PAD_LEFT);
    }

    /**
     * 生成部件唯一编号
     * @return string
     */
    final private function _makePartInstanceIdentityCode(): string
    {
        $code = date('Ymd') . strval(rand(1, 99999999));
        $code = TextHelper::to32($code);
        $repeat = DB::table('part_instances as pi')
            ->select(['id'])
            ->where('pi.identity_code', $code)
            ->first();
        return $repeat ? $this->_makePartInstanceIdentityCode() : $code;
    }

    /**
     * 更新所有型号计数
     */
    final private function _updateEntireInstanceCount()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $noExists = [];

        foreach ($this->_entireInstanceCounts as $entireModelUniqueCode => $count) {
            $entireInstanceCount = DB::table('entire_instance_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->first();
            if ($entireInstanceCount != null) {
                DB::table('entire_instance_counts')->where('entire_model_unique_code', $entireModelUniqueCode)->update(['count' => $count]);
            } else {
                $noExists[] = [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'entire_model_unique_code' => $entireModelUniqueCode,
                    'count' => $count
                ];
            }
        }
        if ($noExists) DB::table('entire_instance_counts')->insert($noExists);
    }

    /**
     * 插入转辙机数据
     * @pararm string $type 导入类型 BUY_IN:新设备 FIXING:检修中 FIXED:成品 INSTALLING:备品 INSTALLED:上道
     * @param string $type
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @throws \Exception
     */
    final private function _insertPointSwitch(string $type,
                                              int $processorId,
                                              string $processedAt,
                                              string $connectionName,
                                              string $connectionPhone)
    {
        # 读取json文件
        if (!is_file("{$this->_dir}/{$this->_filename}.json")) throw new \Exception('json文件不存在', 2);
        $devices = TextHelper::parseJson(file_get_contents("{$this->_dir}/{$this->_filename}.json"));
        $entireInstances = [];
        $partInstances = [];
        foreach ($devices as $device) {
            # 整件数据
            $entireInstance = [
                'created_at' => $device['created_at'],
                'updated_at' => $device['created_at'],
                'entire_model_unique_code' => $device['entire_model_unique_code'],
                'status' => $device['status'],
                'factory_name' => $device['factory_name'],
                'factory_device_code' => $device['factory_device_code'],
                'identity_code' => $entireInstanceIdentityCode = $this->_makeEntireInstanceIdentityCode($device['entire_model_unique_code']),
                'category_unique_code' => $device['category_unique_code'],
                'category_name' => $device['category_name'],
                'fix_cycle_value' => $device['fix_cycle_value'],
                'made_at' => $device['made_at'],
                'scarping_at' => $device['scraping_at'],
                'base_name' => env('ORGANIZATION_CODE'),
                'serial_number' => $device['serial_number'],
                'last_installed_time' => $device['last_installed_time'],//上道日期
                'next_fixing_day' => $device['next_fixing_day'],
                'next_fixing_time' => $device['next_fixing_time'],
                'next_fixing_month' => $device['next_fixing_month'],
                'rfid_code' => $device['rfid_code'],
                'maintain_station_name' => $device['maintain_station_name'],
                'maintain_location_code' => $device['maintain_location_code'],
                'crossroad_number' => $device['crossroad_number'],
                'line_name' => $device['line_name'],
                'open_direction' => $device['open_direction'],
                'said_rod' => $device['said_rod'],
                'extrusion_protect' => $device['extrusion_protect'],
                'model_unique_code' => $device['model_unique_code'],
                'model_name' => $device['model_name'],
                'last_out_at' => $device['last_out_at'],
                'last_fix_workflow_at' => $device['last_fix_workflow_at']
            ];
            switch ($type) {
                case 'FIXING':
                    break;
                case 'FIXED':
                    break;
                case 'INSTALLING':
                    break;
                case 'INSTALLED':
                    break;
            }
            $entireInstances[] = $entireInstance;
            $partInstance = [
                'created_at' => $device['created_at'],
                'updated_at' => $device['created_at'],
                'part_model_unique_code' => $device['part_model_unique_code'],
                'part_model_name' => $device['part_model_name'],
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'status' => $device['status'],
                'factory_name' => $device['factory_name'],
                'factory_device_code' => $device['factory_device_code'],
                'identity_code' => $device['part_instance_identity_code'] ? $device['part_instance_identity_code'] : $this->_makePartInstanceIdentityCode(),
                'category_unique_code' => $device['category_unique_code'],
                'entire_model_unique_code' => $device['entire_model_unique_code'],
            ];
            # 根据导入设备类型额外添加字段
            switch ($type) {
                case 'FIXING':
                    break;
                case 'FIXED':
                    break;
                case 'INSTALLING':
                    break;
                case 'INSTALLED':
                    break;
            }
            $partInstances[] = $partInstance;
        }
        # 写入数据库
        $insertCount = 0;
        DB::transaction(function () use (
            $entireInstances,
            $partInstances,
            $type,
            $processorId,
            $processedAt,
            $connectionName,
            $connectionPhone,
            &$insertCount
        ) {
            $insertCount = DB::table('entire_instances')->insert($entireInstances);
            DB::table('part_instances')->insert($partInstances);
            # 更新型号计数
            $this->_updateEntireInstanceCount();
            # 生成入所单
            list($warehouseReport, $warehouseReportEntireInstances) = $this->_makeWarehouseReport(collect($entireInstances)->pluck('identity_code'),
                $type,
                $processorId,
                $processedAt,
                $connectionName,
                $connectionPhone);
            DB::table('warehouse_reports')->insert($warehouseReport);
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);
        });
        $insertCount = count($entireInstances);
        return $insertCount;
    }

    /**
     * 插入继电器数据
     * @param string $type 导入类型 BUY_IN:新设备 FIXING:检修中 FIXED:成品 INSTALLING:备品 INSTALLED:上道
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final private function _insertRelay(string $type,
                                        int $processorId,
                                        string $processedAt,
                                        string $connectionName,
                                        string $connectionPhone)
    {
        # 读取json文件
        if (!is_file("{$this->_dir}/{$this->_filename}.json")) throw new \Exception('json文件不存在', 2);
        $devices = TextHelper::parseJson(file_get_contents("{$this->_dir}/{$this->_filename}.json"));

        $entireInstances = [];
        foreach ($devices as $device) {
            $entireInstance = [
                'created_at' => $device['created_at'],
                'updated_at' => $device['updated_at'],
                'entire_model_unique_code' => $device['entire_model_unique_code'],
                'status' => $device['status'],
                'factory_name' => $device['factory_name'],
                'factory_device_code' => $device['factory_device_code'],
                'identity_code' => $entireInstanceIdentityCode = $this->_makeEntireInstanceIdentityCode($device['entire_model_unique_code']),
                'category_unique_code' => $device['category_unique_code'],
                'category_name' => $device['category_name'],
                'fix_cycle_value' => $device['fix_cycle_value'],
                'made_at' => $device['made_at'],
                'scarping_at' => $device['scraping_at'],
                'base_name' => env('ORGANIZATION_CODE'),
                'serial_number' => $device['serial_number'],
                'last_installed_time' => $device['last_installed_time'],
                'next_fixing_day' => $device['next_fixing_day'],
                'next_fixing_time' => $device['next_fixing_time'],
                'next_fixing_month' => $device['next_fixing_month'],
                'rfid_code' => $device['rfid_code'],
                'maintain_station_name' => $device['maintain_station_name'],
                'maintain_location_code' => $device['maintain_location_code'],
                'model_unique_code' => $device['model_unique_code'],
                'model_name' => $device['model_name'],
                'last_out_at' => $device['last_out_at'],
                'last_fix_workflow_at' => $device['last_fix_workflow_at']
            ];

            # 根据入所类型添加额外字段
            switch ($type) {
                case 'FIXING': // 2
                    break;
                case 'FIXED':
                    break;
                case 'INSTALLING':
                    break;
                case 'INSTALLED':
                    break;
            }
            $entireInstances[] = $entireInstance;
        }

        # 写入数据库
        $insertCount = 0;
        DB::transaction(function () use (
            $entireInstances,
            $type,
            $processorId,
            $processedAt,
            $connectionName,
            $connectionPhone,
            &$insertCount
        ) {
            $insertCount = DB::table('entire_instances')->insert($entireInstances);
            # 更新型号计数
            $this->_updateEntireInstanceCount();
            # 生成入所单
            list($warehouseReport, $warehouseReportEntireInstances) = $this->_makeWarehouseReport(collect($entireInstances)->pluck('identity_code'),
                $type,
                $processorId,
                $processedAt,
                $connectionName,
                $connectionPhone);
            DB::table('warehouse_reports')->insert($warehouseReport);
            DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstances);

        });
        $insertCount = count($entireInstances);
        return $insertCount;
    }

    /**
     * 生成入所单
     * @param Collection $entireInstanceIdentityCodes
     * @param string $type
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return array
     */
    final private function _makeWarehouseReport(Collection $entireInstanceIdentityCodes,
                                                string $type,
                                                int $processorId,
                                                string $processedAt,
                                                string $connectionName,
                                                string $connectionPhone): array
    {
        $now = Carbon::now()->format('Y-m-d');
        # 生成入所单号
        $warehouseReportSerialNumber = env('ORGANIZATION_CODE') . Carbon::now()->format('YmdHis') . '01' . rand(1000, 9999);
        # 生成入所单数据
        $warehouseReport = [
            'created_at' => $now,
            'updated_at' => $now,
            'processor_id' => $processorId,
            'processed_at' => $processedAt,
            'connection_name' => $connectionName,
            'connection_phone' => $connectionPhone,
            //'type' => $type,
            'direction' => 'IN',
            'serial_number' => $warehouseReportSerialNumber,
        ];

        # 生成入所设备对应关系
        $warehouseReportEntireInstances = [];
        $entireInstanceIdentityCodes->each(function ($entireInstanceIdentityCode)
        use (
            $now,
            $warehouseReportSerialNumber,
            &$warehouseReportEntireInstances
        ) {
            $warehouseReportEntireInstances[] = [
                'created_at' => $now,
                'updated_at' => $now,
                'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
            ];
            EntireInstanceLog::with([])->create([
                'name'=>'入所检修',
                'description'=>'',
                'entire_instance_identity_code'=>$entireInstanceIdentityCode,
                'type'=>1,
                'url'=>"/warehouse/report/{$warehouseReportSerialNumber}?show_type=E&direction=IN",
            ]);

        });
        return [$warehouseReport, $warehouseReportEntireInstances];
    }

    /**
     * excel转换json（新入所）
     * @param string $sheetName
     * @return Device
     */
    final public function excelToJsonWithNew(string $sheetName = "Sheet1"): self
    {
        return DB::transaction(function () use ($sheetName) {
            $entireInstances = [];  # 符合导入的设备数据
            $modelNotExists = [];  # 型号不存在的设备
            $sheet = $this->_getSheetByName($sheetName);
            for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
                $row = $sheet->rangeToArray('A' . $i . ':' . $this->_highestColumn . $i, NULL, TRUE, FALSE)[0];
                list($serial_number, $factoryDeviceCode, $factoryName, $modelName, $partInstanceIdentityCode, $madeAt, $last_fix_time, $last_installed_time, $fixCycleValue, $next_fixing_time, $scrapValue, $scrapingAt, $maintain_station_name, $maintain_location_code, $crossroad_number, $crossroad_type, $line_name, $open_direction, $said_rod, $extrusion_protect, $rfid_code,$last_out_at) = $row;

                $now = Carbon::now()->format('Y-m-d H:i:s');
                $model_unique_code = $this->_getModelUniqueCode($modelName);

                # 转换入所时间
                // if ($createdAt != null) $createdAt = $this->_getDatetime($createdAt);
                # 转换出厂时间
                if ($madeAt != null) $madeAt = $this->_getDatetime($madeAt);
                // if ($allocated_to != null) $allocated_to = strtotime($this->_getDatetime($allocated_to));
                # 计算报废时间
                // $scrapingAt = '';
                if ($scrapingAt != null) $scrapingAt = $this->_getDatetime($scrapingAt);
                if ($scrapValue != null && $madeAt != null) $scrapingAt = Carbon::createFromFormat("Y-m-d", $madeAt)->addYear($scrapValue)->format("Y-m-d");
                #转换最后一次检修时间
                if ($last_installed_time != null) $last_installed_time = strtotime($this->_getDatetime($last_installed_time));
                // if ($last_fix_time != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d",  $this->_getDatetime($last_fix_time))->addYear($fixCycleValue)->format("Y-m-d");
                $next_fixing_day = strtotime($next_fixing_time);
                $next_fixing_month = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $next_fixing_time))->format("Y-m-1");
                if ($fixCycleValue != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $last_installed_time))->addYear($fixCycleValue)->format("Y-m-d");
                $maintain_location_code = '';

                # 组合行数据
                $rowData = [
                    'row' => $i,
                    'serial_number' => $serial_number,
                    'factory_device_code' => $factoryDeviceCode,
                    'rfid_code' => $rfid_code,
                    'factory_name' => $factoryName, //供应商
                    'model_name' => $modelName,
                    'made_at' => $madeAt,//出厂日期
                    'created_at' => $now,//入所时间
                    'last_installed_time' => $last_installed_time,
                    'fix_cycle_value' => $fixCycleValue, //周期修
                    'next_fixing_time' => $next_fixing_time,//下次周期修时间
                    'next_fixing_day' => $next_fixing_day,
                    'next_fixing_month' => $next_fixing_month,
                    'scrapValue' => $scrapValue, //使用寿命
                    'scraping_at' => $scrapingAt,   //报废时间
                    'maintain_station_name' => '',//站名
                    'maintain_location_code' => '',//位置
                    'crossroad_number' => $crossroad_number,
                    'line_name' => $line_name,
                    'open_direction' => $open_direction,
                    'said_rod' => $said_rod,
                    'extrusion_protect' => $extrusion_protect,
                    'model_unique_code' => $model_unique_code,
                    'updated_at' => $now,//更新日期
					'last_out_at' => NULL,
                    'last_fix_workflow_at' => NULL
                ];
                # 查询对应的种类、类型
                switch ($this->_zone) {
                    case 1:
                    default:
                        $model = $this->_getPointSwitchModel($modelName);
                        if ($model == null) {
                            $modelNotExists[] = $rowData;
                            continue;
                        } else {
                            $entireInstances[] = array_merge($rowData, [
                                'category_name' => $model->category_name,
                                'category_unique_code' => $model->category_unique_code,
                                'entire_model_unique_code' => $model->entire_model_unique_code,
                                'part_model_unique_code' => $model->part_model_unique_code,
                                'part_model_name' => $model->part_model_name,
                                'status' => 'BUY_IN',
                                'part_instance_identity_code' => $partInstanceIdentityCode
                            ]);
                        }
                        break;
                    case 2:
                    case 3:
                        $model = $this->_getRelayModel($modelName);
                        if ($model == null) {
                            $modelNotExists[] = $rowData;
                            continue;
                        } else {
                            $entireInstances[] = array_merge($rowData, [
                                'category_name' => $model->category_name,
                                'category_unique_code' => $model->category_unique_code,
                                'entire_model_unique_code' => $model->entire_model_unique_code,
                                'status' => 'BUY_IN',
                            ]);
                        }
                        break;
                }
            }

            if (!is_dir($this->_dir)) $this->_makeDirs($this->_dir);
            file_put_contents("{$this->_dir}/{$this->_filename}.json", TextHelper::toJson($entireInstances));
            file_put_contents("{$this->_dir}/{$this->_filename}-型号不存在.json", TextHelper::toJson($modelNotExists));

            return $this;
        });
    }

    /**
     * 写入数据（新入所）
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final public function insertWithNew(int $processorId,
                                        string $processedAt,
                                        string $connectionName,
                                        string $connectionPhone)
    {
        switch ($this->_zone) {
            case 1:
            default:
                return $this->_insertPointSwitch('BUY_IN',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
            case 2:
            case 3:
                return $this->_insertRelay('BUY_IN',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
        }
    }

    /**
     * excel转换json（待修）
     * @param string $sheetName
     * @return Device
     */
    final public function excelToJsonWithFixing(string $sheetName = "Sheet1"): self
    {
        return DB::transaction(function () use ($sheetName) {
        $entireInstances = [];  # 符合导入的设备数据
        $modelNotExists = [];  # 型号不存在的设备
        $sheet = $this->_getSheetByName($sheetName);
        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $this->_highestColumn . $i, NULL, TRUE, FALSE)[0];
            list($serial_number, $factoryDeviceCode, $factoryName, $modelName, $partInstanceIdentityCode, $madeAt, $last_fix_time, $last_installed_time, $fixCycleValue, $next_fixing_time, $scrapValue, $scrapingAt, $maintain_station_name, $maintain_location_code, $crossroad_number, $crossroad_type, $line_name, $open_direction, $said_rod, $extrusion_protect, $rfid_code,$last_out_at) = $row;
            $now = Carbon::now()->format('Y-m-d H:i:s');
            //转换设备代码
            $model_unique_code = "";
            $model_unique_code = $this->_getModelUniqueCode($modelName);
            # 转换入所时间
            // if ($createdAt != null) $createdAt = $this->_getDatetime($createdAt);
            # 转换出厂时间
            if ($madeAt != null) $madeAt = $this->_getDatetime($madeAt);

            // if ($allocated_to != null) $allocated_to = strtotime($this->_getDatetime($allocated_to));
            # 计算报废时间
            $scrapingAt = '';
            // if ($scarping_at != null) $scrapingAt = $this->_getDatetime($scarping_at);
            if ($scrapValue != null && $madeAt != null) $scrapingAt = Carbon::createFromFormat("Y-m-d", $madeAt)->addYear($scrapValue)->format("Y-m-d");
            #转换最后一次检修时间
            if ($last_installed_time != null) $last_installed_time = strtotime($this->_getDatetime($last_installed_time));
            if ($next_fixing_time != null) $next_fixing_time = strtotime($this->_getDatetime($next_fixing_time));
            //if ($last_fix_time != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d",  $this->_getDatetime($last_fix_time))->addYear($fixCycleValue)->format("Y-m-d");
            //$next_fixing_time = strtotime($next_fixing_day);
            //$next_fixing_month = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $next_fixing_time))->format("Y-m-1");
            //if ($fixCycleValue != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $last_installed_time))->addYear($fixCycleValue)->format("Y-m-d");
            # 组合行数据
            $rowData = [
                'row' => $i,
                'serial_number' => $serial_number,
                'factory_device_code' => $factoryDeviceCode,
                'rfid_code' => $rfid_code,
                'factory_name' => $factoryName, //供应商
                'model_name' => $modelName,
                'made_at' => $madeAt,//出厂日期
                'created_at' => $now,//入所时间
                'last_installed_time' => $last_installed_time,
                'fix_cycle_value' => $fixCycleValue, //周期修
                'next_fixing_time' => $next_fixing_time,//下次周期修时间
                'next_fixing_day' => $next_fixing_time,
                'next_fixing_month' => $next_fixing_time,
                'scrapValue' => $scrapValue, //使用寿命
                'scraping_at' => $scrapingAt,   //报废时间
                'maintain_station_name' => '',//站名
                'maintain_location_code' => '',//位置
                'crossroad_number' => $crossroad_number,
                'line_name' => $line_name,
                'open_direction' => $open_direction,
                'said_rod' => $said_rod,
                'extrusion_protect' => $extrusion_protect,
                'updated_at' => $now,//更新日期
                'model_unique_code' => $model_unique_code,
				'last_out_at' => NULL,
                'last_fix_workflow_at' => NULL

            ];
            # 查询对应的种类、类型
            switch ($this->_zone) {
                case 1:
                default:
                    $model = $this->_getPointSwitchModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'part_model_unique_code' => $model->part_model_unique_code,
                            'part_model_name' => $model->part_model_name,
                            'status' => 'FIXING',
                            'part_instance_identity_code' => $partInstanceIdentityCode
                        ]);
                    }
                    break;
                case 2:
                case 3:
                    $model = $this->_getRelayModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'status' => 'FIXING',
                        ]);
                    }
                    break;
            }
        }
        if (!is_dir($this->_dir)) $this->_makeDirs($this->_dir);
        file_put_contents("{$this->_dir}/{$this->_filename}.json", TextHelper::toJson($entireInstances));
        file_put_contents("{$this->_dir}/{$this->_filename}-型号不存在.json", TextHelper::toJson($modelNotExists));
        return $this;
        });
    }

    /**
     * 写入数据(待修)
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final public function insertWithFixing(int $processorId,
                                           string $processedAt,
                                           string $connectionName,
                                           string $connectionPhone)
    {
        switch ($this->_zone) {
            case 1:
            default:
                return $this->_insertPointSwitch('FIXING',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
            case 2:
            case 3:
                return $this->_insertRelay('FIXING',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
        }
    }

    /**
     * excel转换json（成品）
     * @param string $sheetName
     * @return Device
     */
    final public function excelToJsonWithFixed(string $sheetName = "Sheet1"): self
    {
        return DB::transaction(function () use ($sheetName) {
        $entireInstances = [];  # 符合导入的设备数据
        $modelNotExists = [];  # 型号不存在的设备
        $sheet = $this->_getSheetByName($sheetName);
        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $this->_highestColumn . $i, NULL, TRUE, FALSE)[0];
            list($serial_number, $factoryDeviceCode, $factoryName, $modelName, $partInstanceIdentityCode, $madeAt, $last_fix_time, $last_installed_time, $fixCycleValue, $next_fixing_time, $scrapValue, $scrapingAt, $maintain_station_name, $maintain_location_code, $crossroad_number, $crossroad_type, $line_name, $open_direction, $said_rod, $extrusion_protect, $rfid_code,$last_out_at) = $row;

            $now = Carbon::now()->format('Y-m-d H:i:s');
            //转换设备代码
            $model_unique_code = "";
            $model_unique_code = $this->_getModelUniqueCode($modelName);
            # 转换入所时间
            // if ($createdAt != null) $createdAt = $this->_getDatetime($createdAt);
            # 转换出厂时间
            if ($madeAt != null) $madeAt = $this->_getDatetime($madeAt);
            if ($last_out_at != null) $last_out_at = $this->_getDatetime($last_out_at);
            // if ($allocated_to != null) $allocated_to = strtotime($this->_getDatetime($allocated_to));
            # 计算报废时间
            $scrapingAt = '';
            // if ($scarping_at != null) $scrapingAt = $this->_getDatetime($scarping_at);
            if ($scrapValue != null && $madeAt != null) $scrapingAt = Carbon::createFromFormat("Y-m-d", $madeAt)->addYear($scrapValue)->format("Y-m-d");
            #转换最后一次检修时间
            if ($last_installed_time != null) $last_installed_time = strtotime($this->_getDatetime($last_installed_time));
            if ($last_fix_time != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", $this->_getDatetime($last_fix_time))->addYear($fixCycleValue)->format("Y-m-d");
            $next_fixing_time = strtotime($next_fixing_day);
            $next_fixing_month = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $next_fixing_time))->format("Y-m-1");
            #if ($fixCycleValue != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $last_installed_time))->addYear($fixCycleValue)->format("Y-m-d");
            if ($last_fix_time != null) $last_fix_workflow_at = $this->_getDatetime($last_fix_time);
            # 组合行数据
            $rowData = [
                'row' => $i,
                'serial_number' => $serial_number,
                'factory_device_code' => $factoryDeviceCode,
                'rfid_code' => $rfid_code,
                'factory_name' => $factoryName, //供应商
                'model_name' => $modelName,
                'made_at' => $madeAt,//出厂日期
                'created_at' => $now,//入所时间
                'last_installed_time' => $last_installed_time,
                'fix_cycle_value' => $fixCycleValue, //周期修
                'next_fixing_time' => $next_fixing_time,//下次周期修时间
                'next_fixing_day' => $next_fixing_day,
                'next_fixing_month' => $next_fixing_month,
                'scrapValue' => $scrapValue, //使用寿命
                'scraping_at' => $scrapingAt,   //报废时间
                'maintain_station_name' => $maintain_station_name,//站名
                'maintain_location_code' => $maintain_location_code,//位置
                'crossroad_number' => $crossroad_number,
                'line_name' => $line_name,
                'open_direction' => $open_direction,
                'said_rod' => $said_rod,
                'extrusion_protect' => $extrusion_protect,
                'updated_at' => $now,//更新日期
                'model_unique_code' => $model_unique_code,
                'last_out_at' => NULL,
                'last_fix_workflow_at' =>$last_fix_workflow_at
            ];
            # 查询对应的种类、类型
            switch ($this->_zone) {
                case 1:
                default:
                    $model = $this->_getPointSwitchModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'part_model_unique_code' => $model->part_model_unique_code,
                            'part_model_name' => $model->part_model_name,
                            'status' => 'FIXED',
                            'part_instance_identity_code' => $partInstanceIdentityCode
                        ]);
                    }
                    break;
                case 2:
                case 3:
                    $model = $this->_getRelayModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'status' => 'FIXED',
                        ]);
                    }
                    break;
            }
        }
        if (!is_dir($this->_dir)) $this->_makeDirs($this->_dir);
        file_put_contents("{$this->_dir}/{$this->_filename}.json", TextHelper::toJson($entireInstances));
        file_put_contents("{$this->_dir}/{$this->_filename}-型号不存在.json", TextHelper::toJson($modelNotExists));
        return $this;
        });
    }

    /**
     * 写入数据(成品)
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final public function insertWithFixed(int $processorId,
                                          string $processedAt,
                                          string $connectionName,
                                          string $connectionPhone)
    {
        switch ($this->_zone) {
            case 1:
            default:
                return $this->_insertPointSwitch('FIXED',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
            case 2:
            case 3:
                return $this->_insertRelay('FIXED',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
        }
    }

    /**
     * excel转换json（备品）
     * @param string $sheetName
     * @return Device
     */
    final public function excelToJsonWithINSTALLING(string $sheetName = "Sheet1"): self
    {
        return DB::transaction(function () use ($sheetName) {
        $entireInstances = [];  # 符合导入的设备数据
        $modelNotExists = [];  # 型号不存在的设备
        $sheet = $this->_getSheetByName($sheetName);
        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $this->_highestColumn . $i, NULL, TRUE, FALSE)[0];
            list($serial_number, $factoryDeviceCode, $factoryName, $modelName, $partInstanceIdentityCode, $madeAt, $last_fix_time, $last_installed_time, $fixCycleValue, $next_fixing_time, $scrapValue, $scrapingAt, $maintain_station_name, $maintain_location_code, $crossroad_number, $crossroad_type, $line_name, $open_direction, $said_rod, $extrusion_protect, $rfid_code,$last_out_at) = $row;

            $now = Carbon::now()->format('Y-m-d H:i:s');
            //转换设备代码
            $model_unique_code = "";
            $model_unique_code = $this->_getModelUniqueCode($modelName);
            # 转换入所时间
            // if ($createdAt != null) $createdAt = $this->_getDatetime($createdAt);
            # 转换出厂时间
            if ($madeAt != null) $madeAt = $this->_getDatetime($madeAt);
            if ($last_out_at != null) $last_out_at = $this->_getDatetime($last_out_at);
            // if ($allocated_to != null) $allocated_to = strtotime($this->_getDatetime($allocated_to));
            # 计算报废时间
            $scrapingAt = '';
            // if ($scarping_at != null) $scrapingAt = $this->_getDatetime($scarping_at);
            if ($scrapValue != null && $madeAt != null) $scrapingAt = Carbon::createFromFormat("Y-m-d", $madeAt)->addYear($scrapValue)->format("Y-m-d");
            #转换最后一次检修时间
            if ($last_installed_time != null) $last_installed_time = strtotime($this->_getDatetime($last_installed_time));
            if ($last_fix_time != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", $this->_getDatetime($last_fix_time))->addYear($fixCycleValue)->format("Y-m-d");
            $next_fixing_time = strtotime($next_fixing_day);
            $next_fixing_month = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $next_fixing_time))->format("Y-m-1");
            //if ($fixCycleValue != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $last_installed_time))->addYear($fixCycleValue)->format("Y-m-d");
            if ($last_fix_time != null) $last_fix_workflow_at =$this->_getDatetime($last_fix_time);
            # 组合行数据
            $rowData = [
                'row' => $i,
                'serial_number' => $serial_number,
                'factory_device_code' => $factoryDeviceCode,
                'rfid_code' => $rfid_code,
                'factory_name' => $factoryName, //供应商
                'model_name' => $modelName,
                'made_at' => $madeAt,//出厂日期
                'created_at' => $now,//入所时间
                'last_installed_time' => $last_installed_time,
                'fix_cycle_value' => $fixCycleValue, //周期修
                'next_fixing_time' => $next_fixing_time,//下次周期修时间
                'next_fixing_day' => $next_fixing_day,
                'next_fixing_month' => $next_fixing_month,
                'scrapValue' => $scrapValue, //使用寿命
                'scraping_at' => $scrapingAt,   //报废时间
                'maintain_station_name' => $maintain_station_name,//站名
                'maintain_location_code' => $maintain_location_code,//位置
                'crossroad_number' => $crossroad_number,
                'line_name' => $line_name,
                'open_direction' => $open_direction,
                'said_rod' => $said_rod,
                'extrusion_protect' => $extrusion_protect,
                'updated_at' => $now,//更新日期
                'model_unique_code' => $model_unique_code,
                'last_out_at' => $last_out_at,
                'last_fix_workflow_at' =>$last_fix_workflow_at
            ];
            # 查询对应的种类、类型
            switch ($this->_zone) {
                case 1:
                default:
                    $model = $this->_getPointSwitchModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'part_model_unique_code' => $model->part_model_unique_code,
                            'part_model_name' => $model->part_model_name,
                            'status' => 'INSTALLING',
                            'part_instance_identity_code' => $partInstanceIdentityCode
                        ]);
                    }
                    break;
                case 2:
                case 3:
                    $model = $this->_getRelayModel($modelName);
                    if ($model == null) {
                        $modelNotExists[] = $rowData;
                        continue;
                    } else {
                        $entireInstances[] = array_merge($rowData, [
                            'category_name' => $model->category_name,
                            'category_unique_code' => $model->category_unique_code,
                            'entire_model_unique_code' => $model->entire_model_unique_code,
                            'status' => 'INSTALLING',
                        ]);
                    }
                    break;
            }
        }

        if (!is_dir($this->_dir)) $this->_makeDirs($this->_dir);
        file_put_contents("{$this->_dir}/{$this->_filename}.json", TextHelper::toJson($entireInstances));
        file_put_contents("{$this->_dir}/{$this->_filename}-型号不存在.json", TextHelper::toJson($modelNotExists));

        return $this;
        });
    }

    /**
     * 写入数据(备品)
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final public function insertWithINSTALLING(int $processorId,
                                               string $processedAt,
                                               string $connectionName,
                                               string $connectionPhone)
    {
        switch ($this->_zone) {
            case 1:
            default:
                return $this->_insertPointSwitch('INSTALLING',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
            case 2:
            case 3:
                return $this->_insertRelay('INSTALLING',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
        }
    }

    /**
     * excel转换json（上道）
     * @param string $sheetName
     * @return Device
     */
    final public function excelToJsonWithINSTALLED(string $sheetName = "Sheet1"): self
    {
        return DB::transaction(function () use ($sheetName) {
            $entireInstances = [];  # 符合导入的设备数据
            $modelNotExists = [];  # 型号不存在的设备
            $sheet = $this->_getSheetByName($sheetName);
            for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
                $row = $sheet->rangeToArray('A' . $i . ':' . $this->_highestColumn . $i, NULL, TRUE, FALSE)[0];
                list($serial_number, $factoryDeviceCode, $factoryName, $modelName, $partInstanceIdentityCode, $madeAt, $last_fix_time, $last_installed_time, $fixCycleValue, $next_fixing_time, $scrapValue, $scrapingAt, $maintain_station_name, $maintain_location_code, $crossroad_number, $crossroad_type, $line_name, $open_direction, $said_rod, $extrusion_protect, $rfid_code,$last_out_at) = $row;

                $now = Carbon::now()->format('Y-m-d H:i:s');
                //转换设备代码
                $model_unique_code = "";
                $model_unique_code = $this->_getModelUniqueCode($modelName);
                # 转换入所时间
                // if ($createdAt != null) $createdAt = $this->_getDatetime($createdAt);
                # 转换出厂时间
                if ($madeAt != null) $madeAt = $this->_getDatetime($madeAt);
                if ($last_out_at != null) $last_out_at = $this->_getDatetime($last_out_at);

                // if ($allocated_to != null) $allocated_to = strtotime($this->_getDatetime($allocated_to));
                # 计算报废时间
                $scrapingAt = '';
                // if ($scarping_at != null) $scrapingAt = $this->_getDatetime($scarping_at);
                if ($scrapValue != null && $madeAt != null) $scrapingAt = Carbon::createFromFormat("Y-m-d", $madeAt)->addYear($scrapValue)->format("Y-m-d");
                #转换最后一次检修时间
                if ($last_installed_time != null) $last_installed_time = strtotime($this->_getDatetime($last_installed_time));
                if ($last_fix_time != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", $this->_getDatetime($last_fix_time))->addYear($fixCycleValue)->format("Y-m-d");
                $next_fixing_time = strtotime($next_fixing_day);
                $next_fixing_month = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $next_fixing_time))->format("Y-m-1");
                #if ($fixCycleValue != null) $next_fixing_day = Carbon::createFromFormat("Y-m-d", date("Y-m-d", $last_installed_time))->addYear($fixCycleValue)->format("Y-m-d");
                if ($last_fix_time != null) $last_fix_workflow_at = $this->_getDatetime($last_fix_time);
                #正则处理防挤压
                // if($extrusion_protect != null || preg_match('是',$extrusion_protect)）{
                //     $extrusion_protect == 0;
                // };
                # 组合行数据
                $rowData = [
                    'row' => $i,
                    'serial_number' => $serial_number,
                    'factory_device_code' => $factoryDeviceCode,
                    'rfid_code' => $rfid_code,
                    'factory_name' => $factoryName, //供应商
                    'model_name' => $modelName,
                    'made_at' => $madeAt,//出厂日期
                    'created_at' => $now,//入所时间
                    'last_installed_time' => $last_installed_time,
                    'fix_cycle_value' => $fixCycleValue, //周期修
                    'next_fixing_time' => $next_fixing_time,//下次周期修时间
                    'next_fixing_day' => $next_fixing_day,
                    'next_fixing_month' => $next_fixing_month,
                    'scrapValue' => $scrapValue, //使用寿命
                    'scraping_at' => $scrapingAt,   //报废时间
                    'maintain_station_name' => $maintain_station_name,//站名
                    'maintain_location_code' => $maintain_location_code,//位置
                    'crossroad_number' => $crossroad_number,
                    'line_name' => $line_name,
                    'open_direction' => $open_direction,
                    'said_rod' => $said_rod,
                    'extrusion_protect' => $extrusion_protect,
                    'updated_at' => $now,//更新日期
                    'model_unique_code' => $model_unique_code,
                    'last_out_at' => $last_out_at,
                    'last_fix_workflow_at' =>$last_fix_workflow_at
                ];
                # 查询对应的种类、类型
                switch ($this->_zone) {
                    case 1:
                    default:
                        $model = $this->_getPointSwitchModel($modelName);
                        if ($model == null) {
                            $modelNotExists[] = $rowData;
                            continue;
                        } else {
                            $entireInstances[] = array_merge($rowData, [
                                'category_name' => $model->category_name,
                                'category_unique_code' => $model->category_unique_code,
                                'entire_model_unique_code' => $model->entire_model_unique_code,
                                'part_model_unique_code' => $model->part_model_unique_code,
                                'part_model_name' => $model->part_model_name,
                                'status' => 'INSTALLED',
                                'part_instance_identity_code' => $partInstanceIdentityCode
                            ]);
                        }
                        break;
                    case 2:
                    case 3:
                        $model = $this->_getRelayModel($modelName);
                        if ($model == null) {
                            $modelNotExists[] = $rowData;
                            continue;
                        } else {
                            $entireInstances[] = array_merge($rowData, [
                                'category_name' => $model->category_name,
                                'category_unique_code' => $model->category_unique_code,
                                'entire_model_unique_code' => $model->entire_model_unique_code,
                                'status' => 'INSTALLED',
                            ]);
                        }
                        break;
                }
            }

            if (!is_dir($this->_dir)) $this->_makeDirs($this->_dir);
            file_put_contents("{$this->_dir}/{$this->_filename}.json", TextHelper::toJson($entireInstances));
            file_put_contents("{$this->_dir}/{$this->_filename}-型号不存在.json", TextHelper::toJson($modelNotExists));

            return $this;
        });
    }

    /**
     * 写入数据(上道)
     * @param int $processorId
     * @param string $processedAt
     * @param string $connectionName
     * @param string $connectionPhone
     * @return int
     * @throws \Exception
     */
    final public function insertWithINSTALLED(int $processorId,
                                              string $processedAt,
                                              string $connectionName,
                                              string $connectionPhone)
    {
        switch ($this->_zone) {
            case 1:
            default:
                return $this->_insertPointSwitch('INSTALLED',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
            case 2:
            case 3:
                return $this->_insertRelay('INSTALLED',
                    $processorId,
                    $processedAt,
                    $connectionName,
                    $connectionPhone);
                break;
        }
    }

}
