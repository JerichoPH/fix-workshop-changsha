<?php

namespace App\Http\Controllers;

use App\Http\Requests\V1\FactoryRequest;
use App\Model\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Jericho\BadRequestException;
use Jericho\CurlHelper;

class FactoryController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/factory';
    private $_root_url = null;
    private $_auth = null;

    public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Support\Collection|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            switch (request('type')) {
                case 'category_unique_code':
                    return DB::table('pivot_entire_model_and_factories')->whereIn(
                        'entire_model_unique_code',
                        DB::table('entire_models')->where('category_unique_code', request('category_unique_code'))->pluck('unique_code')
                    )
                        ->pluck('factory_name as name');
                default:
                    return Factory::orderByDesc('id')->get();
                    break;
            }

        }
        $factories = Factory::orderByDesc('id')->paginate();
        return view('Factory.index', ['factories' => $factories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $viewName = request()->ajax() ? 'create_ajax' : 'create';
        return view("Factory.{$viewName}");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $v = Validator::make($request->all(), FactoryRequest::$RULES, FactoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $factory = new Factory;
            $factory->fill($request->all())->saveOrFail();

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
//             return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return Response::make('????????????', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $factory = Factory::findOrFail($id);
            return view('Factory.edit', ['factory' => $factory]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $v = Validator::make($request->all(), FactoryRequest::$RULES, FactoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $factory = Factory::findOrFail($id);
            $factory->fill($request->all())->saveOrFail();

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return Response::make('????????????', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $factory = Factory::findOrFail($id);
            $factory->delete();
            if (!$factory->trashed()) return Response::make('????????????', 500);

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return Response::make('????????????', 500);
        }
    }

    public function getBatch()
    {
        return view('Factory.batch');
    }

    public function postBatch(Request $request)
    {
        try {
            $excel = \App\Facades\FactoryFacade::batch($request, 'file');
            DB::table('factories')->insert($excel['success']);
            return back()->with('success', '????????????');

        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    '??????????????????' . $exception->getMessage() :
                    '???????????????', 404) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    '??????????????????' . $exception->getMessage() :
                    '???????????????');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????", 500) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????");
        }
    }

    /**
     * ??????????????????????????????
     */
    final public function getBackupFromSPAS()
    {
        try {
            # ???????????????
            $factories_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($factories_response['code'] != 200) return response()->json($factories_response['body'], $factories_response['code']);

            # ???????????????
            $insert_factories = [];
            foreach ($factories_response['body']['data'] as $datum) {
                $insert_factories[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'phone' => $datum['phone'],
                    'official_home_link'=>$datum['official_home_link'],
                ];
            }
            if ($insert_factories) {
                DB::table('factories')->truncate();
                DB::table('factories')->insert($insert_factories);
            }

            return response()->json(['message' => '????????????']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '????????????????????????'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
