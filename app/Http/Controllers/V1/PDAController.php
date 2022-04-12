<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\CheckDeviceException;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\JWTFacade;
use App\Facades\ModelBuilderFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\API\V1\RegisterRequest;
use App\Model\Account;
use App\Model\Area;
use App\Model\CheckPlan;
use App\Model\CheckPlanEntireInstance;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\Maintain;
use App\Model\PartInstance;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\RepairBaseBreakdownOrder;
use App\Model\Shelf;
use App\Model\StationInstallLocationRecord;
use App\Model\Storehouse;
use App\Model\TakeStock;
use App\Model\TaskStationCheckEntireInstance;
use App\Model\TaskStationCheckOrder;
use App\Model\CheckProject;
use App\Model\Tier;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\V250WorkshopOutEntireInstances;
use App\Model\Warehouse;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PDAController extends Controller
{
    private $_statuses = null;

    public function __construct()
    {
        $this->_statuses = EntireInstance::$STATUSES;
    }

    /**
     * 注册
     * @param Request $request
     * @return JsonResponse
     */
    final public function postRegister(Request $request): JsonResponse
    {
        try {
            $v = Validator::make($request->all(), RegisterRequest::$RULES, RegisterRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            $work_area = null;
            $workshop_unique_code = null;
            if ($request->get('work_area_unique_code')) {
                $work_area = WorkArea::with(['Workshop'])->where('unique_code', $request->get('work_area_unique_code'))->first();
                if (!$work_area) return JsonResponseFacade::errorEmpty('工区不存在');
                if (!$work_area->Workshop) return JsonResponseFacade::errorEmpty("工区：{$work_area->name}没有找到所属的现场车间");
                $workshop_unique_code = $work_area->workshop_unique_code;
            }
            $station = null;
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
                if (!$station) return JsonResponseFacade::errorEmpty('车站不存在');
                if (!$station->Parent) return JsonResponseFacade::errorEmpty("车站：{$station->name}没有找到所属的现场车间");
                if ($request->get('work_area_unique_code')) {
                    if ($station->parent_unique_code != $work_area->workshop_unique_code) return JsonResponseFacade::errorForbidden("工区：{$work_area->name}和车站：{$station->name}属于不同的现场车间", $station, $work_area);
                }
                $workshop_unique_code = $station->parent_unique_code;
            }

            $account = Account::with([])
                ->create(array_merge($request->all(), [
                    'password' => bcrypt($request->get('password')),
                    'status_id' => 1,
                    'open_id' => md5(time() . $request->get('account') . mt_rand(1000, 9999)),
                    'work_area_unique_code' => @$work_area ? $work_area->unique_code : '',
                    'station_unique_code' => @$station ? $station->unique_code : '',
                    'workshop_unique_code' => $workshop_unique_code ?? '',
                    'identity_code' => mt_rand(0001, 9999),
                    'work_area' => 0,
                    'workshop_code' => env('ORGANIZATION_CODE'),
                    'rank' => $request->get('rank') ?? 'None',
                ]));

            // 分配权限到用户
            DB::table('pivot_role_accounts')->insert(['rbac_role_id' => 1, 'account_id' => $account->id]);

            return JsonResponseFacade::created([], '注册成功');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取车站列表
     * @return JsonResponse
     */
    final public function getStations()
    {
        try {
            return JsonResponseFacade::data(['stations' => ModelBuilderFacade::init(request(), Maintain::with([]))->all()]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (\Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取工区
     * @return JsonResponse
     */
    final public function getWorkAreas()
    {
        try {
            $work_areas = ModelBuilderFacade::init(request(), WorkArea::with([]))
                ->extension(function ($WorkArea) {
                    return $WorkArea->where('paragraph_unique_code', env('ORGANIZATION_CODE'));
                })
                ->all();

            return JsonResponseFacade::data(['work_areas' => $work_areas]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取职务列表
     * @return mixed
     */
    final public function getRanks()
    {
        try {
            return JsonResponseFacade::data(['ranks' => Account::$RANKS]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 登录
     * @param Request $request
     * @return JsonResponse
     */
    final public function postLogin(Request $request): JsonResponse
    {
        try {
            // 表单验证
            $v = Validator::make($request->all(), LoginRequest::$RULES, LoginRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            // 验证密码
            $account = Account::with([])->where('account', $request->get('account'))->firstOrFail();
            if (!Hash::check($request->get('password'), $account->password)) return JsonResponseFacade::errorUnauthorized('账号或密码不匹配');

            // 生成jwt
            $payload = $account->toArray();
            unset($payload['password']);
            $jwt = JWTFacade::generate($payload);

            return JsonResponseFacade::created(
                [
                    'jwt' => $jwt,
                    'account' => $account
                ],
                '登录成功'
            );
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('用户不存在');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 签名
     * @param Request $request
     * @return mixed
     */
    final public function anyMakeSign(Request $request): JsonResponse
    {
        try {
            $ret = [];
            $secretKey = 'TestSecretKey';
            $ret['step1'] = "测试SecretKey:{$secretKey}";
            $ret['step1'] = [
                '测试SecretKey',
                $secretKey,
            ];
            $data = $request->all();
            $ret['step2'] = [
                '接收到的参数',
                $data,
            ];
            $data = array_filter($data);
            $ret['step3'] = [
                '去掉空值',
                $data,
            ];
            $data['secret_key'] = $secretKey;
            $ret['step4'] = [
                '参数中加入SecretKey',
                $data
            ];
            ksort($data);
            $ret['step5'] = [
                '根据key进行正序排序',
                $data,
            ];
            $urlQuery = http_build_query($data);
            $ret['step6'] = [
                '拼接urlQuery',
                $urlQuery,
            ];
            $md5 = md5($urlQuery);
            $ret['step7'] = [
                'md5加密',
                $md5,
            ];
            $sign = strtoupper($md5);
            $ret['step8'] = [
                '转大写',
                $sign,
            ];
            return JsonResponseFacade::data($ret);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 测试验签
     * @param Request $request
     * @return mixed
     */
    final public function anyCheckSign(Request $request)
    {
        try {
            $ret = [];
            $data = $request->all();
            $ret['data'] = $data;
            $account = Account::with([])->where('access_key', $request->header('Access-Key'))->firstOrFail();
            $secretKey = $account->secret_key;
            $ret['secret_key'] = $secretKey;
            $data['secret_key'] = $secretKey;
            ksort($data);
            $ret['sorted'] = $data;
            $query = http_build_query($data);
            $ret['query'] = $query;
            $md5 = md5($query);
            $ret['md5'] = $md5;
            $sign = strtoupper($md5);
            $ret['sign'] = $sign;
            $ret['ret'] = boolval($sign == $request->header('Sign'));

            return JsonResponseFacade::data($ret);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('用户没有找到');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 基础数据->车间车站
     * @return JsonResponse
     */
    final public function getMaintains(): JsonResponse
    {
        try {
            $maintains = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->get()->toArray();
            foreach ($maintains as $v) {
                $station[] = [
                    'unique_code' => $v->unique_code,
                    'name' => $v->name,
                    'subset' => DB::table('maintains')->where('parent_unique_code', $v->unique_code)->where('type', 'STATION')->get(['unique_code', 'name'])->toArray()
                ];
            }
            return JsonResponseFacade::data($station);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 基础数据->种类型
     * @return JsonResponse
     */
    final public function getTypes(): JsonResponse
    {
        try {
            foreach (DB::table('categories')->get()->toArray() as $categoryKey => $category) {
                $types[$categoryKey] = [
                    'name' => $category->name,
                    'unique_code' => $category->unique_code,
                    'subset' => []
                ];
                foreach (DB::table('entire_models')->where('category_unique_code', $category->unique_code)->where('is_sub_model', 0)->get()->toArray() as $entireModelsKey => $entireModels) {
                    $types[$categoryKey]['subset'][$entireModelsKey] = [
                        'name' => $entireModels->name,
                        'unique_code' => $entireModels->unique_code,
                        'subset' => []
                    ];

                    $types[$categoryKey]['subset'][$entireModelsKey]['subset'] = DB::table('entire_models')->where('parent_unique_code', $entireModels->unique_code)->where('is_sub_model', 1)->get(['name', 'unique_code'])->toArray();

                    foreach (DB::table('part_models')->where('entire_model_unique_code', $entireModels->unique_code)->get()->toArray() as $partModelsKey => $partModels) {
                        $types[$categoryKey]['subset'][$entireModelsKey]['subset'][$partModelsKey] = [
                            'name' => $partModels->name,
                            'unique_code' => $partModels->unique_code,
                        ];
                    }
                }
            }
            return JsonResponseFacade::data($types);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 基础数据->仓库位置
     * @return JsonResponse
     */
    final public function getLocations(): JsonResponse
    {
        try {

            $locations = Storehouse::with([
                'subset',
                'subset.subset',
                'subset.subset.subset',
                'subset.subset.subset.subset',
                'subset.subset.subset.subset.subset'
            ])->get(['name', 'unique_code']);
            return JsonResponseFacade::data($locations);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->通用入所
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopInOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('data');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status]
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 通用入所
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopIn(Request $request): JsonResponse
    {
        try {
            $contactName = $request->get('contact_name');
            $contactPhone = $request->get('contact_phone');
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $datas = $request->get('datas');
            $date = $request->get('date');

            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $datas)
                ->get();

            DB::beginTransaction();
            # 生成入所单
            $warehouseReportId = DB::table('warehouse_reports')->insertGetId([
                'created_at' => $date,
                'updated_at' => $date,
                'processor_id' => $accountId,
                'processed_at' => $date,
                'connection_name' => $contactName,
                'connection_phone' => $contactPhone,
                'type' => 'FIXING',
                'direction' => 'IN',
                'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_IN'),
                'work_area_id' => $workAreaId
            ]);
            $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');


            $entireInstances->each(function ($entireInstance)
            use ($date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber, $datas) {

                # 生成入所单设备表
                DB::table('warehouse_report_entire_instances')->insert([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ]);

                # 生成设备日志
                EntireInstanceLog::with([])
                    ->create([
                        'name' => '入所检修',
                        'description' => implode('；', [
                            '经办人：' . $accountNickname,
                            '联系人：' . $contactName ?? '' . ' ' . $contactPhone ?? '',
                            // '车站：' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
                            // '安装位置：' . @$entireInstance->maintain_location_code ?? '' . @$entireInstance->crossroad_number ?? '',
                        ]),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$warehouseReportSerialNumber}"
                    ]);

                # 修改设备状态
                $entireInstance->fill([
                    'maintain_workshop_name' => env('JWT_ISS'),
                    'status' => 'FIXING',
                    'maintain_location_code' => null,
                    'crossroad_number' => null,
                    'next_fixing_time' => null,
                    'next_fixing_month' => null,
                    'next_fixing_day' => null,
                ])
                    ->saveOrFail();
            });
            DB::commit();
            return JsonResponseFacade::created([], '入所成功');
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 搜索->通用出所
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopOutOfSearch(Request $request): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->select(['identity_code', 'category_name', 'model_name as sub_model_name', 'status'])
                ->where('identity_code', $request->get('data'))
                ->firstOrFail();

            // $identityCode = $request->get('data');
            // $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            // if (empty($data)) return JsonResponseFacade::errorEmpty();
            // if (!$data[0]->maintain_station_name) return JsonResponseFacade::errorEmpty('缺少安装位置');
            // $data = [
            //     'identity_code' => $data[0]->identity_code,
            //     'category_name' => $data[0]->category_name,
            //     'sub_model_name' => $data[0]->model_name,
            //     'status' => $this->_statuses[$data[0]->status]
            // ];
            // return JsonResponseFacade::data($data);

            return JsonResponseFacade::data($entire_instance);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty($e);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 通用出所
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopOut(Request $request): JsonResponse
    {
        try {
            $contactName = $request->get('contact_name');
            $contactPhone = $request->get('contact_phone');
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $datas = $request->get('datas');
            $date = $request->get('date');

            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $datas)
                ->get();

            DB::beginTransaction();
            # 生成出所单
            $warehouseReportId = DB::table('warehouse_reports')->insertGetId([
                'created_at' => $date,
                'updated_at' => $date,
                'processor_id' => $accountId,
                'processed_at' => $date,
                'connection_name' => $contactName,
                'connection_phone' => $contactPhone,
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                'work_area_id' => $workAreaId
            ]);
            $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');

            $entireInstances->each(function ($entireInstance)
            use ($request, $date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber) {
                # 修改设备状态
                $entireInstance->fill([
                    'last_out_at' => $date,
                    'in_warehouse_breakdown_explain' => '',
                    'last_warehouse_report_serial_number_by_out' => $warehouseReportSerialNumber,
                    'location_unique_code' => '',
                    'is_bind_location' => 0,
                    'is_overhaul' => '0',
                    'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                    'updated_at' => $date,  // 更新日期
                    'status' => 'FIXING',  // 状态：待修
                    'maintain_station_name' => '',  // 所属车站
                    'crossroad_number' => '',  // 道岔号
                    'open_direction' => '',  // 开向
                    'maintain_location_code' => '',  // 室内上道位置
                    'next_fixing_time' => null,  // 下次周期修时间戳
                    'next_fixing_month' => null,  // 下次周期修月份
                    'next_fixing_day' => null,  // 下次周期修日期
                ])
                    ->saveOrFail();

                # 重新计算周期修
                EntireInstanceFacade::nextFixingTimeWithIdentityCode($entireInstance->identity_code);

                # 生成出所单设备表
                DB::table('warehouse_report_entire_instances')->insert([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ]);

                # 生成设备日志
                EntireInstanceLog::with([])
                    ->create([
                        'name' => '出所安装',
                        'description' => implode('；', [
                            '经办人：' . $accountNickname,
                            '联系人：' . $contactName ?? '' . ' ' . $contactPhone ?? '',
                            // '车站：' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
                            // '安装位置：' . @$entireInstance->maintain_location_code ?? '' . @$entireInstance->crossroad_number ?? '',
                        ]),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$warehouseReportSerialNumber}"
                    ]);
            });
            DB::commit();
            return JsonResponseFacade::created([], '出所成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
            // return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 搜索->报废
     * @param Request $request
     * @return JsonResponse
     */
    final public function postScrapOfSearch(Request $request): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->select(['identity_code', 'category_name', 'model_name as sub_model_name', 'status'])
                ->where('identity_code', $request->get('data'))
                ->firstOrFail();
            return JsonResponseFacade::data($entire_instance);

            // $identityCode = $request->get('data');
            // $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            // if (empty($data)) return JsonResponseFacade::errorEmpty();
            // $data = [
            //     'identity_code' => $data[0]->identity_code,
            //     'category_name' => $data[0]->category_name,
            //     'sub_model_name' => $data[0]->model_name,
            //     'status' => $this->_statuses[$data[0]->status]
            // ];
            // return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
            // return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 报废
     * @param Request $request
     * @return JsonResponse|string[]
     */
    final public function postScrap(Request $request)
    {
        try {
            $date = $request->get('date');
            $identityCodes = $request->get('datas');
            $entireIdentityCodes = DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();
            $partIdentityCodes = DB::table('part_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();

            DB::transaction(function () use ($entireIdentityCodes, $partIdentityCodes, $date) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('SCRAP');
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'state' => 'END',
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'SCRAP',
                    'account_id' => session('account.id')
                ]);
                $warehouseMaterials = [];
                $entireInstanceLogs = [];
                foreach ($entireIdentityCodes as $entireIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $entireIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'ENTIRE'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '报废',
                        'description' => '经办人：' . session('account.nickname') . '；',
                        'entire_instance_identity_code' => $entireIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'ENTIRE'
                    ];
                }
                foreach ($partIdentityCodes as $partIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $partIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'PART'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'name' => '报废',
                        'description' => '经办人：' . session('account.nickname') . '；',
                        'entire_instance_identity_code' => $partIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'PART'
                    ];
                }

                DB::table('warehouse_materials')->insert($warehouseMaterials);
                EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                // 更改设备状态
                DB::table('entire_instances')->whereIn('identity_code', $entireIdentityCodes)->update([
                    'status' => 'SCRAP',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('part_instances')->whereIn('identity_code', $partIdentityCodes)->update([
                    'status' => 'SCRAP',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('warehouses')->where('direction', 'SCRAP')->where('account_id', session('account.id'))->where('unique_code', '<>', $warehouseUniqueCode)->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            });

            return JsonResponseFacade::created([], '报废成功');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->报损
     * @param Request $request
     * @return JsonResponse
     */
    final public function postFrmLossOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('data');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status]
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 报损
     * @param Request $request
     * @return JsonResponse|string[]
     */
    final public function postFrmLoss(Request $request)
    {
        try {
            $date = $request->get('date');
            $identityCodes = $request->get('datas');
            $entireIdentityCodes = DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();
            $partIdentityCodes = DB::table('part_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();

            DB::transaction(function () use ($entireIdentityCodes, $partIdentityCodes, $date) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('FRMLOSS');
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'state' => 'END',
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'FRMLOSS',
                    'account_id' => session('account.id')
                ]);
                $warehouseMaterials = [];
                $entireInstanceLogs = [];
                foreach ($entireIdentityCodes as $entireIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $entireIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'ENTIRE'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '报损',
                        'description' => '经办人：' . session('account.nickname') . '；',
                        'entire_instance_identity_code' => $entireIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'ENTIRE'
                    ];
                }
                foreach ($partIdentityCodes as $partIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $partIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'PART'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '报损',
                        'description' => '经办人：' . session('account.nickname') . '；',
                        'entire_instance_identity_code' => $partIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'PART'
                    ];
                }

                DB::table('warehouse_materials')->insert($warehouseMaterials);
                EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                // 更改设备状态
                DB::table('entire_instances')->whereIn('identity_code', $entireIdentityCodes)->update([
                    'status' => 'FRMLOSS',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('part_instances')->whereIn('identity_code', $partIdentityCodes)->update([
                    'status' => 'FRMLOSS',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('warehouses')->where('direction', 'FRMLOSS')->where('account_id', session('account.id'))->where('unique_code', '<>', $warehouseUniqueCode)->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            });

            return JsonResponseFacade::created([], '报损成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('用户不存在');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->入库
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouseInOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('identity_code');
            $location_unique_code = $request->get('location_unique_code');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            $positions = DB::table('positions')->where('unique_code', $location_unique_code)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty('设备不存在');
            if (empty($positions)) return JsonResponseFacade::errorEmpty('位置不存在');
            $location = Position::with([])->where('unique_code', $location_unique_code)->firstOrFail();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status],
                'location_name' => $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . ' ' . $location->WithTier->WithShelf->WithPlatoon->WithArea->name . ' ' . $location->WithTier->WithShelf->WithPlatoon->name . $location->WithTier->WithShelf->name . $location->WithTier->name . $location->name
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 入库
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouseIn(Request $request): JsonResponse
    {
        try {
            $materials = $request->get('datas');
            $date = $request->get('date');
            DB::transaction(function () use ($materials, $date) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('IN_WAREHOUSE');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'IN_WAREHOUSE',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->save();
                $warehouseId = $warehouse->id;

                foreach ($materials as $material) {
                    $location = Position::with([])->where('unique_code', $material['location_unique_code'])->firstOrFail();
                    $areaType = @$location->WithTier->WithShelf->WithPlatoon->WithArea->type['value'] ?: 'FIXED';
                    $location = @$location->WithTier ? @$location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$location->WithTier->WithShelf->WithPlatoon->name . @$location->WithTier->WithShelf->name . @$location->WithTier->name . @$location->name : '';
                    switch (DB::table('part_instances')->where('identity_code', $material['location_unique_code'])->exists()) {
                        case '0':
                            // 日志
                            $entire_instance = DB::table('entire_instances')->where('identity_code', $material['identity_code'])->select(['maintain_station_name', 'maintain_location_code', 'crossroad_number'])->first();
                            $description = '';
                            // $description .= "上道位置：{$entire_instance->maintain_station_name} " . $entire_instance->maintain_location_code . $entire_instance->crossroad_number . "；";
                            $description .= "入库位置：{$location}" . "；" . "经办人：" . session('account.nickname') . "；";
                            EntireInstanceLogFacade::makeOne('入库', $material['identity_code'], 0, "/storehouse/index/{$warehouseId}", $description, 'ENTIRE');

                            DB::table('entire_instances')
                                ->where('identity_code', $material['identity_code'])
                                ->update([
                                    'status' => $areaType,
                                    'location_unique_code' => $material['location_unique_code'],
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date,
                                    'maintain_station_name' => '',
                                    'maintain_workshop_name' => env('JWT_ISS'),
                                    'maintain_location_code' => '',
                                    'crossroad_number' => '',
                                ]);
                            DB::table('warehouse_materials')
                                ->insert([
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                    'material_unique_code' => $material['identity_code'],
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'ENTIRE'
                                ]);
                            break;
                        case '1':
                            // 日志
                            $description = "入库位置：{$location}" . "；" . "经办人：" . session('account.nickname') . '；';
                            EntireInstanceLogFacade::makeOne('入库', $material['identity_code'], 0, "/storehouse/index/{$warehouseId}", $description, 'PART');

                            DB::table('part_instances')->where('identity_code', $material['identity_code'])
                                ->update([
                                    'status' => $areaType,
                                    'location_unique_code' => $material['location_unique_code'],
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date
                                ]);
                            DB::table('warehouse_materials')->insert([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'material_unique_code' => $material['identity_code'],
                                'warehouse_unique_code' => $warehouseUniqueCode,
                                'material_type' => 'PART'
                            ]);
                            break;
                        default:
                            break;
                    }
                }
            });
            return JsonResponseFacade::created([], '入库成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
            // return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 搜索->上道
     * @param Request $request
     * @return JsonResponse
     */
    final public function postInstallOfSearch(Request $request): JsonResponse
    {
        try {
            $code = $request->get('code');
            switch ($code) {
                case (substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14):
                    $field = 'identity_code';
                    break;
                default:
                    $field = 'serial_number';
                    break;
            }
            $entireInstance = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where($field, $code)
                ->get()
                ->toArray();
            return JsonResponseFacade::data($entireInstance);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 上道
     * @param Request $request
     * @return mixed
     */
    final public function postInstall2(Request $request)
    {
        try {
            $date = $request->get('Y-m-d', date('Y-m-d'));
            $nickname = session('account.nickname');
            $new_entire_instance = EntireInstance::with([])->where('identity_code',$request->get('new_identity_code'))->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 上道
     * @param Request $request
     * @return JsonResponse
     */
    final public function postInstall(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');
            switch ($type) {
                case 'cycle':
                    // 周期修上道
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();
                    if (
                        ($oldEntireInstance->maintain_location_code != $newEntireInstance->maintain_location_code) ||
                        ($oldEntireInstance->maintain_station_name != $newEntireInstance->maintain_station_name)
                    ) return JsonResponseFacade::errorEmpty('上道位置不一致');
                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // 下道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '下道',
                                'description' => implode('；', [
                                    "{$oldEntireInstance->identity_code}被{$newEntireInstance->identity_code}更换",
                                    "位置：{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => ''
                            ]);
                        // 设备下道
                        $oldEntireInstance->fill([
                            'status' => 'UNINSTALLED',  // 下道
                            'maintain_workshop_name' => env('JWT_ISS'),  // 所属车间（当前专业车间）
                            'updated_at' => now(),  // 更新日期
                            'maintain_station_name' => '',  // 所属车站
                            'crossroad_number' => '',  // 道岔号
                            'open_direction' => '',  // 开向
                            'maintain_location_code' => '',  // 室内上道位置
                            'next_fixing_time' => null,  // 下次周期修时间戳
                            'next_fixing_month' => null,  // 下次周期修月份
                            'next_fixing_day' => null,  // 下次周期修日期
                        ])
                            ->saveOrFail();

                        // 设备上道
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'last_installed_time' => strtotime($date),
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // 上道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '周期修上道',
                                'description' => implode('；', [
                                    "{$newEntireInstance->identity_code}更换{$oldEntireInstance->identity_code}",
                                    "位置：{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => ''
                            ]);
                    });
                    break;
                case 'emergency':
                    #应急上道
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();

                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // 设备下道
                        $oldEntireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                        // 下道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '下道',
                                'description' => implode('；', [
                                    "{$oldEntireInstance->identity_code}被{$newEntireInstance->identity_code}更换",
                                    "位置：{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => ''
                            ]);

                        // 设备上道
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'last_installed_time' => strtotime($date),
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // 上道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '应急上道',
                                'description' => implode('；', [
                                    "{$newEntireInstance->identity_code}更换{$oldEntireInstance->identity_code}",
                                    "位置：{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => ''
                            ]);
                    });
                    break;
                case 'fault':
                    // 故障修上道
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();
                    if (
                        ($oldEntireInstance->maintain_location_code != $newEntireInstance->maintain_location_code) ||
                        ($oldEntireInstance->maintain_station_name != $newEntireInstance->maintain_station_name)
                    ) return JsonResponseFacade::errorEmpty('上道位置不一致');
                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // 设备下道
                        $oldEntireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                        // 下道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '下道',
                                'description' => implode('；', [
                                    "{$oldEntireInstance->identity_code}被{$newEntireInstance->identity_code}更换",
                                    "位置：{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => ''
                            ]);

                        // 设备上道
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'last_installed_time' => strtotime($date),
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // 上道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '故障修上道',
                                'description' => implode('；', [
                                    "{$newEntireInstance->identity_code}更换{$oldEntireInstance->identity_code}",
                                    "位置：{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                            ]);
                    });
                    break;
                case 'direct':
                    // 直接上道
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();

                    DB::transaction(function () use ($newEntireInstance, $date, $nickname) {
                        // 设备上道
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'last_installed_time' => strtotime($date),
                            // 'source' => $oldEntireInstance->source,
                            // 'source_traction' => $oldEntireInstance->source_traction,
                            // 'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            // 'traction' => $oldEntireInstance->traction,
                            // 'open_direction' => $oldEntireInstance->open_direction,
                            // 'said_rod' => $oldEntireInstance->said_rod,
                            // 'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            // 'crossroad_number' => $oldEntireInstance->crossroad_number,
                            // 'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            // 'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // 上道日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '直接上道',
                                'description' => implode('；', [
                                    "{$newEntireInstance->identity_code}",
                                    "位置：{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                            ]);
                    });
                    break;
                default:
                    break;
            }
            return JsonResponseFacade::created([], '上道成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备：{$request->get('new_identity_code')}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->下道
     * @param Request $request
     * @return JsonResponse
     */
    final public function postUninstallOfSearch(Request $request): JsonResponse
    {
        try {
            $code = $request->get('code');
            switch ($code) {
                case (substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14):
                    $field = 'identity_code';
                    break;
                default:
                    $field = 'serial_number';
                    break;
            }
            $entireInstance = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where($field, $code)
                ->get()
                ->toArray();
            return JsonResponseFacade::data($entireInstance);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 下道
     * @param Request $request
     * @return JsonResponse
     */
    final public function postUninstall(Request $request): JsonResponse
    {
        try {
            $identity_codes = $request->get('datas');
            $date = $request->get('date');
            $nickname = session('account.nickname');
            DB::transaction(function () use ($identity_codes, $date, $nickname) {
                foreach ($identity_codes as $identity_code) {
                    $entireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $identity_code)->first();
                    // 设备下道
                    $entireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                    // 下道日志
                    EntireInstanceLog::with([])
                        ->create([
                            'created_at' => $date,
                            'updated_at' => $date,
                            'name' => '下道',
                            'description' => implode('；', [
                                "{$entireInstance->identity_code}直接下道",
                                "位置：" . @$entireInstance->Station->Parent->name ?? '' . ' ' . @$entireInstance->maintain_station_name ?? '' . ' ' . @$entireInstance->maintain_location_code ?? '',
                                "操作人：{$nickname}"
                            ]),
                            'entire_instance_identity_code' => $entireInstance->identity_code,
                            'type' => 4,
                            'url' => ''
                        ]);
                }
            });
            return JsonResponseFacade::created([], '下道成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备：{$request->get('identity_code')}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->现场备品入库(所编号/唯一编号)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSceneWarehouseInOfSearch(Request $request): JsonResponse
    {
        try {
            $code = $request->get('code');
            switch ($code) {
                case (substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14):
                    $field = 'identity_code';
                    break;
                default:
                    $field = 'serial_number';
                    break;
            }
            $entireInstance = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where($field, $code)
                ->get()
                ->toArray();
            return JsonResponseFacade::data($entireInstance);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 现场备品入库(唯一编号)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSceneWarehouseIn(Request $request): JsonResponse
    {
        try {
            $materials = $request->get('datas');
            $date = $request->get('date');
            DB::transaction(function () use ($materials, $date) {
                foreach ($materials as $material) {
                    switch (DB::table('part_instances')->where('identity_code', $material['location_unique_code'])->exists()) {
                        case '0':
                            // 日志
                            $description = '';
                            $description .= "现场备品入库" . "；" . "经办人：" . session('account.nickname') . "；";
                            EntireInstanceLogFacade::makeOne('入库', $material['identity_code'], 0, "", $description, 'ENTIRE');

                            DB::table('entire_instances')->where('identity_code', $material['identity_code'])->update([
                                'status' => 'INSTALLING',
                                'is_bind_location' => 1,
                                'in_warehouse_time' => $date,
                                'updated_at' => $date,
                                'maintain_location_code' => '',
                                'crossroad_number' => '',
                                'source' => '',
                                'source_traction' => '',
                                'source_crossroad_number' => '',
                                'traction' => '',
                                'open_direction' => '',
                                'said_rod' => ''
                            ]);
                            break;
                        case '1':
                            // 日志
                            $description = '';
                            $description .= "现场备品入库" . "；" . "经办人：" . session('account.nickname') . "；";
                            EntireInstanceLogFacade::makeOne('入库', $material['identity_code'], 0, "", $description, 'PART');

                            DB::table('part_instances')->where('identity_code', $material['identity_code'])
                                ->update([
                                    'status' => 'INSTALLING',
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date,
                                    'maintain_location_code' => '',
                                    'crossroad_number' => '',
                                    'source' => '',
                                    'source_traction' => '',
                                    'source_crossroad_number' => '',
                                    'traction' => '',
                                    'open_direction' => '',
                                    'said_rod' => ''
                                ]);
                            break;
                        default:
                            break;
                    }
                }
            });
            return JsonResponseFacade::created([], '入库成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备：{$request->get('datas')}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 根据盘点区域编码获取设备数据
     * @return JsonResponse
     */
    final public function takeStockReady()
    {
        try {
            $locationUniqueCode = request('location_unique_code', '');
            if (empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('仓库位置编码不能为空');
            $locationReturn = $this->_getLocationUniqueCode($locationUniqueCode);
            if ($locationReturn['status'] != 200) return JsonResponseFacade::errorEmpty($locationReturn['message']);

            $storehouse_unique_code = $locationReturn['data']['storehouse_unique_code'];
            $area_unique_code = $locationReturn['data']['area_unique_code'];
            $platoon_unique_code = $locationReturn['data']['platoon_unique_code'];
            $shelf_unique_code = $locationReturn['data']['shelf_unique_code'];
            $tier_unique_code = $locationReturn['data']['tier_unique_code'];
            $position_unique_code = $locationReturn['data']['position_unique_code'];
            $data = [];
            // 整件
            $entireInstances = EntireInstance::with(['WithPosition', 'EntireModel'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            $partInstances = PartInstance::with(['WithPosition', 'PartCategory'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            if ($entireInstances->isEmpty() && $partInstances->isEmpty()) return JsonResponseFacade::errorEmpty('该仓库位置没有设备');
            foreach ($entireInstances as $entireInstance) {
                $data[] = [
                    'identity_code' => $entireInstance->identity_code,
                    'category_name' => $entireInstance->category_name ?? '',
                    'sub_model_name' => $entireInstance->model_name ?? '',
                    'status' => $entireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '整件',
                ];
            }
            foreach ($partInstances as $partInstance) {
                $data[] = [
                    'identity_code' => $partInstance->identity_code,
                    'category_name' => $partInstance->PartCategory->name ?? '',
                    'sub_model_name' => $partInstance->part_model_name ?? '',
                    'status' => $partInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '部件',
                ];
            }
            return JsonResponseFacade::data($data);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 根据编码获取具体位置编码
     * @param string $locationUniqueCode
     * @return array|JsonResponse
     */
    final private function _getLocationUniqueCode(string $locationUniqueCode)
    {
        $organizationLocationCode = env('ORGANIZATION_LOCATION_CODE');
        $lenOrganizationLocationCode = strlen($organizationLocationCode);
        if (substr($locationUniqueCode, 0, $lenOrganizationLocationCode) != $organizationLocationCode) return ['status' => 404, 'message' => '仓库位置编码格式不正确'];
        $storehouse_unique_code = '';
        $area_unique_code = '';
        $platoon_unique_code = '';
        $shelf_unique_code = '';
        $tier_unique_code = '';
        $position_unique_code = '';
        $name = '';
        switch (strlen($locationUniqueCode)) {
            case $lenOrganizationLocationCode + 2:
                $storehouse = Storehouse::with([])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($storehouse)) {
                    $storehouse_unique_code = $storehouse->unique_code;
                    $name = $storehouse->name;
                }
                break;
            case $lenOrganizationLocationCode + 4:
                $area = Area::with(['WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($area)) {
                    $storehouse_unique_code = $area->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $area->unique_code;
                    $name = @$area->WithStorehouse->name . $area->name;
                }
                break;
            case $lenOrganizationLocationCode + 6:
                $platoon = Platoon::with(['WithArea', 'WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($platoon)) {
                    $storehouse_unique_code = $platoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $platoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $platoon->unique_code;
                    $name = @$platoon->WithArea->WithStorehouse->name . $platoon->WithArea->name . $platoon->name;
                }
                break;
            case $lenOrganizationLocationCode + 8:
                $shelf = Shelf::with(['WithPlatoon', 'WithPlatoon.WithArea', 'WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($shelf)) {
                    $storehouse_unique_code = $shelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $shelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $shelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $shelf->unique_code;
                    $name = @$shelf->WithPlatoon->WithArea->WithStorehouse->name . @$shelf->WithPlatoon->WithArea->name . @$shelf->WithPlatoon->name . $shelf->name;
                }
                break;
            case $lenOrganizationLocationCode + 10:
                $tier = Tier::with(['WithShelf', 'WithShelf.WithPlatoon', 'WithShelf.WithPlatoon.WithArea', 'WithShelf.WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($tier)) {
                    $storehouse_unique_code = $tier->WithShelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $tier->WithShelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $tier->WithShelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $tier->WithShelf->unique_code ?? '';
                    $tier_unique_code = $tier->unique_code;
                    $name = @$tier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$tier->WithShelf->WithPlatoon->WithArea->name . @$tier->WithShelf->WithPlatoon->name . @$tier->WithShelf->name . $tier->name;
                }
                break;
            case $lenOrganizationLocationCode + 12:
                $position = Position::with(['WithTier', 'WithTier.WithShelf', 'WithTier.WithShelf.WithPlatoon', 'WithTier.WithShelf.WithPlatoon.WithArea', 'WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($position)) {
                    $storehouse_unique_code = $position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $position->WithTier->WithShelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $position->WithTier->WithShelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $position->WithTier->WithShelf->unique_code ?? '';
                    $tier_unique_code = $position->WithTier->unique_code ?? '';
                    $position_unique_code = $position->unique_code;
                    $name = @$position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$position->WithTier->WithShelf->WithPlatoon->WithArea->name . @$position->WithTier->WithShelf->WithPlatoon->name . @$position->WithTier->WithShelf->name . @$position->WithTier->name . $position->name;
                }
                break;
            default:
                return [
                    'status' => 404,
                    'message' => '仓库位置编码不存在',
                ];
        }

        return [
            'status' => 200,
            'message' => '成功',
            'data' => [
                'storehouse_unique_code' => $storehouse_unique_code,
                'area_unique_code' => $area_unique_code,
                'platoon_unique_code' => $platoon_unique_code,
                'shelf_unique_code' => $shelf_unique_code,
                'tier_unique_code' => $tier_unique_code,
                'position_unique_code' => $position_unique_code,
                'name' => $name,
            ]
        ];
    }

    /**
     * 盘点扫码
     * @return array|JsonResponse
     */
    final public function takeStockScanCode()
    {
        try {
            $identityCode = request('identity_code', '');
            $locationUniqueCode = request('location_unique_code', '');
            if (empty($identityCode) || empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('参数不足');
            $entireInstance = EntireInstance::with([])->where('identity_code', $identityCode)->first();
            $partInstance = PartInstance::with(['PartCategory'])->where('identity_code', $identityCode)->first();
            if (empty($entireInstance) && empty($partInstance)) return JsonResponseFacade::errorEmpty('设备数据不存在');
            $data = [];
            if (!empty($entireInstance)) {
                if ($entireInstance->is_bind_location == 1) {
                    $message = '正常';
                } else {
                    $message = '盘盈';
                }
                $data = [
                    'message' => $message,
                    'identity_code' => $entireInstance->identity_code,
                    'category_name' => $entireInstance->category_name ?? '',
                    'sub_model_name' => $entireInstance->model_name ?? '',
                    'status' => $entireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '整件',
                ];
            }
            if (!empty($partInstance)) {
                if ($partInstance->is_bind_location == 1) {
                    $message = '正常';
                } else {
                    $message = '盘盈';
                }
                $data = [
                    'message' => $message,
                    'identity_code' => $partInstance->identity_code,
                    'category_name' => $partInstance->PartCategory->name ?? '',
                    'sub_model_name' => $partInstance->part_model_name ?? '',
                    'status' => $partInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '部件',
                ];
            }

            return JsonResponseFacade::data($data);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 盘点差异分析
     * @return JsonResponse
     */
    final public function takeStock(Request $request)
    {
        try {
            $locationUniqueCode = $request->get('location_unique_code', '');
            $date = $request->get('date');
            if (empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('仓库位置编码不能为空');
            $locationReturn = $this->_getLocationUniqueCode($locationUniqueCode);
            if ($locationReturn['status'] != 200) return JsonResponseFacade::errorEmpty($locationReturn['message']);

            $storehouse_unique_code = $locationReturn['data']['storehouse_unique_code'];
            $area_unique_code = $locationReturn['data']['area_unique_code'];
            $platoon_unique_code = $locationReturn['data']['platoon_unique_code'];
            $shelf_unique_code = $locationReturn['data']['shelf_unique_code'];
            $tier_unique_code = $locationReturn['data']['tier_unique_code'];
            $position_unique_code = $locationReturn['data']['position_unique_code'];
            $locationName = $locationReturn['data']['name'];

            $stockEntireInstances = EntireInstance::with(['WithPosition'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            $stockPartInstances = PartInstance::with(['WithPosition', 'PartCategory'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            if ($stockEntireInstances->isEmpty() && $stockPartInstances->isEmpty()) return JsonResponseFacade::errorEmpty('该仓库位置没有设备');
            $realIdentityCodes = $request->get('identity_code', []);
            $realStockEntireInstances = EntireInstance::with(['WithPosition'])->whereIn('identity_code', $realIdentityCodes)->get();
            $realStockPartInstances = PartInstance::with(['WithPosition', 'PartCategory'])->whereIn('identity_code', $realIdentityCodes)->get();
            // 组合数据
            $stockInstances = [];
            $realStockInstances = [];
            foreach ($stockEntireInstances as $stockEntireInstance) {
                $stockInstances[$stockEntireInstance->identity_code] = [
                    'identity_code' => $stockEntireInstance->identity_code,
                    'category_unique_code' => $stockEntireInstance->category_unique_code ?? '',
                    'category_name' => $stockEntireInstance->category_name ?? '',
                    'sub_model_unique_code' => $stockEntireInstance->model_unique_code ?? '',
                    'sub_model_name' => $stockEntireInstance->model_name ?? '',
                    'location_unique_code' => $stockEntireInstance->location_unique_code ?? '',
                    'location_name' => empty($stockEntireInstance->WithPosition) ? '' : $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->name . $stockEntireInstance->WithPosition->WithTier->name . $stockEntireInstance->WithPosition->name,
                    'status_name' => $stockEntireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '整件',
                ];
            }
            foreach ($stockPartInstances as $stockPartInstance) {
                $stockInstances[$stockPartInstance->identity_code] = [
                    'identity_code' => $stockPartInstance->identity_code,
                    'category_unique_code' => $stockPartInstance->part_category_id ?? '',
                    'category_name' => $stockPartInstance->PartCategory->name ?? '',
                    'sub_model_unique_code' => $stockPartInstance->part_model_unique_code ?? '',
                    'sub_model_name' => $stockPartInstance->part_model_name ?? '',
                    'location_unique_code' => $stockPartInstance->location_unique_code ?? '',
                    'location_name' => empty($stockPartInstance->WithPosition) ? '' : $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $stockPartInstance->WithPosition->WithTier->WithShelf->name . $stockPartInstance->WithPosition->WithTier->name . $stockPartInstance->WithPosition->name,
                    'status_name' => $stockPartInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '部件',
                ];
            }
            foreach ($realStockEntireInstances as $realStockEntireInstance) {
                $realStockInstances[$realStockEntireInstance->identity_code] = [
                    'identity_code' => $realStockEntireInstance->identity_code,
                    'category_unique_code' => $realStockEntireInstance->category_unique_code ?? '',
                    'category_name' => $realStockEntireInstance->category_name ?? '',
                    'sub_model_unique_code' => $realStockEntireInstance->model_unique_code ?? '',
                    'sub_model_name' => $realStockEntireInstance->model_name ?? '',
                    'location_unique_code' => $realStockEntireInstance->location_unique_code ?? '',
                    'location_name' => empty($realStockEntireInstance->WithPosition) ? '' : $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->name . $realStockEntireInstance->WithPosition->WithTier->name . $realStockEntireInstance->WithPosition->name,
                    'status_name' => $realStockEntireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '整件',

                ];
            }
            foreach ($realStockPartInstances as $realStockPartInstance) {
                $realStockInstances[$realStockPartInstance->identity_code] = [
                    'identity_code' => $realStockPartInstance->identity_code,
                    'category_unique_code' => $realStockPartInstance->part_category_id ?? '',
                    'category_name' => $realStockPartInstance->PartCategory->name ?? '',
                    'sub_model_unique_code' => $realStockPartInstance->part_model_unique_code ?? '',
                    'sub_model_name' => $realStockPartInstance->part_model_name ?? '',
                    'location_unique_code' => $realStockPartInstance->location_unique_code ?? '',
                    'location_name' => empty($realStockPartInstance->WithPosition) ? '' : $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->name . $realStockPartInstance->WithPosition->WithTier->name . $realStockPartInstance->WithPosition->name,
                    'status_name' => $realStockPartInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '部件',
                ];
            }
            // 分析
            // 正常
            $intersects = array_intersect_key($stockInstances, $realStockInstances);
            // 盘亏
            $losss = array_diff_key($stockInstances, $realStockInstances);
            // 盘盈
            $surpluss = array_diff_key($realStockInstances, $stockInstances);
            $takeStock = new TakeStock();
            $takeStockUniqueCode = $takeStock->getUniqueCode();
            $takeStockInstances = [];
            $data = [];
            foreach ($intersects as $identityCode => $intersect) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => $identityCode,
                    'real_stock_identity_code' => $identityCode,
                    'difference' => '=',
                    'category_unique_code' => $intersect['category_unique_code'],
                    'category_name' => $intersect['category_name'],
                    'sub_model_unique_code' => $intersect['sub_model_unique_code'],
                    'sub_model_name' => $intersect['sub_model_name'],
                    'location_unique_code' => $intersect['location_unique_code'],
                    'location_name' => $intersect['location_name'],
                    'material_type' => $intersect['material_type'],
                ];
            }
            foreach ($losss as $identityCode => $loss) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => $identityCode,
                    'real_stock_identity_code' => '',
                    'difference' => '-',
                    'category_unique_code' => $loss['category_unique_code'],
                    'category_name' => $loss['category_name'],
                    'sub_model_unique_code' => $loss['sub_model_unique_code'],
                    'sub_model_name' => $loss['sub_model_name'],
                    'location_unique_code' => $loss['location_unique_code'],
                    'location_name' => $loss['location_name'],
                    'material_type' => $loss['material_type'],
                ];
                $data[] = [
                    'message' => '盘亏',
                    'identity_code' => $identityCode,
                    'category_name' => $loss['category_name'],
                    'sub_model_name' => $loss['sub_model_name'],
                    'status_name' => $loss['status_name'],
                    'material_type' => $loss['material_type'],
                    'material_type_name' => $loss['material_type_name'],
                ];
            }

            foreach ($surpluss as $identityCode => $surplus) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => '',
                    'real_stock_identity_code' => $identityCode,
                    'difference' => '+',
                    'category_unique_code' => $surplus['category_unique_code'],
                    'category_name' => $surplus['category_name'],
                    'sub_model_unique_code' => $surplus['sub_model_unique_code'],
                    'sub_model_name' => $surplus['sub_model_name'],
                    'location_unique_code' => $surplus['location_unique_code'],
                    'location_name' => $surplus['location_name'],
                    'material_type' => $surplus['material_type'],
                ];
                $data[] = [
                    'message' => '盘盈',
                    'identity_code' => $identityCode,
                    'category_name' => $surplus['category_name'],
                    'sub_model_name' => $surplus['sub_model_name'],
                    'status_name' => $surplus['status_name'],
                    'material_type' => $surplus['material_type'],
                    'material_type_name' => $surplus['material_type_name'],
                ];
            }
            $accountId = session('account.id');
            if (empty($losss) && empty($surpluss)) {
                // 无差异
                $take_stock = [
                    'unique_code' => $takeStockUniqueCode,
                    'state' => 'END',
                    'result' => 'NODIF',
                    'stock_diff' => count($losss),
                    'real_stock_diff' => count($surpluss),
                    'account_id' => $accountId,
                    'location_unique_code' => $locationUniqueCode,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'name' => $locationName ?? '整仓',
                ];
            } else {
                // 有差异
                $take_stock = [
                    'unique_code' => $takeStockUniqueCode,
                    'state' => 'END',
                    'result' => 'YESDIF',
                    'stock_diff' => count($losss),
                    'real_stock_diff' => count($surpluss),
                    'account_id' => $accountId,
                    'location_unique_code' => $locationUniqueCode,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'name' => $locationName ?? '整仓',
                ];
            }
            DB::transaction(function () use ($take_stock, $takeStockInstances, $accountId, $date) {
                DB::table('take_stocks')->where('account_id', $accountId)->where('state', 'START')->update(['state' => 'CANCEL', 'updated_at' => $date]);
                DB::table('take_stock_instances')->insert($takeStockInstances);
                DB::table('take_stocks')->insert($take_stock);
            });
            return JsonResponseFacade::data($data, '盘点成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('数据不存在');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索->整件or部件
     * @param string|null $identityCode
     * @return mixed
     */
    final public function postBindOfSearch(Request $request): JsonResponse
    {
        try {
            switch ($request->get('type')) {
                case 'entire':
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    if (!$entireInstanceIdentityCode) return JsonResponseFacade::errorEmpty('唯一编号不能为空');
                    $entireInstance = EntireInstance::with([
                        'Station',
                        'Station.Parent',
                        'Category',
                        'EntireModel',
                        'SubModel',
                        'PartModel',
                        'PartInstances',
                        'PartInstances.PartCategory',
                    ])
                        ->where('identity_code', $entireInstanceIdentityCode)
                        ->firstOrFail();
                    return JsonResponseFacade::data($entireInstance);
                case 'part':
                    $partIdentityCode = $request->get('part_identity_code');
                    $partInstance = PartInstance::with([
                        'Category',
                        'EntireModel',
                        'PartCategory',
                    ])
                        ->where('identity_code', $partIdentityCode)
                        ->firstOrFail();
                    return JsonResponseFacade::data($partInstance);
                default:
                    return JsonResponseFacade::errorEmpty();
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 部件绑定/解绑/换绑
     * @param string|null $identityCode
     * @return mixed
     */
    final public function postBind(Request $request): JsonResponse
    {
        try {
            switch ($request->get('type')) {
                case 'bind':
                    // 部件绑定
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $newPartIdentityCode = $request->get('new_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $newPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $newPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => $entireInstanceIdentityCode
                            ]);
                        // 绑定日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '部件绑定',
                                'description' => implode('；', [
                                    "整件:{$entireInstanceIdentityCode}绑定部件:{$newPartIdentityCode}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => ''
                            ]);
                    });
                    return JsonResponseFacade::created([], '绑定成功');
                case 'unbind':
                    // 部件解绑
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $oldPartIdentityCode = $request->get('old_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $oldPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $oldPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => null
                            ]);
                        // 解绑日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '部件解绑',
                                'description' => implode('；', [
                                    "整件:{$entireInstanceIdentityCode}解绑部件:{$oldPartIdentityCode}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => ''
                            ]);
                    });
                    return JsonResponseFacade::created([], '解绑成功');
                case 'change_bind':
                    // 部件换绑
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $newPartIdentityCode = $request->get('new_part_identity_code');
                    $oldPartIdentityCode = $request->get('old_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $newPartIdentityCode, $oldPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $oldPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => null
                            ]);
                        DB::table('part_instances')
                            ->where('identity_code', $newPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => $entireInstanceIdentityCode
                            ]);
                        // 换绑日志
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '部件换绑',
                                'description' => implode('；', [
                                    "部件:{$newPartIdentityCode}替换部件:{$oldPartIdentityCode}",
                                    "操作人：{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => ''
                            ]);
                    });
                    return JsonResponseFacade::created([], '换绑成功');
                default:
                    return JsonResponseFacade::errorEmpty();
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 备品统计
     * @param Request $request
     * @return mixed
     */
    final public function postSparesStatistics(Request $request)
    {
        try {
            $identity_code = $request->get('identity_code');
            $entireInstance = EntireInstance::where('identity_code', $identity_code)->firstOrFail();
            // 备品统计
            if ($entireInstance->status !== '上道') return JsonResponseFacade::errorEmpty('设备未上道');
            if ($entireInstance->maintain_station_name) {
                // 车站
                $maintain_station_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_station_name', $entireInstance->maintain_station_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                $maintain_workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $entireInstance->maintain_workshop_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                // 车间编码
                $scene_workshop_unique_code = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('parent_unique_code');
                // 车站id
                $stationId = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('id');
                // 车间名称
                $workshopName = DB::table('maintains')->where('unique_code', $scene_workshop_unique_code)->value('name');
                // 类型编码
                // $entire_model_unique_code = DB::table('entire_models')->where('unique_code', $entireInstance->model_unique_code)->value('parent_unique_code');
                // 所属车间距离
                $workshop_distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $workshopName)->value('distance') / 1000, 2);
                // 所属车站距离
                $station_distance = 0;
                // 长沙电务段信号检修车间距离
                $current_workshop = DB::table('maintains')->where('type', 'WORKSHOP')->first();  // 当前检修车间
                $distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $current_workshop->name)->value('distance') / 1000, 2);
                // 获取最近的两个车站
                $stations = DB::table('distance')->where('maintains_id', $stationId)->where('distance', '!=', 0)->orderBy('distance')->limit(2)->get()->toArray();
                // 计算临近两个车站的备品数
                foreach ($stations as $key => $station) {
                    $stations[$key]->maintain_station_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_station_name', $station->maintains_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    $stations[$key]->distance = round($station->distance / 1000, 2);
                }
                #长沙电务段信号检修车间
                $workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $current_workshop->name)
                    ->where('status', 'FIXED')
                    ->count();

                # 当前车站
                $current_station = DB::table('maintains as s')
                    ->select([
                        's.name as station_name',
                        's.unique_code as station_unique_code',
                        'sw.name as workshop_name',
                        'sw.name as workshop_unique_code',
                        's.lon as station_lon',
                        's.lat as station_lat',
                        'sw.lon as workshop_lon',
                        'sw.lat as workshop_lat',
                    ])
                    ->join(DB::raw('maintains as sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                    ->where('s.deleted_at', null)
                    ->where('s.name', $entireInstance->maintain_station_name)
                    ->first();
                # 所属车间
                $data = [
                    'belongToStation' => [
                        'name' => $entireInstance->maintain_station_name,
                        'count' => $maintain_station_num,
                        'distance' => $station_distance,
                        'lon' => $current_station->station_lon,
                        'lat' => $current_station->station_lat,
                    ],
                    'belongToWorkshop' => [
                        'name' => $workshopName,
                        'count' => $maintain_workshop_num,
                        'distance' => $workshop_distance,
                        'lon' => $current_station->workshop_lon,
                        'lat' => $current_station->workshop_lat,
                    ],
                    'nearStation' => $stations,
                    'WORKSHOP' => [
                        'name' => $current_workshop->name,
                        'count' => $workshop_num,
                        'distance' => $distance,
                        'lon' => $current_workshop->lon,
                        'lat' => $current_workshop->lat
                    ],
                ];
                return JsonResponseFacade::data($data);
            } else {
                // 车间
                // $maintain_station_num = 0;
                $maintain_workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $entireInstance->maintain_workshop_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                // 车间编码
                // $scene_workshop_unique_code = DB::table('maintains')->where('name', $entireInstance->maintain_workshop_name)->value('unique_code');
                $stationId = DB::table('maintains')->where('name', $entireInstance->maintain_workshop_name)->value('id');
                // 类型编码
                // $entire_model_unique_code = DB::table('entire_models')->where('unique_code', $entireInstance->model_unique_code)->value('parent_unique_code');
                // 所属车间距离
                $workshop_distance = 0;
                // 所属车站距离
                // $station_distance = 0;
                // 长沙电务段信号检修车间距离
                $current_workshop = DB::table('maintains')->where('type', 'WORKSHOP')->first();
                $distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $current_workshop->name)->value('distance') / 1000, 2);
                // 获取最近的两个车站
                $stations = DB::table('distance')->where('maintains_id', $stationId)->where('distance', '!=', 0)->orderBy('distance')->limit(2)->get()->toArray();
                // 计算临近两个车站的备品数
                foreach ($stations as $key => $station) {
                    $stations[$key]->maintain_station_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_station_name', $station->maintains_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    $stations[$key]->distance = round($station->distance / 1000, 2);
                }
                #长沙电务段信号检修车间
                $workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $current_workshop->name)
                    ->where('status', 'FIXED')
                    ->count();

                # 现场车间
                $scene_workshop = DB::table('maintains as sw')->where('sw.deleted_at', null)->where('name', $entireInstance->maintain_workshop_name)->where('type', 'SCENE_WORKSHOP')->first();
                $data = [
                    'belongToStation' => ['name' => '', 'count' => '', 'distance' => '', 'lon' => null, 'lat' => null],
                    'belongToWorkshop' => [
                        'name' => $entireInstance->maintain_workshop_name,
                        'count' => $maintain_workshop_num,
                        'distance' => $workshop_distance,
                        'lon' => $scene_workshop->lon,
                        'lat' => $scene_workshop->lat,
                    ],
                    'nearStation' => $stations,
                    'WORKSHOP' => ['name' => $current_workshop->name, 'count' => $workshop_num, 'distance' => $distance, 'lon' => $current_workshop->lon, 'lat' => $current_workshop->lat],
                ];
                return JsonResponseFacade::data($data);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 搜索(整件设备编码/种类型编码or库房位置编码)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSearch(Request $request): JsonResponse
    {
        try {
            $search_type = $request->get('search_type');
            if ($search_type == 'entire_instances') {
                // 整件设备编码/种类型编码
                if ($request->get('code')) {
                    // 整件设备编码
                    $code = $request->get('code');
                    if ((substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14)) {
                        // 整件设备编码->唯一编号
                        $field = 'identity_code';
                    } else {
                        // 整件设备编码->所编号
                        $field = 'serial_number';
                    }
                    $entireInstance = EntireInstance::with([
                        'Station',
                        'Station.Parent',
                        'Category',
                        // 'EntireModel',
                        'SubModel',
                        'SubModel.Parent',
                    ])
                        ->where($field, $code)
                        ->get()
                        ->toArray();
                    return JsonResponseFacade::data($entireInstance);
                } else {
                    // 种类型编码

                }
            } else {
                // 库房位置编码

            }

            // switch ($code) {
            // case (substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14) :
            // $field = 'identity_code';
            // break;
            // default :
            // $field = 'serial_number';
            // break;
            // }
            // $entireInstance = EntireInstance::with([
            // 'Station',
            // 'Station.Parent',
            // 'Category',
            // 'EntireModel',
            // 'SubModel',
            // ])
            // ->where($field, $code)
            // ->get()
            // ->toArray();
            // return JsonResponseFacade::data($entireInstance);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 设备履历
     * @param string|null $identity_code
     * @return mixed
     */
    final public function getEntireInstance(string $identity_code = ''): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([
                'BreakdownLogs',
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartInstances',
                'FixWorkflow',
                'FixWorkflows',
                'EntireInstanceLogs',
                'WithSendRepairInstances',
                'WithSendRepairInstances.WithSendRepair',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])
                ->where('identity_code', $identity_code)
                ->firstOrFail();
            $entire_instance->location_name = @$entire_instance->WithPosition ? @$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @@$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . @$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->name . @$entire_instance->WithPosition->WithTier->WithShelf->name . @$entire_instance->WithPosition->WithTier->name . @$entire_instance->WithPosition->name : '';  // 仓库位置
            $entire_instance->is_overdue = boolval(strtotime($entire_instance->scarping_at) < time());  // 是否超期

            if ($entire_instance->entire_model_unique_code == $entire_instance->model_unique_code) {
                // 如果类型和型号标识一致则单独查询
                $sm = DB::table('entire_models as sm')
                    ->where('sm.is_sub_model', true)
                    ->where('sm.deleted_at', null)
                    ->where('sm.unique_code', $entire_instance->model_unique_code)
                    ->first();

                $em = DB::table('entire_models as em')
                    ->where('em.is_sub_model', false)
                    ->where('em.deleted_at', null)
                    ->where('em.unique_code', $entire_instance->entire_model_unique_code)
                    ->first();

                $entire_instance->entire_model_name = $em ? $em->name : '';
                $entire_instance->sub_model_name = $sm ? $sm->name : '';
            } else {
                // 如果类型和型号标识不一致
                $pm = DB::table('part_models as pm')
                    ->select([
                        'pm.name as sub_model_name',
                        'em.name as entire_model_name',
                    ])
                    ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->where('pm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('em.is_sub_model', false)
                    ->where('pm.unique_code', $entire_instance->model_unique_code)
                    ->first();
                $entire_instance->entire_model_name = $pm->entire_model_name;
                $entire_instance->sub_model_name = $pm->entire_model_name;
            }

            // 获取周期修时间
            if ($entire_instance->EntireModel->fix_cycle_value == 0 && $entire_instance->fix_cycle_value == 0) $entire_instance->fix_cycle_value = '状态修设备';

            return JsonResponseFacade::data(['entire_instance' => $entire_instance]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取设备列表
     * @return JsonResponse
     */
    final public function getEntireInstances(): JsonResponse
    {
        try {
            $entireInstances = ModelBuilderFacade::init(
                request(),
                EntireInstance::with([])
            )
                ->all();

            return JsonResponseFacade::data($entireInstances);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250获取任务列表
     * @param Request $request
     * @return JsonResponse
     */
    final public function postTaskList(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');
            $v250TaskOrders = V250TaskOrder::with([
                'SceneWorkshop',
                'MaintainStation',
                'WorkAreaByUniqueCode',
                'Principal'
            ])
                ->where('type', $type)
                ->where('status', 'PROCESSING')
                ->where('work_area_unique_code', session('account.work_area_unique_code'))
                ->orderByDesc('id')
                ->paginate();
            return JsonResponseFacade::data($v250TaskOrders);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250获取任务列表基础信息
     * @param Request $request
     * @return JsonResponse
     */
    final public function postListDetails(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250TaskOrders = V250TaskOrder::with([
                'SceneWorkshop',
                'MaintainStation',
                'WorkAreaByUniqueCode',
                'V250TaskEntireInstances',
            ])
                ->where('serial_number', $serialNumber)
                ->get();
            $v250_task_entire_instances = V250TaskEntireInstance::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.EntireModel',
                'EntireInstance.SubModel'
            ])
                ->where('v250_task_order_sn', $serialNumber)
                ->paginate();
            $data = [
                'workshopName' => $v250TaskOrders[0]->SceneWorkshop->name,
                'stationName' => $v250TaskOrders[0]->MaintainStation->name,
                'workAreaName' => $v250TaskOrders[0]->WorkAreaByUniqueCode->name,
                'workAreaUniqueCode' => $v250TaskOrders[0]->WorkAreaByUniqueCode->unique_code,
                'expiringAt' => substr($v250TaskOrders[0]->expiring_at, 0, 10),
                'taskOrderCount' => $v250_task_entire_instances->count(),
                'workshopOutCount' => $v250_task_entire_instances->where('is_out', true)->count(),
                'entire_instances' => $v250_task_entire_instances
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250根据工区获取人员
     * @param Request $request
     * @return JsonResponse
     */
    final public function postPersonnel(Request $request): JsonResponse
    {
        try {
            $workAreaUniqueCode = $request->get('work_area_unique_code');
            $personnel = Account::where('work_area_unique_code', $workAreaUniqueCode)->get(['nickname', 'id']);
            return JsonResponseFacade::data($personnel);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250获取待出所单(新站)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopStayOut(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250workshopStayOut = DB::table('v250_workshop_stay_out')->where('v250_task_orders_serial_number', $serialNumber)->where('status', 'PROCESSING')->get()->toArray();
            return JsonResponseFacade::data($v250workshopStayOut);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250获取待出所单设备详情(新站)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopStayOutEntireInstances(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250TaskEntireInstances = V250WorkshopOutEntireInstances::with([
                'EntireInstance',
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
                'EntireInstance.WithPosition',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])->where('v250_workshop_stay_out_serial_number', $serialNumber)
                ->get();
            return JsonResponseFacade::data($v250TaskEntireInstances);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250检修分配
     * @param Request $request
     * @return JsonResponse
     */
    final public function postOverhaul(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $accountId = $request->get('account_id');
            $identityCodes = $request->get('identity_codes');
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $serialNumber)->whereIn('entire_instance_identity_code', $identityCodes)->update(['fixer_id' => $accountId]);
            DB::table('entire_instances')->whereIn('identity_code', $identityCodes)->update([
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 'FIXING',
                'is_overhaul' => '1'
            ]);
            return JsonResponseFacade::created([], '分配成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250出所(新站)
     * @param Request $request
     * @return JsonResponse
     */
    final public function passWorkshopOut(Request $request): JsonResponse
    {
        try {
            $contactName = $request->get('contact_name');
            $contactPhone = $request->get('contact_phone');
            $date = $request->get('date');
            $sn = $request->get('serial_number');
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $identityCodes = $request->get('identity_codes');
            $serialNumber = DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('entire_instance_identity_code', $identityCodes[0])->value('v250_task_orders_serial_number');
            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $identityCodes)
                ->get();

            DB::beginTransaction();
            // 生成出所单
            $warehouseReportId = DB::table('warehouse_reports')->insertGetId([
                'created_at' => $date,
                'updated_at' => $date,
                'processor_id' => $accountId,
                'processed_at' => $date,
                'connection_name' => $contactName,
                'connection_phone' => $contactPhone,
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                'work_area_id' => $workAreaId
            ]);
            $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');

            $entireInstances->each(function ($entireInstance)
            use ($request, $date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber) {
                // 修改设备状态
                $entireInstance->fill([
                    'updated_at' => $date,
                    'last_out_at' => $date,
                    'status' => 'TRANSFER_OUT',
                    'next_fixing_time' => null,
                    'next_fixing_month' => null,
                    'next_fixing_day' => null,
                    'in_warehouse_breakdown_explain' => '',
                    'last_warehouse_report_serial_number_by_out' => $warehouseReportSerialNumber,
                    'location_unique_code' => '',
                    'is_bind_location' => 0,
                    'is_overhaul' => '0'
                ])
                    ->saveOrFail();

                // 重新计算周期修
                EntireInstanceFacade::nextFixingTimeWithIdentityCode($entireInstance->identity_code);

                // 生成出所单设备表
                DB::table('warehouse_report_entire_instances')->insert([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ]);

                // 生成设备日志
                EntireInstanceLog::with([])
                    ->create([
                        'name' => '出所安装',
                        'description' => implode('；', [
                            '经办人：' . $accountNickname,
                            '联系人：' . $contactName ?? '' . ' ' . $contactPhone ?? '',
                            // '车站：' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
                            // '安装位置：' . @$entireInstance->maintain_location_code ?? '' . @$entireInstance->crossroad_number ?? '',
                        ]),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$warehouseReportSerialNumber}"
                    ]);
            });
            if (!DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->exists()) {
                DB::table('v250_workshop_stay_out')->where('serial_number', $sn)->update([
                    'updated_at' => $date,
                    'finished_at' => $date,
                    'status' => 'DONE',
                ]);
            }

            DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->whereIn('entire_instance_identity_code', $identityCodes)->delete();
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $serialNumber)->whereIn('entire_instance_identity_code', $identityCodes)->update(['is_out' => 1]);
            DB::commit();
            return JsonResponseFacade::created([], '出所成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取人员数据
     * @param int $id
     * @return mixed
     */
    final public function getAccount(int $id)
    {
        try {
            $account = Account::with([])->where('id', $id)->first();

            return JsonResponseFacade::data(['account' => $account]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取用户列表
     * @return mixed
     */
    final public function getAccounts(): JsonResponse
    {
        try {
            $accounts = ModelBuilderFacade::init(request(), Account::with([]))
                ->extension(function ($Account) {
                    return $Account->select([
                        'id',
                        'account',
                        'nickname',
                    ]);
                })
                ->all();
            return JsonResponseFacade::data(['accounts' => $accounts]);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取故障出入所单
     * @params string type
     * @return JsonResponse
     */
    final public function getBreakdownOrders(): JsonResponse
    {
        try {
            $breakdownOrders = ModelBuilderFacade::init(request(), RepairBaseBreakdownOrder::with([
                'InEntireInstances',
                'InEntireInstances',
                'OutEntireInstances',
                'Processor',
            ]))->all();

            return JsonResponseFacade::data($breakdownOrders);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 现场退回
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSceneBackIn(Request $request): JsonResponse
    {
        try {
            // 获取所有新站任务设备
            $v250_task_entire_instances = V250TaskEntireInstance::with(['V250TaskOrder'])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('type', 'NEW_STATION');
                })
                ->where('is_out', true)
                ->get();
            $diff = array_diff($request->get('identityCodes'), $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray());
            if ($diff) return JsonResponseFacade::errorForbidden('存在新站任务以外或没有出所的设备', ['error_identity_codes' => $diff]);
            $intersect = array_values(array_intersect($request->get('identityCodes'), $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray()));

            $edited_entire_instances = [];
            DB::beginTransaction();
            // 修改设备状态
            V250TaskEntireInstance::with([])->whereIn('entire_instance_identity_code', $intersect)->update(['is_out' => false]);
            $warehouse_report = WarehouseReport::with([])->create([
                'processor_id' => session('account.id'),
                'processed_at' => date('Y-m-d H:i:s'),
                'connection_name' => '',
                'connection_phone' => '',
                'type' => 'SCENE_BACK',
                'direction' => 'IN',
                'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('SCENE_BACK_IN'),
                'status' => 'DONE',
            ]);
            foreach ($intersect as $identity_code) {
                $entire_instance = EntireInstance::with([])->where('identity_code', $identity_code)->first();
                WarehouseReportEntireInstance::with([])->create([
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'warehouse_report_serial_number' => $warehouse_report_sn,
                    'entire_instance_identity_code' => $identity_code,
                    'maintain_station_name' => $entire_instance->maintain_station_name,
                    'maintain_location_code' => $entire_instance->maintain_location_code,
                    'crossroad_number' => $entire_instance->crossroad_number,
                    'traction' => $entire_instance->traction,
                    'line_name' => $entire_instance->line_name,
                    'crossroad_type' => $entire_instance->crossroad_type,
                    'extrusion_protect' => $entire_instance->extrusion_protect,
                    'point_switch_group_type' => $entire_instance->point_switch_group_type,
                    'open_direction' => $entire_instance->open_direction,
                    'said_rod' => $entire_instance->said_rod,
                    'is_out' => false,
                ]);
                EntireInstanceLog::with([])->create([
                    'name' => '现场退回',
                    'description' => "经办人：" . session('account.nickname'),
                    'entire_instance_identity_code' => $identity_code,
                    'type' => 1,
                    'url' => "/warehouse/report/{$warehouse_report->serial_number}",
                    'material_type' => 'ENTIRE',
                ]);

                // 修改设备状态
                $entire_instance->fill([
                    'maintain_workshop_name' => env('JWT_ISS'),
                    'status' => 'FIXED',
                    'maintain_location_code' => null,
                    'crossroad_number' => null,
                    'next_fixing_time' => null,
                    'next_fixing_month' => null,
                    'next_fixing_day' => null,
                ])
                    ->saveOrFail();
                $edited_entire_instances[] = $entire_instance;
            }
            DB::commit();

            return JsonResponseFacade::created(['entire_instances' => $entire_instance], '入所成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250搜索->检修完成
     * @return mixed
     */
    final public function postOverhaulOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('identity_code');
            $entireInstance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel'
            ])
                ->where('identity_code', $identityCode)
                ->where('is_overhaul', '1')
                ->get()
                ->toArray();
            if (!$entireInstance) {
                return JsonResponseFacade::errorEmpty();
            }
            return JsonResponseFacade::data($entireInstance);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250检修完成
     * @return mixed
     */
    final public function postCompleteOverhaul(Request $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $selected_for_fix_misson = $request->get('identity_codes');
                $deadLine = $request->get('date');
                DB::table('v250_task_entire_instances as tei')
                    ->join('v250_task_orders as to', 'to.serial_number', 'tei.v250_task_order_sn')
                    ->where('to.status', 'PROCESSING')
                    ->whereIn('entire_instance_identity_code', $selected_for_fix_misson)
                    ->update(['tei.fixed_at' => $deadLine]);
                foreach ($selected_for_fix_misson as $entire_instance) {
                    $deadAt = DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->value('deadline');
                    if (strtotime($deadAt) >= strtotime($deadLine)) {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '1']);
                    } else {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '2']);
                    }
                    //                    if (DB::table('entire_instances')->where('v250_task_order_sn', null)->where('identity_code', $entire_instance)->exists()) {
                    //                        DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
                    //                            'updated_at' => date('Y-m-d H:i:s'),
                    //                            'is_overhaul' => '0'
                    //                        ]);
                    //                    }
                    DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => 'FIXED',
                        'is_overhaul' => '0'
                    ]);
                }
            });
            return JsonResponseFacade::created([], '检修完成');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 设备验收
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCheckDevice(Request $request): JsonResponse
    {
        try {
            $entire_instance_identity_codes_in_v250_task = V250TaskEntireInstance::with([
                'V250TaskOrder',
                'EntireInstance',
                'Fixer',
                'Checker'
            ])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('status', 'UNDONE');
                })
                ->where('checker_id', 0)
                ->pluck('entire_instance_identity_code')
                ->toArray();
            if (empty($entire_instance_identity_codes_in_v250_task)) return JsonResponseFacade::errorEmpty('没有找到设备，或设备均已验收。');
            $entire_instance_identity_codes = EntireInstance::with([])->where('v250_task_order_sn', '')->whereIn('identity_code', $request->get('identityCodes'))->pluck('identity_code')->toArray();
            $diff = array_diff($request->get('identityCodes'), array_unique(array_merge($entire_instance_identity_codes_in_v250_task, $entire_instance_identity_codes)));
            if ($diff) return JsonResponseFacade::errorForbidden('以下设备没有找到', ['error_identity_codes' => $diff]);
            $now = date('Y-m-d H:i:s');

            $edited_entire_instances = [];
            DB::beginTransaction();
            $entire_instances_in_v250_task = V250TaskEntireInstance::with(['V250TaskOrder', 'EntireInstance', 'Fixer', 'Checker'])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('status', 'UNDONE');
                })
                ->where('checker_id', 0)
                ->whereIn('entire_instance_identity_code', $request->get('identityCodes'))
                ->chunkByid(50, function ($v250_task_entire_instances) use ($now, &$edited_entire_instances) {
                    foreach ($v250_task_entire_instances as $v250_task_entire_instance) {
                        if (!$v250_task_entire_instance->Fixer) throw new CheckDeviceException("设备：{$v250_task_entire_instance->entire_instance_identity_code} 没有分配检测/检修人");
                        FixWorkflowFacade::mockEmpty(
                            $v250_task_entire_instance->EntireInstance,
                            $v250_task_entire_instance->fixed_at ?? $now,
                            $now,
                            $v250_task_entire_instance->fixer_id,
                            session('account.id')
                        );

                        $v250_task_entire_instance->EntireInstance->fill(['status' => 'FIXED'])->saveOrFail();  // 修改设备状态
                        $v250_task_entire_instance->fill(['checker_id' => session('account.id'), 'checked_at' => $now])->saveOrFail();  // 修改任务单中设备状态
                        $edited_entire_instances[] = $v250_task_entire_instance->EntireInstance;
                    }
                });

            EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identityCodes'))
                ->chunkById(50, function ($entire_instances) use ($now, &$edited_entire_instances) {
                    foreach ($entire_instances as $entire_instance) {
                        FixWorkflowFacade::mockEmpty(
                            $entire_instance,
                            $now,
                            $now,
                            session('account.id'),
                            session('account.id')
                        );

                        $entire_instance->fill(['status' => 'FIXED'])->saveOrFail(); // 修改设备状态
                        $edited_entire_instances[] = $entire_instance;
                    }
                });
            DB::commit();

            return JsonResponseFacade::created(['entire_instances' => $edited_entire_instances], '验收成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 根据员工获取绑定记录
     */
    final public function getStationInstallLocationRecords(): JsonResponse
    {
        try {
            $station_install_location_records = StationInstallLocationRecord::with(['Station'])
                ->orderByDesc('id')
                ->where('processor_id', session('account.id'))
                ->get();

            return JsonResponseFacade::data(['station_install_location_records' => $station_install_location_records]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取现场车间列表
     * @param string $paragraph_unique_code
     * @return JsonResponse
     */
    final public function getSceneWorkshops(): JsonResponse
    {
        try {
            $scene_workshops = Maintain::with(['Subs'])
                ->where('parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('type', 'SCENE_WORKSHOP')
                ->get();

            return JsonResponseFacade::data(['scene_workshops' => $scene_workshops]);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 纠正现场上道位置
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCorrectMaintainLocation(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            // $maintain = Maintain::with(['Parent'])
            //     ->where('type', 'STATION')
            //     ->where('name', $request->get('maintain_station_name'))
            //     ->first();
            // if (!$maintain) return response()->json(['msg' => '没有找到车站', 'status' => 404], 404);

            $entire_instance = EntireInstance::with([])
                ->where('identity_code', $request->get('entire_instance_identity_code'))
                // ->where('maintain_station_name', $request->get('maintain_station_name'))
                ->firstOrFail();

            $station_install_location_recode = StationInstallLocationRecord::with([])->where('entire_instance_identity_code', $entire_instance->identity_code)->first();
            if ($station_install_location_recode) {
                $station_install_location_recode
                    ->fill([
                        // 'maintain_station_unique_code' => $maintain->unique_code,
                        // 'maintain_station_name' => $maintain->name,
                        'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                        'crossroad_number' => $request->get('crossroad_number') ?? '',
                        'open_direction' => $request->get('open_direction') ?? '',
                        'is_indoor' => 1,
                        'section_unique_code' => '',
                        'processor_id' => session('account.id'),
                        'entire_instance_identity_code' => $entire_instance->identity_code,
                    ])
                    ->saveOrFail();
            } else {
                $station_install_location_recode = StationInstallLocationRecord::with([])->create([
                    // 'maintain_station_unique_code' => $maintain->unique_code,
                    // 'maintain_station_name' => $maintain->name,
                    'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                    'crossroad_number' => $request->get('crossroad_number') ?? '',
                    'open_direction' => $request->get('open_direction') ?? '',
                    'is_indoor' => 1,
                    'section_unique_code' => '',
                    'processor_id' => session('account.id'),
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                ]);
            }

            $entire_instance->fill([
                // 'maintain_station_name' => $maintain->name,
                'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                'crossroad_number' => $request->get('crossroad_number') ?? '',
                'open_direction' => $request->get('open_direction') ?? '',
            ]);
            DB::commit();

            $last3 = StationInstallLocationRecord::with(['Station'])
                ->orderByDesc('updated_at')
                ->limit(3)
                ->where('processor_id', session('account.id'))
                ->get();

            return JsonResponseFacade::created(['station_install_location_recode' => $station_install_location_recode, 'last3' => $last3], '保存成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("{$request->get('maintain_station_name')}没有找到该设备");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 现场检修任务统计(统计项目)
     * 条件：车间（车站、工区）
     * 统计：项目
     */
    final public function getTaskStationCheckStatisticForProject(): JsonResponse
    {
        try {
            list($year, $month) = explode('-', request('expiring_at', date('Y-m')) ?? date('Y-m'));
            $origin_at = Carbon::parse("{$year}-{$month}-1")->startOfMonth()->format('Y-m-d 00:00:00');
            $finish_at = Carbon::parse("{$year}-{$month}-1")->endOfMonth()->format('Y-m-d 23:59:59');
            $maintain = Maintain::with(['Parent'])->where('unique_code', request('maintain_unique_code'))->first();

            // 任务统计
            $mission_statistic = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'mission' as type")
                ->join(DB::raw('task_station_check_orders tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty(request('project_id')),
                    function ($query) {
                        $query->where('cpro.id', request('project_id'));
                    }
                )
                ->when(
                    !empty(request('project_type')),
                    function ($query) {
                        $query->where('cpro.type', request('project_type'));
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // 统计所有，按照车间分组
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain),
                    function ($query) use ($maintain) {
                        switch ($maintain->type) {
                            case '现场车间':
                                // 按照车间统计，按照车站分组
                                $query
                                    ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                                    ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                                    ->addSelect(['s.name', 's.unique_code']);
                                break;
                            case '车站':
                                // 按照工区统计，按照5级负责人分组
                                $query
                                    ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                                    ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                                    ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                                break;
                        }
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type',])
                ->get();

            // 完成统计
            $finish_statistic = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'finish' as type")
                ->join(DB::raw('task_station_check_orders as tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->where('tscei.processor_id', '<>', 0)
                ->where('tscei.processed_at', '<>', null)
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty(request('project_id')),
                    function ($query) {
                        $query->where('cpro.id', request('project_id'));
                    }
                )
                ->when(
                    !empty(request('project_type')),
                    function ($query) {
                        $query->where('cpro.type', request('project_type'));
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // 统计所有，按照车间分组
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain),
                    function ($query) use ($maintain) {
                        switch ($maintain->type) {
                            case '现场车间':
                                // 按照车间统计，按照车站分组
                                $query
                                    ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                                    ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                                    ->addSelect(['s.name', 's.unique_code']);
                                break;
                            case '车站':
                                // 按照工区统计，按照5级负责人分组
                                $query
                                    ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                                    ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                                    ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                                break;
                        }
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type',])
                ->get();

            $statistics = [];

            return JsonResponseFacade::data([
                // 'plan_statistic' => $plan_statistic,
                'mission_statistic' => $mission_statistic,
                'finish_statistic' => $finish_statistic,
            ]);
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取任务单列表
     */
    final public function getTaskStationCheckOrders(): JsonResponse
    {
        try {
            switch (session('account.rank')->code ?? null) {
                case 'SceneWorkAreaPrincipal':
                    $current_work_area_unique_code = session('account.work_area_unique_code');
                    if (!$current_work_area_unique_code) return JsonResponseFacade::errorForbidden('该工长未绑定工区');

                    $origin_at = now()->startOfMonth()->format('Y-m-d 00:00:00');
                    $finish_at = now()->endOfMonth()->format('Y-m-d 23:59:59');

                    $list = DB::table('task_station_check_entire_instances as tscei')
                        ->selectRaw("DATE_FORMAT(tscei.processed_at, '%d') as processed_at,count(tscei.entire_instance_identity_code) as count")
                        ->join(DB::raw('task_station_check_orders tsco'), 'tscei.task_station_check_order_sn', '=', 'tsco.serial_number')
                        ->where('tsco.work_area_unique_code', $current_work_area_unique_code)
                        ->whereBetween('tscei.processed_at', [$origin_at, $finish_at])
                        ->groupBy(['tscei.processed_at'])
                        ->get();
                    $total = DB::table('task_station_check_orders')->selectRaw('sum(number) as total')->whereBetween('expiring_at', [$origin_at, $finish_at])->value('total');
                    $data = [
                        'info' => [
                            'work_area_name' => DB::table('work_areas')->where('unique_code', $current_work_area_unique_code)->value('name'),
                            'total' => $total,
                        ],
                        'list' => $list
                    ];
                    return JsonResponseFacade::data($data);
                    break;
                default:
                    $task_station_check_orders = ModelBuilderFacade::init(
                        request(),
                        TaskStationCheckOrder::with([
                            'TaskStationCheckEntireInstances',
                            'PrincipalIdLevel1',
                            'PrincipalIdLevel2',
                            'PrincipalIdLevel3',
                            'PrincipalIdLevel4',
                            'PrincipalIdLevel5',
                            'MaintainStation',
                            'WithCheckPlan',
                            'WithCheckPlan.WithCheckProject',
                        ])
                            ->withCount('TaskStationCheckEntireInstances'),
                        [
                            'principal_id_level_1',
                            'principal_id_level_2',
                            'principal_id_level_3',
                            'principal_id_level_4',
                            'principal_id_level_5',
                        ]
                    )
                        ->extension(function ($TaskStationCheckOrder) {
                            return $TaskStationCheckOrder
                                ->where(function ($query) {
                                    $query
                                        ->where('principal_id_level_1', session('account.id'))
                                        ->orWhere('principal_id_level_2', session('account.id'))
                                        ->orWhere('principal_id_level_3', session('account.id'))
                                        ->orWhere('principal_id_level_4', session('account.id'))
                                        ->orWhere('principal_id_level_5', session('account.id'));
                                });
                        })
                        ->all();

                    return JsonResponseFacade::data(['task_station_check_orders' => $task_station_check_orders]);
                    break;
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取现场检修任务详情
     * @param string $sn
     * @return JsonResponse
     */
    final public function getTaskStationCheckOrder(string $sn): JsonResponse
    {
        try {
            $task_station_check_order = TaskStationCheckOrder::with([
                'TaskStationCheckEntireInstances',
                'TaskStationCheckEntireInstances.EntireInstance',
                'TaskStationCheckEntireInstances.Processor',
                'PrincipalIdLevel1',
                'PrincipalIdLevel2',
                'PrincipalIdLevel3',
                'PrincipalIdLevel4',
                'PrincipalIdLevel5',
                'WithCheckPlan',
                'WithCheckPlan.WithCheckProject',
            ])
                ->where('serial_number', $sn)
                ->withCount(['TaskStationCheckEntireInstances' => function ($TaskStationCheckEntireInstances) {
                    return $TaskStationCheckEntireInstances->where('processor_id', '<>', 0)
                        ->where('processed_at', '<>', null);
                }])
                ->firstOrFail();

            return JsonResponseFacade::data(['task_station_check_order' => $task_station_check_order]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 新建现场检修任务单
     * @param Request $request
     * @return mixed
     */
    final public function postTaskStationCheckOrder(Request $request): JsonResponse
    {
        try {
            // 获取一级负责人（科长）
            // $principal1 = Account::with([])->where('id', $request->get('principal_id_1'))->where('rank', 'SectionChief')->first();
            $principal1 = Account::with([])->where('rank', 'SectionChief')->where('id', 4615)->orderByDesc('id')->first();
            if (!$principal1) return JsonResponseFacade::errorEmpty('一级负责人（科长）不存在');
            // 获取二级负责人（主管工程师）
            // $principal2 = Account::with([])->where('id', $request->get('principal_id_2'))->where('rank', 'EngineerMaster')->first();
            $principal2 = Account::with([])->where('rank', 'EngineerMaster')->where('workshop_unique_code', session('workshop_unique_code'))->orderByDesc('id')->first();
            if (!$principal2) return JsonResponseFacade::errorEmpty('二级负责人（主管工程师）不存在');
            // 获取现场工区职工
            $principal5 = Account::with(['WorkAreaByUniqueCode', 'Workshop'])->where('id', $request->get('principal_id_5'))->first();
            if (!$principal5) return JsonResponseFacade::errorEmpty('现场工区职工不存在');
            if ($principal5->rank->code != 'SceneWorkAreaCrew') return JsonResponseFacade::errorForbidden("现场工区职工职务错误");
            if (!@$principal5->WorkAreaByUniqueCode) return JsonResponseFacade::errorEmpty('现场工区职工没有分配工区', $principal5);
            if (!@$principal5->WorkAreaByUniqueCode->Workshop) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}没有所属车间");
            // 获取现场工区工长
            $principal4 = Account::with([])->where('rank', 'SceneWorkAreaPrincipal')->where('work_area_unique_code', $principal5->WorkAreaByUniqueCode->unique_code)->get();
            if ($principal4->isEmpty()) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}没有设置工长", $principal4);
            if ($principal4->count() > 1) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}有多个工长", $principal4);
            $principal4 = $principal4->first();
            if (!session('account.workshop_unique_code')) return JsonResponseFacade::errorEmpty('当前用户没有所属车间');
            // 项目
            if (!$request->get('project')) return JsonResponseFacade::errorEmpty('项目不能为空');
            // 截止日期
            if (!$request->get('expiring_at')) return JsonResponseFacade::errorEmpty('截止日期不能为空');
            $expiring_at = Carbon::parse($request->get('expiring_at'))->format('Y-m-d 00:00:00');
            // 设备
            $diff = [];
            $identity_codes = EntireInstance::with([])->whereIn('identity_code', $request->get('identity_codes'))->pluck('identity_code')->toArray();
            if (!$identity_codes) return JsonResponseFacade::errorForbidden("没有找到以下设备：<br>" . join("<br>", $identity_codes));
            $diff = array_diff($identity_codes, $request->get('identity_codes'));
            if (!empty($diff)) return JsonResponseFacade::errorForbidden("没有找到以下设备：<br>" . join("<br>", $diff));

            if ($request->get('check_plan_serial_number')) {
                // 根据计划创建任务
                $check_plan = CheckPlan::with([])->where('serial_number', $request->get('check_plan_serial_number'))->first();
                if (!$check_plan) return JsonResponseFacade::errorEmpty('现场检修计划不存在');

                // 获取车站
                if (!$check_plan->station_unique_code) return JsonResponseFacade::errorEmpty('计划中车站代码丢失');
                $station = Maintain::with([])->where('unique_code', $check_plan->station_unique_code)->first();
                if (!$station) return JsonResponseFacade::errorForbidden('没有找到车站');

                // 创建现场检修任务
                $task_station_check_order = TaskStationCheckOrder::with([])->create([
                    'serial_number' => TaskStationCheckOrder::generateSerialNumber(session('account.workshop_unique_code')),
                    'work_area_unique_code' => $principal5->WorkAreaByUniqueCode->unique_code,  // 工区代码
                    'scene_workshop_unique_code' => session('account.workshop_unique_code'),  // 车间代码
                    'maintain_station_unique_code' => $check_plan->station_unique_code,  // 车站代码
                    'principal_id_level_1' => $principal1->id,  // 1级负责人（科长）
                    'principal_id_level_2' => $principal2->id,  // 2级负责人（主管工程师）
                    'principal_id_level_3' => session('account.id'),  // 3级负责人（现场车间主任）
                    'principal_id_level_4' => $principal4->id,  // 4级负责人（现场工区工长）
                    'principal_id_level_5' => $principal5->id,  // 5级负责人（现场工区职工）
                    'expiring_at' => $expiring_at,  // 截止日期
                    'title' => "{$principal5->Workshop->name} {$principal5->WorkAreaByUniqueCode->name} {$principal5->nickname} " . date('Y-m-d', strtotime($expiring_at)),
                    'unit' => $request->get('unit'),  // 单位
                    'number' => count($request->get('identity_codes')),  // 任务数量
                    'check_plan_serial_number' => $check_plan->serial_number,
                ]);

                // 添加设备
                foreach ($request->get('identity_codes') as $identity_code) {
                    // 添加现场检修任务设备
                    TaskStationCheckEntireInstance::with([])->create([
                        'task_station_check_order_sn' => $task_station_check_order->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                    ]);

                    // 修改现场检修计划设备is_use状态
                    CheckPlanEntireInstance::with([])
                        ->where('entire_instance_identity_code', $identity_code)
                        ->where('check_plan_serial_number', request('check_plan_serial_number'))
                        ->update(['updated_at' => now(), 'is_use' => 1]);
                }

                // 检查计划中的设备是否都已经被分配，如果都已经分配则修改计划为进行中
                $cpei = CheckPlanEntireInstance::with([])
                    ->select(['id'])
                    ->where('check_plan_serial_number', $request->get('check_plan_serial_number'))
                    ->get();
                if ($cpei->count() > 0) {
                    if ($cpei->count() == $cpei->where('is_use', 1)->count())
                        $check_plan->fill(['status' => 3])->saveOrFail();
                }
            } else {
                // 没有计划，同时创建计划
                // 获取现场检修任务项目
                $check_project = CheckProject::with([])->where('name', $request->get('project'))->where('type', 1)->first();
                if (!$check_project) $check_project = CheckProject::with([])->create(['name' => $request->get('project'), 'type' => 1]);

                // 获取车站
                if (!$request->get('station_unique_code')) return JsonResponseFacade::errorEmpty('没有选择车站');
                $station = Maintain::with([])->where('unique_code', $request->get('station_unique_code'))->first();
                if (!$station) return JsonResponseFacade::errorForbidden('没有找到车站');

                // 创建现场检修计划
                $check_plan = CheckPlan::with([])->create([
                    'serial_number' => CheckPlan::generateSerialNumber(session('account.workshop_unique_code')),
                    'status' => 3,
                    'check_project_id' => $check_project->id,
                    'station_unique_code' => $station->unique_code,
                    'unit' => $request->get('unit'),
                    'expiring_at' => $expiring_at,
                    'number' => count($request->get('identity_codes')),
                    'account_id' => session('account.id'),
                ]);

                // 创建现场检修任务
                $task_station_check_order = TaskStationCheckOrder::with([])->create([
                    'serial_number' => TaskStationCheckOrder::generateSerialNumber(session('account.workshop_unique_code')),
                    'work_area_unique_code' => $principal5->WorkAreaByUniqueCode->unique_code,  // 工区代码
                    'scene_workshop_unique_code' => session('account.workshop_unique_code'),  // 车间代码
                    'maintain_station_unique_code' => $request->get('station_unique_code'),
                    'principal_id_level_1' => $principal1->id,  // 1级负责人（科长）
                    'principal_id_level_2' => $principal2->id,  // 2级负责人（主管工程师）
                    'principal_id_level_3' => session('account.id'),  // 3级负责人（现场车间主任）
                    'principal_id_level_4' => $principal4->id,  // 4级负责人（现场工区工长）
                    'principal_id_level_5' => $principal5->id,  // 5级负责人（现场工区职工）
                    'expiring_at' => $expiring_at,  // 截止日期
                    'title' => "{$principal5->Workshop->name} {$principal5->WorkAreaByUniqueCode->name} {$principal5->nickname} " . date('Y-m-d', strtotime($expiring_at)),
                    'unit' => $request->get('unit'),  // 单位
                    'number' => count($request->get('identity_codes')),  // 任务数量
                    'check_plan_serial_number' => $check_plan->serial_number,
                ]);

                // 添加设备
                foreach ($request->get('identity_codes') as $identity_code) {
                    // 添加现场检修计划设备
                    CheckPlanEntireInstance::with([])->create([
                        'check_plan_serial_number' => $check_plan->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                        'is_use' => 1,
                        'task_station_check_order_serial_number' => $task_station_check_order->serial_number,
                    ]);

                    // 添加现场检修任务设备
                    TaskStationCheckEntireInstance::with([])->create([
                        'task_station_check_order_sn' => $task_station_check_order->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                    ]);
                }
            }

            return JsonResponseFacade::created(['task_station_check_order' => $task_station_check_order], '分配任务成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取任务设备
     * @return mixed
     */
    final public function getTaskStationCheckEntireInstances(): JsonResponse
    {
        try {
            $task_station_check_entire_instances = ModelBuilderFacade::init(
                request(),
                TaskStationCheckEntireInstance::with(['TaskStationCheckOrder']),
                ['maintain_station_unique_code']
            )
                ->extension(function ($query) {
                    return $query->when(request('maintain_station_unique_code'), function ($query) {
                        $query->whereHas('TaskStationCheckOrder', function ($TaskStationCheckOrder) {
                            $TaskStationCheckOrder->where('maintain_station_unique_code', request('maintain_station_unique_code'));
                        });
                    });
                })
                ->all();

            return JsonResponseFacade::data(['task_station_check_entire_instances' => $task_station_check_entire_instances]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加任务设备
     * @param Request $request
     * @param string $sn
     * @return mixed
     * @throws Throwable
     */
    final public function postTaskStationCheckEntireInstance(Request $request, string $sn): JsonResponse
    {
        try {
            // 查询任务
            $task_station_check_order = TaskStationCheckOrder::with([])->where('serial_number', $sn)->first();
            if (!$task_station_check_order) return JsonResponseFacade::errorEmpty('现场检修任务不存在');

            // 查询设备
            $entire_instance = EntireInstance::with([])->select(['id'])->where('identity_code', $request->get('entire_instance_identity_code'))->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty('设备不存在');

            // 记录设备
            $task_station_check_entire_instance = TaskStationCheckEntireInstance::with([])
                ->where('task_station_check_order_sn', $sn)
                ->where('entire_instance_identity_code', $request->get('entire_instance_identity_code'))
                ->first();
            if (!$task_station_check_entire_instance) return JsonResponseFacade::errorEmpty('设备不存在');
            $task_station_check_entire_instance->fill(
                array_merge($request->all(), [
                    'task_station_check_order_sn' => $sn,
                    'processor_id' => session('account.id'),
                    'processed_at' => date('Y-m-d H:i:s'),
                ])
            )
                ->saveOrFail();

            // 生成设备日志
            EntireInstanceLogFacade::makeOne(
                '现场检修',
                $request->get('entire_instance_identity_code'),
                2,
                '',
                session('account.nickname')
                . "完成现场检修：{$task_station_check_order->project} "
                . '<a href="javascript:" onclick="fnShowTaskStationCheckEntireInstanceImages(' . $task_station_check_entire_instance->id . ')">查看现场工作图片</a>'
            );

            // 检查任务是否完成
            $task_station_check_order = TaskStationCheckOrder::with(['TaskStationCheckEntireInstances'])
                ->withCount(['TaskStationCheckEntireInstances' => function ($TaskStationEntireInstances) {
                    return $TaskStationEntireInstances->where('processor_id', '<>', 0)
                        ->where('processed_at', '<>', null);
                }])
                ->where('serial_number', $sn)
                ->first();
            if ($task_station_check_order->task_station_check_entire_instances_count >= $task_station_check_order->number)
                $task_station_check_order->fill(['updated_at' => now(), 'status' => 'DONE', 'finished_at' => now()])->saveOrFail();

            // 检查计划是否完成
            $task_station_check_orders_same_plan_done_count = TaskStationCheckOrder::with([])
                ->where('check_plan_serial_number', $task_station_check_order->check_plan_serial_number)
                ->where('status', 'DONE')
                ->count('id');
            $task_station_check_orders_same_plan_undone_count = TaskStationCheckOrder::with([])
                ->where('check_plan_serial_number', $task_station_check_order->check_plan_serial_number)
                ->where('status', 'UNDONE')
                ->count('id');
            if (($task_station_check_orders_same_plan_done_count == $task_station_check_orders_same_plan_undone_count) && ($task_station_check_orders_same_plan_undone_count > 0)) {
                // 修改计划为已完成
                CheckPlan::with([])->where('serial_number', $task_station_check_order->check_plan_serial_number)->update(['updated_at' => now(), 'status' => 1]);
            }

            return JsonResponseFacade::updated([
                'finished_at' => date('Y-m-d H:i:s'),
                'task_station_check_order' => $task_station_check_order,
                'task_station_check_entire_instance' => $task_station_check_entire_instance
            ], '完成任务');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 删除任务设备
     * @param Request $request
     * @param string $sn
     * @return mixed
     */
    final public function deleteTaskStationCheckEntireInstance(Request $request, string $sn): JsonResponse
    {
        try {
            TaskStationCheckEntireInstance::with([])
                ->where('task_station_check_order_sn', $sn)
                ->where('entire_instance_identity_code', $request->get('entire_instance_identity_code'))
                ->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 删除现场检修任务
     * @param string $sn
     * @return mixed
     */
    final public function deleteTaskStationCheckOrder(string $sn): JsonResponse
    {
        try {
            // 获取任务
            $task_station_check_order = TaskStationCheckOrder::with(['TaskStationCheckEntireInstances'])->where('serial_number', $sn)->firstOrFail();
            if ($task_station_check_order->TaskStationCheckEntireInstances->isNotEmpty()) return JsonResponseFacade::errorForbidden('任务已经开始执行，不能删除');

            $task_station_check_order->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取检修计划列表或详情
     * @param string $serial_number
     * @return mixed
     */
    final public function getCheckPlan(string $serial_number = ''): JsonResponse
    {
        try {
            if ($serial_number) {
                $check_plan = CheckPlan::with([
                    'WithAccount',
                    'WithCheckProject',
                    'WithStation',
                    'CheckPlanEntireInstances',
                    'CheckPlanEntireInstances.EntireInstance',
                ])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();
                return JsonResponseFacade::data(['check_plan' => $check_plan]);
            } else {
                $check_plans = ModelBuilderFacade::init(
                    request(),
                    CheckPlan::with([
                        'WithAccount',
                        'WithCheckProject',
                        'WithStation',
                        'CheckPlanEntireInstances',
                        'CheckPlanEntireInstances.EntireInstance',
                    ])
                )
                    ->all();
                return JsonResponseFacade::data(['check_plans' => $check_plans]);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取设备列表 用于创建临时任务
     * @return JsonResponse
     */
    final public function getEntireInstanceForCreateTaskStationCheckOrder(): JsonResponse
    {
        try {
            $entire_instances = ModelBuilderFacade::init(
                request(),
                EntireInstance::with([
                    'Category',
                    'EntireModel',
                    'PartModel',
                ])
            )
                ->extension(function ($builder) {
                    return $builder->where('crossroad_number', '<>', '');
                })
                ->all()
                ->groupBy(['crossroad_number']);

            return JsonResponseFacade::data(['entire_instances' => $entire_instances]);
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取现场检修项目（列表、详情）
     */
    final public function getCheckProject(int $id = 0)
    {
        try {
            if ($id > 0) {
                $check_project = ModelBuilderFacade::init(request(), CheckProject::with([]))
                    ->extension(function ($builder) use ($id) {
                        return $builder->where('id', $id);
                    })
                    ->firstOrFail();
                return JsonResponseFacade::data(['check_project' => $check_project]);
            } else {
                $check_projects = ModelBuilderFacade::init(request(), CheckProject::with([]))->all();
                return JsonResponseFacade::data(['check_projects' => $check_projects]);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
