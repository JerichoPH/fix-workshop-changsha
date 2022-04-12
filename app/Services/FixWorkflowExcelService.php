<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\TextHelper;

class FixWorkflowExcelService
{
    private static $_ONLY_ONCE_FIXED_PATH = 'onlyOnceFixed';
    private static $_CYCLE_PATH = 'fixWorkflow';
    private $_year = null;
    private $_month = null;

    final public function init(int $year, int $month): self
    {
        $currentTime = Carbon::createFromDate($year, $month, 1);
        $this->_year = $currentTime->firstOfMonth()->format('Y');
        $this->_month = $currentTime->firstOfMonth()->format('m');
        return $this;
    }

    /**
     * 生成一次过检率Excel
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function onlyOnceFixedToExcel()
    {
        $filePath = self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}";
        if (!Storage::disk('local')->exists($filePath)) throw new \Exception('一次过检 统计数据不存在');

        # 获取人员
        $filename = '人员.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $accounts = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取人员-姓名
        $filename = '人员-姓名.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $accountNicknames = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月-检修单总数
        $filename = '当月-检修单总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $fixedCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月-一次过检总数
        $filename = '当月-一次过检总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $onlyOnceCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月-一次过检率
        $filename = '当月-一次过检率.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rateCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $accountNicknames,
            $fixedCount,
            $onlyOnceCount,
            $rateCount
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('统计');
            # 设置表头
            $activeSheet->setCellValue('A1', '姓名');
            $activeSheet->setCellValue('B1', '检修单总数');
            $activeSheet->setCellValue('C1', '一次过检总数');
            $activeSheet->setCellValue('D1', '一次过检率');
            foreach ($accountNicknames as $nickname) {
                $i++;
                $activeSheet->setCellValue('A' . $i, $nickname);
                $activeSheet->setCellValue('B' . $i, $fixedCount[$nickname]);
                $activeSheet->setCellValue('C' . $i, $onlyOnceCount[$nickname]);
                $activeSheet->setCellValue('D' . $i, $rateCount[$nickname] . '%');
            }
            return $objPHPExcel;
        }, 'app/' . self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}/统计");

        # 调用python辅助函数生成"统计.xlsx"
//        $pythonPath = __DIR__ . '/../../public';
//        $pythonShellName = env('PYTHON_SHELL_NAME', 'python36');
//        $shell = "{$pythonShellName} {$pythonPath}/onlyOnceFixed_excel.py " . storage_path("app/onlyOnceFixed/{$this->_year}-{$this->_month}");
//        $shellRet = trim(shell_exec($shell));
    }

    /**
     * 生成一次过检率Excel（种类）
     * @param array $categories
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function onlyOnceFixedWithCategory(array $categories)
    {
        $filePath = self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月设备总数（种类）
        $filename = '当月-设备-种类-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $deviceCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检总数（种类）
        $filename = '当月-一次过检-种类-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $onlyOnceFixedCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检率总数（种类）
        $filename = '当月-一次过检率-种类.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rateCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $categories,
            $deviceCount,
            $onlyOnceFixedCount,
            $rateCount
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('种类');
            # 设置表头
            $activeSheet->setCellValue('A1', '种类名称');
            $activeSheet->setCellValue('B1', '设备总数');
            $activeSheet->setCellValue('C1', '一次过检');
            $activeSheet->setCellValue('D1', '一次过检率');
            foreach ($deviceCount as $uniqueCode => $value) {
                $i++;
                $activeSheet->setCellValue('A' . $i, $categories[$uniqueCode]);
                $activeSheet->setCellValue('B' . $i, $value);
                $activeSheet->setCellValue('C' . $i, $onlyOnceFixedCount[$uniqueCode]);
                $activeSheet->setCellValue('D' . $i, $rateCount[$uniqueCode] . '%');
            }
            return $objPHPExcel;
        }, 'app/' . self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}/种类");
    }

    /**
     * 生成一次过检率Excel（类型）
     * @param array $entireModels
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function onlyOnceFixedWithEntireModel(array $entireModels)
    {
        $filePath = self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月设备总数（种类）
        $filename = '当月-设备-类型-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $deviceCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检总数（种类）
        $filename = '当月-一次过检-类型-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $onlyOnceFixedCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检率总数（种类）
        $filename = '当月-一次过检率-类型.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rateCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $entireModels,
            $deviceCount,
            $onlyOnceFixedCount,
            $rateCount
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('类型');
            # 设置表头
            $activeSheet->setCellValue('A1', '种类名称');
            $activeSheet->setCellValue('B1', '设备总数');
            $activeSheet->setCellValue('C1', '一次过检');
            $activeSheet->setCellValue('D1', '一次过检率');
            foreach ($deviceCount as $uniqueCode => $value) {
                $i++;
                $activeSheet->setCellValue('A' . $i, $entireModels[$uniqueCode]);
                $activeSheet->setCellValue('B' . $i, $value);
                $activeSheet->setCellValue('C' . $i, $onlyOnceFixedCount[$uniqueCode]);
                $activeSheet->setCellValue('D' . $i, $rateCount[$uniqueCode] . '%');
            }
            return $objPHPExcel;
        }, 'app/' . self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}/类型");
    }

    /**
     * 生成一次过检率Excel（型号和子类）
     * @param array $subEntireModels
     * @param array $partModels
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function onlyOnceFixedWithSub(array $subEntireModels, array $partModels)
    {
        $filePath = self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月设备总数（种类）
        $filename = '当月-设备-型号和子类-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $deviceCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检总数（种类）
        $filename = '当月-一次过检-型号和子类-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $onlyOnceFixedCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检率总数（种类）
        $filename = '当月-一次过检率-型号和子类.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rateCount = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $subEntireModels,
            $partModels,
            $deviceCount,
            $onlyOnceFixedCount,
            $rateCount
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('型号和子类');
            # 设置表头
            $activeSheet->setCellValue('A1', '种类名称');
            $activeSheet->setCellValue('B1', '设备总数');
            $activeSheet->setCellValue('C1', '一次过检');
            $activeSheet->setCellValue('D1', '一次过检率');
            foreach ($deviceCount as $uniqueCode => $value) {
                $i++;
                switch (strtoupper(substr($uniqueCode, 0, 1))) {
                    case 'S':
                        $activeSheet->setCellValue('A' . $i, $partModels[$uniqueCode]);
                        $activeSheet->setCellValue('B' . $i, $value);
                        $activeSheet->setCellValue('C' . $i, $onlyOnceFixedCount[$uniqueCode]);
                        $activeSheet->setCellValue('D' . $i, $rateCount[$uniqueCode] . '%');
                        break;
                    case 'Q':
                        $activeSheet->setCellValue('A' . $i, $subEntireModels[$uniqueCode]);
                        $activeSheet->setCellValue('B' . $i, $value);
                        $activeSheet->setCellValue('C' . $i, $onlyOnceFixedCount[$uniqueCode]);
                        $activeSheet->setCellValue('D' . $i, $rateCount[$uniqueCode] . '%');
                        break;
                    default:
                        continue;
                        break;
                }
            }
            return $objPHPExcel;
        }, 'app/' . self::$_ONLY_ONCE_FIXED_PATH . "/{$this->_year}-{$this->_month}/型号和子类");
    }

    /**
     * 生成检修率统计Excel
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function fixWorkflowCycleToExcel()
    {
        $filePath = self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}";
        if (!Storage::disk('local')->exists($filePath)) throw new \Exception('检修率 统计数据不存在');

        # 获取种类
        $filename = '种类.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $categories = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取类型
        $filename = '类型.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $entireModels = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取型号
        $filename = '型号.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $partModels = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取子类
        $filename = '子类.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $subEntireModels = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        $this->fixWorkflowCycleWithCategory($categories);
        $this->fixWorkflowCycleWithEntireModel($entireModels);
        $this->fixWorkflowCycleWithSub($subEntireModels, $partModels);

        # 调用python辅助函数生成"统计.xlsx"
        $pythonPath = __DIR__ . '/../../public';
        $pythonShellName = env('PYTHON_SHELL_NAME', 'python36');
        $shell = "{$pythonShellName} {$pythonPath}/fixWorkflowCycle_excel.py " . storage_path("app/fixWorkflow/{$this->_year}-{$this->_month}");
        $shellRet = trim(shell_exec($shell));
    }

    /**
     * 生成检修完成率Excel（种类）
     * @param array $categories
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function fixWorkflowCycleWithCategory(array $categories)
    {
        $filePath = self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月检修单总数（种类）
        $filename = '当月-检修单-种类-总数.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $total = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月检修单完成数（种类）
        $filename = '当月-检修单-种类-完成.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $finished = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月检修单完成率（种类）
        $filename = '当月-检修单-种类-完成率.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rate = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $categories,
            $total,
            $finished,
            $rate
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('种类');
            # 设置表头
            $activeSheet->setCellValue('A1', '名称');
            $activeSheet->setCellValue('B1', '计划');
            $activeSheet->setCellValue('C1', '完成');
            $activeSheet->setCellValue('D1', '完成率');
            foreach ($total as $uniqueCode => $value) {
                $i++;
                $activeSheet->setCellValue('A' . $i, $categories[$uniqueCode]);
                $activeSheet->setCellValue('B' . $i, $value);
                $activeSheet->setCellValue('C' . $i, $finished[$uniqueCode]);
                $activeSheet->setCellValue('D' . $i, $rate[$uniqueCode] . '%');
            }
            return $objPHPExcel;
        }, 'app/' . self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}/种类");
    }

    /**
     * 生成检修完成率Excel（类型）
     * @param array $entireModels
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function fixWorkflowCycleWithEntireModel(array $entireModels)
    {
        $filePath = self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月检修单总数（类型）
        $filename = '当月-检修单-类型-总数.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $total = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月检修单完成数（类型）
        $filename = '当月-检修单-类型-完成.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $finished = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取当月检修单完成率（类型）
        $filename = '当月-检修单-类型-完成率.json';
        if (!Storage::disk('local')->exists("{$filePath}/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rate = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（类型）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $entireModels,
            $total,
            $finished,
            $rate
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('类型');
            # 设置表头
            $activeSheet->setCellValue('A1', '名称');
            $activeSheet->setCellValue('B1', '计划');
            $activeSheet->setCellValue('C1', '完成');
            $activeSheet->setCellValue('D1', '完成率');
            foreach ($total as $uniqueCode => $value) {
                $i++;
                $activeSheet->setCellValue('A' . $i, $entireModels[$uniqueCode]);
                $activeSheet->setCellValue('B' . $i, $value);
                $activeSheet->setCellValue('C' . $i, $finished[$uniqueCode]);
                $activeSheet->setCellValue('D' . $i, $rate[$uniqueCode] . '%');
            }
            return $objPHPExcel;
        }, 'app/' . self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}/类型");
    }

    /**
     * 生成检修完成率Excel（型号和子类）
     * @param array $subEntireModels
     * @param array $partModels
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final private function fixWorkflowCycleWithSub(array $subEntireModels, array $partModels)
    {
        $filePath = self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}";
        # 获取当月设备总数（种类）
        $filename = '当月-检修单-型号和子类-总数.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $total = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检总数（种类）
        $filename = '当月-检修单-型号和子类-完成.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $finished = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 获取一次过检率总数（种类）
        $filename = '当月-检修单-型号和子类-完成率.json';
        if (!Storage::disk('local')->exists($filePath . "/{$filename}")) throw new \Exception("{$filename} 不存在");
        $rate = TextHelper::parseJson(Storage::disk('local')->get("{$filePath}/{$filename}"));

        # 写入组合信息到Excel（种类）
        $i = 1;
        ExcelWriteHelper::save(function ($objPHPExcel) use (
            &$i,
            $subEntireModels,
            $partModels,
            $total,
            $finished,
            $rate
        ) {
            $activeSheet = $objPHPExcel->getActiveSheet();
            $activeSheet->setTitle('型号和子类');
            # 设置表头
            $activeSheet->setCellValue('A1', '名称');
            $activeSheet->setCellValue('B1', '总数');
            $activeSheet->setCellValue('C1', '完成');
            $activeSheet->setCellValue('D1', '完成率');
            foreach ($total as $uniqueCode => $value) {
                $i++;
                switch (strtoupper(substr($uniqueCode, 0, 1))) {
                    case 'S':
                        $activeSheet->setCellValue('A' . $i, $partModels[$uniqueCode]);
                        $activeSheet->setCellValue('B' . $i, $value);
                        $activeSheet->setCellValue('C' . $i, $finished[$uniqueCode]);
                        $activeSheet->setCellValue('D' . $i, $rate[$uniqueCode] . '%');
                        break;
                    case 'Q':
                        $activeSheet->setCellValue('A' . $i, $subEntireModels[$uniqueCode]);
                        $activeSheet->setCellValue('B' . $i, $value);
                        $activeSheet->setCellValue('C' . $i, $finished[$uniqueCode]);
                        $activeSheet->setCellValue('D' . $i, $rate[$uniqueCode] . '%');
                        break;
                    default:
                        continue;
                        break;
                }
            }
            return $objPHPExcel;
        }, 'app/' . self::$_CYCLE_PATH . "/{$this->_year}-{$this->_month}/型号和子类");
    }
}
