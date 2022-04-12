<?php

namespace App\Console\Commands;

use App\Model\EntireModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KindInputQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-input:q {operator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * 增加种类型
     * @param string $category_unique_code
     * @param array $models
     */
    private function pushModels(string $category_unique_code, array $models): void
    {
        collect($models)
            ->each(function ($sub_models, $entire_model_name) use ($category_unique_code) {
                $entire_model = EntireModel::with([])
                    ->where('category_unique_code', $category_unique_code)
                    ->where('name', $entire_model_name)
                    ->first();
                if (!$entire_model) {
                    $entire_model = EntireModel::with([])->create([
                        'unique_code' => EntireModel::generateEntireModelUniqueCode($category_unique_code),
                        'name' => $entire_model_name,
                        'category_unique_code' => $category_unique_code,
                        'is_sub_model' => false,
                    ]);
                    $this->info("创建新类型：{$entire_model->name} {$entire_model->unique_code}");
                }

                collect($sub_models)->each(function ($sub_model_name) use ($entire_model, $category_unique_code) {
                    if (!EntireModel::with([])
                        ->where('name', $sub_model_name)
                        ->where('category_unique_code', $category_unique_code)
                        ->where('parent_unique_code', $entire_model->unique_code)
                        ->where('is_sub_model', true)
                        ->exists()) {
                        $sub_model = EntireModel::with([])->create([
                            'unique_code' => EntireModel::generateSubModelUniqueCode($entire_model->unique_code),
                            'name' => $sub_model_name,
                            'category_unique_code' => $category_unique_code,
                            'parent_unique_code' => $entire_model->unique_code,
                            'is_sub_model' => true,
                        ]);
                        $this->info("创建新型号：{$sub_model->name} {$sub_model->unique_code}");
                    } else {
                        $this->comment("跳过型号：{$sub_model_name}");
                    }
                });
            });
    }

    /**
     * 转辙机改为器材
     */
    private function zzj(): void
    {
        DB::table("categories")->where("unique_code", "S03")->update(["name" => "转辙机（旧码）"]);
        $models = [
            "ZD6" => [
                "ZD6-A",
                "ZD6-B",
                "ZD6-D",
                "ZD6-E",
                "ZD6-F",
                "ZD6-G",
                "ZD6-H",
                "ZD6-J",
                "ZDG-III",
                "ZD6-K",
                "ZD6-D-F",
                "ZD6-F-F",
                "ZD6-G-F",
                "ZD6-J-F",
                "DZG",
                "DZ1",
                "ZD6-G",
                "ZD6-D",
                "ZD6-F",
                "DZ1-A",
            ],
            "ZD-9" => [
                "ZD9",
                "ZD9-A",
                "ZD9-B",
                "ZD9-C",
                "ZD9-D",
                "ZD(J)9",
            ],
            "ZY(J)" => [
                "ZY-4",
                "ZY-6",
                "ZY-7",
                "ZYJ-2",
                "ZYJ-3",
                "ZYJ-4",
                "ZYJ-5",
                "ZYJ-6",
                "ZYJ7",
                "ZYJ7-A",
                "ZYJ7-J",
                "ZYJ7-K",
                "ZY4交流电液",
                "ZY4直流电液",
                "ZY7-F",
                "ZY7-N",
                "ZYJ7-1",
                "ZYJ7-B",
                "ZYJ7-C",
                "ZYJ7-D",
                "ZYJ7-F",
                "ZYJ7-H",
                "ZYJ7-M",
                "ZYJ7-N",
                "ZYJ7-P",
                "ZYJ7-Q",
                "ZYJ7-R",
                "ZYJ7-R1",
                "ZYJ7-R2",
                "ZYJ7S",
                "ZYJ7-SS",
                "ZYJ7-U",
                "ZYJ7-W",
                "ZYS7",
                "ZYJ7",
                "ZY4",
                "ZY6",
                "YJ1电机",
            ],
            "S700K" => [
                "S700K-A10",
                "S700K-A13",
                "S700K-A14",
                "S700K-A15",
                "S700K-A16",
                "S700K-A17",
                "S700K-A18",
                "S700K-A19",
                "S700K-A20",
                "S700K-A21",
                "S700K-A22",
                "S700K-A29",
                "S700K-A30",
                "S700K-A33",
                "S700K-A27",
                "S700K-A28",
                "S700K-A13G",
                "S700K-A14G",
                "S700K-A17G",
                "S700K-A18G",
                "S700K-A35G",
                "S700K-A36G",
                "S700K-A47G",
                "S700K-A48G",
                "S700K-A49G",
                "S700K-A50G",
                "S700K-A91",
                "S700K-A92",
                "S700K-A93",
                "S700K-A94",
                "S700K-A95",
                "S700K-A97",
                "S700K-A98",
            ],
            "ZK" => [
                "ZK-3A",
                "ZK-4",
            ],
            "ZD7" => [
                "ZD7-A",
                "ZD7-C",
            ],
        ];

        $this->pushModels("Q42", $models);
    }

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->{$this->argument("operator")}();
    }
}
