<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\TextHelper;

class EveryMonthExcelService
{
    private static $_DIR = 'everyMonth';
    private $_year = null;
    private $_month = null;
    private $_monthOrigin = null;
    private $_monthFinish = null;

    /**
     * 初始化对象参数
     * @param int $year
     * @param int $month
     * @return self
     */
    final public function init(int $year, int $month): self
    {
        $currentTime = Carbon::createFromDate($year, $month);
        $this->_year = $currentTime->firstOfMonth()->format('Y');
        $this->_month = $currentTime->firstOfMonth()->format('m');
        $this->_monthOrigin = $currentTime->firstOfMonth()->format('Y-m-d');
        $this->_monthFinish = $currentTime->lastOfMonth()->format('Y-m-d');
        return $this;
    }

    /**
     * 获取基础信息
     * @param int $year
     * @param int $month
     * @throws FileNotFoundException
     */
    final public function getBasicInfo(int $year, int $month)
    {
        $this->init($year, $month);

        $timestamp = Carbon::createFromDate($this->_year, $this->_month)->getTimestamp();

        $this->getCategories();  # 获取全部种类
        $this->getEntireModels();  # 获取全部类型
        $this->getSubEntireModels();  # 获取全部子类
        $this->getPartModels();  # 获取全部型号

        if (Storage::disk('local')->exists('app/' . self::$_DIR . '/dateList.json')) {
            $dateList = TextHelper::parseJson(Storage::disk('local')->get(self::$_DIR . '/dateList.json'));
            $dateList[$timestamp] = "{$this->_year}-{$this->_month}";
            krsort($dateList);
            if (count($dateList) > 1) $dateList = array_unique($dateList);
        } else {
            $dateList = [$timestamp => "{$this->_year}-{$this->_month}"];
        }

        Storage::disk('local')->put(self::$_DIR . '/dateList.json', TextHelper::toJson($dateList, 256));
    }

    /**
     * 保存到文件
     * @param string $filename
     * @param $data
     * @return bool
     */
    final private function fileSave(string $filename, $data): bool
    {
        return Storage::disk('local')
            ->put(
                self::$_DIR . "/{$this->_year}-{$this->_month}/{$filename}",
                TextHelper::toJson($data)
            );
    }

    /**
     * 读取文件
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    final public function fileRead(string $filename): array
    {
        return TextHelper::parseJson(
            Storage::disk('local')
                ->get(self::$_DIR . "/{$this->_year}-{$this->_month}/{$filename}")
        );
    }

    /**
     * 获取所有种类
     */
    final private function getCategories()
    {
        $categories = DB::table('categories')
            ->where('deleted_at', null)
            ->pluck('name', 'unique_code')
            ->each(function ($categoryName, $categoryUniqueCode) use (&$categoryNames) {
                $categoryNames[] = $categoryName;
            });

        $this->fileSave('种类.json', $categories);
        $this->fileSave('种类-值.json', $categoryNames);
    }

    /**
     * 获取所有类型
     */
    final private function getEntireModels()
    {
        $entireModelValues = [];
        $entireModels = DB::table('entire_models')
            ->where('deleted_at', null)
            ->where('is_sub_model', false)
            ->pluck('name', 'unique_code')
            ->each(function ($entireModelName, $entireModelUniqueCode) use (&$entireModelValues) {
                $entireModelValues[] = $entireModelName;
            });

        $this->fileSave('类型.json', $entireModels);
        $this->fileSave('类型-值.json', $entireModelValues);
    }

    /**
     * 获取所有子类
     */
    final private function getSubEntireModels()
    {
        $entireModelValues = [];
        $subEntireModels = DB::table('entire_models')
            ->where('deleted_at', null)
            ->where('is_sub_model', true)
            ->pluck('name', 'unique_code')
            ->each(function ($entireModelName, $entireModelUniqueCode) use (&$entireModelValues) {
                $entireModelValues[] = $entireModelName;
            });

        $this->fileSave('子类.json', $subEntireModels);
        $this->fileSave('子类-值.json', $entireModelValues);
    }

    /**
     * 获取所有型号
     */
    final private function getPartModels()
    {
        $partModelValues = [];
        $partModels = DB::table('part_models')
            ->where('deleted_at', null)
            ->pluck('name', 'unique_code')
            ->each(function ($entireModelName, $entireModelUniqueCode) use (&$partModelValues) {
                $partModelValues[] = $entireModelName;
            });

        $this->fileSave('型号.json', $partModels);
        $this->fileSave('型号-值.json', $partModelValues);
    }

    /**
     * 根据种类获取设备唯一编号列表
     * @return array
     * @throws \Exception
     */
    final public function getEntireInstanceIdentityCodesWithCategory(): array
    {
        $categoryUniqueCodes = $this->fileRead('种类.json');

        $entireInstances = [];
        $entireInstancesCount = [];
        foreach ($categoryUniqueCodes as $categoryUniqueCode => $categoryName) {
            $entireInstances[$categoryUniqueCode] = DB::table('entire_instances')
                ->where('category_unique_code', $categoryUniqueCode)
                ->where('deleted_at', null)
                ->whereBetween('created_at', [$this->_monthOrigin, $this->_monthFinish])
                ->pluck('identity_code');
            $entireInstancesCount[$categoryUniqueCode] = count($entireInstances[$categoryUniqueCode]);
        }

        $this->fileSave('本月-设备列表-种类.json', $entireInstances);
        $this->fileSave('本月-设备总数-种类.json', $entireInstancesCount);

        ExcelWriteHelper::save(function ($phpExcelObj)
        use (
            $entireInstancesCount,
            $categoryUniqueCodes
        ) {
            $sheet = $phpExcelObj->getActiveSheet();
            $sheet->setTitle('种类');
            $i = 1;

            # 设置表头
            $sheet->setCellValue('A1', '种类名称');
            $sheet->setCellValue('B1', '设备总数');

            foreach ($entireInstancesCount as $uniqueCode => $count) {
                $i++;
                $sheet->setCellValue('A' . $i, $categoryUniqueCodes[$uniqueCode]);
                $sheet->setCellValue('B' . $i, $count);
            }
            return $phpExcelObj;
        }, 'app/' . self::$_DIR . "/{$this->_year}-{$this->_month}/种类");

        return $entireInstances;
    }

    /**
     * 根据类型获取设备唯一编号列表
     * @return array
     * @throws \Exception
     */
    final public function getEntireInstanceIdentityCodesWithEntireModel()
    {
        $entireModelUniqueCodes = $this->fileRead('类型.json');

        $entireInstances = [];
        $entireInstancesCount = [];
        foreach ($entireModelUniqueCodes as $entireModelUniqueCode => $entireModelName) {
            $entireInstances[$entireModelUniqueCode] = DB::table('entire_instances')
                ->where('entire_model_unique_code', $entireModelUniqueCode)
                ->where('deleted_at', null)
                ->whereBetween('created_at', [$this->_monthOrigin, $this->_monthFinish])
                ->pluck('identity_code');
            $entireInstancesCount[$entireModelUniqueCode] = count($entireInstances[$entireModelUniqueCode]);
        }

        $this->fileSave('本月-设备列表-类型.json', $entireInstances);
        $this->fileSave('本月-设备总数-类型.json', $entireInstancesCount);

        ExcelWriteHelper::save(function ($phpExcelObj)
        use (
            $entireInstancesCount,
            $entireModelUniqueCodes
        ) {
            $sheet = $phpExcelObj->getActiveSheet();
            $sheet->setTitle('类型');
            $i = 1;

            # 设置表头
            $sheet->setCellValue('A1', '类型名称');
            $sheet->setCellValue('B1', '设备总数');

            foreach ($entireInstancesCount as $uniqueCode => $count) {
                $i++;
                $sheet->setCellValue('A' . $i, $entireModelUniqueCodes[$uniqueCode]);
                $sheet->setCellValue('B' . $i, $count);
            }
            return $phpExcelObj;
        }, 'app/' . self::$_DIR . "/{$this->_year}-{$this->_month}/类型");

        return $entireInstances;
    }

    /**
     * 根据型号和子类获取设备唯一编号列表
     * @return array
     * @throws \Exception
     */
    final public function getEntireInstanceIdentityCodesWithSub()
    {
        $subEntireModelUniqueCodes = $this->fileRead('子类.json');
        $partModelUniqueCodes = $this->fileRead('型号.json');

        $entireInstances = [];
        $entireInstancesCount = [];
        foreach ($subEntireModelUniqueCodes as $subEntireModelUniqueCode => $subEntireModelName) {
            $entireInstances[$subEntireModelUniqueCode] = DB::table('entire_instances')
                ->where('entire_model_unique_code', $subEntireModelUniqueCode)
                ->where('deleted_at', null)
                ->whereBetween('created_at', [$this->_monthOrigin, $this->_monthFinish])
                ->pluck('identity_code');
            $entireInstancesCount[$subEntireModelUniqueCode] = count($entireInstances[$subEntireModelUniqueCode]);
        }

        foreach ($partModelUniqueCodes as $partModelUniqueCode => $partModelName) {
            $entireInstances[$partModelUniqueCode] = DB::table('part_instances')
                ->select([
                    'part_instances.part_model_unique_code',
                    'part_instances.entire_instance_identity_code',
                    'part_instances.deleted_at as part_instance_deleted_at',
                    'entire_instances.deleted_at as entire_instance_delete_at',
                    'entire_instances.created_at as entire_instance_created_at'
                ])
                ->join('entire_instances', 'entire_instances.identity_code', '=', 'part_instances.entire_instance_identity_code')
                ->where('part_instances.deleted_at', null)
                ->where('entire_instances.deleted_at', null)
                ->whereBetween('entire_instances.created_at', [$this->_monthOrigin, $this->_monthFinish])
                ->pluck('part_instances.entire_instance_identity_code');
            $entireInstancesCount[$partModelUniqueCode] = count($entireInstances[$partModelUniqueCode]);
        }

        $this->fileSave('本月-设备列表-型号和子类.json', $entireInstances);
        $this->fileSave('本月-设备总数-型号和子类.json', $entireInstancesCount);

        $i = 1;
        ExcelWriteHelper::save(function ($phpExcelObj)
        use (
            &$i,
            $entireInstancesCount,
            $subEntireModelUniqueCodes,
            $partModelUniqueCodes
        ) {
            $sheet = $phpExcelObj->getActiveSheet();
            $sheet->setTitle('型号和子类');

            # 设置表头
            $sheet->setCellValue('A1', '型号或子类名称');
            $sheet->setCellValue('B1', '设备总数');

            foreach ($entireInstancesCount as $uniqueCode => $count) {
                $i++;
                switch (strtoupper(substr($uniqueCode, 0, 1))) {
                    case 'S':
                        $sheet->setCellValue('A' . $i, $partModelUniqueCodes[$uniqueCode]);
                        $sheet->setCellValue('B' . $i, $count);
                        break;
                    case 'Q':
                        $sheet->setCellValue('A' . $i, $subEntireModelUniqueCodes[$uniqueCode]);
                        $sheet->setCellValue('B' . $i, $count);
                        break;
                    default:
                        continue;
                        break;
                }
            }
            return $phpExcelObj;
        }, 'app/' . self::$_DIR . "/{$this->_year}-{$this->_month}/型号和子类");

        return $entireInstances;
    }
}
