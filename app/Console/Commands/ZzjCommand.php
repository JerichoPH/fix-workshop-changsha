<?php

namespace App\Console\Commands;

use App\Model\EntireInstanceCount;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ZzjCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zzjPartToEntire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private function step1()
    {
        // @todo: 第一步 通过部件型号寻找对应型号
        $this->comment("第一步 通过部件型号寻找对应型号");
        $tmp = DB::table('part_instances as pi')
            ->select('pi.part_model_name')
            ->groupBy(['pi.part_model_name'])
            ->pluck('part_model_name')
            ->toArray();

        foreach ($tmp as $t) {
            $a = DB::table('entire_models as sm')->select('sm.unique_code', 'sm.name')->where('sm.name', $t)->where('name', $t)->first();
            if(!$a) continue;
            $b = DB::table('part_instances as pi')->where('pi.device_model_unique_code','')->where('pi.part_model_name',$t)->update(['pi.device_model_unique_code'=>$a->unique_code]);
        }
    }

    private function step2()
    {
        // @todo: 第二步 复制错误的唯一编号到old_identity_code
        $this->comment("第二步 复制错误的唯一编号到old_identity_code");
        DB::select("update part_instances set old_identity_code = identity_code where true");
    }

    private function step3()
    {
        // @todo: 第三步 重新赋码
        $this->comment("第三步 重新赋码");
        DB::table('part_instances as pi')
            ->orderByDesc('pi.device_model_unique_code')
            ->where('pi.device_model_unique_code', '<>', '')
            ->get()
            ->groupBy('device_model_unique_code')
            ->each(function ($part_instances, $device_model_unique_code) {
                $entire_instance_count = DB::table('entire_instance_counts as eic')
                    ->where('eic.entire_model_unique_code', $device_model_unique_code)
                    ->first();

                if (!$entire_instance_count) {
                    $next_count = 0;
                } else {
                    $next_count = intval($entire_instance_count->count);
                }

                $part_instances->each(function ($part_instance) use ($device_model_unique_code, &$next_count) {
                    $new_identity_code = $device_model_unique_code . env('ORGANIZATION_CODE') . str_pad(++$next_count, 7, '0', STR_PAD_LEFT) . 'H';

                    DB::table('part_instances')->where('id', $part_instance->id)->update(['identity_code' => $new_identity_code]);
                    $this->comment($new_identity_code);
                });

                $entire_instance_count = EntireInstanceCount::with([])->where('entire_model_unique_code', $device_model_unique_code)->first();
                if ($entire_instance_count) {
                    $entire_instance_count->fill(['count' => $next_count])->saveOrFail();
                } else {
                    EntireInstanceCount::with([])->create([
                        'entire_model_unique_code' => $device_model_unique_code,
                        'count' => $next_count,
                    ]);
                }
                $this->comment('-------------' . $next_count);
            });
    }

    private function step4()
    {
        // @todo: 第四步 部件信息复制到整件表
        $this->comment("第四步 部件信息复制到整件表");
        $part_models_in_part_instances = DB::table('part_instances as pi')
            ->select(['pi.device_model_unique_code'])
            ->groupBy(['pi.device_model_unique_code'])
            ->pluck('device_model_unique_code');
        $sub_models = DB::table('entire_models as sm')
            ->selectRaw(implode(',', [
                'c.name as cn',
                'c.unique_code as cu',
                'em.name as en',
                'em.unique_code eu',
                'sm.name as sn',
                'sm.unique_code as su'
            ]))
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->whereIn('sm.unique_code', $part_models_in_part_instances)
            ->get()
            ->toArray();
        $sub_models2 = [];
        foreach ($sub_models as $sub_model) $sub_models2[$sub_model->su] = $sub_model;

        DB::beginTransaction();

        try {
            DB::table('part_instances as pi')
                ->orderBy('id')
                ->where('pi.device_model_unique_code', '!=', '')
                ->chunk(50, function ($part_instances) use ($sub_models2){
                    $insert_data = [];
                    $part_instances->each(function ($part_instance) use (&$insert_data, $sub_models2) {
                        $this->comment('--------------');
                        dump(@(array)$sub_models2[$part_instance->device_model_unique_code], $part_instance->device_model_unique_code);
                        if(!@(array)$sub_models2[$part_instance->device_model_unique_code])
                            return null;

                        ['cu' => $cu, 'cn' => $cn, 'sn' => $sn] = (array)$sub_models2[$part_instance->device_model_unique_code];
                        if (!$cu) throw new Exception("$part_instance->device_model_unique_code 种类不存在");

                        $insert_data[] = [
                            'created_at' => $part_instance->created_at,
                            'updated_at' => $part_instance->updated_at,
                            'part_model_unique_code' => $part_instance->part_model_unique_code,
                            'part_model_name' => $part_instance->part_model_name,
                            'entire_instance_identity_code' => $part_instance->entire_instance_identity_code,
                            'category_unique_code' => $cu,
                            'category_name' => $cn,
                            'entire_model_unique_code' => $part_instance->device_model_unique_code,
                            'model_unique_code' => $part_instance->device_model_unique_code,
                            'model_name' => $sn,
                            'part_category_id' => $part_instance->part_category_id,
                            'identity_code' => $part_instance->identity_code,
                            'factory_device_code' => $part_instance->factory_device_code,
                            'factory_name' => $part_instance->factory_name,
                            'made_at' => $part_instance->made_at,
                            'scarping_at' => $part_instance->scraping_at,
                            'work_area_unique_code' => $part_instance->work_area_unique_code,
                            'status' => $part_instance->status,
                            'is_part' => true,
                            'serial_number' => $part_instance->old_identity_code,
                        ];

                        dump($part_instance->id);
                    });

                    $insert_ret = DB::table('entire_instances')->insert($insert_data);
                    $this->info("执行结果：$insert_ret");
                    $this->info("----------------------");
                });
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            dd("错误：",$e->getMessage(), $e->getFile(), $e->getLine());
        }
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
        $this->info("PROCESSING");
        $this->step1();
        $this->step2();
        $this->step3();
        $this->step4();
        $this->info("FINISHED");
    }
}
