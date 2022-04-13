<?php

namespace App\Console\Commands;

use App\Facades\KindsFacade;
use App\Facades\TextFacade;
use App\Model\Category;
use App\Model\EntireModel;
use App\Model\Platoon;
use App\Services\KindsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KindClearUpQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-clear-up:q {operator} {old_conn_name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @throws Exception
     */
    private function moveModels(string $old_model_unique_code, string $new_model_unique_code)
    {
        if (!$old_model_unique_code) throw new Exception('旧型号不存在');
        if (!$new_model_unique_code) throw new Exception('新型号不存在');

        $old_model = EntireModel::with([])->where('unique_code', $old_model_unique_code)->where('is_sub_model', true)->first();

        if (!$old_model) {
            $this->comment("旧型号不存在：$old_model_unique_code");
        } else {
            $new_model = EntireModel::with([])->where('unique_code', $new_model_unique_code)->where('is_sub_model', true)->first();
            if (!$new_model) $this->comment('新型号不存在');

            DB::table('entire_instances as ei')
                ->whereNull('ei.deleted_at')
                ->where('model_unique_code', $old_model_unique_code)
                ->update([
                    'model_unique_code' => $new_model_unique_code,
                    'entire_model_unique_code' => $new_model_unique_code,
                ]);
            $this->info("移动型号：{$old_model->name}({$old_model->unique_code}) >> {$new_model->unique_code}");

            DB::table('entire_models')->where('unique_code', $old_model_unique_code)->update(['deleted_at' => now()]);
        }
    }

    private function changsha()
    {
        $models = [
            "Q012002" => "Q012103",  // JSDXC-1700
            "Q012003" => "Q012102",  // JSDXC2-1700
        ];
        collect($models)->each(
        /** @throws Exception */
            function ($new_model_unique_code, $old_model_unique_code) {
                $this->moveModels($old_model_unique_code, $new_model_unique_code);
            });
    }

    private function gz()
    {
        // 移动型号
        $models = [
            // 'Q13022F' => 'Q131763',
            // 'Q13022G' => 'Q131764',
            // 'Q13022H' => 'Q131765',
            // 'Q053406' => 'Q131766',
            // 'Q05340F' => 'Q131767',
            // 'Q05340P' => 'Q13174V',
            // 'Q05340Q' => 'Q13174W',
            // 'Q13022Y' => 'Q131766',
            // 'Q13022N' => 'Q13172I',
            // 'Q13022P' => 'Q131768',
            // 'Q13022Q' => 'Q131769',
            // 'Q053404' => 'Q13176A',
            // 'Q05340B' => 'Q13176B',
            // 'Q05340C' => 'Q13176C',
            // 'Q05340G' => 'Q13176D',
            // 'Q05340H' => 'Q13176E',
            // 'Q05340I' => 'Q13176F',
            // 'Q05340J' => 'Q13176G',
            // 'Q05340K' => 'Q13176H',
            // 'Q05340U' => 'Q13173K',
            // "Q13173W" => "Q131762",
            // "Q05050O",  // 删除
            // "Q05050P",  // 删除
            // "Q05050Q",  // 删除
            // "Q05050R",  // 删除
            // "Q050515" => "Q05020I",
            // "Q020806" => "Q050212",
            // "Q051701" => "Q050212",
        ];

        collect($models)->each(
        /**
         * @throws Exception
         */ function ($new_model_unique_code, $old_model_unique_code) {
            $this->moveModels($old_model_unique_code, $new_model_unique_code);
        });
    }

    final private function __generateCategoryUniqueCode(): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_categories')
            ->orderByDesc('unique_code')
            ->first();

        // 36进制
        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;  // 36进制
        return 'Q' . str_pad(TextFacade::to36(strval($max + 1)), 2, '0', STR_PAD_LEFT);
    }

    final private function __generateEntireModelUniqueCode(string $category_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_models')
            ->where('equipment_category_unique_code', $category_unique_code)
            ->orderByDesc('unique_code')
            ->first();

        // $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        // return $category_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
        $max = $last ? intval(substr($last->unique_code, -2)) : 0;
        return $category_unique_code . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    final private function __generateSubModelUniqueCode(string $parent_unique_code): string
    {
        $last = DB::connection('paragraph_center')
            ->table('equipment_sub_models')
            ->where('equipment_model_unique_code', $parent_unique_code)
            ->orderByDesc('unique_code')
            ->first();

        $max = $last ? intval(TextFacade::from36(substr($last->unique_code, -2))) : 0;
        return $parent_unique_code . str_pad(TextFacade::to36($max + 1), 2, '0', STR_PAD_LEFT);
    }

    final private function syncW2P()
    {

        $old_conn_name = $this->argument("old_conn_name");
        if (!$old_conn_name) {
            $this->error("错误：数据库链接名不能为空");
            return;
        }
        $paragraph_center_conn_name = "paragraph_center";

        $__getDB = function (string $old_conn_name): Builder {
            return DB::connection($old_conn_name)
                ->table("entire_instances as ei")
                ->selectRaw(join(",", [
                    "c.name as category_name",
                    "c.unique_code as category_unique_code",
                    "c.nickname as category_nickname",
                    "em.name as entire_model_name",
                    "em.unique_code as entire_model_unique_code",
                    "em.nickname as entire_model_nickname",
                    "sm.name as sub_model_name",
                    "sm.unique_code as sub_model_unique_code",
                    "sm.nickname as sub_model_nickname",
                ]))
                ->join(DB::raw("entire_models sm"), "ei.model_unique_code", "=", "sm.unique_code")
                ->join(DB::raw("entire_models em"), "sm.parent_unique_code", "=", "em.unique_code")
                ->join(DB::raw("categories c"), "em.category_unique_code", "=", "c.unique_code")
                ->where("sm.is_sub_model", true)
                ->whereNull("sm.deleted_at")
                ->where("em.is_sub_model", false)
                ->whereNull("em.deleted_at")
                ->where("c.unique_code", "like", "Q%")
                ->whereNull("c.deleted_at")
                ->where("ei.model_name", "<>", "");
        };

        $__getDB($old_conn_name)
            ->orderBy("c.unique_code")
            ->orderBy("em.unique_code")
            ->orderBy("sm.unique_code")
            ->groupBy(["sm.unique_code",])
            // ->where("ei.identity_code", "Q02070KB0740000001H")
            ->chunk(500, function ($entire_instances) use ($__getDB, $old_conn_name, $paragraph_center_conn_name) {
                $entire_instances->each(function ($datum) use ($__getDB, $old_conn_name, $paragraph_center_conn_name) {
                    [
                        "category_name" => $old_category_name,
                        "category_unique_code" => $old_category_unique_code,
                        "category_nickname" => $old_category_nickname,
                        "entire_model_name" => $old_entire_model_name,
                        "entire_model_unique_code" => $old_entire_model_unique_code,
                        "entire_model_nickname" => $old_entire_model_nickname,
                        "sub_model_name" => $old_sub_model_name,
                        "sub_model_unique_code" => $old_sub_model_unique_code,
                        "sub_model_nickname" => $old_sub_model_nickname,
                    ] = (array)$datum;

                    // 比对种类
                    $new_category = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_categories")
                        ->where("name", $old_category_name)
                        ->first();
                    if ($new_category) {
                        // 种类存在，
                        $new_category_unique_code = $new_category->unique_code;
                        if ($old_category_nickname) DB::connection($paragraph_center_conn_name)->table("equipment_categories")->where("id", $new_category->id)->update(["nickname" => $old_category_nickname]);
                        $this->comment("类型存在：$old_category_name");
                    } else {
                        // 种类不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_categories")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "unique_code" => $new_category_unique_code = $this->__generateCategoryUniqueCode(),
                                "name" => $old_category_name
                            ]);
                        $this->info("种类不存在：$old_category_name >> $new_category_unique_code");
                    }
                    $ret = $__getDB($old_conn_name)
                        ->where("c.name", $old_category_name)
                        ->where("em.name", $old_entire_model_name)
                        ->where("sm.name", $old_sub_model_name)
                        ->update(["ei.new_category_unique_code" => $new_category_unique_code,]);
                    $this->info("修改种类归属关系：$old_category_name $old_category_unique_code >> $new_category_unique_code 共：$ret");

                    // 比对类型
                    $new_entire_model = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_models as eem")
                        ->selectRaw("eem.*")
                        ->join(DB::raw("equipment_categories ec"), "ec.unique_code", "=", "eem.equipment_category_unique_code")
                        ->where("ec.name", $old_category_name)
                        ->where("ec.unique_code", $new_category_unique_code)
                        ->where("eem.name", $old_entire_model_name)
                        ->first();
                    if ($new_entire_model) {
                        // 类型存在
                        $new_entire_model_unique_code = $new_entire_model->unique_code;
                        if ($old_entire_model_nickname) DB::connection($paragraph_center_conn_name)->table("equipment_models")->where("id", $new_entire_model->id)->update(["nickname" => $old_entire_model_nickname]);
                        $this->comment("类型存在：$old_category_name $old_entire_model_name");
                    } else {
                        // 类型不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_models")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "equipment_category_unique_code" => $new_category_unique_code,
                                "unique_code" => $new_entire_model_unique_code = $this->__generateEntireModelUniqueCode($new_category_unique_code),
                                "name" => $old_entire_model_name,
                            ]);
                        $this->info("类型不存在：$old_category_name $old_entire_model_name >> $new_entire_model_unique_code");
                    }

                    // 比对型号
                    $new_sub_model = DB::connection($paragraph_center_conn_name)
                        ->table("equipment_sub_models as esm")
                        ->selectRaw("esm.*")
                        ->join(DB::raw("equipment_models eem"), "eem.unique_code", "=", "esm.equipment_model_unique_code")
                        ->join(DB::raw("equipment_categories ec"), "ec.unique_code", "=", "eem.equipment_category_unique_code")
                        ->where("esm.name", $old_sub_model_name)
                        ->where("eem.name", $old_entire_model_name)
                        ->where("eem.unique_code", $new_entire_model_unique_code)
                        ->where("ec.name", $old_category_name)
                        ->where("ec.unique_code", $new_category_unique_code)
                        ->first();
                    if ($new_sub_model) {
                        // 型号存在
                        $new_sub_model_unique_code = $new_sub_model->unique_code;
                        if ($old_sub_model_nickname) DB::connection($paragraph_center_conn_name)->table("equipment_sub_models")->where("id", $new_sub_model->id)->update(["nickname" => $old_sub_model_nickname]);
                        $this->comment("型号存在：$old_category_name $old_entire_model_name $old_sub_model_name");
                    } else {
                        // 型号不存在
                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_sub_models")
                            ->insert([
                                "created_at" => now(),
                                "updated_at" => now(),
                                "equipment_model_unique_code" => $new_entire_model_unique_code,
                                "unique_code" => $new_sub_model_unique_code = $this->__generateSubModelUniqueCode($new_entire_model_unique_code),
                                "name" => $old_sub_model_name,
                            ]);
                        $this->info("型号不存在：$old_category_name $old_entire_model_name $old_sub_model_name >> $new_sub_model_unique_code");
                    }
                    $ret = $__getDB($old_conn_name)
                        ->where("c.name", $old_category_name)
                        ->where("em.name", $old_entire_model_name)
                        ->where("sm.name", $old_sub_model_name)
                        ->update([
                            "ei.new_sub_model_unique_code" => $new_sub_model_unique_code,
                            "ei.new_entire_model_unique_code" => $new_entire_model_unique_code,
                            "ei.new_category_unique_code" => $new_category_unique_code,
                        ]);
                    $this->info("修改型号归属关系：$old_category_name $old_entire_model_name $old_sub_model_name $old_sub_model_unique_code >> $new_sub_model_unique_code 共：$ret");
                });
            });
    }

    final private function syncP2W()
    {
        $old_conn_name = $this->argument("old_conn_name");
        if (!$old_conn_name) {
            $this->error("错误：数据库链接名不能为空");
            return;
        }
        $paragraph_center_conn_name = "paragraph_center";
        $is_truncate = true;

        if ($is_truncate) DB::connection($old_conn_name)->table("categories")->truncate();
        if ($is_truncate) DB::connection($old_conn_name)->table("entire_models")->truncate();

        $categories = [];
        $entire_models = [];
        $sub_models = [];

        DB::connection($paragraph_center_conn_name)
            ->table("equipment_categories")
            ->orderBy("unique_code")
            ->each(function ($equipment_category) use (&$categories, &$entire_models, &$sub_models, $paragraph_center_conn_name, $old_conn_name) {
                if (!DB::connection($old_conn_name)->table("categories")->where("name", $equipment_category->name)->exists()) {
                    $categories[] = [
                        "unique_code" => $equipment_category->unique_code,
                        "name" => $equipment_category->name,
                        "nickname" => $equipment_category->nickname,
                    ];
                    $this->info("新增种类：{$equipment_category->name}");
                } else {
                    $this->comment("跳过种类：{$equipment_category->name}");
                }

                DB::connection($paragraph_center_conn_name)
                    ->table("equipment_models")
                    ->orderBy("unique_code")
                    ->where("equipment_category_unique_code", $equipment_category->unique_code)
                    ->each(function ($equipment_model) use (&$entire_models, &$sub_models, $equipment_category, $paragraph_center_conn_name, $old_conn_name) {
                        if (!DB::connection($old_conn_name)->table("entire_models")->where("is_sub_model", false)->where("name", $equipment_model->name)->exists()) {
                            $entire_models[] = [
                                "unique_code" => $equipment_model->unique_code,
                                "name" => $equipment_model->name,
                                "nickname" => $equipment_model->nickname,
                                "category_unique_code" => $equipment_category->unique_code,
                                "is_sub_model" => false,
                            ];
                            $this->info("新增类型：{$equipment_model->name}");
                        } else {
                            $this->comment("跳过类型：{$equipment_model->name}");
                        }

                        DB::connection($paragraph_center_conn_name)
                            ->table("equipment_sub_models")
                            ->orderBy("unique_code")
                            ->where("equipment_model_unique_code", $equipment_model->unique_code)
                            ->each(function ($equipment_sub_model) use (&$sub_models, $equipment_category, $equipment_model, $paragraph_center_conn_name, $old_conn_name) {
                                if (!DB::connection($old_conn_name)->table("entire_models")->where("is_sub_model", true)->where("name", $equipment_sub_model->name)->exists()) {
                                    $sub_models[] = [
                                        "unique_code" => $equipment_sub_model->unique_code,
                                        "name" => $equipment_sub_model->name,
                                        "nickname" => $equipment_sub_model->nickname,
                                        "category_unique_code" => $equipment_category->unique_code,
                                        "parent_unique_code" => $equipment_model->unique_code,
                                        "is_sub_model" => true,
                                    ];
                                    $this->info("新增型号：{$equipment_sub_model->name}");
                                } else {
                                    $this->comment("跳过型号：{$equipment_sub_model->name}");
                                }
                            });
                    });
            });

        DB::connection($old_conn_name)->table("categories")->insert($categories);
        DB::connection($old_conn_name)->table("entire_models")->insert($entire_models);
        DB::connection($old_conn_name)->table("entire_models")->insert($sub_models);
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $operator = $this->argument("operator");
        $this->$operator();
    }
}
