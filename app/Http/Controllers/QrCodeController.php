<?php

namespace App\Http\Controllers;

use App\Model\Position;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Jericho\HttpResponseHelper;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function show($entireInstanceIdentityCode)
    {
        $qrCodeContent = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(512)->encoding('UTF-8')->errorCorrection('H')->generate($entireInstanceIdentityCode);
        return view($this->view())->with("qrCodeContent", $qrCodeContent);
    }

    private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "QrCode.{$viewName}";
    }

    /**
     * 解析二维码扫码请求
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function parse(Request $request)
    {
        try {
            switch (request()->type) {
                case 'scan':
                    return response()->json([
                        'type' => 'redirect',
                        'url' => url('search', $request->params['identity_code'])
                    ]);
                case 'buy_in':
                case 'fixing':
                case 'return_factory':
                case 'factory_return':
                    break;
            }
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 生成二维码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQrcode(Request $request)
    {
        try {
            $data = [];
            $size = $request->get('size', 200);
            $type = $request->get('type', 1); #1.生成base64,直接在页面使用不保存图片到文件服务器;2.保存图片到文件服务器,返回可访问的网络路径
            $url = request('url', url('form/apply'));
            $contents = $request->get('contents', '');
            if (empty($contents)) return HttpResponseHelper::errorEmpty('生成二维码数据为空');
            $logo = ''; #logo
            switch ($type) {
                case 1:
                    if (empty($logo)) {
                        foreach ($contents as $content) {
                            $gen = QrCode::format('png')->size($size)->generate($content);
                            $data[$content] = 'data:image/png;base64,' . base64_encode($gen);
                        }
                    } else {
                        foreach ($contents as $content) {
                            $gen = QrCode::format('png')->size($size)->merge($logo, .3, true)->generate($content);
                            $data[$content] = 'data:image/png;base64,' . base64_encode($gen);
                        }
                    }
                    break;
                case 2:
                    $qrcode_name = 'qrcodes/' . date('YmdHis_') . str_random(8) . '.png';
                    QrCode::format('png')->size($size)->merge($logo, .3, true)->generate($url, Storage::disk('public')->path($qrcode_name));
                    $data = Storage::disk('public')->url($qrcode_name);
                    break;

                default:
                    $data = '';
                    break;
            }

            return HttpResponseHelper::data($data);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 打印二维码
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    final public function printQrCode(Request $request)
    {
        try {
            $size_types = [
                1 => 160,
                2 => 85,
                3 => 140,
            ];
            $size_type = $size_types[$request->get('size_type')] ?? 140;  // 二维码标签大小

            $contents = [];  # 等待生成二维码的数组
            DB::table('print_identity_codes as pic')
                ->select([
                    'ei.identity_code',
                    'ei.category_name',
                    'ei.model_name',
                    'ei.serial_number',
                    'ei.made_at',
                    'pic.account_id',
                    'pic.id',
                    'em.fix_cycle_value',
                ])
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'pic.entire_instance_identity_code')
                ->join(DB::raw('entire_models em'),'em.unique_code','=','ei.entire_model_unique_code')
                ->where('pic.account_id', session('account.id'))
                ->orderBy('pic.id')
                ->each(function ($entireInstance) use (&$contents, $size_type) {
                    $contents[] = [
                        'category_name' => $entireInstance->category_name,
                        'model_name' => $entireInstance->model_name,
                        'identity_code' => $entireInstance->identity_code,
                        'serial_number' => $entireInstance->serial_number,
                        'made_at' => $entireInstance->made_at ?? '',
                        'fix_cycle_value'=>$entireInstance->fix_cycle_value,
                        'img' => QrCode::format('png')->size($size_type)->margin(0)->generate($entireInstance->identity_code)
                    ];
                });
            DB::table('print_identity_codes')->where('account_id', session('account.id'))->delete();

            return view('QrCode.printQrCode', [
                'contents' => $contents
            ]);
        } catch (\Exception $e) {
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }

    /**
     * 打印标签
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function printLabel(Request $request)
    {
        try {
            $contents = [];  # 等待生成二维码的数组
            DB::table('print_identity_codes as pic')
                ->select([
                    'ei.identity_code',
                    'ei.category_name',
                    'ei.model_name',
                    'ei.serial_number',
                    'ei.made_at',
                    'pic.account_id',
                    'pic.id',
                    'ei.maintain_station_name',
                    'ei.model_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.last_out_at',
                ])
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'pic.entire_instance_identity_code')
                ->where('pic.account_id', session('account.id'))
                ->orderBy('pic.id')
                ->each(function ($entireInstance) use (&$contents) {
                    $contents[] = [
                        'out_time' => empty($entireInstance->last_out_at) ? date('Y-m-d') : date('Y-m-d', strtotime($entireInstance->last_out_at)),
                        'model_name' => $entireInstance->model_name ?? '',
                        'identity_code' => $entireInstance->identity_code ?? '',
                        'maintain_station_name' => $entireInstance->maintain_station_name ?? '',
                        'maintain_location_code' => $entireInstance->maintain_location_code ?? '',
                        'crossroad_number' => $entireInstance->crossroad_number ?? '',
                    ];
                });
            DB::table('print_identity_codes')->where('account_id', session('account.id'))->delete();

            return view('QrCode.printLabel', [
                'contents' => $contents
            ]);
        } catch (\Exception $e) {
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }

    /**
     * 打印标签-仓库位置
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function printQrCodeWithLocation(Request $request)
    {
        try {
            $locationUniqueCode = $request->get('locationUniqueCodes', '');
            $locations = Position::with([
                'WithTier',
                'WithTier.WithShelf',
                'WithTier.WithShelf.WithPlatoon',
                'WithTier.WithShelf.WithPlatoon.WithArea',
                'WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])->whereIn('unique_code', explode(',', $locationUniqueCode))->get();
            $contents = [];
            foreach ($locations as $location) {
                $contents[] = [
                    'storehouse_name' => $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name ?? '',
                    'area_name' => $location->WithTier->WithShelf->WithPlatoon->WithArea->name ?? '',
                    'platoon_name' => $location->WithTier->WithShelf->WithPlatoon->name . $location->WithTier->WithShelf->name,
                    'tier_name' => $location->WithTier->name . $location->name,
                    'img' => QrCode::format('png')->size(170)->margin(0)->generate($location->unique_code)
                ];
            }
            $code = env('ORGANIZATION_CODE');
            return view("QrCode.Location.{$code}printQrCode", [
                'contents' => $contents,
            ]);
        } catch (\Exception $e) {
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }


}
