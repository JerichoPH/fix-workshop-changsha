<?php

namespace App\Http\Controllers;

use App\Exceptions\ExcelInException;
use App\Facades\AccountFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\JWTFacade;
use App\Facades\Rbac;
use App\Http\Requests\V1\AccountUpdateRequest;
use App\Http\Requests\V1\ForgetPasswordRequest;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RegisterRequest;
use App\Http\Requests\V1\UpdatePasswordRequest;
use App\Model\Account;
use App\Model\Maintain;
use App\Model\Organization;
use App\Model\PivotRoleAccount;
use App\Model\RbacRole;
use App\Model\WorkArea;
use App\Model\Workshop;
use Curl\Curl;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jericho\BadRequestException;
use Jericho\TextHelper;
use Jericho\ValidateHelper;
use Throwable;

class AccountController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic';
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
        $this->_root_url = "{$this->_spas_protocol}:// {$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        $accounts = Account::with(["status", "organization", 'WorkAreaByUniqueCode'])
            ->where(function ($query) {
                $query->where('workshop_code', null)
                    ->orWhere('workshop_code', env('ORGANIZATION_CODE'));
            })
            ->orderBy('id', 'desc')
            ->paginate();
        return Response::view('Account.index', ['accounts' => $accounts]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        $organizations = Organization::with([])->orderByDesc('id')->get();
        $accounts_with_current = Account::with([])->where('id', '<>', session('account.id'))->get();
        $workshops = Maintain::with([])->whereIn('type', ['SCENE_WORKSHOP', 'WORKSHOP'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();
        $stations = Maintain::with(['Parent'])
            ->where('type', 'STATION')
            ->whereHas('Parent', function ($Parent) {
                $Parent->where('parent_unique_code', env('ORGANIZATION_CODE'));
            })
            ->get()
            ->groupBy('parent_unique_code');
        $workAreas = WorkArea::with([])->where('paragraph_unique_code', env('ORGANIZATION_CODE'))->get()->groupBy('workshop_unique_code');
        return Response::view('Account.create', [
            'organizations' => $organizations,
            'tempTaskPositions' => Account::$TEMP_TASK_POSITIONS,
            'ranks' => Account::$RANKS,
            'accounts_with_current' => $accounts_with_current,
            'workshops_as_json' => $workshops->toJson(),
            'stations_as_json' => $stations->toJson(),
            'work_areas_as_json' => $workAreas->toJson(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\Response|mixed
     */
    final public function store(Request $request)
    {
        try {
            $v = Validator::make($request->all(), RegisterRequest::$RULES, RegisterRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            $work_area_id = 0;
            $work_area = null;
            $workshop_unique_code = null;
            if ($request->get('work_area_unique_code')) {
                $work_area = WorkArea::with(['Workshop'])->where('unique_code', $request->get('work_area_unique_code'))->first();
                if (!$work_area) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$work_area->Workshop) return JsonResponseFacade::errorEmpty("?????????{$work_area->name}???????????????????????????");
                switch ($work_area->type) {
                    case 'pointSwitch':
                        $work_area_id = 1;
                        break;
                    case 'reply':
                        $work_area_id = 2;
                        break;
                    case 'synthesize':
                        $work_area_id = 3;
                        break;
                    case 'scene':
                    default:
                        $work_area_id = 0;
                        break;
                }
            }
            $station = null;
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
                if (!$station) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$station->Parent) return JsonResponseFacade::errorEmpty("?????????{$station->name}?????????????????????????????????");
                if ($request->get('work_area_unique_code')) {
                    if (!$station->parent_unique_code != $work_area->workshop_unique_code) return JsonResponseFacade::errorForbidden("?????????{$work_area->name}????????????{$station->name}???????????????????????????");
                }
            }

            # ??????????????????
            $account = Account::with([])->create(array_merge($request->except('s'), [
                'open_id' => md5(time() . $request->get('account') . rand(1000, 9999)),
                'password' => bcrypt($request->get('password')),
                'identity_code' => Str::upper(md5(time() . Str::random())),
                'work_area' => $work_area_id,
            ]));

            //# ?????????????????????
            //$curl = new Curl();
            //$accessKey = env('GROUP_ACCESS_KEY');
            //$params = [
            //    'id' => $account->id,
            //    'account' => $account->account,
            //    'nickname' => $account->nickname,
            //    'password' => $account->password,
            //    'temp_task_position' => $request->get('temp_task_position'),
            //    'paragraph_unique_code' => env('ORGANIZATION_CODE'),
            //    'organization_type_unique_code' => 'FIX_WORKSHOP',
            //    'access_key' => $account->access_key,
            //    'secret_key' => $account->secret_key,
            //    'nonce' => time() . TextHelper::rand(),
            //    'work_area_unique_code' => @$work_area ? $work_area->unique_code : '',
            //    'station_unique_code' => @$station ? $station->unique_code : '',
            //    'workshop_unique_code' => $workshop_unique_code ?? '',
            //];
            //$sign = TextHelper::makeSign($params, env('GROUP_SECRET_KEY'));
            //$curl->setHeaders(['Access-Key' => $accessKey, 'Sign' => $sign]);
            //$curl->post(env('GROUP_URL') . '/user/backup', $params);

            return JsonResponseFacade::created(['account' => $account]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return RedirectResponse|\Illuminate\Http\Response
     */
    final public function show($id)
    {
        try {
            $account = Account::with(['organization', 'roles'])->where('id', $id)->firstOrFail();
            return Response::view('Account.show', ['account' => $account]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            return back()->withInput()->with('????????????', 500);
        }
    }

    /**
     * ????????????
     * @return RedirectResponse|\Illuminate\Http\Response
     */
    final public function profile()
    {
        try {
            $account = Account::with(['organization', 'roles'])->findOrFail(session('account.id'));
            return Response::view('Account.profile', ['account' => $account]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            return back()->withInput()->with('????????????', 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return RedirectResponse|\Illuminate\Http\Response
     */
    final public function edit($id)
    {
        try {
            $roles = RbacRole::all();
            $account = Account::with(['organization', 'roles'])->where('id', $id)->firstOrFail();
            $accounts_with_current = Account::with([])->where('id', '<>', $id)->get();
            $roleIds = [];
            foreach ($account->roles as $role) $roleIds[] = $role->id;

            $workshops = Maintain::with([])->whereIn('type', ['SCENE_WORKSHOP', 'WORKSHOP'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();
            $stations = Maintain::with(['Parent'])
                ->where('type', 'STATION')
                ->whereHas('Parent', function ($Parent) {
                    $Parent->where('parent_unique_code', env('ORGANIZATION_CODE'));
                })
                ->get()
                ->groupBy('parent_unique_code');
            $workAreas = WorkArea::with([])->where('paragraph_unique_code', env('ORGANIZATION_CODE'))->get()->groupBy('workshop_unique_code');

            return Response::view('Account.edit', [
                'account' => $account,
                'roles' => $roles,
                'roleIds' => $roleIds,
                'tempTaskPositions' => Account::$TEMP_TASK_POSITIONS,
                'ranks' => Account::$RANKS,
                'accounts_with_current' => $accounts_with_current,
                'workshops_as_json' => $workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'work_areas_as_json' => $workAreas->toJson(),
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '????????????');
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return ResponseFactory|JsonResponse|\Illuminate\Http\Response
     * @throws Throwable
     */
    final public function update(Request $request, $id)
    {
        try {
            $v = ValidateHelper::firstError($request->all(), new AccountUpdateRequest);
            if ($v !== true) return \response($v, 422);

            # ??????????????????
            $account = Account::with([])->where('id', $id)->firstOrFail();

            $work_area_id = 0;
            $work_area = null;
            $workshop_unique_code = null;
            if ($request->get('work_area_unique_code')) {
                $work_area = WorkArea::with(['Workshop'])->where('unique_code', $request->get('work_area_unique_code'))->first();
                if (!$work_area) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$work_area->Workshop) return JsonResponseFacade::errorEmpty("?????????{$work_area->name}???????????????????????????");
                switch ($work_area->type) {
                    case 'pointSwitch':
                        $work_area_id = 1;
                        break;
                    case 'reply':
                        $work_area_id = 2;
                        break;
                    case 'synthesize':
                        $work_area_id = 3;
                        break;
                    case 'powerSupplyPanel':
                        $work_area_id = 4;
                        break;
                    case 'scene':
                    default:
                        $work_area_id = 0;
                        break;
                }
            }

            $station = null;
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
                if (!$station) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$station->Parent) return JsonResponseFacade::errorEmpty("?????????{$station->name}?????????????????????????????????");
                if ($request->get('work_area_unique_code')) {
                    if (!$station->parent_unique_code != $work_area->workshop_unique_code) return JsonResponseFacade::errorForbidden("?????????{$work_area->name}????????????{$station->name}???????????????????????????");
                }
            }

            $account
                ->fill(
                    array_merge($request->all(), [
                        'workshop_code' => env('ORGANIZATION_CODE'),
                        'identity_code' => Str::upper(md5(time() . Str::random())),
                        'work_area' => $work_area_id,
                    ])
                )
                ->saveOrFail();

            //# ?????????????????????
            //$curl = new Curl();
            //$accessKey = env('GROUP_ACCESS_KEY');
            //$params = [
            //    'id' => $account->id,
            //    'account' => $account->account,
            //    'password' => $account->password,
            //    'nickname' => $account->nickname,
            //    'temp_task_position' => $request->get('temp_task_position'),
            //    'paragraph_unique_code' => env('ORGANIZATION_CODE'),
            //    'organization_type_unique_code' => 'FIX_WORKSHOP',
            //    'access_key' => $account->access_key,
            //    'secret_key' => $account->secret_key,
            //    'nonce' => time() . TextHelper::rand(),
            //    'work_area_unique_code' => @$work_area ? $work_area->unique_code : '',
            //    'station_unique_code' => @$station ? $station->unique_code : '',
            //    'workshop_unique_code' => $workshop_unique_code ?? '',
            //];
            //$sign = TextHelper::makeSign($params, env('GROUP_SECRET_KEY'));
            //$curl->setHeaders(['Access-Key' => $accessKey, 'Sign' => $sign]);
            //$curl->post(env('GROUP_URL') . '/user/backup', $params);

            return JsonResponseFacade::updated([
                // 'backup_res' => $curl->response,
                'account' => $account
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            // return JsonResponseFacade::dump($e->getMessage(), $e->getFile(), $e->getLine());
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return RedirectResponse
     * @throws Throwable
     */
    final public function forget(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new ForgetPasswordRequest);
            if ($v !== true) return back()->withInput()->with('danger', $v);

            switch ($request->get('type')) {
                case 'email':
                default:
                    $account = Account::with([])->where('account', $request->get('account'))
                        ->where('email_code', $request->get('code'))
                        ->where('email_code_exp', '>', date('Y-m-d H:i:s'))
                        ->first();
                    if (!$account) return back()->withInput()->with('danger', '?????????????????????????????????');
                    $account->email_code = null;
                    $account->email_code_exp = null;
                    break;
                case 'sms':
                    $account = Account::with([])->where('account', $request->get('account'))
                        ->where('sms_code', $request->get('sms'))
                        ->where('sms_code_exp', '>', date('Y-m-d H:i:s'))
                        ->first();
                    if (!$account) return back()->withInput()->with('danger', '?????????????????????????????????');
                    $account->sms_code = null;
                    $account->sms_code_exp = null;
                    break;
            }

            $account->password = bcrypt($request->get('password'));
            $account->saveOrFail();

            return redirect('/login')->withInput()->with('success', '?????????????????????????????????????????????');
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            return back()->withInput()->with('????????????', 500);
        }
    }

    /**
     * ??????????????????
     * @return \Illuminate\Http\Response
     */
    final public function getForget()
    {
        return Response::view('Account.forget');
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws Throwable
     */
    final public function password(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new UpdatePasswordRequest);
            if ($v !== true) return Response::make($v, 422);

            $account = Account::with([])->where('id', session('account.id'))->firstOrFail();
            if (!Hash::check($request->get('password'), $account->password)) return Response::make('????????????????????????', 500);;
            $account->password = bcrypt($request->get('new_password'));
            $account->saveOrFail();

            return Response::make();
        } catch (ModelNotFoundException $e) {
            return Response::make('???????????????', 404);
        } catch (Exception $e) {
            return Response::make('??????', 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @param int $id
     */
    final public function putEditPassword(Request $request, int $id)
    {
        try {
            if (strlen($request->get('password')) < 6) return JsonResponseFacade::errorForbidden('????????????????????????6???');
            if ($request->get('password') != $request->get('password_confirm')) return JsonResponseFacade::errorForbidden('?????????????????????');
            $account = Account::with([])->where('id', $id)->first();


            $account->fill(['password' => bcrypt($request->get('password'))])->saveOrFail();
            return JsonResponseFacade::updated(['account' => $account], '??????????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        try {
            $account = Account::with([])->where('id', $id)->firstOrFail();
            if (1 == $account->id || 'admin' == $account->account) return JsonResponseFacade::errorForbidden('?????????????????????');
            $account->delete();
            if (!$account->trashed()) return JsonResponseFacade::errorForbidden('????????????');

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return RedirectResponse
     */
    final public function login(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new LoginRequest);
            if ($v !== true) return back()->withInput()->with('danger', $v);

            # ????????????
            $account = Account::with(['WorkAreaByUniqueCode'])->where('account', $request->get('account'))->firstOrFail()->toArray();
            if (!Hash::check($request->get('password'), $account['password'])) return back()->withInput()->with('danger', '??????????????????');

            // ??????jwt
            unset($account['password']);
            $jwt = JWTFacade::generate($account);

            # ??????????????????????????????
            $account['menus'] = Rbac::getMenus($account['id'])->toArray();  # ??????????????????
            $account['treeJson'] = TextHelper::toJson(Rbac::toTree($account['menus']));
            $account['permissionIds'] = Rbac::getPermissionIds($account['id'])->toArray();  # ??????????????????
            $account['jwt'] = $jwt;

            # ??????????????????
            session()->put('account', $account);
            $target = $request->get('target', '/') ?? '/';
            return redirect($target)->with('????????????');
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '????????????');
        }
    }

    /**
     * ?????????
     * @return \Illuminate\Http\Response
     */
    final public function getLogin()
    {
        return Response::view('Account.login');
    }

    /**
     * ??????
     * @param Request $request
     * @return RedirectResponse
     * @throws Throwable
     */
    final public function register(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new RegisterRequest);
            if ($v !== true) return back()->withInput()->with('danger', $v);

            $account = new Account;
            $req = $request->all();
            $req['open_id'] = md5(time() . $req['account']);
            $req['password'] = bcrypt($req['password']);
            $req['workshop_code'] = env('ORGANIZATION_CODE');
            $account->fill($req);
            $account->saveOrFail();

            # ?????????????????????
            DB::table('pivot_role_accounts')->insert(['rbac_role_id' => 1, 'account_id' => $account->id]);

            return redirect('/login')->withInput()->with('success', '????????????');
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (Exception $e) {
            return back()->withInput()->with('danger', $e->getMessage());
        }
    }

    /**
     * ?????????
     * @return \Illuminate\Http\Response
     */
    final public function getRegister()
    {
        return Response::view('Account.register');
    }

    /**
     * ????????????
     * @return RedirectResponse
     */
    final public function logout()
    {
        session()->forget('account');
        return redirect('/login')->with('success', '????????????');
    }

    /**
     * ????????????
     * @return \Illuminate\Http\Response
     */
    final public function avatar()
    {
        try {
            if (!request()->hasFile('image')) return Response::make('??????????????????', 403);

            $avatar = request()->file('image');
            $extension = $avatar->getClientOriginalExtension();

            $savePath = 'uploads/account/avatar';
            $saveName = session('account.id') . '.' . $extension;

            $result = $avatar->move($savePath, $saveName);

            if ($result) {
                $account = Account::with([])->findOrFail(session('account.id'));
                $account->avatar = $result;
                $account->save();

                $session = session('account');
                $session['avatar'] = $result->getPathname();
                session()->put('account', $session);

                return Response::make('????????????');
            } else {
                return Response::make('????????????', 500);
            }
        } catch (ModelNotFoundException $e) {
            return Response::make('???????????????', 404);
        } catch (Exception $e) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ?????????????????????
     * @param string $id ??????????????????
     * @return JsonResponse|\Illuminate\Http\Response
     */
    final public function bindRoles($id)
    {
        try {
            $account = Account::with([])->where('id', $id)->firstOrFail();
            if (!$account) return JsonResponseFacade::errorEmpty('???????????????');

            PivotRoleAccount::with([])->where('account_id', $id)->delete();  # ?????????????????????

            if (request('role_ids')) {
                # ???????????????
                $insertData = [];
                foreach (request('role_ids') as $item) {
                    $insertData[] = ['account_id' => $id, 'rbac_role_id' => $item];
                }
                $insertResult = DB::table('pivot_role_accounts')->insert($insertData);
                if (!$insertResult) return JsonResponseFacade::errorForbidden('????????????');
            }

            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return JsonResponse
     */
    final public function postBackupToGroup()
    {
        try {
            # ?????????????????????
            $accounts = DB::table('accounts as a')->where('deleted_at', null)->get();

            $curl = new Curl();
            $accessKey = env('GROUP_ACCESS_KEY');

            $ret = [];
            $successCount = 0;
            foreach ($accounts as $account) {
                $account->nonce = TextHelper::rand();
                $account->paragraph_unique_code = env('ORGANIZATION_CODE');
                $account->organization_type_unique_code = 'FIX_WORKSHOP';
                $account->is_paragraph = true;
                $sign = TextHelper::makeSign((array)$account, env('GROUP_SECRET_KEY'));
                $curl->setHeaders(['Access-Key' => $accessKey, 'Sign' => $sign]);
                $curl->post(env('GROUP_URL') . '/user/backup', (array)$account);
                $ret[] = $curl->response;
                $curl->success(function () use (&$successCount) {
                    $successCount++;
                });
            }

            return response()->json(['message' => "???????????????{$successCount}???", 'details' => $ret]);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '????????????????????????'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => '????????????', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????????????????Excel??????(??????)
     */
    final public function getDownloadUploadCreateAccountBySceneExcelTemplate()
    {
        try {
            return AccountFacade::downloadUploadCreateAccountBySceneExcelTemplate();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ????????????????????????
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    final public function getUploadCreateAccountByScene()
    {
        try {
            return view('Account.uploadCreateAccountByScene');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ??????????????????(??????)
     * @param Request $request
     * @return RedirectResponse
     */
    final public function postUploadCreateAccountByScene(Request $request)
    {
        try {
            return AccountFacade::uploadCreateAccountByScene($request);
        } catch (ExcelInException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ??????????????????Excel??????(?????????)
     */
    final public function getDownloadUploadCreateAccountByParagraphExcelTemplate()
    {
        try {
            return AccountFacade::downloadUploadCreateAccountByParagraphExcelTemplate();
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ??????????????????(?????????)
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|\Illuminate\View\View
     */
    final public function getUploadCreateAccountByParagraph()
    {
        try {
            return view('Account.uploadCreateAccountByParagraph');
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????(?????????)
     * @param Request $request
     * @return RedirectResponse
     */
    final public function postUploadCreateAccountByParagraph(Request $request)
    {
        try {
            return AccountFacade::uploadCreateAccountByParagraph($request);
        } catch (ExcelInException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }
}
