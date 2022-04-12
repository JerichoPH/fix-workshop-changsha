<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\PartInstance;
use App\Model\PartModel;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;

class MockDataService
{
    public $rootDirname = "mockData/fixWorkflow";
    public $fixers = [];
    public $checkers = [];

    /**
     * 开始执行模拟数据
     * @return mixed
     */
    final public function runMockFixWorkflow()
    {
        try {
            return DB::transaction(function () {
                # 获取型号
                $models = $this->__getModels();
                # 从数据库生成人员
                $this->__makeAccounts();
                # 获取设备
                $entireInstances = $this->__getEntireInstances($models);

                # 生成检修单
                $fixWorkflows = [];
                foreach ($entireInstances as $entireInstance) {
                    $fixWorkflows[] = $this->__mockFixWorkflow($entireInstance);
                }

                return "生成检修单完成：" . count($fixWorkflows) . "条";
            });
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取型号
     */
    final private function __getModels()
    {
        return json_decode(file_get_contents(storage_path('mockData/fixWorkflow/models.json')), true);
    }

    /**
     * 生成人员
     */
    final private function __makeAccounts()
    {
        $fixers = Account::with([])->where('work_area', '>', 0)->get();
        $checkers = Account::with([])->where('work_area', '>', 0)->where('supervision', 1)->get();

        $fixers->groupBy('work_area')->each(function ($val, $key) {
            $workArea = array_flip(Account::$WORK_AREAS)[$key];
            foreach ($val as $item) $this->fixers[$workArea][] = ['id' => $item['id'], 'nickname' => $item['nickname']];

        });
        $checkers->groupBy('work_area')->each(function ($val, $key) {
            $workArea = array_flip(Account::$WORK_AREAS)[$key];
            foreach ($val as $item) $this->checkers[$workArea][] = ['id' => $item['id'], 'nickname' => $item['nickname']];
        });
//        file_put_contents(storage_path("{$this->rootDirname}/fixers.json"), json_encode($this->fixers, 256));
//        file_put_contents(storage_path("{$this->rootDirname}/checkers.json"), json_encode($this->checkers, 256));
    }

    /**
     * 获取型号下设备
     * @param array $models
     * @return EntireInstance[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    final private function __getEntireInstances(array $models)
    {
        return EntireInstance::with([])->whereIn('model_unique_code', $models)->get();
    }

    /**
     * 生成检修单数据
     * @param EntireInstance $entireInstance
     * @return FixWorkflow|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    final private function __mockFixWorkflow(EntireInstance $entireInstance)
    {
        $randYear = rand(2, 4);
        $randMonth = rand(1, 5);
        $randDay = rand(10, 20);

        $fixedTime = Carbon::createFromFormat('Y-m-d H:i:s', $entireInstance->made_at)
            ->addYears($randYear)
            ->addMonths($randMonth)
            ->addDays($randDay);

        # 获取对应工区的检修人和验收人
        switch (substr($entireInstance->identity_code, 0, 3)) {
            default:
                $workArea = "3";
                break;
            case 'S03':
                $workArea = "1";
                break;
            case 'Q01':
                $workArea = "2";
                break;
        }
        $fixer = array_random($this->fixers[$workArea]);
        $checker = array_random($this->checkers[$workArea]);

        $fixWorkflow = FixWorkflow::with(['EntireInstance'])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'entire_instance_identity_code' => $entireInstance->identity_code,
            'status' => 'CHECKED',
            'processor_id' => $checker['id'],
            'serial_number' => $fixWorkflowSn = CodeFacade::makeSerialNumber('FIX_WORKFLOW', $fixedTime->format('Ymd')),
            'processed_times' => 0,
            'stage' => 'CHECKED',
            'type' => 'FIX',
        ]);

        $this->__mockFixWorkflowProcess($fixWorkflow, $fixedTime, $fixer, $checker);  # 生成检测过程

        # 同步设备检修单号
        $entireInstance->fix_workflow_serial_number = $fixWorkflowSn;  # 检修单号
        $entireInstance->last_fix_workflow_at = $fixedTime;  # 检修时间
        $entireInstance->save();

        return $fixWorkflow;
    }

    /**
     * 生成检测过程
     * @param FixWorkflow $fixWorkflow
     * @param Carbon $fixedTime
     * @param array $fixer
     * @param array $checker
     * @throws \Exception
     */
    final private function __mockFixWorkflowProcess(FixWorkflow $fixWorkflow, Carbon $fixedTime, array $fixer, array $checker)
    {
        # 记录日志（检修）
        EntireInstanceLog::with([])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'name' => '设备开始检修',
            'description' => '',
            'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
            'type' => 2,
            'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
        ]);

        # 修前检
        $fixWorkflowProcess1 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'fix_workflow_serial_number' => $fixWorkflow->serial_number,
            'stage' => 'FIX_BEFORE',
            'type' => 'ENTIRE',
            'auto_explain' => '接口',
            'serial_number' => $fixWorkflowProcessSn1 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $fixedTime->format('Ymd')) . '_1',
            'numerical_order' => '1',
            'is_allow' => 1,
            'processor_id' => $fixer['id'],
            'processed_at' => $fixedTime,
            'upload_url' => "/check/{$fixWorkflowProcessSn1}.json",
            'check_type' => 'JSON',
            'upload_file_name' => "{$fixWorkflowProcessSn1}.json",
        ]);
        $this->__mockFixWorkflowRecode($fixWorkflowProcess1);  # 生成实测记录
        EntireInstanceLog::with([])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'name' => '修前检',
            'description' => '检修人：' . $fixer['nickname'],
            'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
            'type' => 2,
            'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
        ]);

        # 修后检
        $fixWorkflowProcess2 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'fix_workflow_serial_number' => $fixWorkflow->serial_number,
            'stage' => 'FIX_AFTER',
            'type' => 'ENTIRE',
            'auto_explain' => '接口',
            'serial_number' => $fixWorkflowProcessSn2 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $fixedTime->format('Ymd')) . '_2',
            'numerical_order' => '1',
            'is_allow' => 1,
            'processor_id' => $fixer['id'],
            'processed_at' => $fixedTime,
            'upload_url' => "/check/{$fixWorkflowProcessSn2}.json",
            'check_type' => 'JSON',
            'upload_file_name' => "{$fixWorkflowProcessSn2}.json",
        ]);
        $this->__mockFixWorkflowRecode($fixWorkflowProcess2);  # 生成实测记录
        EntireInstanceLog::with([])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'name' => '修后检',
            'description' => '检修人：' . $fixer['nickname'],
            'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
            'type' => 2,
            'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
        ]);

        # 验收
        $fixWorkflowProcess3 = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'fix_workflow_serial_number' => $fixWorkflow->serial_number,
            'stage' => 'CHECKED',
            'type' => 'ENTIRE',
            'auto_explain' => '接口',
            'serial_number' => $fixWorkflowProcessSn3 = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $fixedTime->format('Ymd')) . '_3',
            'numerical_order' => '1',
            'is_allow' => 1,
            'processor_id' => $checker['id'],
            'processed_at' => $fixedTime,
            'upload_url' => "/check/{$fixWorkflowProcessSn3}.json",
            'check_type' => 'JSON',
            'upload_file_name' => "{$fixWorkflowProcessSn3}.json",
        ]);
        $this->__mockFixWorkflowRecode($fixWorkflowProcess3);  # 生成实测记录
        EntireInstanceLog::with([])->create([
            'created_at' => $fixedTime,
            'updated_at' => $fixedTime,
            'name' => '验收',
            'description' => '验收人：' . $checker['nickname'],
            'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
            'type' => 2,
            'url' => "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit",
        ]);
    }

    /**
     * 生成检测值
     * @param FixWorkflowProcess $fixWorkflowProcess
     * @throws \Exception
     */
    final private function __mockFixWorkflowRecode(FixWorkflowProcess $fixWorkflowProcess)
    {
        # 获取设备型号代码
        $modelUniqueCode = $fixWorkflowProcess->FixWorkflow->EntireInstance->model_unique_code;
        $measurementDirname = storage_path("{$this->rootDirname}/measurements/{$modelUniqueCode}.json");
        if (!is_file($measurementDirname)) throw new \Exception("没有找到：{$modelUniqueCode}型号对应的检测标准值({$measurementDirname})");

        # 解析检测标准值
        $measurementFile = json_decode(file_get_contents($measurementDirname), true);
        $recode = [
            'header' => [
                'message_ID' => '',
                'platform' => '',
                'testing_device_ID' => '',
                'time' => $fixWorkflowProcess->updated_at->format('Y-m-d H:i:s'),
                '器材型号' => $fixWorkflowProcess->FixWorkflow->EntireInstance->model_name,
                '条码编号' => $fixWorkflowProcess->FixWorkflow->entire_instance_identity_code,
                '测试人' => $fixWorkflowProcess->Processor->nickname,
                '记录类型' => $fixWorkflowProcess->stage,
            ],
            'body' => [
                '测试项目' => [],
            ]
        ];

        /**
         * 申城随机小数
         * @param int $min
         * @param int $max
         * @return string
         */
        $randomFloat = function ($min = 0, $max = 1) {
            $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
            return sprintf("%.2f", $num);  //控制小数后几位
        };

        $i = 0;
        foreach ($measurementFile as $measurement) {
            ++$i;

            if ($measurement['min_allow'] == null) {
                # 负无穷
                $a = "≤ {$measurement['max_allow']}";
                $b = $randomFloat(0, floatval($measurement['max_allow']));
            } elseif ($measurement['max_allow'] == null) {
                # 正无穷
                $a = "≥ {$measurement['min_allow']}";
                $b = $randomFloat(floatval($measurement['min_allow']), floatval($measurement['min_allow']) * 100);
            } elseif (($measurement['min_allow'] == $measurement['max_allow']) && $measurement['min_allow'] != null && $measurement['max_allow'] != null) {
                # 最大值最小值相同
                $a = "= {$measurement['min_allow']}";
                $b = floatval($measurement['min_allow']);
            } elseif ($measurement['min_allow'] == null && $measurement['max_allow'] == null) {
                $a = $measurement['explain'] ?? '';
                $b = '合格';
            } else {
                # 区间
                $a = "{$measurement['min_allow']} ～ {$measurement['max_allow']}";
                $b = $randomFloat(floatval($measurement['min_allow']), floatval($measurement['max_allow']));
            }
            $recode['body']['测试项目'][] = [
                '流水号' => CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS') . "_{$i}",
                '判定结论' => 1,
                '单位' => $measurement['unit'],
                '标准值' => $a,
                '测试值' => $b,
                '项目编号' => $measurement['key'],
                '类型' => '整件',
            ];
        }

        file_put_contents(public_path("check/{$fixWorkflowProcess->serial_number}.json"), json_encode($recode, 256));
    }

    /**
     * 补充数据（根据生产日期补充设备日志）
     */
    final public function runReplenishMadeAtForLogs()
    {
        try {
            $total = 0;
            EntireInstance::with(['EntireInstanceLogs' => function ($EntireInstanceLogs) {
                return $EntireInstanceLogs->where('type', 6);
            }])
                ->where('made_at', '<>', null)
                ->where('deleted_at', null)
                ->select(['identity_code', 'made_at'])
                ->chunk(100, function ($entireInstances) {
                    $entireInstances->each(function ($entireInstance) use (&$total) {
                        if ($entireInstance->EntireInstanceLogs->isEmpty()) {
                            $madeAt = date('Y-m-d', strtotime($entireInstance->made_at));
                            EntireInstanceLog::with([])->insert([
                                'created_at' => $madeAt,
                                'updated_at' => $madeAt,
                                'name' => '设备生产日期',
                                'entire_instance_identity_code' => $entireInstance->identity_code,
                                'type' => 6,
                            ]);
                            $total++;
                        }
                    });
                });
            return "完成：{$total}";
        } catch (\Exception $e) {
            return "意外错误：{$e->getMessage()}";
        }
    }

    /**
     * 补充数据（非电机部件）
     */
    final public function tmpReplenishPartInstances()
    {
        return DB::transaction(function () {
            $i = 1;
            $pis = [];
            $fileDir = storage_path("mockData/partInstance/");
            ExcelReadHelper::NEW_FROM_STORAGE("{$fileDir}/汕头信号车间转辙机.xlsx")
                ->originRow(2)
                ->withSheetIndex(0, function ($row) use (&$i, &$pis) {
                    list($eiid, $eisn, $piid1, $pmn1, $madeDt1, $f1, $piid21, $piid22, $pmn2, $madeDt2, $f2, $s) = $row;

                    $eiid = strval($eiid);
                    $eisn = strval($eisn);
                    $piid1 = strval($piid1);
                    $pmn1 = strval($pmn1);
                    $madeDt1 = strval($madeDt1);
                    $f1 = strval($f1);
                    $piid21 = strval($piid21);
                    $piid22 = strval($piid22);
                    $pmn2 = strval($pmn2);
                    $madeDt2 = strval($madeDt2);
                    $f2 = strval($f2);
                    $s = strval($s);

                    # 查询整件是否存在
                    $ei = EntireInstance::with([])->where('identity_code', $eiid)->where('maintain_station_name', $s)->first();
//                    $ei = EntireInstance::with([])->where('serial_number', $eisn)->where('maintain_station_name', $s)->first();
                    if (!$ei) return null;
                    # 查询型号1是否存在
                    $pm1 = PartModel::with([])->where('name', $pmn1)->first();
                    if (!$pm1) dd("第{$i}行错误，部件型号1不存在：{$pmn1}。");
                    # 查询型号2是否存在
                    $pm2 = PartModel::with([])->where('name', $pmn2)->first();
                    if (!$pm2) dd("第{$i}行错误，部件型号2不存在：{$pmn2}。");

                    # 转换时间
                    $madeAt1 = $madeDt1 ? Carbon::createFromFormat('Y年m月d日', $madeDt1) : null;
                    $scrapingAt1 = $madeAt1 ? $madeAt1->addYear(15) : null;
                    $madeAt2 = $madeDt2 ? Carbon::createFromFormat('Y年m月d日', $madeDt2) : null;
                    $scrapingAt2 = $madeAt2 ? $madeAt2->addYear(15) : null;

                    # 导入部件1
                    if ($piid1) {
                        $pis[] = $pi = PartInstance::with([])->create([
                            'part_model_unique_code' => $pm1->unique_code,
                            'part_model_name' => $pm1->name,
                            'entire_instance_identity_code' => $ei->identity_code,
                            'status' => 'FIXED',
                            'factory_name' => $f1 ?? '',
                            'factory_device_code' => '',
                            'identity_code' => CodeFacade::makePartInstanceIdentityCode($pm1->unique_code),
                            'entire_instance_serial_number' => $eisn,
                            'category_unique_code' => $pm1->category_unique_code,
                            'entire_model_unique_code' => $pm1->entire_model_unique_code,
                            'part_category_id' => 3,
                            'made_at' => $madeAt1,
                            'scraping_at' => $scrapingAt1,
                            'old_identity_code' => $piid1,
                        ]);
                        $i++;
                    }

                    # 导入部件2(1)
                    if ($piid21) {
                        $pis[] = $pi = PartInstance::with([])->create([
                            'part_model_unique_code' => $pm2->unique_code,
                            'part_model_name' => $pm2->name,
                            'entire_instance_identity_code' => $ei->identity_code,
                            'status' => 'FIXED',
                            'factory_name' => $f2 ?? '',
                            'factory_device_code' => '',
                            'identity_code' => CodeFacade::makePartInstanceIdentityCode($pm2->unique_code),
                            'entire_instance_serial_number' => $eisn,
                            'category_unique_code' => $pm2->category_unique_code,
                            'entire_model_unique_code' => $pm2->entire_model_unique_code,
                            'part_category_id' => 2,
                            'made_at' => $madeAt2,
                            'scraping_at' => $scrapingAt2,
                            'old_identity_code' => $piid21,
                        ]);
                        $i++;
                    }

                    # 导入部件2(2)
                    if ($piid22) {
                        $pis[] = $pi = PartInstance::with([])->create([
                            'part_model_unique_code' => $pm2->unique_code,
                            'part_model_name' => $pm2->name,
                            'entire_instance_identity_code' => $ei->identity_code,
                            'status' => 'FIXED',
                            'factory_name' => $f2 ?? '',
                            'factory_device_code' => '',
                            'identity_code' => CodeFacade::makePartInstanceIdentityCode($pm2->unique_code),
                            'entire_instance_serial_number' => $eisn,
                            'category_unique_code' => $pm2->category_unique_code,
                            'entire_model_unique_code' => $pm2->entire_model_unique_code,
                            'part_category_id' => 2,
                            'made_at' => $madeAt2,
                            'scraping_at' => $scrapingAt2,
                            'old_identity_code' => $piid22,
                        ]);
                        $i++;
                    }
                });
            $i--;
            return ["导入完成：{$i}条。", $pis];
        });
    }

    /**
     * 生成入所单
     * @param EntireInstance $entireInstance
     * @param Account $processor
     */
    final private function __mockWarehouseReportIn(EntireInstance $entireInstance, Collection $processors)
    {
        $category_unique_code = substr($entireInstance->identity_code, 0, 3);
        $work_area = 3;
        switch ($category_unique_code) {
            case 'S03':
                $work_area = 1;
                break;
            case 'Q01':
                $work_area = 2;
                break;
            default:
                $work_area = 3;
                break;
        }
        # 生成入所单
        WarehouseReport::with([])->create([
            'created_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'updated_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'processor_id' => $processors->random()->nickname,
            'processed_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'connection_name' => '',
            'connection_phone' => '',
            'type' => 'FIXING',
            'direction' => 'IN',
            'serial_number' => $warehouseInSn = CodeFacade::makeSerialNumber('IN'),
            'scene_workshop_name' => '',
            'station_name' => $entireInstance->maintain_station_name,
            'work_area_id' => $work_area
        ]);

        # 记录日志
        EntireInstanceLog::with([])->create([
            'created_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'updated_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'name' => '入所',
            'description' => '',
            'entire_instance_identity_code' => $entireInstance->identity_code,
            'type' => 1,
            'url' => "/warehouse/report/{$warehouseInSn}",
        ]);

        # 生成入所单设备
        WarehouseReportEntireInstance::with([])->create([
            'created_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'updated_at' => $entireInstance->made_at ?? $entireInstance->created_at,
            'warehouse_report_serial_number' => $warehouseInSn,
            'entire_instance_identity_code' => $entireInstance->identity_code,
        ]);

        return $warehouseInSn;
    }
}
