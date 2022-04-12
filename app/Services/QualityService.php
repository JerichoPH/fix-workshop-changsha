<?php

namespace App\Services;

use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use Carbon\Carbon;
use Exception;
use Faker\ORM\Spot\EntityPopulator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\TextHelper;

class QualityService
{
    private static $_DIR = 'quality';
    private $_year = null;
    private $_month = null;

    /**
     * 初始化
     * @param int $year
     * @param int $month
     * @return $this
     */
    final public function init(int $year, int $month)
    {
        $currentTime = Carbon::createFromDate($year, $month, 1);
        $this->_year = $currentTime->firstOfMonth()->format('Y');
        $this->_month = $currentTime->firstOfMonth()->format('m');
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
        $this->getFactories();  # 获取所有供应商

        if (Storage::disk('local')->exists(self::$_DIR . '/dateList.json')) {
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
     * @throws Exception
     */
    final public function fileRead(string $filename): array
    {
        try {
            return TextHelper::parseJson(file_get_contents(storage_path("app/" . self::$_DIR . "/{$this->_year}-{$this->_month}/{$filename}")));
        } catch (\Exception $exception) {
            throw new \Exception("文件：" . storage_path("app/" . self::$_DIR . "/{$this->_year}-{$this->_month}/{$filename}" . "不存在"));
        }
    }

    /**
     * 获取所有厂家
     */
    final public function getFactories()
    {
        $factories = DB::table('factories')
            ->where('deleted_at', null)
            ->pluck('name', 'unique_code')
            ->each(function ($factoryName, $factoryUniqueCode) use (&$factoryNames) {
                $factoryNames[] = $factoryName;
            });
        $this->fileSave('供应商.json', $factories);
        $this->fileSave('供应商-名称.json', $factoryNames);
    }

    /**
     * 根据厂商获取设备总数
     * @throws Exception
     */
    final public function getDeviceCountWithFactory()
    {
        try {
            $factoryNames = $this->fileRead('供应商-名称.json');
            $entireInstances = [];
            foreach ($factoryNames as $factoryName) {
                $entireInstances[$factoryName] = DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('factory_name', $factoryName)
                    ->count('id');
            }

            # 获取供应商对应型号
            $entireModels = [];
            foreach ($factoryNames as $factoryName) {
                $entireModels[$factoryName] = DB::table('pivot_entire_model_and_factories')
                    ->join('entire_models', 'unique_code', '=', 'entire_model_unique_code')
                    ->where('pivot_entire_model_and_factories.factory_name', $factoryName)
                    ->pluck('entire_models.name', 'entire_models.unique_code');
            }
            $this->fileSave("供应商-型号.json", $entireModels);

            # 获取供应商对应型号下对应的设备总数
            $entireInstancesWithEntireModel = [];
            foreach ($entireModels as $factoryName => $entireModel) {
                $tmp = [];
                foreach ($entireModel as $entireModelUniqueCode => $entireModelName) {
                    $tmp[$entireModelName] = DB::table('entire_instances')
                        ->where('deleted_at', null)
                        ->where('status', '<>', 'SCRAP')
                        ->where('entire_model_unique_code', $entireModelUniqueCode)
                        ->count('id');
                }
                $entireInstancesWithEntireModel[$factoryName] = $tmp;

            }
            $this->fileSave('供应商-型号-设备数量.json', $entireInstancesWithEntireModel);

            # 获取所有厂商下所有数量
            $entireInstancesWithFactory = [];
            foreach ($entireInstancesWithEntireModel as $factoryName => $entireModels) {
                $tmp = 0;
                foreach ($entireModels as $count) if (is_numeric($count)) $tmp += $count;
                if ($tmp == 0) continue;
                $entireInstancesWithFactory[$factoryName] = $tmp;
            }
            $this->fileSave('供应商-设备数量.json', $entireInstancesWithFactory);
        } catch (Exception $exception) {
            dd($exception->getMessage());
        }
    }

    /**
     * 获取所有具备非周期超过1次的设备数量
     * @throws Exception
     */
    final public function getNotCycleFixWithFactory()
    {
        $factoryNames = $this->fileRead('供应商-名称.json');
        $entireInstances = EntireInstance::with(['FixWorkflows' => function ($FixWorkflows) {
            $FixWorkflows->select(['serial_number'])->where('status', 'FIXED')->where('is_cycle', 0);
        }])
            ->select(['identity_code'])
            ->get()
            ->toArray();
        $tmp = [];
        foreach ($entireInstances as $entireInstance) if ($entireInstance['fix_workflows'] != []) $tmp[] = $entireInstance;

        $this->fileSave('供应商-型号-非周期修设备数量.json', $tmp);
        dd($tmp);
    }

    /**
     * 获取种类
     */
    final public function getCategories()
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
     * 获取类型
     */
    final public function getEntireModels()
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
     * 获取子类
     */
    final public function getSubEntireModels()
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
     * 获取型号
     */
    final public function getPartModels()
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
     * 获取设备总数（种类）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    final public function getDeviceCountWithCategory(bool $isTest = false): array
    {
        $categoryUniqueCodes = $this->fileRead('种类.json');

        $deviceCount = [];
        $deviceCountValues = [];
        foreach ($categoryUniqueCodes as $categoryUniqueCode => $categoryName) {
            $count = $isTest == false ?
                DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('category_unique_code', $categoryUniqueCode)
                    ->count('id') :
                rand(1000, 3000);

            $deviceCount[$categoryUniqueCode] = $count;
            $deviceCountValues[] = $count;
        }

        $this->fileSave('设备-种类-总数.json', $deviceCount);
        $this->fileSave('设备-种类-总数-值.json', $deviceCountValues);

        return $deviceCount;
    }

    /**
     * 获取非周期修数量-1 （种类）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    final public function getFixedCountWithoutCycleWithCategory(bool $isTest = false): array
    {
        $carbon = Carbon::create($this->_year, $this->_month, 1);
        $originTime = $carbon->firstOfMonth()->format('Y-m-d');
        $finishTime = $carbon->lastOfMonth()->format('Y-m-d');

        $categoryUniqueCodes = $this->fileRead('种类.json');

        $fixedWithoutCycleCount = [];
        $fixedWithoutCycleCountValues = [];
        foreach ($categoryUniqueCodes as $categoryUniqueCode => $categoryName) {
            $count = EntireInstance::with(['FixWorkflows'])
                ->whereHas('FixWorkflows', function ($query) use ($originTime, $finishTime) {
                    $query->whereBetween('created_at', [$originTime, $finishTime])
                        ->where('is_cycle', false);
                })
                ->withCount('FixWorkflows')
                ->where('category_unique_code', $categoryUniqueCode)
                ->count('id');
            $fixedWithoutCycleCount[$categoryUniqueCode] = $fixedWithoutCycleCountValues[] = $count;
        }

        $this->fileSave('检修单-种类-总数.json', $fixedWithoutCycleCount);
        $this->fileSave('检修单-种类-总数-值.json', $fixedWithoutCycleCountValues);

        return $fixedWithoutCycleCount;
    }

    /**
     * 计算返修率（种类）
     * @return array
     * @throws Exception
     */
    final public function getRateWithoutCycleWithCategory(): array
    {
        $deviceCount = $this->fileRead('设备-种类-总数.json');
        $fixedCount = $this->fileRead('检修单-种类-总数.json');

        $rateWithoutCycle = [];
        $rateWithoutCycleValues = [];
        foreach ($deviceCount as $key => $value) {
            if ($value == 0 || $fixedCount[$key] == 0) {
                $rateWithoutCycle[$key] = $rateWithoutCycleValues[] = 0;
                continue;
            }
            $rateWithoutCycle[$key] = $rateWithoutCycleValues[] = number_format($fixedCount[$key] / $value, 2) * 100;
        }

        $this->fileSave('返修率-种类-总数.json', $rateWithoutCycle);
        $this->fileSave('返修率-种类-总数-值.json', $rateWithoutCycleValues);

        return $rateWithoutCycle;
    }

    /**
     * 获取设备总数（类型）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    final public function getDeviceCountWithEntireModel(bool $isTest = false): array
    {
        $entireModels = $this->fileRead('类型.json');

        $deviceCount = [];
        $deviceCountValues = [];
        foreach ($entireModels as $entireModelUniqueCode => $entireModelName) {
            $count = $isTest == false ?
                DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModelUniqueCode)
                    ->count('id') :
                rand(1000, 3000);

            $deviceCount[$entireModelUniqueCode] = $count;
            $deviceCountValues[] = $count;
        }

        $this->fileSave('设备-类型-总数.json', $deviceCount);
        $this->fileSave('设备-类型-总数-值.json', $deviceCountValues);

        return $deviceCount;
    }

    /**
     * 获取非周期修数量-1 （类型）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    final public function getFixedWithoutCycleWithEntireModel(bool $isTest = false): array
    {
        $entireModels = $this->fileRead('类型.json');

        $carbon = Carbon::create($this->_year, $this->_month, 1);
        $originTime = $carbon->firstOfMonth()->format('Y-m-d');
        $finishTime = $carbon->lastOfMonth()->format('Y-m-d');

        $fixedWithoutCycleCount = [];
        $fixedWithoutCycleCountValues = [];
        foreach ($entireModels as $entireModelUniqueCode => $entireModelName) {
            $count = $isTest == false ?
                DB::table('fix_workflows')
                    ->select([
                        'fix_workflows.id',
                        'fix_workflows.is_cycle',
                        'entire_instances.entire_model_unique_code'
                    ])
                    ->whereBetween('fix_workflows.created_at', [$originTime, $finishTime])
                    ->where('fix_workflows.deleted_at', null)
                    ->join('entire_instances', 'entire_instances.identity_code', '=', 'fix_workflows.entire_instance_identity_code')
                    ->where('entire_instances.entire_model_unique_code', $entireModelUniqueCode)
                    ->count('fix_workflows.id') :
                rand(1000, 3000);

            $count = $count > 0 ? $count - 1 : 0;
            $fixedWithoutCycleCount[$entireModelUniqueCode] = $count;
            $fixedWithoutCycleCountValues[] = $count;
        }

        $this->fileSave('检修单-类型-总数.json', $fixedWithoutCycleCount);
        $this->fileSave('检修单-类型-总数-值.json', $fixedWithoutCycleCountValues);

        return $fixedWithoutCycleCount;
    }

    /**
     * 计算返修率（类型）
     * @return array
     * @throws Exception
     */
    final public function getRateWithoutCycleWithEntireModel(): array
    {
        $deviceCount = $this->fileRead('设备-类型-总数.json');
        $fixedCount = $this->fileRead('检修单-类型-总数.json');

        $rateWithoutCycle = [];
        $rateWithoutCycleValues = [];
        foreach ($deviceCount as $key => $value) {
            if ($value == 0 || $fixedCount[$key] == 0) {
                $rateWithoutCycle[$key] = $rateWithoutCycleValues[] = 0;
                continue;
            }
            $rateWithoutCycle[$key] = $rateWithoutCycleValues[] = number_format($fixedCount[$key] / $value, 2) * 100;
        }

        $this->fileSave('返修率-类型-总数.json', $rateWithoutCycle);
        $this->fileSave('返修率-类型-总数-值.json', $rateWithoutCycleValues);

        return $rateWithoutCycle;
    }

    /**
     * 获取设备总数（型号和子类）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    public function getDeviceCountWithSub(bool $isTest = false): array
    {
        $subEntireModels = $this->fileRead('子类.json');

        $deviceCount = [];
        $deviceCountValues = [];
        foreach ($subEntireModels as $subEntireModelUniqueCode => $subEntireModelName) {
            $count = $isTest == false ?
                DB::table('entire_instances')
                    ->select(['entire_instances.id', 'entire_instances.entire_model_unique_code',])
                    ->where('entire_instances.deleted_at', null)
                    ->where('entire_instances.entire_model_unique_code', $subEntireModelUniqueCode)
                    ->count('entire_instances.id') :
                rand(1000, 3000);

            $deviceCount[$subEntireModelUniqueCode] = $count;
            $deviceCountValues[] = $count;
        }

        $partModels = $this->fileRead('型号.json');
        foreach ($partModels as $partModelUniqueCode => $partModelName) {
            $count = $isTest == false ?
                DB::table('entire_instances')
                    ->select([
                        'entire_instances.id',
                        'part_instances.part_model_unique_code'
                    ])
                    ->join('part_instances', 'part_instances.entire_instance_identity_code', '=', 'entire_instances.identity_code')
                    ->where('entire_instances.deleted_at', null)
                    ->where('part_instances.part_model_unique_code', $partModelUniqueCode)
                    ->count('entire_instances.id') :
                rand(1000, 3000);

            $deviceCount[$partModelUniqueCode] = $count;
            $deviceCountValues[] = $count;
        }

        $this->fileSave('设备-型号和子类-总数.json', $deviceCount);
        $this->fileSave('设备-型号和子类-总数-值.json', $deviceCountValues);

        return $deviceCount;
    }

    /**
     * 获取非周期修数量（型号和子类）
     * @param bool $isTest
     * @return array
     * @throws Exception
     */
    public function getFixedCountWithoutCycleWithSub(bool $isTest = false): array
    {
        $carbon = Carbon::create($this->_year, $this->_month, 1);
        $originTime = $carbon->firstOfMonth()->format('Y-m-d');
        $finishTime = $carbon->lastOfMonth()->format('Y-m-d');

        $subEntireModels = $this->fileRead('子类.json');

        $fixedWithoutCycleCount = [];
        $fixedWithoutCycleCountValues = [];
        foreach ($subEntireModels as $subEntireModelUniqueCode => $subEntireModelName) {
            $count = $isTest == false ?
                DB::table('fix_workflows')
                    ->select([
                        'fix_workflows.id',
                        'fix_workflows.is_cycle',
                        'entire_instances.entire_model_unique_code',
                    ])
                    ->whereBetween('fix_workflows.created_at', [$originTime, $finishTime])
                    ->where('fix_workflows.deleted_at', null)
                    ->join('entire_instances', 'entire_instances.identity_code', '=', 'fix_workflows.entire_instance_identity_code')
                    ->where('entire_instances.entire_model_unique_code', $subEntireModelUniqueCode)
                    ->count('fix_workflows.id') :
                rand(1000, 3000);

            $count = $count > 0 ? $count - 1 : 0;
            $fixedWithoutCycleCount[$subEntireModelUniqueCode] = $fixedWithoutCycleCountValues[] = $count;
        }

        $partModels = $this->fileRead('型号.json');
        foreach ($partModels as $partModelUniqueCode => $partModelName) {
            $count = $isTest == false ?
                DB::table('fix_workflows')
                    ->select([
                        'fix_workflows.id',
                        'fix_workflows.is_cycle',
                        'entire_instances.entire_model_unique_code',
                        'part_instances.part_model_unique_code',
                    ])
                    ->whereBetween('fix_workflows.created_at', [$originTime, $finishTime])
                    ->where('fix_workflows.deleted_at', null)
                    ->join('entire_instances', 'entire_instances.identity_code', '=', 'fix_workflows.entire_instance_identity_code')
                    ->join('part_instances', 'part_instances.entire_instance_identity_code', '=', 'entire_instances.identity_code')
                    ->where('part_instances.part_model_unique_code', $partModelUniqueCode)
                    ->count('fix_workflows.id') :
                rand(1000, 300);

            $count = $count > 0 ? $count - 1 : 0;
            $fixedWithoutCycleCount[$partModelUniqueCode] = $fixedWithoutCycleCountValues[] = $count;
        }

        $this->fileSave('返修率-型号和子类-总数.json', $fixedWithoutCycleCount);
        $this->fileSave('返修率-型号和子类-总数-值.json', $fixedWithoutCycleCountValues);

        return $fixedWithoutCycleCount;
    }

    /**
     * 计算返修率（型号和子类）
     * @return array
     * @throws Exception
     */
    public function getRateWithoutCycleWithSub(): array
    {

    }
}
