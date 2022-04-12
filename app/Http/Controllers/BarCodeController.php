<?php

namespace App\Http\Controllers;

use App\Model\EntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Milon\Barcode\DNS1D;

class BarCodeController extends Controller
{
    public function show($entireInstanceIdentityCode)
    {
        $entireInstance = EntireInstance::where('identity_code', $entireInstanceIdentityCode)->firstOrFail();
        $barcode = new DNS1D();
        return view($this->view())
            ->with('serialNumber', date('Y-m') . $entireInstance->entire_model_unique_code)
            ->with('entireInstanceIdentityCode', $entireInstanceIdentityCode)
            ->with('entireInstance', $entireInstance)
            ->with('barcode', $barcode);
    }

    private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "BarCode.{$viewName}";
    }

    /**
     * 解析条形码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function parse(Request $request)
    {
        $entireInstance = DB::table('entire_instances')->where('rfid_code', $request->serial_number)->first(['identity_code']);
        if (!$entireInstance) return response()->make('数据不存在', 404);
//        return response()->json($identityCode);
        try {
            switch (request()->type) {
                case 'scan':
                    return Response::json([
                        'type' => 'redirect',
                        'url' => url('search', $entireInstance->identity_code)
                    ]);
                case 'buy_in':
                    break;
                case 'fixing':
                    break;
                case 'return_factory':
                    break;
                case 'factory_return':
                    break;
            }
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }
}
