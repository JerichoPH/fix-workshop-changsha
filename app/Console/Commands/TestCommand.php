<?php

namespace App\Console\Commands;

use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Maintain;
use App\Model\PartInstance;
use App\Model\PartModel;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    final public function handle()
    {
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([113.262101, 23.13093]),
        //     'points' => json_encode(config('monitor.B048')),
        //     'organization_code' => 'B048',
        // ]);
        // $points = config('monitor.B049');
        // $a = [];
        // foreach ($points as $point) {
        //     $b = [];
        //     foreach ($point as $item) {
        //         $b[] = [
        //             (float)$item['lon'],
        //             (float)$item['lat'],
        //         ];
        //     }
        //     $a[] = $b;
        // }
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([113.019455, 28.200103]),
        //     'points' => json_encode($a),
        //     'organization_code' => 'B049',
        // ]);
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([110.001492, 27.570676]),
        //     'points' => json_encode(config('monitor.B050')),
        //     'organization_code' => 'B050',
        // ]);
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([112.571114, 26.893869]),
        //     'points' => json_encode(config('monitor.B051')),
        //     'organization_code' => 'B051',
        // ]);
        //
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([114.415641, 23.112953]),
        //     'points' => json_encode(config('monitor.B052')),
        //     'organization_code' => 'B052',
        // ]);
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([112.463372, 23.04864]),
        //     'points' => json_encode(config('monitor.B053')),
        //     'organization_code' => 'B053',
        // ]);
        // DB::table('line_points')->insert([
        //     'created_at' => date('Y-m-d H:i:s'),
        //     'updated_at' => date('Y-m-d H:i:s'),
        //     'center_point' => json_encode([109.721763, 19.224042]),
        //     'points' => json_encode(config('monitor.B054')),
        //     'organization_code' => 'B054',
        // ]);

        $a = storage_path('第二次更改怀化段铁路线描点三维数组.json');
        $b = file_get_contents($a);
        $c = json_decode($b, true);
        foreach ($c as $ck => $cv) {
            foreach ($cv as $key => $item) {
                list($lon, $lat) = $item;
                ['lon' => $new_lon, 'lat' => $new_lat] = CommonFacade::gcj02_to_bd09($lon, $lat);
                $c[$ck][$key] = [$new_lon, $new_lat];
            }
        }
        file_put_contents(storage_path('第二次更改怀化段铁路线描点三维数组.new.json'), json_encode($c));
    }
}
