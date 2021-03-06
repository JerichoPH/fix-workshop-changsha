<?php

namespace App\Http\Controllers\TemporaryTask\Production;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Exceptions\EntireInstanceLockException;
use App\Model\RepairBaseFullFixOrder;
use App\Model\RepairBaseFullFixOrderEntireInstance;
use App\Model\RepairBaseFullFixOrderModel;
use App\Model\RepairBaseHighFrequencyOrder;
use App\Model\RepairBaseHighFrequencyOrderEntireInstance;
use App\Model\RepairBaseNewStationOrder;
use App\Model\RepairBaseNewStationOrderEntireInstance;
use App\Model\RepairBaseNewStationOrderModel;
use App\Model\WarehouseReport;
use Curl\Curl;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\BadRequestException;
use Jericho\CurlHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\TextHelper;
use stdClass;

class SubController extends Controller
{

    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'temporaryTask/production/sub';
    private $_root_url = null;
    private $_auth = null;
    private $__work_areas = [];
    private $__category_with_work_area = [1 => 'S03', 2 => 'Q01'];

    final public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = [
            "Username:{$this->_spas_username}",
            "Password:{$this->_spas_password}"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    final public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|JsonResponse|RedirectResponse|View
     */
    final public function create()
    {
        try {
            if (!request('type')) return back()->with('danger', '???????????????????????????');

            $base_data = function () {
                # ?????????????????????
                $entire_models = DB::table('entire_models as em')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->select([
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                        'c.name as category_name',
                        'c.unique_code as category_unique_code',
                    ])
                    ->where('em.deleted_at', null)
                    ->where('c.deleted_at', null)
                    ->where('em.is_sub_model', false)
                    ->where('em.category_unique_code', '<>', '')
                    ->where('em.category_unique_code', '<>', null)
                    ->get();

                # ?????????????????????
                $sub_models = DB::table('entire_models as sm')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->select([
                        'sm.name as model_name',
                        'sm.unique_code as model_unique_code',
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                    ])
                    ->where('sm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('sm.is_sub_model', true)
                    ->where('sm.parent_unique_code', '<>', '')
                    ->where('sm.parent_unique_code', '<>', null)
                    ->get();

                # ?????????????????????
                $part_models = DB::table('part_models as pm')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->select([
                        'pm.name as model_name',
                        'pm.unique_code as model_unique_code',
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                    ])
                    ->where('pm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('pm.entire_model_unique_code', '<>', '')
                    ->where('pm.entire_model_unique_code', '<>', null)
                    ->get();

                # ?????????????????????
                $models = $sub_models->merge($part_models);

                # ??????????????????
                $accounts = DB::table('accounts')->pluck('nickname', 'id');

                # ???????????????????????????
                $stations = DB::table('maintains as s')
                    ->join(DB::raw('maintains sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                    ->select([
                        's.name as station_name',
                        's.unique_code as station_unique_code',
                        'sw.name as scene_workshop_name',
                        'sw.unique_code as scene_workshop_unique_code',
                    ])
                    ->where('s.deleted_at', null)
                    ->where('sw.deleted_at', null)
                    ->get();

                return [$entire_models, $models, $accounts, $stations];
            };
            list($entire_models, $models, $accounts, $stations) = $base_data();

            $new_station = function () use ($entire_models, $models, $accounts, $stations) {
                # ????????????????????????
                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # ????????????????????????????????????????????????
                $new_station_models = RepairBaseNewStationOrderModel::with(['Order'])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->whereHas('Order', function ($Order) {
                        return $Order->where('direction', 'IN');
                    })
                    ->get();

                return view('TemporaryTask.Production.Sub.create_NEW_STATION', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                    'new_station_models' => $new_station_models,
                ]);
            };

            $full_fix = function () use ($entire_models, $models, $accounts, $stations) {
                # ????????????????????????
                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # ????????????????????????????????????????????????
                $full_fix_models = RepairBaseFullFixOrderModel::with(['Order'])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get();

                return view('TemporaryTask.Production.Sub.create_FULL_FIX', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'scene_workshops_as_json' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                    'full_fix_models' => $full_fix_models,
                ]);
            };

            $high_frequency = function () use ($entire_models, $models, $accounts, $stations) {
                # ????????????????????????
                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                return view('TemporaryTask.Production.Sub.create_HIGH_FREQUENCY', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'scene_workshops_as_json' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (BadRequestException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????????????????');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # ????????????????????????
                $new_station_models_in = RepairBaseNewStationOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();
                if (empty($new_station_models_in)) return response()->json(['message' => '??????????????????'], 404);

                # ???????????????????????????
                $work_area_accounts = DB::table('accounts')
                    ->where('deleted_at', null)
                    ->where('temp_task_position', 'ParagraphWorkArea')
                    ->groupBy('work_area')
                    ->get();
                if ($work_area_accounts->isEmpty()) return response()->json(['message' => '????????????????????????'], 404);
                if (!$work_area_accounts->get(1)) return response()->json(['message' => '?????????????????????????????????'], 404);
                if (!$work_area_accounts->get(2)) return response()->json(['message' => '?????????????????????????????????'], 404);
                if (!$work_area_accounts->get(3)) return response()->json(['message' => '??????????????????????????????'], 404);
                $work_area_names = [1 => '???????????????', 2 => '???????????????', 3 => '????????????'];

                $now = date('Y-m-d H:i:s');  # ????????????

                $ret = [];

                foreach ($new_station_models_in as $work_area_id => $new_station_model_in) {
                    $ret[] = DB::transaction(function () use ($now, $main_task_response, $work_area_accounts, $work_area_id, $work_area_names, $request, $new_station_model_in) {
                        $ret = [];

                        # ???????????????????????????
                        DB::table('repair_base_new_station_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $new_station_order_in_sn = CodeFacade::makeSerialNumber('NEW_STATION_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId'),
                        ]);

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '??????????????????',
                                'intro' => '??????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$new_station_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $new_station_order_in_sn,
                                'type' => 'NEW_STATION',
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ??????????????????????????????
                        DB::table('repair_base_new_station_order_models')
                            ->whereIn('id', collect($new_station_model_in)->pluck('id')->toArray())
                            ->update([
                                'new_station_model_order_sn' => $new_station_order_in_sn
                            ]);

                        # ???????????????????????????
                        DB::table('repair_base_new_station_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $new_station_order_out_sn = CodeFacade::makeSerialNumber('NEW_STATION_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId'),
                            'in_sn' => $new_station_order_in_sn,
                        ]);

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '??????????????????',
                                'intro' => '??????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$new_station_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $new_station_order_out_sn,
                                'type' => 'NEW_STATION',
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ?????????????????????????????????
                        $new_station_models_out = [];
                        foreach ($new_station_model_in as $item) {
                            $new_station_models_out[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $item->work_area_id,
                                'number' => $item->number,
                                'picked' => $item->picked,
                                'new_station_model_order_sn' => $new_station_order_out_sn,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                            ];
                        }
                        DB::table('repair_base_new_station_order_models')->insert($new_station_models_out);

                        return $ret;
                    });
                }

                # ???????????????????????????????????????
                $new_station_orders_as_json = RepairBaseNewStationOrderModel::with([
                    'SubModel',
                    'PartModel',
                    'EntireModel',
                    'Category',
                ])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->toJson(256);

                $save_main_task_file_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/saveMainTaskFile/" . request('mainTaskId'),
                    'method' => CurlHelper::POST,
                    'contents' => [
                        'orders' => $new_station_orders_as_json
                    ],
                ]);
                if ($save_main_task_file_response['code'] > 399) return response()->json($save_main_task_file_response['body'], $save_main_task_file_response['code']);

                return response()->json(['message' => '????????????', 'ret' => $ret, 'save_main_file' => $save_main_task_file_response]);
            };

            $full_fix = function () use ($request) {
                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # ??????????????????????????????????????????
                $full_fix_models_in = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->where('picked', true)
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();
                if (empty($full_fix_models_in)) return response()->json(['message' => '????????????????????????'], 404);

                # ????????????????????????????????????
                $full_fix_models_out = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();

                # ????????????????????????????????????
                $full_fix_models_scrap = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->where('picked', false)
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();

                # ???????????????????????????
                $work_area_accounts = DB::table('accounts')->where('deleted_at', null)->where('temp_task_position', 'ParagraphWorkArea')->groupBy('work_area')->get();
                if ($work_area_accounts->isEmpty()) return response()->json(['message' => '????????????????????????'], 404);
                if (!$work_area_accounts->get(1)) return response()->json(['message' => '?????????????????????????????????'], 404);
                if (!$work_area_accounts->get(2)) return response()->json(['message' => '?????????????????????????????????'], 404);
                if (!$work_area_accounts->get(3)) return response()->json(['message' => '??????????????????????????????'], 404);
                $work_area_names = [1 => '???????????????', 2 => '???????????????', 3 => '????????????'];

                $now = date('Y-m-d H:i:s');  # ????????????

                $ret = [];

                # ??????????????????
                foreach ($full_fix_models_in as $work_area_id => $full_fix_model_in) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_in
                    ) {
                        $ret = [];

                        # ???????????????????????????
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_in_sn = CodeFacade::makeSerialNumber('FULL_FIX_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '??????????????????',
                                'intro' => '??????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_in_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ??????????????????????????????
                        DB::table('repair_base_full_fix_order_models')
                            ->whereIn('id', collect($full_fix_model_in)->pluck('id')->all())
                            ->update(['full_fix_order_sn' => $full_fix_order_in_sn, 'direction' => 'IN',]);

                        return $ret;
                    });
                }

                # ??????????????????
                foreach ($full_fix_models_out as $work_area_id => $full_fix_model_out) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_out
                    ) {
                        $ret = [];

                        # ???????????????????????????
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_out_sn = CodeFacade::makeSerialNumber('FULL_FIX_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '??????????????????',
                                'intro' => '??????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_out_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        $insert = [];
                        foreach ($full_fix_model_out as $item) {
                            $insert[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $work_area_id,
                                'number' => $item->number,
                                'full_fix_order_sn' => $full_fix_order_out_sn,
                                'picked' => $item->picked,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                                'direction' => 'OUT',
                            ];
                        }
                        if (!empty($insert)) DB::table('repair_base_full_fix_order_models')->insert($insert);

                        return $ret;
                    });
                }

                # ??????????????????
                foreach ($full_fix_models_scrap as $work_area_id => $full_fix_model_scrap) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_scrap
                    ) {
                        $ret = [];

                        # ???????????????????????????
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_scrap_sn = CodeFacade::makeSerialNumber('FULL_FIX_SCRAP'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'SCRAP',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '??????????????????',
                                'intro' => '??????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_scrap_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_scrap_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'SCRAP',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ????????????????????????????????????
                        $insert = [];
                        foreach ($full_fix_model_scrap as $item) {
                            $insert[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $work_area_id,
                                'number' => $item->number,
                                'full_fix_order_sn' => $full_fix_order_scrap_sn,
                                'picked' => $item->picked,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                                'direction' => 'SCRAP',
                            ];
                        }
                        if (!empty($insert)) DB::table('repair_base_full_fix_order_models')->insert($insert);

                        return $ret;
                    });
                }

                # ???????????????????????????????????????
                $full_fix_orders_as_json = RepairBaseFullFixOrderModel::with([
                    'SubModel',
                    'PartModel',
                    'EntireModel',
                    'Category',
                ])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->toJson(256);

                $save_main_task_file_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/saveMainTaskFile/" . request('mainTaskId'),
                    'method' => CurlHelper::POST,
                    'contents' => [
                        'orders' => $full_fix_orders_as_json
                    ],
                ]);
                if ($save_main_task_file_response['code'] > 399) return response()->json($save_main_task_file_response['body'], $save_main_task_file_response['code']);

                return response()->json(['message' => '????????????', 'ret' => $ret, 'save_main_file' => $save_main_task_file_response]);
            };

            $high_frequency = function () use ($request) {
                $ret = [];
                return DB::transaction(function () use ($request, &$ret) {
                    # ???????????????????????????????????????
                    $work_area_1 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->where('category_unique_code', 'S03')->get();
                    $work_area_2 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->where('category_unique_code', 'Q01')->get();
                    $work_area_3 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->whereNotIn('category_unique_code', ['S03', 'Q01'])->get();

                    # ?????????????????????
                    $main_task_response = CurlHelper::init([
                        'headers' => $this->_auth,
                        'url' => "{$this->_root_url}/temporaryTask/production/main/{$request->get('mainTaskId')}",
                        'method' => CurlHelper::GET,
                    ]);
                    if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                    # ???????????????????????????
                    $work_area_accounts = DB::table('accounts')->where('deleted_at', null)->where('temp_task_position', 'ParagraphWorkArea')->groupBy('work_area')->get();
                    if ($work_area_accounts->isEmpty()) return response()->json(['message' => '????????????????????????'], 404);
                    if (!$work_area_accounts->get(1)) return response()->json(['message' => '?????????????????????????????????'], 404);
                    if (!$work_area_accounts->get(2)) return response()->json(['message' => '?????????????????????????????????'], 404);
                    if (!$work_area_accounts->get(3)) return response()->json(['message' => '??????????????????????????????'], 404);
                    $work_area_names = [1 => '???????????????', 2 => '???????????????', 3 => '????????????'];

                    $_make = function (
                        array $entire_instances,
                        int $work_area_id
                    )
                    use (
                        $request,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_names,
                        &$ret
                    ) {
                        # ???????????????????????????
                        $high_frequency_order_in = new RepairBaseHighFrequencyOrder();
                        $high_frequency_order_in->fill([
                            'serial_number' => $high_frequency_order_in_sn = CodeFacade::makeSerialNumber('HIGH_FREQUENCY_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                        ])->saveOrFail();

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '?????????????????????',
                                'intro' => '?????????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$high_frequency_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $high_frequency_order_in_sn,
                                'type' => $request->get('type'),
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ???????????????????????????
                        $high_frequency_order_out = new RepairBaseHighFrequencyOrder();
                        $high_frequency_order_out->fill([
                            'serial_number' => $high_frequency_order_out_sn = CodeFacade::makeSerialNumber('HIGH_FREQUENCY_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'in_sn' => $high_frequency_order_in_sn,
                        ])->saveOrFail();

                        # ????????????????????????????????????
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '?????????????????????',
                                'intro' => '?????????????????????',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$high_frequency_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $high_frequency_order_out_sn,
                                'type' => $request->get('type'),
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # ?????????????????????
                        $high_frequency_order_entire_instances = [];
                        $entire_instance_locks = [];
                        foreach ($entire_instances as $entire_instance) {
                            $high_frequency_order_entire_instances[] = [
                                'old_entire_instance_identity_code' => $entire_instance->identity_code,
                                'maintain_location_code' => $entire_instance->maintain_location_code,
                                'crossroad_number' => $entire_instance->crossroad_number,
                                'in_sn' => $high_frequency_order_in_sn,
                                'out_sn' => $high_frequency_order_out_sn,
                                'source' => $entire_instance->source,
                                'source_traction' => $entire_instance->source_traction,
                                'source_crossroad_number' => $entire_instance->source_crossroad_number,
                                'traction' => $entire_instance->traction,
                                'open_direction' => $entire_instance->open_direction,
                                'said_rod' => $entire_instance->said_rod,
                                'scene_workshop_name' => @$entire_instance->Station->Parent->name,
                                'station_name' => @$entire_instance->Station->name,
                            ];
                            $entire_instance_locks[$entire_instance->identity_code] = "?????????{$entire_instance->identity_code}???????????????????????????????????????????????????????????????{$high_frequency_order_in_sn}";
                        }
                        # ??????????????????
                        EntireInstanceLock::setOnlyLocks(
                            array_pluck($high_frequency_order_entire_instances, 'identity_code'),
                            ['HIGH_FREQUENCY'],
                            $entire_instance_locks,
                            function () use ($high_frequency_order_entire_instances) {
                                DB::table('repair_base_high_frequency_order_entire_instances')->insert($high_frequency_order_entire_instances);
                            });

                        return null;
                    };

                    # ???????????????
                    if ($work_area_1->isNotEmpty()) $_make($work_area_1->all(), 1);
                    # ???????????????
                    if ($work_area_2->isNotEmpty()) $_make($work_area_2->all(), 2);
                    # ????????????
                    if ($work_area_3->isNotEmpty()) $_make($work_area_3->all(), 3);

                    return response()->json(['message' => '????????????', 'main_task_id' => $request->get('mainTaskId')]);
                });

            };

            $func = strtolower($request->get('type'));
            return $$func();
        } catch (BadRequestException $e) {
            return response()->json(['message' => '????????????????????????'], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $sn
     * @return Factory|Application|RedirectResponse|View
     */
    final public function show($sn)
    {
        try {
            $new_station = function () use ($sn) {
                # ?????????????????????
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                # ???????????????
                $new_station_order = RepairBaseNewStationOrder::with([
                    'Models',
                    'InEntireInstances' => function ($InEntireInstances) {
                        return $InEntireInstances->orderBy('in_warehouse_sn')->orderBy('id', 'desc');
                    },
                    'InEntireInstances.OldEntireInstance',
                    'OutEntireInstances' => function ($OutEntireInstances) {
                        return $OutEntireInstances->orderBy('out_warehouse_sn')->orderBy('id', 'desc');
                    },
                    'OutEntireInstances.OldEntireInstance',
                ])
                    ->where('serial_number', $sn)
                    ->firstOrFail();

                # ???????????????????????????
                switch ($new_station_order->direction) {
                    default:
                    case 'IN':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_new_station_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.in_sn = ?
  and r.in_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'OUT':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_new_station_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.out_sn = ?
  and r.out_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                }

                return view('TemporaryTask.Production.Sub.show_NEW_STATION', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'new_station_order' => $new_station_order,
                    'warehouse_aggregate' => $warehouse_aggregate,
                ]);
            };

            $full_fix = function () use ($sn) {
                # ?????????????????????
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                $full_fix_order = RepairBaseFullFixOrder::with(['Models'])->where('serial_number', $sn)->firstOrFail();

                # ???????????????????????????
                switch ($full_fix_order->direction) {
                    default:
                    case 'IN':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.in_sn = ?
  and r.in_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'OUT':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.out_sn = ?
  and r.out_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'SCRAP':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.scrap_sn = ?
  and r.scrap_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                }

                return view('TemporaryTask.Production.Sub.show_FULL_FIX', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'full_fix_order' => $full_fix_order,
                ]);
            };

            $high_frequency = function () use ($sn) {
                # ?????????????????????
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # ?????????????????????
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $sn)->firstOrFail();

                return view('TemporaryTask.Production.Sub.show_HIGH_FREQUENCY', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'high_frequency_order' => $high_frequency_order,
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (BadRequestException $e) {
            return back()->with('danger', '????????????????????????');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * ???????????????????????????
     * @return JsonResponse
     */
    final public function getModel()
    {
        try {
            $full_fix = function () {
                return response()->json([
                    'message' => '????????????',
                    'entire_instances' => DB::table('repair_base_full_fix_order_entire_instances')
                        ->where('model_unique_code', request('modelUniqueCode'))
                        ->where('temporary_task_production_main_id', request('mainTaskId'))
                        ->get()
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (\Throwable $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]]);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postModel(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                switch (substr($request->get('modelUniqueCode'), 0, 1)) {
                    case 'S':
                        $model = DB::table('part_models as m')
                            ->select([
                                'm.id as id',
                                'm.name as model_name',
                                'm.unique_code as model_unique_code',
                                'em.name as entire_model_name',
                                'em.unique_code as entire_model_unique_code',
                                'c.name as category_name',
                                'c.unique_code as category_unique_code',
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code');
                        break;
                    case 'Q':
                        $model = DB::table('entire_models as m')
                            ->select([
                                'm.id as id',
                                'm.name as model_name',
                                'm.unique_code as model_unique_code',
                                'em.name as entire_model_name',
                                'em.unique_code as entire_model_unique_code',
                                'c.name as category_name',
                                'c.unique_code as category_unique_code',
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code');
                        break;
                    default:
                        return response()->json(['message' => '??????????????????']);
                        break;
                }

                $model = $model->where('m.unique_code', $request->get('modelUniqueCode'))->first();
                if (!$model) return response()->json(['message' => '???????????????'], 403);

                switch (substr($request->get('modelUniqueCode'), 0, 3)) {
                    case 'S03':
                        $work_area_id = 1;
                        break;
                    case 'Q01':
                        $work_area_id = 2;
                        break;
                    default:
                        $work_area_id = 3;
                        break;
                }

                $repeat = RepairBaseNewStationOrderModel::with([])
                    ->where('model_unique_code', $request->get('modelUniqueCode'))
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->first();
                if ($repeat) return response()->json(['message' => '????????????'], 403);

                $new_station_order_model = new RepairBaseNewStationOrderModel([
                    'model_name' => $model->model_name,
                    'model_unique_code' => $model->model_unique_code,
                    'entire_model_name' => $model->entire_model_name,
                    'entire_model_unique_code' => $model->entire_model_unique_code,
                    'category_name' => $model->category_name,
                    'category_unique_code' => $model->category_unique_code,
                    'work_area_id' => $work_area_id,
                    'number' => $request->get('number'),
                    'temporary_task_production_main_id' => $request->get('mainTaskId'),
                ]);
                $new_station_order_model->saveOrFail();

                return response()->json(['message' => '????????????', 'data' => $new_station_order_model]);
            };

            $full_fix = function () use ($request) {
                $full_fix_model = RepairBaseFullFixOrderModel::with([])
                    ->where('id', $request->get('id'))
                    ->firstOrFail();
                $full_fix_model->fill(['picked' => true])->saveOrFail();

                # ??????????????????????????????
                $entire_instances = RepairBaseFullFixOrderEntireInstance::with([])
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->where('model_unique_code', $request->get('modalUniqueCode'))
                    ->get();

                return response()->json([
                    'message' => '????????????',
                    'entire_instances' => $entire_instances,
                    'requests' => $request->all(),
                ]);
            };

            $fun_name = strtolower($request->get('type'));
            return $$fun_name();
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => '???????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 403);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function deleteModel(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                $new_station_model = RepairBaseNewStationOrderModel::with([])
                    ->where('id', $request->get('id'))
                    ->firstOrFail();

                $new_station_model->delete();

                return response()->json(['message' => '????????????']);
            };

            $full_fix = function () use ($request) {
                DB::table('repair_base_full_fix_order_entire_instances')
                    ->where('model_unique_code', $request->get('modelUniqueCode'))
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->delete();
                DB::table('repair_base_full_fix_order_models')
                    ->where('id', $request->get('id'))
                    ->update(['picked' => false]);
                return response()->json(['message' => '????????????']);
            };

            $fun_name = strtolower($request->get('type'));
            return $$fun_name();
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => '???????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 403);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ????????????
     */
    final public function getEntireInstance()
    {
        try {
            $high_frequency = function () {
                $entire_instances = EntireInstance::with([])
                    ->when(request('searchType'), function ($query) {
                        switch (request('searchType')) {
                            default:
                            case 'CODE':
                                return $query->where('identity_code', request('searchCondition'))->whereOr('serial_number', request('searchCondition'));
                                break;
                            case 'LOCATION':
                                return $query->where('maintain_location_code', request('searchCondition'))->whereOr('crossroad_number', request('searchCondition'));
                                break;
                        }
                    })
                    ->whereHas('Station', function ($Station) {
                        return $Station->where('unique_code', request('stationCode'));
                    })
                    ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN', 'TRANSFER_OUT'])
                    ->get();
                return response()->json(['message' => '????????????', 'entire_instances' => $entire_instances]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '']);
        } catch (\Throwable $e) {
            return response()->json(['message' => '????????????'], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $new_station = function () use ($request) {
                    /**
                     * ????????????
                     * @return JsonResponse
                     */
                    $in = function () use ($request) {
                        $new_station_order_out = RepairBaseNewStationOrder::with([])->where('in_sn', $request->get('serialNumber'))->first();
                        if (!$new_station_order_out) return response()->json(['message' => '??????????????????????????????'], 404);
                        $new_station_order_model_unique_codes = RepairBaseNewStationOrderModel::with([])
                            ->where('temporary_task_production_main_id', $new_station_order_out->temporary_task_production_main_id)
                            ->groupBy('model_unique_code')
                            ->get()
                            ->pluck('model_unique_code')
                            ->all();
                        $entire_instance = EntireInstance::with(['Station', 'Station.Parent'])
                            ->where('identity_code', $request->get('identityCode'))
                            ->whereIn('model_unique_code', $new_station_order_model_unique_codes)
                            ->first();
                        if (!$entire_instance) return response()->json(['message' => '???????????????'], 404);

                        try {
                            $lock_ret = EntireInstanceLock::setOnlyLock(
                                $request->get('identityCode'),
                                ['NEW_STATION'],
                                "?????????{$request->get('identityCode')}??????????????????????????????????????????????????????????????????{$request->get('serialNumber')}",
                                function () use ($request, $new_station_order_out, $entire_instance) {
                                    # ????????????????????????
                                    $new_station_entire_instance = new RepairBaseNewStationOrderEntireInstance();
                                    $new_station_entire_instance->fill([
                                        'old_entire_instance_identity_code' => $request->get('identityCode'),
                                        'maintain_location_code' => $entire_instance->maintain_location_code,
                                        'crossroad_number' => $entire_instance->crossroad_number,
                                        'source' => $entire_instance->source,
                                        'source_crossroad_number' => $entire_instance->source_crossroad_number,
                                        'source_traction' => $entire_instance->source_traction,
                                        'traction' => $entire_instance->traction,
                                        'open_direction' => $entire_instance->open_direction,
                                        'said_rod' => $entire_instance->said_rod,
                                        'in_sn' => $request->get('serialNumber'),
                                        'out_sn' => $new_station_order_out->serial_number,
                                    ])->saveOrFail();
                                });

                            return response()->json(['message' => '????????????', 'lock_ret' => $lock_ret]);
                        } catch (EntireInstanceLockException $e) {
                            return response()->json(['message' => $e->getMessage()], 403);
                        }
                    };

                    /**
                     * ????????????
                     * @return JsonResponse
                     */
                    $out = function () use ($request) {
                        $new_station_order_out = RepairBaseNewStationOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$new_station_order_out) return response()->json(['message' => '??????????????????????????????'], 404);

                        # ??????????????????????????????
                        $new_station_entire_instance = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                            ->where('out_sn', $request->get('serialNumber'))
                            ->first();
                        if (!$new_station_entire_instance) return response()->json(['message' => '???????????????'], 404);
                        $new_station_entire_instance->fill(['out_scan' => true])->saveOrFail();

                        return response()->json(['message' => '????????????']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $full_fix = function () use ($request) {
                    DB::table('repair_base_full_fix_order_entire_instances')
                        ->where('id', $request->get('id'))
                        ->update(['picked' => true]);
                    return response()->json(['message' => '????????????']);
                };

                $high_frequency = function () use ($request) {
                    $in = function () use ($request) {
                        $high_frequency_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                            ->first();
                        if (!$high_frequency_entire_instance) return response()->json(['message' => '???????????????'], 404);
                        $high_frequency_entire_instance->fill(['in_scan' => true])->saveOrFail();  # ????????????????????????

                        return response()->json(['message' => '????????????']);
                    };

                    $out = function () use ($request) {
                        $high_frequency_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('new_entire_instance_identity_code', $request->get('identityCode'))
                            ->first();
                        if (!$high_frequency_entire_instance) return response()->json(['message' => '???????????????'], 404);
                        $high_frequency_entire_instance->fill(['out_scan' => true])->saveOrFail();  # ????????????????????????

                        return response()->json(['message' => '????????????']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (EntireInstanceLockException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function deleteEntireInstance(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                $new_station_entire_instance = RepairBaseNewStationOrderEntireInstance::with([])->where('id', $request->get('id'))->first();
                if (!$new_station_entire_instance) return response()->json(['message' => '???????????????'], 404);

                EntireInstanceLock::freeLock(
                    $new_station_entire_instance->old_entire_instance_identity_code,
                    ['NEW_STATION'],
                    function () use ($new_station_entire_instance) {
                        $new_station_entire_instance->delete();
                    });

                return response()->json(['message' => '????????????']);
            };

            $full_fix = function () use ($request) {
                DB::table('repair_base_full_fix_order_entire_instances')
                    ->where('id', $request->get('id'))
                    ->update(['picked' => false]);
                return response()->json(['message' => '??????']);
            };

            $high_frequency = function () use ($request) {
                $high_frequency = RepairBaseHighFrequencyOrderEntireInstance::with([])->where('id', $request->get('id'))->first();
                if (!$high_frequency) return response()->json(['message' => '???????????????'], 404);
                $direction = strtolower($request->get('direction')) . '_scan';
                $high_frequency->fill([$direction => false])->saveOrFail();

                return response()->json(['message' => '????????????']);
            };

            $func = strtolower($request->get('type'));
            return $$func();
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ?????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouse(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $new_station = function () use ($request) {
                    $work_area_id = array_flip(Account::$WORK_AREAS)[session('account.work_area')];
                    $now = date('Y-m-d H:i:s');
                    # ?????????????????????
                    $new_station_order = RepairBaseNewStationOrder::with(['SceneWorkshop', 'Station'])->where('serial_number', $request->get('serialNumber'))->first();
                    if (!$new_station_order) return response()->json(['message' => '?????????????????????'], 404);
                    $func = strtolower(array_flip(RepairBaseNewStationOrder::$DIRECTIONS)[$new_station_order->direction]);

                    # ??????
                    $in = function () use ($request, $work_area_id, $now) {
                        # ?????????????????????
                        $new_station_entire_instances = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('in_warehouse_sn', '')
                            ->where('in_sn', $request->get('serialNumber'))
                            ->get();
                        if ($new_station_entire_instances->isEmpty()) return response()->json(['message' => '????????????????????????'], 404);
                        $new_station_entire_instance_identity_codes =$new_station_entire_instances->pluck('old_entire_instance_identity_code')->toArray();

                        # ????????????
                        $in_warehouse_sn = WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                            $new_station_entire_instance_identity_codes,
                            session('account.id'),
                            $now,
                            'NEW_STATION',
                            $request->get('connectionName'),
                            $request->get('connectionPhone')
                        );

                        # ???????????????????????????
                        RepairBaseNewStationOrderEntireInstance::with([])->whereIn('old_entire_instance_identity_code',$new_station_entire_instance_identity_codes)->update(['updated_at'=>$now,'in_warehouse_sn'=>$in_warehouse_sn]);

                        return response()->json(['message' => '????????????']);
                    };

                    # ??????
                    $out = function () use ($request, $work_area_id, $now, $new_station_order) {
                        # ?????????????????????
                        $new_station_entire_instances = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('out_warehouse_sn', '')
                            ->where('out_sn', $request->get('serialNumber'))
                            ->get();
                        if ($new_station_entire_instances->isEmpty()) return response()->json(['message' => '????????????????????????'], 404);

                        # ????????????
                        $out_warehouse_sn = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                            $new_station_entire_instances->pluck('old_entire_instance_identity_code')->all(),
                            session('account.id'),
                            $now,
                            'NORMAL',
                            $request->get('connectionName'),
                            $request->get('connectionPhone')
                        );
                        # ????????????????????????????????????
                        RepairBaseNewStationOrderEntireInstance::with([])->whereIn('id', $new_station_entire_instances->pluck('id'))->update(['out_warehouse_sn' => $out_warehouse_sn]);

                        # ???????????????????????????
                        $out_entire_instance_correspondences = [];
                        foreach ($new_station_entire_instances->pluck('old_entire_instance_identity_code') as $item)
                            $out_entire_instance_correspondences[] = [
                                'old' => $item,
                                'new' => $item,
                                'location' => '????????????',
                                'station' => @$new_station_order->Station->name,
                                'out_warehouse_sn' => $out_warehouse_sn,
                                'account_id' => session('account.id'),
                            ];
                        DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);

                        # ????????????
                        EntireInstanceLock::freeLocks($new_station_entire_instances->pluck('old_entire_instance_identity_code')->all(), ['NEW_STATION']);

                        return response()->json(['message' => '????????????']);
                    };

                    return $$func();
                };

                $high_frequency = function () use ($request) {
                    $now = date('Y-m-d H:i:s');
                    $in = function () use ($request, $now) {
                        # ??????????????????????????????
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$high_frequency_order) return response()->json(['message' => '?????????????????????????????????????????????'], 404);

                        # ?????????????????????????????????????????????????????????????????????
                        $high_frequency_entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('in_scan', true)
                            ->where('in_warehouse_sn', '')
                            ->where('in_sn', $request->get('serialNumber'))
                            ->get();
                        if (!$high_frequency_entire_instances) return response()->json(['message' => '?????????????????????'], 404);

                        # ???????????????
                        $warehouse_report = new WarehouseReport();
                        $warehouse_report->fill([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'processor_id' => session('account.id'),
                            'processed_at' => $now,
                            'connection_name' => $request->get('connectionName'),
                            'connection_phone' => $request->get('connectionPhone'),
                            'type' => 'HIGH_FREQUENCY',
                            'direction' => 'IN',
                            'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('WAREHOUSE_IN'),
                            'work_area_id' => $high_frequency_order->work_area_id,
                        ])->saveOrFail();

                        $warehouse_report_entire_instances = [];
                        $entire_instance_logs = [];
                        foreach ($high_frequency_entire_instances as $high_frequency_entire_instance) {
                            # ????????????
                            $warehouse_report_entire_instances[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'warehouse_report_serial_number' => $warehouse_report_sn,
                                'entire_instance_identity_code' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                            ];
                            # ????????????
                            $entire_instance_logs[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'name' => '??????????????????',
                                'description' => 'IN',
                                'entire_instance_identity_code' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$warehouse_report_sn}?show_type=D&direction=IN",
                            ];
                        }
                        DB::table('warehouse_report_entire_instances')->insert($warehouse_report_entire_instances);  # ???????????????
                        EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  # ??????
                        EntireInstanceLock::freeLocks(array_pluck($entire_instance_logs, 'old_entire_instance_identity_code'), ['HIGH_FREQUENCY']);  # ????????????
                        DB::table('entire_instances')->whereIn('identity_code', $high_frequency_entire_instances->pluck('old_entire_instance_identity_code')->all())->update(['updated_at' => $now, 'status' => 'FIXED']);  # ??????????????????
                        DB::table('repair_base_high_frequency_order_entire_instances')->whereIn('id', $high_frequency_entire_instances->pluck('id')->all())->update(['updated_at' => $now, 'in_warehouse_sn' => $warehouse_report_sn]);  # ?????????????????????????????????

                        return response()->json(['message' => '????????????']);
                    };

                    $out = function () use ($request, $now) {
                        # ??????????????????????????????
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$high_frequency_order) return response()->json(['message' => '?????????????????????????????????????????????'], 404);

                        # ?????????????????????????????????????????????????????????????????????
                        $high_frequency_entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('out_scan', true)
                            ->where('out_warehouse_sn', '')
                            ->where('out_sn', $request->get('serialNumber'))
                            ->get();
                        if (!$high_frequency_entire_instances) return response()->json(['message' => '?????????????????????'], 404);

                        # ???????????????
                        $warehouse_report = new WarehouseReport();
                        $warehouse_report->fill([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'processor_id' => session('account.id'),
                            'processed_at' => $now,
                            'connection_name' => $request->get('connectionName'),
                            'connection_phone' => $request->get('connectionPhone'),
                            'type' => 'HIGH_FREQUENCY',
                            'direction' => 'OUT',
                            'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                            'work_area_id' => $high_frequency_order->work_area_id,
                        ])->saveOrFail();

                        $warehouse_report_entire_instances = [];
                        $entire_instance_logs = [];
                        $out_entire_instance_correspondences = [];
                        foreach ($high_frequency_entire_instances as $high_frequency_entire_instance) {
                            # ????????????
                            $warehouse_report_entire_instances[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'warehouse_report_serial_number' => $warehouse_report_sn,
                                'entire_instance_identity_code' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                            ];
                            # ????????????
                            $entire_instance_logs[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'name' => '??????????????????',
                                'description' => 'OUT',
                                'entire_instance_identity_code' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$warehouse_report_sn}?show_type=D&direction=OUT",
                            ];
                            # ???????????????????????????
                            $out_entire_instance_correspondences[] = [
                                'old' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                                'new' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                                'location' => $high_frequency_entire_instance->maintain_location_code ?
                                    $high_frequency_entire_instance->maintain_location_code :
                                    $high_frequency_entire_instance->maintain_location_code .
                                    $high_frequency_entire_instance->crossroad_number .
                                    $high_frequency_entire_instance->source .
                                    $high_frequency_entire_instance->source_traction .
                                    $high_frequency_entire_instance->source_crossroad_number .
                                    $high_frequency_entire_instance->traction .
                                    $high_frequency_entire_instance->open_direction .
                                    $high_frequency_entire_instance->said_rod,
                                'station' => $high_frequency_entire_instance->station_name,
                                'out_warehouse_sn' => $warehouse_report_sn,
                                'account_id' => session('account.id'),
                            ];
                        }
                        DB::table('warehouse_report_entire_instances')->insert($warehouse_report_entire_instances);  # ???????????????
                        EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  # ??????
                        EntireInstanceLock::freeLocks(array_pluck($entire_instance_logs, 'new_entire_instance_identity_code'), ['HIGH_FREQUENCY']);  # ????????????
                        DB::table('entire_instances')->whereIn('identity_code', $high_frequency_entire_instances->pluck('new_entire_instance_identity_code')->all())->update(['updated_at' => $now, 'status' => 'TRANSFER_IN']);  # ??????????????????
                        DB::table('repair_base_high_frequency_order_entire_instances')->whereIn('id', $high_frequency_entire_instances->pluck('id')->all())->update(['updated_at' => $now, 'out_warehouse_sn' => $warehouse_report_sn]);  # ?????????????????????????????????
                        DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);  # ???????????????????????????

                        return response()->json(['message' => '????????????']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });


        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * ?????????????????????
     * @param Request $request
     * @return mixed
     */
    final public function putFinish(Request $request)
    {
        $new_station = function () use ($request) {
            try {
                $new_station_order = RepairBaseNewStationOrder::with([])->where('serial_number', $request->get('newStationOrderSn'))->firstOrFail();
                $new_station_order->fill(['status' => 'DONE'])->saveOrFail();

                $response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/finish/{$request->get('subTaskId')}",
                    'method' => CurlHelper::PUT,
                    'contents' => [
                        'sender_id' => session('account.id'),
                        'sender_affiliation' => env('ORGANIZATION_CODE'),
                        'finish_message' => $request->get('finishMessage'),
                    ]
                ]);
                if ($response['code'] > 399) return response()->json($response['body'], $response['code']);

                return response()->json(['message' => '????????????']);
            } catch (BadRequestException $e) {
                return response()->json(['message' => '????????????????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
            } catch (\Throwable $e) {
                return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
            }
        };

        $func = strtolower($request->get('type'));
        return $$func();
    }

    /**
     * ?????????????????????????????????
     * @param int $main_task_id
     * @return Factory|JsonResponse|RedirectResponse|View
     */
    final public function getMakeSubTask105(int $main_task_id)
    {
        try {
            # ?????????????????????
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], 500);
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);

            # ??????????????????
            $accounts = DB::table('accounts')->pluck('nickname', 'id');

            return view('TemporaryTask.Production.Sub.create', [
                'main_task' => $main_task_response['body']['data'],
                'accounts' => $accounts,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '????????????????????????');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * ???????????????????????????
     *
     * @param Request $request
     * @param int $main_task_id
     * @return JsonResponse|RedirectResponse
     */
    final public function postMakeSubTask105(Request $request, int $main_task_id)
    {
        try {
            # ?????????????????????
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], 500);
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);
            $models = $main_task_response['body']['data']['models'];

            # ??????????????????
            $sub_tasks = [
                '???????????????' => [
                    'account' => null,
                    'contents' => null,
                ],
                '???????????????' => [
                    'account' => null,
                    'contents' => null,
                ],
                '????????????' => [
                    'account' => null,
                    'contents' => null,
                ],
            ];

            # ???????????????
            if (isset($models['???????????????']) && !empty($models['???????????????'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 1)->where('temp_task_position', 'WorkArea')->first();  # ??????????????????
                if (!$account) return response()->json(['message' => '??????????????????????????????'], 404);
                $sub_tasks['???????????????']['account'] = $account->id;
                $sub_tasks['???????????????']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 1,
                    'receiver_work_area_name' => '???????????????',
                ];
            }
            if (isset($models['???????????????']) && !empty($models['???????????????'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 2)->where('temp_task_position', 'WorkArea')->first();  # ??????????????????
                if (!$account) return response()->json(['message' => '??????????????????????????????'], 404);
                $sub_tasks['???????????????']['account'] = $account->id;
                $sub_tasks['???????????????']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 2,
                    'receiver_work_area_name' => '???????????????',
                ];
            }
            if (isset($models['????????????']) && !empty($models['????????????'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 3)->where('temp_task_position', 'WorkArea')->first();  # ??????????????????
                if (!$account) return response()->json(['message' => '???????????????????????????'], 404);
                $sub_tasks['????????????']['account'] = $account->id;
                $sub_tasks['????????????']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 3,
                    'receiver_work_area_name' => '????????????',
                ];
            }

            $ret = '';
            foreach ($sub_tasks as $work_area_name => $sub_task) {
                if ($sub_task['account'] !== null) {
                    $response = CurlHelper::init([
                        'headers' => $this->_auth,
                        'url' => "{$this->_root_url}/{$this->_spas_url}",
                        'method' => CurlHelper::POST,
                        'contents' => $sub_task['contents'],
                    ]);
                    $ret .= $response['code'] > 399 ? "{$work_area_name}?????????\r\n" : "{$work_area_name}?????????\r\n";
                    $sub_tasks[$work_area_name]['response'] = $response;
                }
            }

            return response()->json(['message' => $ret, 'sub_tasks' => $sub_tasks]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '????????????????????????');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * ??????????????????
     * @param int $sub_task_id
     * @return Factory|RedirectResponse|View
     */
    final public function getProcess(int $sub_task_id)
    {
        try {
            # ?????????????????????
            $sub_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$sub_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['message_id' => request('message_id')],
            ]);
            if ($sub_task_response['code'] > 399) return back()->with('danger', '???????????????????????????');

            # ?????????????????????
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['message_id' => request('message_id')],
            ]);
            if ($main_task_response['code'] > 399) return back()->with('danger', '???????????????????????????');
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);
            $model_codes = [];
            foreach ($main_task_response['body']['data']['models'] as $model) foreach ($model as $item) $model_codes[] = $item['code3'];

            # ???????????????????????????
            $model_codes_str = implode("', '", $model_codes);
            $model_codes_fixed_count = [];
            foreach (DB::select("select model_unique_code, count(model_unique_code) as c from temporary_task_production_sub_instances
where sub_task_id = ?
and work_area_name = ?
and model_unique_code in ('{$model_codes_str}')
group by model_unique_code", [$sub_task_id, session('account.work_area')]) as $item) {
                $model_codes_fixed_count[$item->model_unique_code] = $item->c;
            }

            # ???????????????????????????????????????????????????
            $entire_instances = DB::table('temporary_task_production_sub_instances as t')
                ->select(['t.entire_instance_identity_code', 'ei.model_name', 't.id'])
                ->join(DB::raw('entire_instances as ei'), 't.entire_instance_identity_code', '=', 'ei.identity_code')
                ->where('t.sub_task_id', $sub_task_id)
                ->orderByDesc('t.id')
                ->where('t.work_area_name', session('account.work_area'))
                ->paginate(50);

            return view('TemporaryTask.Production.Sub.process', [
                'sub_task' => $sub_task_response['body']['data'],
                'main_task' => $main_task_response['body']['data'],
                'entire_instances' => $entire_instances,
                'model_codes' => TextHelper::toJson($model_codes),
                'model_codes_fixed_count' => $model_codes_fixed_count,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '????????????????????????');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param int $sub_task_id
     * @return JsonResponse
     */
    final public function postProcess(Request $request, int $sub_task_id)
    {
        try {
            # ????????????????????????
            $repeat = DB::table('temporary_task_production_sub_instances')
                ->where('sub_task_id', $sub_task_id)
                ->where('entire_instance_identity_code', $request->get('entireInstanceIdentityCode'))
                ->first();
            if ($repeat) return response()->json(['message' => '????????????'], 403);

            # ??????????????????
            $entire_instance = EntireInstance::with([])
                ->where('identity_code', $request->get('entireInstanceIdentityCode'))
                // ->where('status', 'FIXED')
                ->firstOrFail(['identity_code', 'model_unique_code', 'model_name']);

            # ??????????????????????????????????????????
            if (!in_array($entire_instance['model_unique_code'], json_decode($request->get('modelCodes'), true))) return response()->json(['message' => "?????????????????????{$entire_instance['model_name']}?????????"], 404);

            # ??????????????????
            DB::table('temporary_task_production_sub_instances')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'main_task_id' => $request->get('mainTaskId'),
                'sub_task_id' => $sub_task_id,
                'entire_instance_identity_code' => $entire_instance['identity_code'],
                'work_area_name' => session('account.work_area'),
                'model_unique_code' => $entire_instance['model_unique_code'],
                'model_name' => $entire_instance['model_name'],
            ]);
            return response()->json(['message' => '????????????']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????????????????????????????'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * ?????????????????????????????????
     * @param $id
     * @return JsonResponse
     */
    final public function deleteCutEntireInstance($id)
    {
        DB::table('temporary_task_production_sub_instances')->where('id', $id)->delete();
        return response()->json(['message' => '????????????']);
    }

    /**
     * ?????? ??????
     */
    final public function getPlan(int $sub_task_id)
    {
        if (request('download') == 1) {
            try {
                $cell_key = [
                    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                    'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                    'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
                    'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
                ];

                $work_area = session('account.work_area');

                ExcelWriteHelper::download(
                    function ($excel) use ($cell_key, $sub_task_id) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        $fs = FileSystem::init(__FILE__);

                        # ??????????????????
                        $accounts = DB::table('accounts')
                            ->where('deleted_at', null)
                            ->where('work_area', array_flip(Account::$WORK_AREAS)[session('account.work_area')])
                            ->where('supervision', 0)
                            ->pluck('nickname', 'id');
                        # ??????????????????
                        $plan = $fs->setPath(storage_path("app/????????????/{$sub_task_id}.json"))->fromJson();

                        # ?????????
                        $total_plan = 0;

                        # ????????????
                        $col = 2;
                        $current_sheet->setCellValue("A1", "??????/??????");
                        $current_sheet->setCellValue("B1", "??????");
                        $current_sheet->getColumnDimension('A')->setWidth(20);
                        foreach ($accounts as $account_nickname) {
                            $current_sheet->setCellValue("{$cell_key[$col]}1", $account_nickname);
                            $current_sheet->getColumnDimension("{$cell_key[$col]}")->setWidth(15);
                            $col++;
                        }

                        # ??????????????????
                        $row = 2;
                        foreach ($plan as $item) {
                            $col = 2;
                            $current_sheet->setCellvalue("A{$row}", $item['name3']);  # ?????????
                            $current_sheet->setCellValue("B{$row}", $item['plan']);  # ?????????
                            $total_plan += $item['plan'];
                            foreach ($accounts as $account_nickname) {
                                $current_sheet->setCellValue("{$cell_key[$col]}{$row}", key_exists($account_nickname, $item['accounts']) ? intval($item['accounts'][$account_nickname]) : 0);
                                $col++;
                            }
                            $row++;
                        }
                        return $excel;
                    },
                    "{$work_area}?????????????????????????????????"
                );
            } catch (Exception $exception) {
                return back()->with('info', $exception->getMessage());
            }
        }

        # ???????????????
        $curl = new Curl();
        $curl->setHeaders([
            'Username' => $this->_spas_username,
            'Password' => $this->_spas_password,
        ]);
        $curl->get("{$this->_root_url}/{$this->_spas_url}/{$sub_task_id}");
        if ($curl->error) return back()->with('danger', $curl->response);
        $sub_task = (array)$curl->response->data;

        # ???????????????
        $curl->get("{$this->_root_url}/temporaryTask/production/main/{$sub_task['main_task']}");
        if ($curl->error) return back()->with('danger', $curl->response);
        $main_task = (array)$curl->response->data;
        $curl->close();

        $main_task['models'] = json_decode($main_task['models'], true);

        # ??????????????????
        $accounts = DB::table('accounts')
            ->where('deleted_at', null)
            ->where('work_area', array_flip(Account::$WORK_AREAS)[session('account.work_area')])
            ->where('supervision', 0)
            ->pluck('nickname', 'id');

        # ????????????
        $plan_file = storage_path("app/????????????/{$sub_task['id']}.json");
        if (is_file($plan_file)) {
            $plan = json_decode(file_get_contents($plan_file), true);
        } else {
            foreach ($main_task['models'][session('account.work_area')] as $item) $plan[$item['code3']] = array_merge($item, ['accounts' => new stdClass()]);
        }

        // foreach($plan as $item){
        //     dump($item);
        // }
        // dd();

        # ????????????
        $account_plan_total = [];
        foreach ($accounts as $account_nickname) $account_plan_total[$account_nickname] = 0;
        foreach ($plan as $item) foreach ($item['accounts'] as $account_nickname => $value) $account_plan_total[$account_nickname] += $value;

        return view('TemporaryTask.Production.Sub.plan', [
            'main_task' => $main_task,
            'sub_task' => $sub_task,
            'accounts' => $accounts,
            'plan' => $plan,
            'plan_as_json' => json_encode($plan),
            'mission_as_json' => json_encode($main_task['models'][$sub_task['receiver_work_area_name']]),
            'account_plan_total' => $account_plan_total,
        ]);
    }

    /**
     * ?????? ??????
     */
    final public function postPlan(Request $request, int $sub_task_id)
    {
        if (!is_dir(storage_path("app/????????????"))) mkdir(storage_path("app/????????????"));
        $save_ret = file_put_contents(storage_path("app/????????????/{$sub_task_id}.json"), json_encode($request->all(), 256));
        return response()->json(['message' => '????????????']);
    }

    /**
     * ??????Excel
     */
    final public function makeExcelWithPlan(int $sub_task_id)
    {
    }

    /**
     * ??????????????????
     * @param int $sub_task_id
     * @return JsonResponse
     */
    final public function putChecked(int $sub_task_id)
    {
        try {

            $update_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/checked/{$sub_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::PUT,
            ]);
            if ($update_response['code'] != 200) return response()->json($update_response['body'], $update_response['code']);

            return response()->json(['message' => '????????????']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '????????????????????????'], 500);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * ????????????
     * @param string $serial_number
     * @return JsonResponse
     */
    final public function getPrintLabel(string $serial_number)
    {
        try {
            return DB::transaction(function () use ($serial_number) {
                $new_station = function () use ($serial_number) {
                    $in = function () use ($serial_number) {
                        $entire_instances = RepairBaseNewStationOrderEntireInstance::with([
                            'InOrder',
                            'OldEntireInstance'
                        ])
                            ->when(
                                request('search_content'),
                                function ($query) {
                                    return $query
                                        ->whereHas('OldEntireInstance', function ($EntireInstance) {
                                            $EntireInstance->where('identity_code', request('search_content'))
                                                ->orWhere('serial_number', request('search_content'));
                                        });
                                }
                            )
                            ->where('in_sn', $serial_number)
                            ->paginate();

                        return view('TemporaryTask.Production.Sub.printLabelIn_NEW_STATION', [
                            'entire_instances' => $entire_instances,
                            'in_sn' => $serial_number,
                        ]);
                    };

                    $func = strtolower(request('direction'));
                    return $$func();
                };

                $high_frequency = function () use ($serial_number) {
                    $in = function () use ($serial_number) {
                        $entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'InOrder',
                            'OldEntireInstance'
                        ])
                            ->when(
                                request('search_content'),
                                function ($query) {
                                    return $query
                                        ->whereHas('OldEntireInstance', function ($EntireInstance) {
                                            $EntireInstance->where('identity_code', request('search_content'))
                                                ->orWhere('serial_number', request('search_content'));
                                        });
                                }
                            )
                            ->where('in_sn', $serial_number)
                            ->paginate();

                        return view('TemporaryTask.Production.Sub.printLabelIn_HIGH_FREQUENCY', [
                            'entire_instances' => $entire_instances,
                            'in_sn' => $serial_number,
                        ]);
                    };

                    $out = function () use ($serial_number) {
                        # ???????????????
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])
                            ->where('serial_number', $serial_number)
                            ->first();
                        if (!$high_frequency_order) return back()->with('danger', '???????????????????????????');

                        $entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'OldEntireInstance',
                            'NewEntireInstance',
                        ])
                            ->where('out_sn', $serial_number)
                            ->get();

                        $plan_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_high_frequency_order_entire_instances` as `oei`
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `oei`.out_sn = ?
group by `ei`.`model_name`",
                            [$serial_number]))
                            ->pluck('aggregate', 'model_name')
                            ->sum();

                        $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($serial_number);
                        $usable_entire_instance_sum = $usable_entire_instances->sum(function ($value) {
                            return $value->count();
                        });

                        $old_count = DB::table('repair_base_high_frequency_order_entire_instances')->where('out_sn', $serial_number)->count();
                        $new_count = DB::table('repair_base_high_frequency_order_entire_instances')->where('out_sn', $serial_number)->where('new_entire_instance_identity_code', '<>', '')->count();
                        $is_all_bound = (($new_count === $old_count) && ($old_count > 0));  # ????????????????????????

                        return view('TemporaryTask.Production.Sub.printLabelOut_HIGH_FREQUENCY', [
                            'entire_instances' => $entire_instances,
                            'usable_entire_instances' => $usable_entire_instances,
                            'out_sn' => $serial_number,
                            'is_all_bound' => $is_all_bound,
                            'plan_sum' => $plan_sum,
                            'usable_entire_instance_sum' => $usable_entire_instance_sum,
                        ]);
                    };

                    $func = strtolower(request('direction'));
                    return $$func();
                };

                $func = strtolower(request('type'));
                return $$func();
            });
        } catch (\Throwable $th) {
            dd($th->getMessage(), $th->getFile(), $th->getLine());
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ???????????????????????????????????????
     * @param string $out_sn
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    final private function _getUsableEntireInstancesWithOutSn(string $out_sn)
    {
        $must_warehouse_location = false;  # ?????????????????????

        $out_order = DB::table('repair_base_high_frequency_orders')
            ->where('serial_number', $out_sn)
            ->first(['in_sn']);
        if (!$out_order) throw new \Exception('??????????????????', 404);
        if (!$out_order->in_sn) throw new \Exception('????????????????????????', 404);

        # ????????????????????????
        return DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.model_name', 'ei.location_unique_code'])
            ->where('status', 'FIXED')
            ->when($must_warehouse_location, function ($query) {
                return $query
                    ->where('location_unique_code', '<>', null)
                    ->where('location_unique_code', '<>', '');
            })
            ->whereNotIn('identity_code', DB::table('entire_instance_locks')
                ->where('lock_name', 'HIGH_FREQUENCY')
                ->pluck('entire_instance_identity_code')
                ->toArray())
            ->whereNotIn('identity_code', DB::table('repair_base_high_frequency_order_entire_instances')->pluck('old_entire_instance_identity_code')->all())
            ->whereIn('model_name', DB::table('repair_base_high_frequency_order_entire_instances as oei')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('oei.in_sn', $out_order->in_sn)
                ->groupBy('ei.model_name')
                ->pluck('ei.model_name')
                ->toArray())
            ->get()
            ->groupBy('model_name');
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $old_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([
                        'OldEntireInstance'
                    ])
                        ->where('out_sn', $request->get('outSn'))
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->firstOrFail();

                    $usable_entire_instance = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'))
                        ->get($old_entire_instance->OldEntireInstance->model_name);
                    if (is_null($usable_entire_instance)) return response()->json(['message' => '????????????????????????'], 404);

                    # ????????????
                    EntireInstanceLock::setOnlyLock(
                        $usable_entire_instance->first()->identity_code,
                        ['HIGH_FREQUENCY'],
                        "?????????{$usable_entire_instance}??????????????????????????????????????????????????????{$request->get('outSn')}",
                        function () use ($old_entire_instance, $usable_entire_instance) {
                            $old_entire_instance->fill(['new_entire_instance_identity_code' => $usable_entire_instance->first()->identity_code])->saveOrFail();
                        }
                    );

                    return response()->json(['message' => '????????????']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * ???????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstances(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'));
                    if (is_null($usable_entire_instances)) return response()->json(['message' => '????????????????????????'], 404);

                    $out_order = DB::table('repair_base_high_frequency_orders')->where('serial_number', $request->get('outSn'))->first(['in_sn']);
                    if (!$out_order) return response()->json(['??????????????????????????????????????????'], 404);

                    $old_entire_instances = DB::table('repair_base_high_frequency_order_entire_instances as oei')
                        ->select(['ei.identity_code', 'ei.model_name'])
                        ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                        ->where('in_sn', $out_order->in_sn)
                        ->where('new_entire_instance_identity_code', '')
                        ->get()
                        ->groupBy('model_name')
                        ->all();

                    $new_entire_instance_identity_codes = [];
                    $entire_instance_locks = [];
                    foreach ($old_entire_instances as $model_name => $entire_instances) {
                        foreach ($entire_instances as $entire_instance) {
                            if ($usable_entire_instances->get($entire_instance->model_name)) {
                                if (!$usable_entire_instance = @$usable_entire_instances->get($entire_instance->model_name)->shift()->identity_code) continue;
                                DB::table('repair_base_high_frequency_order_entire_instances')
                                    ->where('in_sn', $out_order->in_sn)
                                    ->where('old_entire_instance_identity_code', $entire_instance->identity_code)
                                    ->update(['new_entire_instance_identity_code' => $usable_entire_instance]);

                                $new_entire_instance_identity_codes[] = $usable_entire_instance;
                                $entire_instance_locks[$usable_entire_instance] = "?????????{$usable_entire_instance}??????????????????????????????????????????????????????{$out_order->serial_number}";
                            }
                        }
                    }

                    # ????????????
                    EntireInstanceLock::setOnlyLocks($new_entire_instance_identity_codes, ['HIGH_FREQUENCY'], $entire_instance_locks);

                    return response()->json(['message' => '????????????????????????']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $old_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                        ->where('out_sn', $request->get('outSn'))
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->firstOrFail();

                    # ?????????????????????????????????????????????
                    if ($old_entire_instance->new_entire_instance_identity_code) {
                        EntireInstanceLock::freeLock(
                            $old_entire_instance->new_entire_instance_identity_code,
                            ['HIGH_FREQUENCY'],
                            function () use ($request, $old_entire_instance) {
                                # ????????????
                                EntireInstanceLock::setOnlyLock(
                                    $request->get('newIdentityCode'),
                                    ['HIGH_FREQUENCY'],
                                    "?????????{$request->get('newIdentityCode')}??????????????????????????????????????????????????????{$request->get('outSn')}",
                                    function () use ($request, $old_entire_instance) {
                                        $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
                                    }
                                );
                            }
                        );
                        return response()->json(['message' => '????????????']);
                    } else {
                        # ????????????
                        EntireInstanceLock::setOnlyLock(
                            $request->get('newIdentityCode'),
                            ['HIGH_FREQUENCY'],
                            "?????????{$request->get('newIdentityCode')}??????????????????????????????????????????????????????{$request->get('outSn')}",
                            function () use ($request, $old_entire_instance) {
                                $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
                            }
                        );
                        return response()->json(['message' => '????????????']);
                    }
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '??????????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $ei = RepairBaseHighFrequencyOrderEntireInstance::with([])
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->where('out_sn', $request->get('outSn'))
                        ->firstOrFail();

                    # ????????????
                    EntireInstanceLock::freeLock(
                        $ei->new_entire_instance_identity_code,
                        ['HIGH_FREQUENCY'],
                        function () use ($ei) {
                            $ei->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                        }
                    );
                    return response()->json(['message' => '????????????']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * ???????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstances(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $out_order = RepairBaseHighFrequencyOrder::with([
                        'OutEntireInstances',
                    ])
                        ->where('serial_number', $request->get('outSn'))
                        ->first();
                    if (!$out_order) return response()->json(['??????????????????????????????????????????'], 404);

                    # ????????????
                    $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
                    $ret = EntireInstanceLock::freeLocks(
                        $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all(),
                        ['HIGH_FREQUENCY'],
                        function () use ($out_order) {
                            DB::table('repair_base_high_frequency_order_entire_instances')
                                ->where('in_sn', $out_order->in_sn)
                                ->update(['new_entire_instance_identity_code' => '']);
                        }
                    );

                    return response()->json(['message' => '????????????', 'details' => $ret]);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '????????????', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }
}
