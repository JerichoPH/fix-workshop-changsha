<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\WechatException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Facades\WechatMiniAppFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\StationLocationStoreRequest;
use App\Model\BasicParagraph;
use App\Model\Category;
use App\Model\CollectDeviceOrder;
use App\Model\CollectDeviceOrderEntireInstance;
use App\Model\CollectDeviceOrderModel;
use App\Model\EntireInstance;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Maintain;
use App\Model\PartModel;
use App\Model\StationInstallLocationCode;
use App\Model\StationInstallUser;
use App\Model\StationLocation;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class WechatMiniAppController extends Controller
{
    /**
     * 微信小程序登陆 通过code获取openid
     */
    final public function getWechatOpenIdByJsCode()
    {
        try {
            if (!request('js_code')) return response()->json(['msg' => 'js_code参数丢失', 'status' => 404], 404);

            $curl = new Curl();
            $curl->get('https:// api.weixin.qq.com/sns/jscode2session', [
                'appid' => 'wx973fb35824e78f1c',
                'secret' => '16cbff25158419fe912e28f4c991c834',
                'js_code' => request('js_code'),
                'grant_type' => 'authorization_code'
            ]);

            $res = json_decode($curl->response);
            if (($res->errcode ?? 0) > 0) {
                return response()->json(['msg' => '登录失败', 'status' => 403, 'details' => $res]);
            } else {
                // 登陆成功检查员工是否已经登记
                $user = StationInstallUser::with([])
                    ->where('wechat_open_id', request('wechat_open_id'))
                    ->first();

                // 获取电务段列表
                $basicParagraphs = BasicParagraph::with([])->get();

                return response()->json(['msg' => '登录成功', 'status' => 200, 'data' => $res, 'user' => $user, 'paragraphs' => $basicParagraphs]);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在', 'status' => 404,], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 检查是否已经注册
     */
    final public function getCheckStationInstallUser()
    {
        try {
            $users = StationInstallUser::with([])
                ->where('wechat_open_id', request('wechat_open_id'))
                ->firstOrFail();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $users]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '未注册', 'status' => 404], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 注册用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postRegisterStationInstallUser(Request $request)
    {
        try {
            $user = StationInstallUser::with([])
                ->where('wechat_open_id', $request->get('wechat_open_id'))
                ->first();

            if ($user) {
                $user->nickname = $request->get('nickname');
                $user->saveOrFail();
            } else {
                $user = StationInstallUser::with([])
                    ->create([
                        'wechat_open_id' => request('wechat_open_id'),
                        'nickname' => request('nickname'),
                    ]);
            }

            return response()->json(['msg' => '保存成功', 'status' => 200, 'data' => $user]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在', 'status' => 404,], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 获取电务段
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getBasicParagraphs()
    {
        try {
            $basicParagraphs = BasicParagraph::with([])->get();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $basicParagraphs]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在', 'status' => 404,], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 获取现场车间列表
     * @param string $paragraphUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getSceneWorkshopsByParagraphUniqueCode(string $paragraphUniqueCode = null)
    {
        try {
            $sceneWorkshops = Maintain::with(['Subs'])
                ->where('parent_unique_code', $paragraphUniqueCode ?? env('ORGANIZATION_CODE'))
                ->where('type', 'SCENE_WORKSHOP')
                ->get();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $sceneWorkshops]);
        } catch (\Exception $e) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 通过名称获取车站
     */
    final public function getStationsByName()
    {
        try {
            if (!request('name')) return response()->json(['msg' => '名称不能为空', 'status' => 403], 403);
            $stations = Maintain::with(['Parent'])->where('type', 'STATION')->where('name', 'like', '%' . request('name') . '%')->get();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $stations]);
        } catch (\Exception $e) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取车站列表
     * @param string $sceneWorkshopUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getStationsBySceneWorkshopUniqueCode(string $sceneWorkshopUniqueCode)
    {
        try {
            $stations = Maintain::with([])->where('type', 'STATION')->where('parent_unique_code', $sceneWorkshopUniqueCode)->pluck('name', 'unique_code');
            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $stations]);
        } catch (\Exception $e) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取型号列表
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getSubModels()
    {
        try {
            $models = Category::with([
                'EntireModels' => function ($EntireModels) {
                    return $EntireModels->select([
                        'unique_code',
                        'name',
                        'is_sub_model',
                        'parent_unique_code',
                        'category_unique_code',
                    ])
                        ->where('is_sub_model', false);
                },
                'EntireModels.Subs' => function ($Subs) {
                    return $Subs->select(['unique_code', 'name', 'is_sub_model', 'parent_unique_code', 'category_unique_code',])->where('is_sub_model', true);
                }
            ])
                ->select(['unique_code', 'name'])
                ->where('unique_code', 'like', 'Q%')
                ->get();

            return JsonResponseFacade::data($models);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 根据名称获取型号列表
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getSubModelsByName()
    {
        try {
            if (!request('name')) return response()->json(['msg' => '名称不能为空', 'status' => 403], 403);
            $models = EntireModel::with(['Parent'])->where('is_sub_model', true)->where('name', 'like', '%' . request('name') . '%')->get();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $models]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在', 'status' => 404,], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 纠正现场上道位置
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCorrectMaintainLocation(Request $request)
    {
        try {
            // $maintain = Maintain::with(['Parent'])
            //     ->where('type', 'STATION')
            //     ->where('name', $request->get('maintain_station_name'))
            //     ->first();
            // if (!$maintain) return response()->json(['msg' => '没有找到车站', 'status' => 404], 404);

            $stationInstallUser = StationInstallUser::with([])->where('wechat_open_id', $request->get('wechat_open_id'))->first();
            if (!$stationInstallUser) return response()->json(['msg' => '没有找到用户', 'status' => 404], 404);

            $entireInstance = EntireInstance::with([])
                ->where('identity_code', $request->get('entire_instance_identity_code'))
                // ->where('maintain_station_name', $request->get('maintain_station_name'))
                ->firstOrFail();

            $stationInstallLocationCode = StationInstallLocationCode::with([])
                ->where('entire_instance_identity_code', $entireInstance->identity_code)
                ->first();

            if ($stationInstallLocationCode) {
                $stationInstallLocationCode->fill([
                    // 'scene_workshop_unique_code' => $maintain->Parent ? ($maintain->Parent->unique_code ?? '') : '',
                    // 'maintain_station_unique_code' => $maintain->unique_code,
                    'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                    'crossroad_number' => $request->get('crossroad_number') ?? '',
                    'is_indoor' => 1,
                    'section_unique_code' => '',
                    'processor_id' => $stationInstallUser->id,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ])
                    ->saveOrFail();
            } else {
                $stationInstallLocationCode = StationInstallLocationCode::with([])->create([
                    // 'scene_workshop_unique_code' => $maintain->Parent ? ($maintain->Parent->unique_code ?? '') : '',
                    // 'maintain_station_unique_code' => $maintain->unique_code,
                    'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                    'crossroad_number' => $request->get('crossroad_number') ?? '',
                    'is_indoor' => 1,
                    'section_unique_code' => '',
                    'processor_id' => $stationInstallUser->id,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ]);
            }

            $last3 = StationInstallLocationCode::with(['Station'])
                ->orderByDesc('updated_at')
                ->limit(3)
                ->where('processor_id', $stationInstallUser->id)
                ->get();

            return response()->json(['msg' => '保存成功', 'status' => 200, 'data' => $stationInstallLocationCode, 'last3' => $last3]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '没有找到设备', 'status' => 404], 404);
        } catch (\Throwable $e) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 根据员工获取绑定记录
     */
    final public function getStationInstallLocationCodesByWechatOpenId()
    {
        try {
            if (!request('wechat_open_id')) return response()->json(['msg' => '微信没有授权', 'status' => 403], 403);
            $stationInstallUser = StationInstallUser::with([])->where('wechat_open_id', request('wechat_open_id'))->first();
            if (!$stationInstallUser) return response()->json(['msg' => '员工不存在', 'status' => 404], 404);

            $stationInstallLocationCodes = StationInstallLocationCode::with(['Station'])->orderByDesc('updated_at')->where('processor_id', $stationInstallUser->id)->get();

            return response()->json(['msg' => '读取成功', 'status' => 200, 'data' => $stationInstallLocationCodes]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在', 'status' => 404,], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'status' => 500, 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 获取车站记录
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getStationLocationsByWechatOpenId()
    {
        try {
            $processor = StationInstallUser::with([])->where('wechat_open_id', request('wechat_open_id'))->first();
            if (!$processor) return JsonResponseFacade::errorEmpty('员工未登记');

            $stationLocations = StationLocation::with(['Processor'])->orderByDesc('id')->where('processor_id', $processor->id)->get();

            return JsonResponseFacade::data(['station_locations' => $stationLocations]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 补登车站记录
     * @param Request $request
     * @return mixed
     */
    final public function postStationLocation(Request $request)
    {
        try {
            $v = Validator::make($request->all(), StationLocationStoreRequest::$RULES, StationLocationStoreRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            $processor = StationInstallUser::with([])->where('wechat_open_id', $request->get('wechat_open_id'))->first();
            if (!$processor) return JsonResponseFacade::errorEmpty('员工未登记');

            $station = Maintain::with(['Parent'])->where('type', 'STATION')->where('name', $request->get('maintain_station_name'))->first();
            if (!$station) return JsonResponseFacade::errorEmpty('车站不存在');
            if (!$station->Parent) return JsonResponseFacade::errorEmpty('车站没有对应的现场车间');

            $stationLocation = StationLocation::with(['Processor'])
                ->where('maintain_station_name', $request->get('maintain_station_name'))
                ->where('processor_id', $processor->id)
                ->first();

            $data = array_merge($request->except('wechat_open_id'), [
                'processor_id' => $processor->id,
                'scene_workshop_name' => $station->Parent->name,
                'scene_workshop_unique_code' => $station->parent_unique_code,
                'maintain_station_unique_code' => $station->unique_code,
                'maintain_station_name' => $station->name,
            ]);

            if ($stationLocation) {
                $stationLocation->fill($data)->saveOrFail();
                $last3 = StationLocation::with(['Processor'])->orderByDesc('id')->where('processor_id', $processor->id)->get();
                return JsonResponseFacade::updated(['station_location' => $stationLocation, 'last3' => $last3]);
            } else {
                $stationLocation = StationLocation::with(['Processor'])->create($data);
                $last3 = StationLocation::with(['Processor'])->orderByDesc('id')->where('processor_id', $processor->id)->get();
                return JsonResponseFacade::created(['station_location' => $stationLocation, 'last3' => $last3]);
            }

        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 生成数据采集单
     * @param Request $request
     */
    final public function postCollectDeviceOrder(Request $request)
    {
        try {
            $stationInstallUser = StationInstallUser::with([])
                ->where('wechat_open_id', $request->get('wechat_open_id'))
                ->first();
            if (!$stationInstallUser) return JsonResponseFacade::errorUnauthorized('用户未登记');

            DB::beginTransaction();
            // 获取该用户下所有没有生成单据的设备数据
            $count = CollectDeviceOrderEntireInstance::with([])
                ->where('station_install_user_id', $stationInstallUser->id)
                ->where('collect_device_order_sn', '')
                ->count();
            if ($count == 0) return JsonResponseFacade::errorEmpty('没有需要提交的数据');

            $collectDeviceOrder = CollectDeviceOrder::with([])->create([
                'serial_number' => strtoupper(md5(date('YmdHis') . $request->get('wechat_open_id'))),
                'station_install_user_id' => $stationInstallUser->id,
            ]);

            // 更新设备信息
            $collectDeviceOrderEntireInstances = CollectDeviceOrderEntireInstance::with([])
                ->where('station_install_user_id', $stationInstallUser->id)
                ->where('collect_device_order_sn', '')
                ->update(['collect_device_order_sn' => $collectDeviceOrder->serial_number]);
            DB::commit();

            // 生成Excel
            $filename = $this->_makeExcelForCollectDeviceOrder($collectDeviceOrder->serial_number);

            return JsonResponseFacade::created(['collect_device_order' => $collectDeviceOrder, 'filename' => $filename], '提交成功');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 生成器件采集单Excel
     * @param string $collectDeviceOrderSN
     * @return string
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function _makeExcelForCollectDeviceOrder(string $collectDeviceOrderSN)
    {
        $collectDeviceOrder = CollectDeviceOrder::with(['CollectDeviceOrderEntireInstances'])->where('serial_number', $collectDeviceOrderSN)->first();
        if ($collectDeviceOrder->CollectDeviceOrderEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有需要下载的设备');

        $excelData = [];
        $row = 1;
        $collectDeviceOrder->CollectDeviceOrderEntireInstances->each(function ($val) use (&$row, &$excelData) {
            // 计算周期修时间s
            $nextFixingAt = '';
            if ($val->last_installed_time && $val->cycle_fix_value) {
                $nextFixingAt = Carbon::parse($val->last_installed_time)->addYears($val->cycle_fix_value)->format('Y-m-d');
            }
            $row++;
            $excelData[$row] = [
                "A" => $val->entire_instance_serial_number,  // 所编号
                "B" => $val->factory_device_code,  // 厂编号
                "C" => $val->factory_name,  // 供应商
                "D" => $val->model_name,  // 型号
                "E" => '',  // 电机编号
                "F" => $val->made_at ? date('Y-m-d', strtotime($val->made_at)) : '',  // 出厂日期
                "G" => $val->last_out_at ? date('Y-m-d', strtotime($val->last_out_at)) : '',  // 上次检修时间/最新出所时间
                "H" => $val->last_installed_time ? date('Y-m-d', strtotime($val->last_installed_time)) : '',  // 安装日期
                "I" => $val->cycle_fix_value,  // 周期修
                "J" => $nextFixingAt,  // 下次周期修时间
                "K" => $val->life_year,  // 使用寿命
                "L" => date('Y-m-d', strtotime($val->scarping_at)),  // 报废日期
                "M" => $val->maintain_station_name,  // 站名
                "N" => $val->maintain_location_code,  // 位置
                "O" => '',  // 道岔号
                "P" => '',  // 道岔类型
                "Q" => '',  // 配线制
                "R" => '',  // 开向
                "S" => '',  // 表示杆特征
                "T" => '',  // TID码
                "U" => '',  // 出所日期
            ];
        });

        $savePath = 'wechatMiniApp/collectDeviceOrder';
        if (!is_dir(storage_path($savePath))) FileSystem::init(storage_path($savePath))->makeDir();
        $filename = $collectDeviceOrderSN;

        ExcelWriteHelper::save(function ($excel) use ($excelData) {
            $excel->setActiveSheetIndex(0);
            $currentSheet = $excel->getActiveSheet();

            // 字体颜色
            $red = new \PHPExcel_Style_Color();
            $red->setRGB('FF0000');

            // 表头
            $currentSheet->setCellValueExplicit('A1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('B1', '厂编号', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('C1', '供应商*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('D1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('E1', '电机编号', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('F1', '出厂日期/首次入所日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('G1', '上次检修时间/最新出所时间*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('H1', '安装日期', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('I1', '周期修（年）', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('J1', '下次周期修时间', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('K1', '使用寿命(年)', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('L1', '报废日期', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('M1', '站名*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('N1', '位置*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('O1', '道岔号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('P1', '道岔类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('Q1', '配线制*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('R1', '开向*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('S1', '表示杆特征*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('T1', 'TID码*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('U1', '出所日期', \PHPExcel_Cell_DataType::TYPE_STRING);

            // 写入数据
            foreach ($excelData as $row => $excelDatum) {
                foreach ($excelDatum as $key => $val) {
                    $currentSheet->setCellValueExplicit("{$key}{$row}", $val, \PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }

            for ($i = 0; $i < 21; $i++) {
                $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($i))->setWidth(25);
            }

            return $excel;
        },
            storage_path("{$savePath}/{$filename}"),
            ExcelWriteHelper::$VERSION_5
        );

        return $filename;
    }

    /**
     * 保存采集数据设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postCollectDeviceOrderEntireInstance(Request $request)
    {
        try {
            $stationInstallUser = StationInstallUser::with([])
                ->where('wechat_open_id', $request->get('wechat_open_id'))
                ->first();
            if (!$stationInstallUser) return JsonResponseFacade::errorUnauthorized('用户未登记');

            // 验证车检车站
            $maintain = Maintain::with(['Parent'])
                ->where('name', $request->get('maintain_station_name'))
                ->where('type', 'STATION')
                ->first();
            if (!$maintain) return JsonResponseFacade::errorEmpty('车站不存在');
            if (!$maintain->Parent) return JsonResponseFacade::errorEmpty('该车站数据有误，没有找到对应的现场车间');

            // 验证供应商
            $factory = Factory::with([])->where('name', $request->get('factory_name'))->first();
            if (!$factory) return JsonResponseFacade::errorEmpty('供应商不存');

            // 验证型号
            $subModel = EntireModel::with(['EntireModel.Category'])->where('unique_code', $request->get('model_unique_code'))->where('is_sub_model', true)->first();
            $partModel = PartModel::with(['EntireModel.Category'])->where('unique_code', $request->get('model_unique_code'))->first();

            $model = null;
            if ($subModel && !$partModel) $model = $subModel;
            if (!$subModel && $partModel) $model = $partModel;
            if (!$subModel && !$partModel) return JsonResponseFacade::errorEmpty('型号不存在');
            if ($subModel && $partModel) return JsonResponseFacade::errorForbidden('型号存在冲突');
            if (!$model->EntireModel) return JsonResponseFacade::errorEmpty('类型不存在');
            if (!$model->EntireModel->Category) return JsonResponseFacade::errorEmpty('种类不存在');

            // 计算到期日期
            if (!$request->get('made_at') ?? null) return JsonResponseFacade::errorEmpty('出厂日期/首次入所日期不能为空');
            if (!$request->get('life_year') ?? null) return JsonResponseFacade::errorEmpty('使用寿命不能为空或0');
            $scarpingAt = Carbon::parse($request->get('made_at'))->addYears($request->get('life_year'))->format('Y-m-d');

            $status = $request->get('status') ?? null;
            if (!$status) return JsonResponseFacade::errorForbidden('状态参数错误');

            // 写入设备信息
            $collectDeviceOrderEntireInstance = CollectDeviceOrderEntireInstance::with([])
                ->create([
                    'entire_instance_serial_number' => $request->get('entire_instance_serial_number', '') ?? '',
                    'status' => $status,
                    'factory_device_code' => $request->get('factory_device_code', '') ?? '',
                    'factory_name' => $factory->name,
                    'model_unique_code' => $model->unique_code,
                    'model_name' => $model->name,
                    'entire_model_unique_code' => $model->EntireModel->unique_code,
                    'entire_model_name' => $model->EntireModel->name,
                    'category_unique_code' => $model->EntireModel->Category->unique_code,
                    'category_name' => $model->EntireModel->Category->name,
                    'made_at' => $request->get('made_at'),
                    'last_out_at' => $request->get('last_out_at'),
                    'last_installed_time' => $request->get('last_installed_time', 0) ?? 0,
                    'cycle_fix_value' => $request->get('cycle_fix_value') ?? 0,
                    'life_year' => $request->get('life_year') ?? 0,
                    'scarping_at' => $scarpingAt,
                    'maintain_station_name' => $maintain->name ?? '',
                    'maintain_station_unique_code' => $maintain->unique_code ?? '',
                    'maintain_workshop_name' => $maintain->Parent->name ?? '',
                    'maintain_workshop_unique_code' => $maintain->Parent->unique_code ?? '',
                    'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                    'crossroad_number' => $request->get('crossroad_number') ?? '',
                    'open_direction' => $request->get('open_direction') ?? '',
                    'said_rod' => $request->get('said_rod') ?? '',
                    'point_switch_group_type' => $request->get('point_switch_group_type') ?? '',
                    'line_name' => $request->get('line_name') ?? '',
                    'extrusion_protect' => boolval($request->get('extrusion_protect', false) ?? false),
                    'station_install_user_id' => $stationInstallUser->id,
                ]);

            return JsonResponseFacade::created(['entire_instance' => $collectDeviceOrderEntireInstance], '保存成功');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 下载基础数据采集单Excel
     * @param string $collectDeviceOrderSN
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadCollectDeviceOrder(string $collectDeviceOrderSN)
    {
        try {
            $collectDeviceOrder = CollectDeviceOrder::with([])->where('serial_number', $collectDeviceOrderSN)->first();
            if (!$collectDeviceOrder) return JsonResponseFacade::errorEmpty('设备采集单不存在');

            $savePath = 'wechatMiniApp/collectDeviceOrder';
            $filename = $collectDeviceOrderSN;
            if (!is_file(storage_path("{$savePath}/{$filename}.xls"))) $this->_makeExcelForCollectDeviceOrder($collectDeviceOrderSN);

            // return response()->download(storage_path("{$savePath}/{$filename}.xls"), "{$filename}.xls");

            $file = fopen(storage_path("{$savePath}/{$filename}.xls"), "r");
            header("Content-type:text/html;charset=utf-8");
            header("Content-Type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: " . filesize(storage_path("{$savePath}/{$filename}.xls")));
            header("Content-Disposition: attachment; filename={$filename}.xls");
            echo fread($file, filesize(storage_path("{$savePath}/{$filename}.xls")));
            fclose($file);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取供应商列表
     * @return mixed
     */
    final public function getFactories()
    {
        try {
            $factories = Factory::with([])->get()->pluck('name', 'unique_code');
            return JsonResponseFacade::data(['factories' => $factories]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加型号
     * @param Request $request
     */
    final public function postSubModel(Request $request)
    {
        try {
            $category = null;
            $entireModel = null;
            $subModel = null;
            $categoryUniqueCode = $request->get('category_unique_code');
            $entireModelUniqueCode = $request->get('entire_model_unique_code');

            // 如果没有种类则创建种类
            if (!$categoryUniqueCode) {
                // 判断种类是否重复
                if (Category::with([])->where('name', $request->get('category_name'))->first())
                    return JsonResponseFacade::errorForbidden("种类重复：{$request->get('category_name')}");

                // 获取最大种类数量
                $maxCategory = Category::with([])
                    ->where('unique_code', 'like', $request->get('type') . '%')
                    ->count();

                // 生成新种类代码
                $newCategoryUniqueCode = $request->get('type', 'Q') . str_pad(++$maxCategory, 2, '0', 0);

                // 创建种类
                $category = Category::with([])->create([
                    'name' => $request->get('category_name'),
                    'unique_code' => $newCategoryUniqueCode,
                    'race_unique_code' => 2,
                ]);
            } else {
                // 获取种类对象
                $category = Category::with([])->where('unique_code', $categoryUniqueCode)->first();
                if (!$category) return JsonResponseFacade::errorEmpty("没有找到种类：{$request->get('category_unique_code')}");
            }

            // 如果没有类型则创建类型
            if (!$entireModelUniqueCode) {
                // 判断类型名称是否重复
                if (EntireModel::with([])->where('is_sub_model', false)->where('name', $request->get('entire_model_name'))->first())
                    return JsonResponseFacade::errorForbidden("类型重复：{$request->get('entire_model_name')}");

                // 获取最大类型数量
                $maxEntireModel = EntireModel::with([])
                    ->where('is_sub_model', false)
                    ->where('category_unique_code', $category->unique_code)
                    ->count();

                // 生成新类型代码
                $newEntireModelUniqueCode = $category->unique_code . str_pad(++$maxEntireModel, 2, '0', 0);

                // 创建类型
                $entireModel = EntireModel::with([])
                    ->create([
                        'name' => $request->get('entire_model_name'),
                        'unique_code' => $newEntireModelUniqueCode,
                        'category_unique_code' => $category->unique_code,
                        'is_sub_model' => false,
                        'fix_cycle_value' => 0,
                    ]);
            } else {
                $entireModel = EntireModel::with([])->where('unique_code', $entireModelUniqueCode)->first();
                if (!$entireModel) return JsonResponseFacade::errorEmpty("没有找到类型：{$request->get('entire_model_name')}");
            }

            // 创建型号
            // 判断型号是否存在
            if (EntireModel::with([])
                ->where('is_sub_model', true)
                ->where('parent_unique_code', $entireModel->unique_code)
                ->where('name', $request->get('model_name'))
                ->first())
                return JsonResponseFacade::errorForbidden("型号重复：{$request->get('model_name')}");

            // 获取最大型号
            $maxModel = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $entireModel->unique_code)->count();
            $newModelUniqueCode = $entireModel->unique_code . str_pad(TextFacade::to36(++$maxModel), 2, '0', 0);
            $subModel = EntireModel::with([])->create([
                'name' => $request->get('sub_model_name'),
                'unique_code' => $newModelUniqueCode,
                'category_unique_code' => $category->unique_code,
                'fix_cycle_unit' => 'YEAR',
                'fix_cycle_value' => $request->get('fix_cycle_value'),
                'is_sub_model' => true,
                'parent_unique_code' => $entireModel->unique_code,
            ]);

            CollectDeviceOrderModel::with([])->create([
                'category_unique_code' => $category->unique_code,
                'entire_model_unique_code' => $entireModel->unique_code,
                'sub_model_unique_code' => $subModel->unique_code,
                'part_model_unique_code' => '',
                'category_name' => $category->name,
                'entire_model_name' => $entireModel->name,
                'sub_model_name' => $subModel->name,
                'part_model_name' => '',
            ]);

            return JsonResponseFacade::created(
                Category::with([
                    'EntireModels' => function ($EntireModels) {
                        return $EntireModels->select([
                            'unique_code',
                            'name',
                            'is_sub_model',
                            'parent_unique_code',
                            'category_unique_code',
                        ])
                            ->where('is_sub_model', false);
                    },
                    'EntireModels.Subs' => function ($Subs) {
                        return $Subs->select(['unique_code', 'name', 'is_sub_model', 'parent_unique_code', 'category_unique_code',])->where('is_sub_model', true);
                    }
                ])
                    ->select(['unique_code', 'name'])
                    ->where('unique_code', 'like', 'Q%')
                    ->get()
            );
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取 access_token
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getAccessToken()
    {
        try {
            return JsonResponseFacade::data(['access_token' => WechatMiniAppFacade::getAccessToken()]);
        } catch (WechatException $e) {
            return JsonResponseFacade::errorForbidden($e->getMessage());
        } catch (\Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取js_api_ticket
     * @return mixed
     */
    final public function getJsApiTicket()
    {
        try {
            return JsonResponseFacade::data(['js_api_ticket' => WechatMiniAppFacade::getJsApiTicket()]);
        } catch (WechatException $e) {
            return JsonResponseFacade::errorForbidden($e->getMessage());
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取js_api_signature
     */
    final public function getJsApiSignature()
    {
        try {
            $js_api_ticket = WechatMiniAppFacade::getJsApiTicket();
            $nonce = Str::random();
            $timestamp = strval(time());
            $url = request('url');
            $params = [
                'noncestr' => $nonce,
                'timestamp' => $timestamp,
                'jsapi_ticket' => $js_api_ticket,
                'url' => $url,
            ];
            $params = array_filter($params);
            ksort($params);
            $params2 = [];
            foreach ($params as $k => $v) {
                $params2[] = "{$k}={$v}";
            }
            $signature = sha1(join('&', $params2));
            return JsonResponseFacade::data([
                'js_api_ticket' => $js_api_ticket,
                'noncestr' => $nonce,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'app_id' => WechatMiniAppFacade::getAppId(),
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
