<?php

namespace App\Services;

use App\Facades\CodeFacade;
use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\TextHelper;

class FixWorkflowCycleService
{
    /**
     * 获取基础信息
     * @param int $year
     * @param int $month
     */
    final public function getBasicInfo(int $year, int $month)
    {
        $timestamp = Carbon::createFromDate($year, $month, 1)->getTimestamp();

        $this->getAllCategories($year, $month);  # 获取全部种类
        $this->getAllSubEntireModels($year, $month);  # 获取全部子类
        $this->getAllPartModels($year, $month);  # 获取全部型号
        $this->getAllEntireModels($year, $month);  # 获取全部类型

        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        if (Storage::disk('local')->exists('fixWorkflow/dateList.json')) {
            $dateList = TextHelper::parseJson(file_get_contents(storage_path('app/fixWorkflow/dateList.json')));
            $dateList[$timestamp] = "{$year}-{$month}";
            krsort($dateList);
            if (count($dateList) > 1) $dateList = array_unique($dateList);
        } else {
            $dateList = [$timestamp => "{$year}-{$month}"];
        }

        Storage::disk('local')->put('fixWorkflow/dateList.json', TextHelper::toJson($dateList));
    }

    /**
     * 获取全部种类
     * @param int $year
     * @param int $month
     */
    final public function getAllCategories(int $year, int $month)
    {
        $categories = DB::table('categories')
            ->where('deleted_at', null)
            ->pluck('name', 'unique_code');
        $this->saveFile($year, $month, '种类.json', $categories);

        $categoriesValues = [];
        foreach ($categories as $index => $item) {
            $categoriesValues[] = $item;
        }
        $this->saveFile($year, $month, '种类-值.json', $categoriesValues);
    }

    /**
     * 保存文件
     * @param int $year
     * @param int $month
     * @param string $fileName
     * @param $content
     */
    final public function saveFile(int $year, int $month, string $fileName, $content)
    {
        Storage::disk('local')->put($this->makeSavePath($year, $month, $fileName), json_encode($content, 256));
    }

    /**
     * 获取保存文件地址
     * @param int $year
     * @param int $month
     * @param string $fileName
     * @return string
     */
    final public function makeSavePath(int $year, int $month, string $fileName): string
    {
        $monthDay = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        return "/fixWorkflow/{$monthDay}/{$fileName}";
    }

    /**
     * 获取全部子类
     * @param int $year
     * @param int $month
     */
    final public function getAllSubEntireModels(int $year, int $month)
    {
        $subEntireModels = DB::table('entire_models')
            ->where('deleted_at', null)
            ->where('is_sub_model', 1)
            ->pluck('name', 'unique_code');
        $this->saveFile($year, $month, '子类.json', $subEntireModels);

        $subEntireModelsValues = [];
        foreach ($subEntireModels as $index => $item) {
            $subEntireModelsValues[] = $item;
        }
        $this->saveFile($year, $month, '子类-值.json', $subEntireModelsValues);
    }

    /**
     * 获取全部型号
     * @param int $year
     * @param int $month
     */
    final public function getAllPartModels(int $year, int $month)
    {
        $partModels = DB::table('part_models')
            ->where('deleted_at', null)
            ->pluck('name', 'unique_code');
        $this->saveFile($year, $month, '型号.json', $partModels);

        $partModelsValues = [];
        foreach ($partModels as $index => $item) {
            $partModelsValues[] = $item;
        }
        $this->saveFile($year, $month, '型号-值.json', $partModelsValues);
    }

    /**
     * 获取全部类型
     * @param int $year
     * @param int $month
     */
    final public function getAllEntireModels(int $year, int $month)
    {
        $entireModels = DB::table('entire_models')
            ->where('deleted_at', null)
            ->where('is_sub_model', 0)
            ->pluck('name', 'unique_code');
        $this->saveFile($year, $month, '类型.json', $entireModels);

        $entireModelsValues = [];
        foreach ($entireModels as $index => $item) {
            $entireModelsValues[] = $item;
        }
        $this->saveFile($year, $month, '类型-值.json', $entireModelsValues);
    }

    /**
     * 检查目录是否存在
     * @param string $dir
     * @return bool
     */
    final public function dirExist(string $dir): bool
    {
        return is_dir(storage_path($dir));
    }

    /**
     * 获取本月全部检修单（子类，型号）
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthAllFixCountWithSub(int $year, int $month, bool $isTest = false): array
    {
        # 获取上月时间
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-m-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-m-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixCountWithSub = [];
        if ($this->fileExist($filePath . '子类.json')) {
            $subEntireModels = $this->readFile($year, $month, '子类.json');
        } else {
            throw new \Exception('子类.json 不存在');
        }
        if ($this->fileExist($filePath . '型号.json')) {
            $partModels = $this->readFile($year, $month, '型号.json');
        } else {
            throw new \Exception('型号.json 不存在');
        }

        foreach ($subEntireModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('entire_model_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->count('id');
            }

            $fixCountWithSub[$index] = $isTest ? rand(1000, 3000) : $count;
        }
        foreach ($partModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance',
                    'EntireInstance.PartInstances',
                    'EntireInstance.PartInstances.PartModel'
                ])
                    ->whereHas('EntireInstance.PartInstances.PartModel', function ($query) use ($index) {
                        $query->where('unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->count('id');
            }

            $fixCountWithSub[$index] = $isTest ? rand(1000, 3000) : $count;
        }
        $this->saveFile($year, $month, '当月-检修单-型号和子类-总数.json', $fixCountWithSub);

        $currentMonthAllFixCountWithSubValues = [];
        foreach ($fixCountWithSub as $item) {
            $currentMonthAllFixCountWithSubValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-类型和子类-总数-值.json', $currentMonthAllFixCountWithSubValues);

        return $fixCountWithSub;
    }

    /**
     * 检查文件是否存在
     * @param string $filename
     * @return bool
     */
    final public function fileExist(string $filename): bool
    {
        return is_file(storage_path($filename));
    }

    /**
     * 读取文件
     * @param int $year
     * @param int $month
     * @param string $fileName
     * @return array
     */
    final public function readFile(int $year, int $month, string $fileName): array
    {
        return json_decode(file_get_contents($this->makeReadPath($year, $month, $fileName)), true);
    }

    /**
     * 获取读取文件路径
     * @param int $year
     * @param int $month
     * @param string $fileName
     * @return string
     */
    final public function makeReadPath(int $year, int $month, string $fileName): string
    {
        $monthDay = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        return storage_path("app/fixWorkflow/{$monthDay}/{$fileName}");
    }

    /**
     * 获取本月全部检修单（种类）
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthAllFixCountWithCategory(int $year, int $month, bool $isTest = false): array
    {
        # 获取上月时间
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-m-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-m-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixCount = [];
        if ($this->fileExist($filePath . '种类.json')) {
            $categories = $this->readFile($year, $month, '种类.json');
        } else {
            throw new \Exception('种类.json不存在');
        }

        foreach ($categories as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('category_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->count('id');
            }
            $fixCount[$index] = $isTest ? rand(1000, 3000) : $count;
        }

        $this->saveFile($year, $month, '当月-检修单-种类-总数.json', $fixCount);

        $currentMonthAllFixCountCategoryValues = [];
        foreach ($fixCount as $item) {
            $currentMonthAllFixCountCategoryValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-种类-总数-值.json', $currentMonthAllFixCountCategoryValues);
        return $fixCount;
    }

    /**
     * 获取本月全部检修单
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthAllFixCount(int $year, int $month, bool $isTest = false): array
    {
        # 获取上月时间
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-m-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-m-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixCount = [];
        if ($this->fileExist($filePath . '类型.json')) {
            $entireModels = $this->readFile($year, $month, '类型.json');
        } else {
            throw new \Exception('类型.json不存在');
        }

        foreach ($entireModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('entire_model_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->count('id');
            }
            $fixCount[$index] = $isTest ? rand(1000, 3000) : $count;
        }

        $this->saveFile($year, $month, '当月-检修单-类型-总数.json', $fixCount);

        $currentMonthAllFixCountValues = [];
        foreach ($fixCount as $item) {
            $currentMonthAllFixCountValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-类型-总数-值.json', $currentMonthAllFixCountValues);
        return $fixCount;
    }

    /**
     * 获取本月已修（子类、型号）
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedCountWithSub(int $year, int $month, bool $isTest = false): array
    {
        # 获取本月时间起点和终点
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-M-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-M-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixedsWithSub = [];
        if ($this->fileExist($filePath . '子类.json')) {
            $subEntireModels = $this->readFile($year, $month, '子类.json');
        } else {
            throw new \Exception($filePath . '子类.json 不存在');
        }
        if ($this->fileExist($filePath . '型号.json')) {
            $partModels = $this->readFile($year, $month, '型号.json');
        } else {
            throw new \Exception($filePath . '型号.json 不存在');
        }

        foreach ($subEntireModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('entire_model_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->where('status', 'FIXED')
                    ->count('id');
            }

            $fixedsWithSub[$index] = $isTest ? rand(1000, 2000) : $count;
        }
        foreach ($partModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance',
                    'EntireInstance.PartInstances',
                    'EntireInstance.PartInstances.PartModel'
                ])
                    ->whereHas('EntireInstance.PartInstances.PartModel', function ($query) use ($index) {
                        $query->where('unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->where('status', 'FIXED')
                    ->count('id');
            }

            $fixedsWithSub[$index] = $isTest ? rand(1000, 2000) : $count;
        }
        $this->saveFile($year, $month, '当月-检修单-型号和子类-完成.json', $fixedsWithSub);

        $currentMonthFixedCountWithSubValues = [];
        foreach ($fixedsWithSub as $item) {
            $currentMonthFixedCountWithSubValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-型号和子类-完成-值.json', $currentMonthFixedCountWithSubValues);

        return $fixedsWithSub;
    }

    /**
     * 获取本月已修（种类）
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedCountWithCategory(int $year, int $month, bool $isTest = false): array
    {
        # 获取本月时间起点和终点
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-M-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-M-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixeds = [];
        if ($this->fileExist($filePath . '种类.json')) {
            $categories = $this->readFile($year, $month, '种类.json');
        } else {
            throw new \Exception($filePath . '类型.json不存在');
        }

        foreach ($categories as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('category_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->where('status', 'FIXED')
                    ->count('id');
            }

            $fixeds[$index] = $isTest ? rand(1000, 2000) : $count;
        }
        $this->saveFile($year, $month, '当月-检修单-种类-完成.json', $fixeds);

        $currentMonthFixedCountWithCategoryValues = [];
        foreach ($fixeds as $item) {
            $currentMonthFixedCountWithCategoryValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-种类-完成-值.json', $currentMonthFixedCountWithCategoryValues);

        return $fixeds;
    }

    /**
     * 获取本月已修
     * @param int $year
     * @param int $month
     * @param bool $isTest
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedCount(int $year, int $month, bool $isTest = false): array
    {
        # 获取本月时间起点和终点
        $originDatetime = Carbon::createFromDate($year, $month)->firstOfMonth()->format('Y-M-d');
        $finishDatetime = Carbon::createFromDate($year, $month)->lastOfMonth()->format('Y-M-d');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        $fixeds = [];
        if ($this->fileExist($filePath . '类型.json')) {
            $entireModels = $this->readFile($year, $month, '类型.json');
        } else {
            throw new \Exception($filePath . '类型.json不存在');
        }

        foreach ($entireModels as $index => $item) {
            $count = 0;
            if (!$isTest) {
                $count = FixWorkflow::with(['EntireInstance'])
                    ->whereHas('EntireInstance', function ($query) use ($index) {
                        $query->where('entire_model_unique_code', $index);
                    })
                    ->whereBetween('created_at', [$originDatetime, $finishDatetime])
                    ->where('status', 'FIXED')
                    ->count('id');
            }

            $fixeds[$index] = $isTest ? rand(1000, 2000) : $count;
        }
        $this->saveFile($year, $month, '当月-检修单-类型-完成.json', $fixeds);

        $currentMonthFixedCountValues = [];
        foreach ($fixeds as $item) {
            $currentMonthFixedCountValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-类型-完成-值.json', $currentMonthFixedCountValues);

        return $fixeds;
    }

    /**
     * 获取本月完成率（子类、型号）
     * @param int $year
     * @param int $month
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedRateCountWithSub(int $year, int $month): array
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        if ($this->fileExist($filePath . '当月-检修单-型号和子类-总数.json')) {
            $currentMonthAllFixCountWithSub = $this->readFile($year, $month, '当月-检修单-型号和子类-总数.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-型号和子类-总数.json 不存在');
        }
        if ($this->fileExist($filePath . '当月-检修单-型号和子类-完成.json')) {
            $currentMonthFixedCountWithSub = $this->readFile($year, $month, '当月-检修单-型号和子类-完成.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-型号和子类-完成.json 不存在');
        }

        $currentMonthFixedRateCountWithSub = [];
        foreach ($currentMonthAllFixCountWithSub as $index => $item) {
            if ($item == 0 || $currentMonthFixedCountWithSub[$index] == 0) {
                $currentMonthFixedRateCountWithSub[$index] = 0;
                continue;
            }
            $currentMonthFixedRateCountWithSub[$index] = number_format($currentMonthFixedCountWithSub[$index] / $item, 2) * 100;
        }
        $this->saveFile($year, $month, '当月-检修单-型号和子类-完成率.json', $currentMonthFixedRateCountWithSub);

        # 将全部的结果转换为只有value
        $currentMonthFixedRateCountWithSubValues = [];
        foreach ($currentMonthFixedRateCountWithSub as $item) {
            $currentMonthFixedRateCountWithSubValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-型号和子类-完成率-值.json', $currentMonthFixedRateCountWithSubValues);

        return $currentMonthFixedRateCountWithSub;
    }

    /**
     * 获取本月完成率（种类）
     * @param int $year
     * @param int $month
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedRateCountWithCategory(int $year, int $month): array
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        if ($this->fileExist($filePath . '当月-检修单-种类-总数.json')) {
            $currentMonthAllFixCountWithCategory = $this->readFile($year, $month, '当月-检修单-种类-总数.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-种类-总数.json不存在');
        }
        if ($this->fileExist($filePath . '当月-检修单-种类-完成.json')) {
            $currentMonthFixedCountWithCategory = $this->readFile($year, $month, '当月-检修单-种类-完成.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-种类-完成.json不存在');
        }

        $currentMonthFixedRateWithCategoryCount = [];
        foreach ($currentMonthAllFixCountWithCategory as $index => $item) {
            if ($item == 0 || $currentMonthFixedCountWithCategory[$index] == 0) {
                $currentMonthFixedRateWithCategoryCount[$index] = 0;
                continue;
            }
            $currentMonthFixedRateWithCategoryCount[$index] = number_format($currentMonthFixedCountWithCategory[$index] / $item, 2) * 100;
        }
        $this->saveFile($year, $month, '当月-检修单-种类-完成率.json', $currentMonthFixedRateWithCategoryCount);

        # 将全部的结果转换为只有value
        $currentMonthFixedRateCountWithCategoryValues = [];
        foreach ($currentMonthFixedRateWithCategoryCount as $item) {
            $currentMonthFixedRateCountWithCategoryValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-种类-完成率-值.json', $currentMonthFixedRateCountWithCategoryValues);

        return $currentMonthFixedRateWithCategoryCount;
    }

    /**
     * 获取本月完成率
     * @param int $year
     * @param int $month
     * @return array
     * @throws \Exception
     */
    final public function getCurrentMonthFixedRateCount(int $year, int $month): array
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filePath = "app/fixWorkflow/{$year}-{$month}/";

        if ($this->fileExist($filePath . '当月-检修单-类型-总数.json')) {
            $currentMonthAllFixCount = $this->readFile($year, $month, '当月-检修单-类型-总数.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-类型-总数.json不存在');
        }
        if ($this->fileExist($filePath . '当月-检修单-类型-完成.json')) {
            $currentMonthFixedCount = $this->readFile($year, $month, '当月-检修单-类型-完成.json');
        } else {
            throw new \Exception($filePath . '当月-检修单-类型-完成.json不存在');
        }

        $currentMonthFixedRateCount = [];
        foreach ($currentMonthAllFixCount as $index => $item) {
            if ($item == 0 || $currentMonthFixedCount[$index] == 0) {
                $currentMonthFixedRateCount[$index] = 0;
                continue;
            }
            $currentMonthFixedRateCount[$index] = number_format($currentMonthFixedCount[$index] / $item, 2) * 100;
        }
        $this->saveFile($year, $month, '当月-检修单-类型-完成率.json', $currentMonthFixedRateCount);

        # 将全部的结果转换为只有value
        $currentMonthFixedRateCountValues = [];
        foreach ($currentMonthFixedRateCount as $item) {
            $currentMonthFixedRateCountValues[] = $item;
        }
        $this->saveFile($year, $month, '当月-检修单-类型-完成率-值.json', $currentMonthFixedRateCountValues);

        return $currentMonthFixedRateCount;
    }

    /**
     * 获取用于自动生成检修单的整件实例身份码
     * @param int $year
     * @param int $month
     * @return Collection
     */
    final public function getEntireInstanceIdentityCodesForGoingToAutoMakeFixWorkflow(int $year, int $month): Collection
    {
        # 获取本月时间
        $originTimestamp = mktime(null, null, null, $month, 1, $year);
        $finishTimestamp = strtotime("+1 month -1 day", $originTimestamp);
        $entireInstances = EntireInstance::with(['EntireModel'])
            ->whereBetween('next_auto_making_fix_workflow_time', [$originTimestamp, $finishTimestamp]);
        $this->saveFile($year, $month, '当月-预检修设备列表.json', $entireInstances->get());
        return $entireInstances->get();
    }

    /**
     * 自动生成检修单
     * @param Collection $entireInstances
     * @return array
     */
    final public function autoMakeFixWorkflow(Collection $entireInstances): array
    {
        $fixWorkflows = [];
        $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW');
        $i = 0;

        return DB::transaction(function ()
        use (
            $entireInstances,
            $fixWorkflowSerialNumber,
            $fixWorkflows,
            $i
        ): array {
            $currentDatetime = date('Y-m-d');
            foreach ($entireInstances as $entireInstance) {
                $i += 1;
                $fixWorkflows[] = [
                    'created_at' => $currentDatetime,
                    'updated_at' => $currentDatetime,
                    'status' => 'UNFIX',
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                    'serial_number' => $fixWorkflowSerialNumber . "_{$i}",
                    'note' => '周期自动生成',
                    'maintain_station_name' => $entireInstance->maintain_station_name,
                    'maintain_location_name' => $entireInstance->maintain_location_code,
                    'is_cycle' => true,
                    'processor_id' => 0,
                    'stage' => 'UNFIX',
                    'type' => 'FIX',
                ];
            }
            DB::table('fix_workflows')->insert($fixWorkflows);
            return $fixWorkflows;
        });
    }
}
