<?php

namespace App\Http\Controllers\Measurement;

use App\Exceptions\FixWorkflowException;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceChangePartLog;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\Maintain;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\TempTaskSubOrderEntireInstance;
use App\Model\UnCycleFixReport;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\WarehouseProductInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\TextHelper;

class FixWorkflowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function index(Request $request)
    {
        $status = $request->get('status', '');
        if ($status == 'CHECKED') {
            $fixWorkflows = FixWorkflow::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.EntireModel',
                'EntireInstance.EntireModel.Category',
                'EntireInstance.EntireModel.Measurements',
                'EntireInstance.EntireModel.Measurements.PartModel',
                'Processor',
                'FixWorkflowProcesses'
            ])
                ->orderByDesc('id')
                ->where('status', 'FIXED')
                ->paginate();
        } else {
            $fixWorkflows = FixWorkflow::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.EntireModel',
                'EntireInstance.EntireModel.Category',
                'EntireInstance.EntireModel.Measurements',
                'EntireInstance.EntireModel.Measurements.PartModel',
                'Processor',
                'FixWorkflowProcesses'
            ])
                ->orderByDesc('id')
                ->paginate();
        }

        return view('Measurement.FixWorkflow.index', [
            'fixWorkflows' => $fixWorkflows
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    final public function create()
    {
        try {
            $fixWorkflowSerialNumber = CodeFacade::makeSerialNumber('FIX_WORKFLOW');
            DB::transaction(function () use ($fixWorkflowSerialNumber) {
                # ????????????????????????
                if (session('account.supervision') == 0) throw new \Exception("????????????????????????????????????");

                # ???????????????????????????????????????????????????
                // $unFixedCount = FixWorkflow::with(['EntireInstance'])->where('entire_instance_identity_code', request('identity_code'))
                // ->whereNotIn('status', ['FIXED'])
                // ->count('id');
                // if ($unFixedCount) throw new \Exception("????????????????????????????????????");

                # ???????????????
                $fixWorkflow = new FixWorkflow;
                $fixWorkflow->fill([
                    'entire_instance_identity_code' => request('identity_code'),
                    'status' => 'FIXING',
                    'processor_id' => session('account.id'),
                    'serial_number' => $fixWorkflowSerialNumber,
                    'stage' => 'UNFIX',
                    'type' => request('type'),
                    'check_serial_number' => request('type', 'FIX') == 'CHECK'
                        ? FixWorkflow::with([])->where('entire_instance_identity_code', request('identity_code'))
                            ->where('type', 'FIX')
                            ->where('status', 'FIXED')
                            ->firstOrFail(['serial_number'])
                            ->serial_number
                        : null,
                ])
                    ->saveOrFail();

                # ???????????????????????????????????????????????????????????????
                $fixWorkflow->EntireInstance->fill([
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                    'status' => 'FIXING',
                    'in_warehouse' => false
                ])
                    ->saveOrFail();

                # ??????????????????????????????
                DB::table('part_instances')
                    ->where('entire_instance_identity_code', request('identity_code'))
                    ->update(['status' => 'FIXING']);

                # ?????????????????????????????????
                $fixUnCycleReport = new UnCycleFixReport;
                $fixUnCycleReport->fill([
                    'entire_instance_identity_code' => $fixWorkflow->entire_instance_identity_code,
                    'fix_workflow_serial_number' => $fixWorkflow->serial_number,
                ]);
            });

            return redirect(url('measurement/fixWorkflow', $fixWorkflowSerialNumber) . '/edit');
        } catch (ModelNotFoundException $e) {
            // dd('???????????????');
            return back()->with('danger', '???????????????');
        } catch (\Exception $e) {
            // dd([get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param $serialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function show($serialNumber)
    {
        # ????????????????????????
        $fixWorkflows = FixWorkflow::with([
            'EntireInstance',
            'EntireInstance.EntireModel',
            'EntireInstance.EntireModel.Category',
            'EntireInstance.EntireModel.Measurements',
            'EntireInstance.EntireModel.Measurements.PartModel',
            'WarehouseReport',
            'Processor',
            'FixWorkflowProcesses',
            'FixWorkflowProcesses.Measurement',
            'FixWorkflowProcesses.Processor',
            'FixWorkflowProcesses.Measurement.PartModel'
        ])
            ->where('serial_number', $serialNumber)
            ->orderByDesc('id')
            ->get();

        return view('Measurement.FixWorkflow.show', [
            'fixWorkflows' => $fixWorkflows
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $serialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Throwable
     */
    final public function edit($serialNumber)
    {
        try {
            $fixWorkflow = FixWorkflow::with([
                'EntireInstance',
                'EntireInstance.EntireModel',
                'EntireInstance.EntireModel.PartModels',
                'EntireInstance.EntireModel.Category',
                'EntireInstance.EntireModel.Measurements',
                'EntireInstance.EntireModel.Measurements.PartModel',
                'EntireInstance.PartInstances',
                'EntireInstance.PartInstances.PartModel',
                'WarehouseReport',
                'Processor',
                'FixWorkflowProcesses',
                'FixWorkflowProcesses.Measurement',
                'FixWorkflowProcesses.Processor',
                'FixWorkflowProcesses.Measurement.PartModel',
            ])
                ->whereHas('EntireInstance')
                ->where('serial_number', $serialNumber)
                ->orderByDesc('id')
                ->firstOrFail();

            # ???????????????????????????????????????
            $workshops = Maintain::with([])->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('type', 'SCENE_WORKSHOP')->get();

            # ??????????????????????????????????????????????????????
            $fixWorkflowProcesses_entire = FixWorkflowProcess::with([])->where('type', 'ENTIRE')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->orderByDesc('updated_at')
                ->get();
            $fixWorkflowProcesses_part = FixWorkflowProcess::with([])->where('type', 'PART')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->orderByDesc('updated_at')
                ->get();

            // ????????????????????????????????????????????????
            $lastFixWorkflowRecodeEntire = FixWorkflowProcess::with([
                'FixWorkflowRecords',
                'FixWorkflowRecords.Measurement',
                'FixWorkflowRecords.EntireInstance',
                'FixWorkflowRecords.EntireInstance.EntireModel',
            ])
                ->orderByDesc('id')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->first();
            $check_json_data = [];
            switch (@$lastFixWorkflowRecodeEntire->check_type ?? ''){
                case 'JSON':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body']['????????????'];
                        }
                    }
                    break;
                case 'JSON2':
                    $file = public_path($lastFixWorkflowRecodeEntire->upload_url);
                    if (is_file($file)) {
                        $json = TextHelper::parseJson(file_get_contents("{$file}"));
                        if (!empty($json)) {
                            $check_json_data = @$json['body'];
                        }
                    }
                    break;
            }

            # ???????????????????????????
            # ?????????????????????????????????????????????????????????
            $isAllow = false;
            $fixWorkflowStage = $fixWorkflow->flipStage($fixWorkflow->stage);
            if ($fixWorkflowStage == 'CHECKED' || $fixWorkflowStage == 'WORKSHOP' || $fixWorkflowStage == 'SECTION') {
                # ???????????????????????????????????????
                $isAllow = boolval(FixWorkflowProcess::with([])
                    ->where('stage', $fixWorkflowStage)
                    ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                    ->pluck('is_allow')
                    ->unique()
                    ->sort()
                    ->first());
            }

            $lastFixWorkflowProcessEntire = FixWorkflowProcess::with(['FixWorkflow'])
                ->orderByDesc('id')
                ->where('type', 'ENTIRE')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->first();

            $lastFixWorkflowProcessChecker = FixWorkflowProcess::with(['Processor'])
                ->orderByDesc('id')
                ->where('type', 'ENTIRE')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->where('stage', 'CHECKED')
                ->where('is_allow', 1)
                ->first();

            $lastFixWorkflowProcessFixer = FixWorkflowprocess::with(['Processor'])
                ->orderByDesc('id')
                ->where('type', 'ENTIRE')
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->where('stage', 'FIX_AFTER')
                ->where('is_allow', 1)
                ->first();

            if ($isAllow) {  # ????????????
                # ?????????????????????
                if (!in_array($fixWorkflow->prototype('status'), ['FIXED', 'RETURN_FACTORY']))
                    $fixWorkflow->fill(['updated_at' => date('Y-m-d H:i:s'), 'status' => 'FIXED', 'processor_id' => $lastFixWorkflowProcessEntire->processor_id])->saveOrFail();

                # ?????????????????? ??????????????????
                if (!in_array(array_flip(EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status], ['FIXED', 'INSTALLED', 'INSTALLING', 'RETURN_FACTORY']))
                    $fixWorkflow->EntireInstance->fill(['updated_at' => date('Y-m-d H:i:s'), 'status' => 'FIXED', 'maintain_station_name' => '', 'maintain_location_code' => ''])->saveOrFail();

                # ??????????????????
                if (!in_array(array_flip(EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status], ['FIXED', 'INSTALLED', 'INSTALLING', 'RETURN_FACTORY']))
                    $fixWorkflow->EntireInstance->PartInstances->fill(['status' => 'FIXED'])->save();

                # ?????????????????????????????????
                $entireInstance = EntireInstance::with(['EntireModel', 'WarehouseReportByOut', 'FixWorkflow'])
                    ->where("identity_code", $fixWorkflow->entire_instance_identity_code)
                    ->firstOrFail();
                $nextFixingData = EntireInstanceFacade::nextFixingTime($entireInstance, $entireInstance->fix_cycle_value);
                $entireInstance->fill($nextFixingData)->saveOrFail();

                # ?????????????????????????????????????????????????????????????????????????????????????????????????????????
                if (!DB::table("entire_instance_logs as eil")->where("eil.deleted_at", null)->where("eil.url", "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit")->first())
                    EntireInstanceLogFacade::makeOne(
                        '????????????',
                        '????????????' . @$lastFixWorkflowProcessFixer->Processor->nickname . '????????????' . @$lastFixWorkflowProcessChecker->Processor->nickname,
                        2,
                        "/measurement/fixWorkflow/{$fixWorkflow->serial_number}/edit"
                    );

                # ???????????????????????????????????????????????????
                $fixWorkflow->fill([
                    'entire_fix_after_count' => FixWorkflowProcess::with([])->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                        ->where('stage', 'FIX_AFTER')
                        ->count('id')
                ])
                    ->saveOrFail();
            } else {
                # ???????????????
                if ($fixWorkflow->prototype('status') != 'RETURN_FACTORY') {
                    # ?????????????????????
                    $fixWorkflow->fill(['status' => 'FIXING'])->saveOrFail();
                    # ??????????????????
                    $fixWorkflow->EntireInstance->fill(['status' => 'FIXING'])->saveOrFail();
                }
            }

            # ????????????????????????????????????
            if ($fixWorkflow->EntireInstance->EntireModel) {
                $part_models = DB::table('part_models')->where('deleted_at', null)->where('entire_model_unique_code', $fixWorkflow->EntireInstance->EntireModel->unique_code)->pluck('unique_code');
            } else {
                $part_models = [];
            }

            # ??????????????????????????????
            $breakdownTypes = DB::table('breakdown_types')
                ->where('deleted_at', null)
                ->where('category_unique_code', $fixWorkflow->EntireInstance->category_unique_code)
                ->pluck('name', 'id')
                ->chunk(3);

            # ?????????????????????????????????????????????????????????
            $breakdownTypeIds = DB::table('pivot_entire_instance_and_breakdown_types')
                ->where('entire_instance_identity_code', $fixWorkflow->EntireInstance->identity_code)
                ->where('fix_workflow_serial_number', $fixWorkflow->serial_number)
                ->pluck('breakdown_type_id');

            return view('Measurement.FixWorkflow.edit', [
                'fixWorkflow' => $fixWorkflow,
                'fixWorkflowProcesses_entire' => $fixWorkflowProcesses_entire,
                'fixWorkflowProcesses_part' => $fixWorkflowProcesses_part,
                'part_models' => $part_models,
                'lastFixWorkflowRecodeEntire' => $lastFixWorkflowRecodeEntire,
                'check_json_data' => $check_json_data,
                'workshops' => $workshops,
                'breakdownTypes' => $breakdownTypes,
                'breakdownTypeIds' => $breakdownTypeIds->toArray(),
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eFile = $exception->getFile();
            $eLine = $exception->getLine();
            dd($exception->getMessage(), $exception->getFile(), $exception->getLine());
            return back()->with('danger', env('APP_DEBUG') ? "{$eMsg}<br>{$eFile}<br>{$eLine}" : "????????????");
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $serialNumber
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function update(Request $request, $serialNumber)
    {
        try {
            $fixWorkflow = FixWorkflow::with(['EntireInstance'])->where('serial_number', $serialNumber)->firstOrFail();
            $fixWorkflow->fill(array_merge($request->all()))->saveOrFail();

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
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Http\Response
     */
    final public function destroy($fixWorkflowSerialNumber)
    {
        try {
            # ?????????????????????????????????
            $fixWorkflow = FixWorkflow::with(['EntireInstance'])->where('serial_number', $fixWorkflowSerialNumber)->firstOrFail();
            $fixWorkflowCount = FixWorkflow::with([])->where('entire_instance_identity_code', $fixWorkflow->entire_instance_identity_code)->where('id', $fixWorkflow->id)->count('id');
            $fixWorkflow->EntireInstance->fill(['status' => $fixWorkflowCount > 1 ? 'FIXED' : 'FIXING'])->saveOrFail();

            $fixWorkflow->delete();
            if (!$fixWorkflow->trashed()) return Response::make('????????????', 500);

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
     * ???????????????????????????
     * @param string $fixWorkflowSerialNumber ???????????????
     * @return \Illuminate\Http\Response
     */
    final public function fixed(string $fixWorkflowSerialNumber)
    {
        try {
            DB::transaction(function () use ($fixWorkflowSerialNumber) {
                # ?????????????????????
                DB::table('fix_workflows')->where('deleted_at', null)->where('serial_number', $fixWorkflowSerialNumber)->update(['status' => 'FIXED']);

                # ????????????????????????
                $entireInstance = EntireInstance::with([])->where('fix_workflow_serial_number', $fixWorkflowSerialNumber)->firstOrFail();
                $entireInstance->fill(['status' => 'FIXED'])->saveOrFail();

                # ????????????????????????
                DB::table('part_instances')->where('entire_instance_identity_code', $entireInstance->identity_code)->update(['status' => 'FIXED']);
            });

            return Response::make('??????????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
    }

    /**
     * ????????????????????????
     * @param int $fixWorkflowId ???????????????
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function spotCheckFailed($fixWorkflowId)
    {
        try {
            $fixWorkflow = FixWorkflow::findOrFail($fixWorkflowId);
            if ($fixWorkflow->flipStatus($fixWorkflow->status) != 'WORKSHOP') return Response::make('????????????????????????' . $fixWorkflow->status . '???' . $fixWorkflow->flipStatus($fixWorkflow->status) . '???', 403);
            $fixWorkflow->fill(['status' => 'SPOT_CHECK_FAILED'])->saveOrFail();

            # ???????????????
            $newFixWorkflow = new FixWorkflow;
            $newFixWorkflow->fill([
                'warehouse_product_instance_open_code' => $fixWorkflow->warehouse_product_instance_open_code,
                'warehouse_report_product_id' => $fixWorkflow->warehouse_report_product_id,
                'status' => 'UNFIX',
                'id_by_failed' => $fixWorkflowId
            ])->saveOrFail();

            # ????????????????????????
            $warehouseProductInstance = WarehouseProductInstance::with([])->where('fix_workflow_id', $fixWorkflowId)->firstOrFail();
            $warehouseProductInstance->fill(['fix_workflow_id' => $newFixWorkflow->id])->saveOrFail();

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ?????????????????????????????????
     * @param int $fixWorkflowId ???????????????
     * @return \Illuminate\Http\Response
     */
    final public function nextFixWorkflow($fixWorkflowId)
    {
        try {
            $fixWorkflow = FixWorkflow::with([])->where('id_by_failed', $fixWorkflowId)->firstOrFail();
            return Response::make(url('measurement/fixWorkflow') . '/' . $fixWorkflow->id . '/edit');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ?????????????????????????????????
     * @param int $fixWorkflowId ???????????????
     * @return \Illuminate\Http\Response
     */
    final public function previousFixWorkflow($fixWorkflowId)
    {
        try {
            $fixWorkflow = FixWorkflow::with([])->where('id', $fixWorkflowId)->firstOrFail();
            return Response::make(url('measurement/fixWorkflow') . '/' . $fixWorkflow->id . '/edit');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ????????????????????????
     */
    final public function getAddPartInstance()
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->where('identity_code', request('entireInstanceIdentityCode'))
                ->firstOrFail();
            $part_categories = collect(DB::table('part_categories')->where('deleted_at', null)->where('category_unique_code', $entire_instance->category_unique_code)->get())->toArray();
            if (!$part_categories) return response()->json(['message' => '???????????????????????????????????????'], 404);
            $part_instances = collect(
                DB::table('part_instances')
                    ->where('deleted_at', null)
                    ->where('location_unique_code', '<>', null)
                    ->where('status', 'FIXED')
                    ->whereIn('part_category_id', array_column($part_categories, 'id'))
                    ->get()
            )
                ->toArray();
            if (!$part_instances) return response()->json(['message' => '????????????????????????????????????'], 404);
            $part_instances2 = [];
            foreach ($part_instances as $part_instance)
                $part_instances2[$part_instance->part_category_id][] = $part_instance;

            return view('Measurement.FixWorkflow.addPartInstance_ajax', [
                'entire_instance' => $entire_instance,
                'part_categories' => $part_categories,
                'part_instances_as_json' => json_encode($part_instances2),
                'fixWorkflowSerialNumber' => request('fixWorkflowSerialNumber'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '???????????????'], 404);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            return response()->json(['message' => "{$msg}\r\n{$line}\r\n{$file}"], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function postAddPartInstance(Request $request)
    {
        try {
            $part_instance = PartInstance::with([])->where('identity_code', $request->get('partInstanceIdentityCode'))->first();
            if (!$part_instance) return response()->json(['message' => '???????????????'], 404);

            $part_instance
                ->fill([
                    'entire_instance_identity_code' => $request->get('entireInstanceIdentityCode'),
                    'location_unique_code' => null,
                    'in_warehouse_time' => null
                ])
                ->saveOrFail();

            # ????????????????????????
            EntireInstanceLogFacade::makeOne('????????????',
                $request->get('entireInstanceIdentityCode'),
                2,
                "/measurement/fixWorkflow/{$request->get('fixWorkflowSerialNumber')}",
                "?????????" . $part_instance->identity_code
            );
            return response()->json(['message' => '????????????']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * ??????????????????
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function getChangePartInstance()
    {
        try {
            # ??????????????????
            $old = PartInstance::with([])->where('identity_code', request('partInstanceIdentityCode'))->firstOrFail();
            # ?????????????????????
            $news = PartInstance::with(['PartModel', 'PartCategory'])
                ->where('status', 'FIXED')
                ->where('location_unique_code', '<>', null)
                ->where('part_model_unique_code', $old->part_model_unique_code)
                ->where('part_category_id', $old->part_category_id)
                ->get();

            return view('Measurement.FixWorkflow.changePartInstance_ajax', [
                'part_instance' => $old,
                'part_instances' => $news,
                'fixWorkflowSerialNumber' => request('fixWorkflowSerialNumber'),
                'entireInstanceIdentityCode' => request('entireInstanceIdentityCode'),
            ]);
        } catch (ModelNotFoundException $exception) {
            return Response::make('??????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function postChangePartInstance(Request $request)
    {
        try {

            $old = PartInstance::with([])->where('identity_code', $request->get('old_identity_code'))->first();
            if (!$old) return response()->make('????????????????????????', 404);
            $new = PartInstance::with([])->where('identity_code', $request->get('new_identity_code'))->first();
            if (!$new) return response()->make('?????????????????????', 404);
            $entire_instance = EntireInstance::with([])->where('identity_code', $request->get('entireInstanceIdentityCode'))->first();
            if (!$entire_instance) return response()->make('???????????????', 404);

            # ???????????????????????????????????????
            $old->location_unique_code = $new->location_unique_code;
            $old->in_warehouse_time = date('Y-m-d H:i:s');

            # ???????????????????????????????????????
            $new->location_unique_code = null;
            $new->in_warehouse_time = null;
            $new->entire_instance_identity_code = $old->entire_instance_identity_code;
            $new->saveOrFail();

            $old->entire_instance_identity_code = null;
            $old->saveOrFail();

            # ????????????????????????
            EntireInstanceLogFacade::makeOne('????????????',
                $request->get('entireInstanceIdentityCode'),
                2,
                "/measurement/fixWorkflow/{$request->get('fixWorkflowSerialNumber')}",
                "{$old->identity_code} => {$new->identity_code}"
            );

            return Response::make('????????????');
        } catch (ModelNotFoundException $e) {
            return response()->make('???????????????');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
            return Response::make("{$msg}<br>{$line}<br>{$file}", 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function postUninstallPartInstance(Request $request)
    {
        try {
            # ????????????
            $part_instance = PartInstance::with([])->where('identity_code', $request->get('partInstanceIdentityCode'))->first();
            if (!$part_instance) return response()->make('???????????????', 404);
            $part_instance->entire_instance_identity_code = null;
            $part_instance->saveOrFail();

            # ????????????????????????
            EntireInstanceLogFacade::makeOne('????????????',
                $request->get('entireInstanceIdentityCode'),
                2,
                "/measurement/fixWorkflow/" . $request->get('fixWorkflowSerialNumber'),
                "???????????????" . $request->get('partInstanceIdentityCode')
            );

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function postScrapPartInstance(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                # ??????????????????
                $partInstance = PartInstance::with([
                    'EntireInstance',
                    'PartModel'
                ])
                    ->where('identity_code', $request->get('partInstanceIdentityCode'))
                    ->firstOrFail();
                # ??????????????????
                $partInstance->fill(['entire_instance_identity_code' => null, 'status' => 'SCRAP'])->saveOrFail();
                $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('identity_code', $partInstance->entire_instance_identity_code)->first(['fix_workflow_serial_number']);

                # ??????????????????????????????
                $entireInstanceChangePartLog = new EntireInstanceChangePartLog;
                $entireInstanceChangePartLog->fill([
                    'entire_instance_identity_code' => $request->get('entireInstanceIdentityCode'),
                    'part_instance_identity_code' => $request->get('partInstanceIdentityCode'),
                    'fix_workflow_serial_number' => $request->get('fixWorkflowSerialNumber'),
                    'note' => "???????????????{$partInstance->PartModel->name}???{$partInstance->PartModel->unique_code}???{$partInstance->factory_device_code}???",
                ])
                    ->saveOrFail();

                # ????????????????????????
                EntireInstanceLogFacade::makeOne('????????????',
                    $partInstance->entire_instance_identity_code,
                    2,
                    "/measurement/fixWorkflow/{$entireInstance->identity_code}",
                    "???????????????{$partInstance->PartModel->name}???{$partInstance->PartModel->unique_code}???{$partInstance->factory_device_code}???"
                );
            });

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ??????????????????
     */
    final public function getInstall()
    {
        try {
            $organizationCode = env('ORGANIZATION_CODE');
            $fixWorkflow = FixWorkflow::with([])->where('serial_number', request('fixWorkflowSerialNumber'))->firstOrFail();
            # ???????????????????????????????????????
            $workshops = Maintain::with([])->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('type', 'SCENE_WORKSHOP')->get();
            # ?????????????????????????????????
            $accounts = DB::select(DB::raw("select * from accounts where workshop_code is null or workshop_code='{$organizationCode}'"));;

            return view('Measurement.FixWorkflow.install_ajax', [
                'accounts' => $accounts,
                'fixWorkflow' => $fixWorkflow,
                'workshops' => $workshops,
            ]);
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param null $serialNumber
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    final public function postInstall(Request $request, $serialNumber = null)
    {
        try {
            return response()->json(
                WarehouseReportFacade::fixWorkflowOutOnce(
                    $request,
                    FixWorkflow::with(['EntireInstance', 'EntireInstance.EntireModel'])
                        ->where('serial_number', $serialNumber)
                        ->firstOrFail()
                )
            );
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage() . "\r\n" . $exception->getFile() . "\r\n" . $exception->getLine(), 500);
        }
    }

    /**
     * ????????????
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function getForceInstall()
    {
        try {
            $organizationCode = env('ORGANIZATION_CODE');
            $fixWorkflow = FixWorkflow::with([])->where('serial_number', request('fixWorkflowSerialNumber'))->firstOrFail();
            # ???????????????????????????????????????
            $workshops = Maintain::with([])->where('parent_unique_code', env('ORGANIZATION_CODE'))->where('type', 'SCENE_WORKSHOP')->get();
            # ?????????????????????????????????
            $accounts = DB::select(DB::raw("select * from accounts where workshop_code is null or workshop_code='{$organizationCode}'"));

            return view($this->view('forceInstall_ajax'))
                ->with('accounts', $accounts)
                ->with('fixWorkflow', $fixWorkflow)
                ->with('workshops', $workshops)
                ->with('accounts', $accounts);
        } catch (ModelNotFoundException $exception) {
            return request()->ajax() ?
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
            return request()->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????", 500) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????");
        }
    }

    final private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Measurement.FixWorkflow.{$viewName}";
    }

    /**
     * ????????????
     * @param Request $request
     * @param string $serialNumber
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function postForceInstall(Request $request, string $serialNumber)
    {
        try {
            WarehouseReportFacade::fixWorkflowOutOnce($request, FixWorkflow::with(['EntireInstance', 'EntireInstance.EntireModel'])->where('serial_number', $serialNumber)->firstOrFail());
            return response()->make();
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
     * ????????????????????????
     * @param $fixWorkflowSerialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getIn($fixWorkflowSerialNumber)
    {
        return view($this->view('in_ajax'))
            ->with('fixWorkflowSerialNumber', $fixWorkflowSerialNumber)
            ->with('accounts', Account::orderByDesc('id')->pluck('nickname', 'id'));
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Http\Response
     */
    final public function postIn(Request $request, string $fixWorkflowSerialNumber)
    {
        try {
            # ?????????????????????
            WarehouseReportFacade::fixWorkflowInOnce($request, FixWorkflow::with(['EntireInstance'])->where('serial_number', $fixWorkflowSerialNumber)->firstOrFail());
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * ??????????????????
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getReturnFactory(string $fixWorkflowSerialNumber)
    {
        return view($this->view('returnFactory_ajax'))
            ->with('fixWorkflowSerialNumber', $fixWorkflowSerialNumber)
            ->with('accounts', Account::orderByDesc('id')->pluck('nickname', 'id'));
    }

    /**
     * ????????????
     * @param Request $request
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Http\Response
     */
    final public function postReturnFactory(Request $request, string $fixWorkflowSerialNumber)
    {
        try {
            # ?????????????????????
            WarehouseReportFacade::returnFactoryOutOnce($request, FixWorkflow::with(['EntireInstance'])->where('serial_number', $fixWorkflowSerialNumber)->firstOrFail());
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ??????????????????
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getFactoryReturn(string $fixWorkflowSerialNumber)
    {
        return view($this->view('factoryReturn_ajax'))
            ->with('fixWorkflowSerialNumber', $fixWorkflowSerialNumber)
            ->with('accounts', Account::orderByDesc('id')->pluck('nickname', 'id'));
    }

    /**
     * ????????????
     * @param Request $request
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Http\Response
     */
    final public function postFactoryReturn(Request $request, string $fixWorkflowSerialNumber)
    {
        try {
            # ?????????????????????
            WarehouseReportFacade::factoryReturnInOnce($request, FixWorkflow::with(['EntireInstance'])->where('serial_number', $fixWorkflowSerialNumber)->firstOrFail());
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param string $fixWorkflowSerialNumber
     * @return \Illuminate\Http\Response
     */
    final public function postUpdateBreakdownType(Request $request, string $fixWorkflowSerialNumber)
    {
        try {
            DB::table('pivot_entire_instance_and_breakdown_types')->where('fix_workflow_serial_number', $fixWorkflowSerialNumber)->delete();
            $entireInstanceIdentityCode = DB::table('fix_workflows')->where('deleted_at', null)->where('serial_number', $fixWorkflowSerialNumber)->first(['entire_instance_identity_code'])->entire_instance_identity_code;
            $entireInstance = DB::table('entire_instances')->where('deleted_at', null)->where('identity_code', $entireInstanceIdentityCode)->first();

            $categoryUniqueCode = substr($entireInstanceIdentityCode, 0, 3);
            $entireModelUniqueCode = substr($entireInstanceIdentityCode, 0, 5);
            $categoryName = DB::table('categories')->where('unique_code', $categoryUniqueCode)->first(['name'])->name;
            $entireModelName = DB::table('entire_models')->where('unique_code', $entireModelUniqueCode)->first(['name'])->name;

            switch (substr($entireInstanceIdentityCode, 0, 1)) {
                case 'Q':
                    $subModel = DB::table('entire_models')->where('deleted_at', null)->where('is_sub_model', true)->where('unique_code', substr($entireInstanceIdentityCode, 0, 7))->first(['unique_code', 'name']);
                    $isSub = true;
                    $isPart = false;
                    break;
                case 'S':
                    $partModel = DB::table('part_models')
                        ->join('part_instances', 'part_instances.part_model_unique_code', '=', 'part_models.unique_code')
                        ->where('part_models.deleted_at', null)
                        ->where('part_instances.entire_instance_identity_code', $entireInstanceIdentityCode)
                        ->first(['part_models.unique_code', 'part_models.name']);
                    $isSub = false;
                    $isPart = true;
                    break;
            }

            if ($isSub && !$isPart) {
                # ??????
                $subName = $subModel->name;
                $subUniqueCode = $subModel->unique_code;
            } else {
                # ??????
                $subName = $partModel->name;
                $subUniqueCode = $partModel->unique_code;
            }

            $pivots = [];
            foreach ($request->get('breakdown_type') as $item) {
                $field = ($isSub && !$isPart) ? 'sub' : 'part';
                $pivots[] = [
                    'entire_instance_identity_code' => $entireInstanceIdentityCode,
                    'breakdown_type_id' => $item,
                    'category_name' => $categoryName,
                    'category_unique_code' => $categoryUniqueCode,
                    'entire_model_name' => $entireModelName,
                    'entire_model_unique_code' => $entireModelUniqueCode,
                    'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                    "{$field}_model_unique_code" => $subUniqueCode,
                    "{$field}_model_name" => $subName,
                ];
            }

            DB::table('pivot_entire_instance_and_breakdown_types')->insert($pivots);

            return response()->make('????????????');
        } catch (\Exception $exception) {
            return response()->make($exception->getMessage() . ':' . $exception->getLine());
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getUploadCheck(Request $request)
    {
        $stages = [
            'CHECKED' => '????????????',
            'WORKSHOP' => '????????????',
            'SECTION' => '??????????????????',
            'FIX_BEFORE' => '?????????',
            'FIX_AFTER' => '?????????',
        ];
        $fixWorkflowSerialNumber = $request->get('fixWorkflowSerialNumber');
        $entireInstanceIdentityCode = $request->get('entireInstanceIdentityCode');

        return view($this->view('upload_check'))->with('stages', $stages)->with('fixWorkflowSerialNumber', $fixWorkflowSerialNumber)->with('entireInstanceIdentityCode', $entireInstanceIdentityCode);
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadCheck(Request $request)
    {
        try {
            $file = $request->file('file', null);
            if (empty($file) && is_null($file)) return back()->with('danger', '??????????????????');
            $file_types = FixWorkflowProcess::$CHECK_TYPE;
            $file_type = $file->extension();
            $upload_file_name = $file->getClientOriginalName();
            $filename = date('YmdHis') . session('account.id') . strval(rand(1000, 9999)) . '.' . $file->getClientOriginalExtension();
            $path = storage_path('upload/fixWorkflowCheck');
            if (!is_dir($path)) FileSystem::init($path)->makeDir($path);
            $file->move($path, $filename);

            $now = date('Y-m-d H:i:s');
            $fixWorkflowSerialNumber = $request->get('fixWorkflowSerialNumber');
            $entireInstanceIdentityCode = $request->get('entireInstanceIdentityCode');
            $stage = $request->get('stage');
            $is_allow = $request->get('is_allow');
            $auto_explain = empty($request->get('auto_explain')) ? '' : $request->get('auto_explain');
            # ??????????????????
            $id = DB::table('fix_workflow_processes')->insertGetId([
                'created_at' => $now,
                'updated_at' => $now,
                'fix_workflow_serial_number' => $fixWorkflowSerialNumber,
                'stage' => $stage,
                'type' => 'ENTIRE',
                'auto_explain' => $auto_explain,
                'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS'),
                'numerical_order' => 1,
                'is_allow' => $is_allow,
                'processor_id' => session('account.id'),
                'processed_at' => $now,
                'upload_url' => 'upload/fixWorkflowCheck/' . $filename,
                'check_type' => array_key_exists($file_type, $file_types) ? $file_types[$file_type] : 'BINARY',
                'upload_file_name' => $upload_file_name
            ]);
            # ??????????????????????????? ??????????????????????????????????????????????????????????????????
            if (in_array($stage, ['CHECKED', 'WORKSHOP', 'SECTION',]) && $is_allow == 1) {
                DB::table('fix_workflows')->where('serial_number', $fixWorkflowSerialNumber)->update(['stage' => $stage, 'status' => 'FIXED', 'updated_at' => $now]);
                DB::table('entire_instances')->where('identity_code', $entireInstanceIdentityCode)->update(['status' => 'FIXED', 'updated_at' => $now, 'last_fix_workflow_at' => $now]);
            } else {
                DB::table('fix_workflows')->where('serial_number', $fixWorkflowSerialNumber)->update(['stage' => $stage, 'status' => 'FIXING', 'updated_at' => $now]);
                DB::table('entire_instances')->where('identity_code', $entireInstanceIdentityCode)->update(['status' => 'FIXING', 'updated_at' => $now]);
            }
            # ??????????????????
            EntireInstanceLogFacade::makeOne('??????????????????', $entireInstanceIdentityCode, 0, "/measurement/fixWorkflow/downloadCheck/{$id}", $auto_explain);

            return back()->with('success', '????????????');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * ?????????????????????
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function downloadCheck($id)
    {
        try {
            $fixWorkflowProces = FixWorkflowProcess::with([])->where('id', $id)->firstOrFail();
            if (!is_file(public_path($fixWorkflowProces->upload_url))) return back()->with('danger', '???????????????');
            return response()->download(public_path($fixWorkflowProces->upload_url), $fixWorkflowProces->upload_file_name);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '???????????????');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * ???????????????????????????????????????
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getBatchUploadFixerAndChecker()
    {
        try {
            if (request('download') == 1) {
                # ??????Excel??????
                ExcelWriteHelper::download(function ($excel) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    # ????????????
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');
                    $black = new \PHPExcel_Style_Color();
                    $black->setRGB();

                    // ??????
                    $currentSheet->setCellValueExplicit('A1', '????????????(?????????)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('A1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('B1', '?????????(?????????)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('B1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('C1', '??????*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('C1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('D1', '?????????*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('D1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('E1', '?????????*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('E1')->getFont()->setColor($red);
                    // ?????????????????????
                    $currentSheet->setCellValueExplicit('A2', 'EA1200000001', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('A2')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('B2', '', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('B2')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('C2', '??????????????????', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('C2')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('D2', 'Admin', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('D2')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('E2', 'Admin', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('E2')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('A3', 'EA1200000002', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('A3')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('B3', '', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('B3')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('C3', '???????????????', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('C3')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('D3', 'Admin', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('D3')->getFont()->setColor($black);
                    $currentSheet->setCellValueExplicit('E3', 'Admin', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('E3')->getFont()->setColor($black);

                    # ????????????
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(10);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(10);

                    return $excel;
                }, '????????????????????????');
            }

            return view('Measurement.FixWorkflow.batchUploadFixerAndChecker', []);
        } catch (\Exception $e) {
            // dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return redirect('/measurement/fixWorkflow/batchUploadFixerAndChecker')->with('danger', '????????????');
        }
    }

    /**
     * ?????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postBatchUploadFixerAndChecker(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '??????????????????');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '????????????excel??????????????????' . $request->file('file')->getClientMimeType());

            DB::beginTransaction();
            $origin_row = 2;
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')->originRow($origin_row)->withSheetIndex(0);

            $current_row = $origin_row;
            $ret = [];

            foreach ($excel['success'] as $row) {
                if (empty(array_filter($row, function ($value) {
                    return !empty($value);
                }))) continue;

                list($identity_code, $serial_number, $model_name, $fixer_name, $checker_name) = $row;
                $identity_code = strval($identity_code);
                $serial_number = strval($serial_number);
                $model_name = strval($model_name);
                $fixer_name = strval($fixer_name);
                $checker_name = strval($checker_name);

                if ($identity_code) {
                    $entire_instance = EntireInstance::with([])->where('identity_code', $identity_code)->first();
                    if (!$entire_instance) throw new FixWorkflowException("???{$current_row}??????????????????????????????/?????????{$identity_code}");
                } elseif ($serial_number) {
                    $entire_instances = EntireInstance::with([])
                        ->where('serial_number', $serial_number)
                        ->where('model_name', $model_name)
                        ->get();
                    if ($entire_instances->isEmpty()) throw new FixWorkflowException("???{$current_row}??????????????????????????????/?????????{$serial_number}???{$model_name}???");
                    if ($entire_instances->count() > 1) throw new FixWorkflowException("?????????????????????????????????/?????????{$serial_number}???{$model_name}???");
                    $entire_instance = $entire_instances->first();
                } else {
                    throw new FixworkflowException("???{$current_row}???????????????????????????????????????????????????");
                }

                $fixer = DB::table('accounts')->where('deleted_at', null)->where('nickname', $fixer_name)->first();
                if (!$fixer) throw new FixWorkflowException("???{$current_row}????????????????????????????????????{$fixer_name}");

                $checker = DB::table('accounts')->where('deleted_at', null)->where('nickname', $checker_name)->first();
                if (!$checker) throw new FixWorkflowException("???{$current_row}?????????????????????????????????{$checker_name}");

                # ????????????????????????
                $ret[] = [
                    'identity_code' => $entire_instance->identity_code,
                    'serial_number' => $serial_number,
                    'ret' => FixWorkflowFacade::mockEmpty(
                        $entire_instance,
                        $request->get('fixed_at') ?? null,
                        $request->get('checked_at') ?? null,
                        $fixer->id,
                        $checker->id
                    )
                ];

                # ????????????????????????????????????????????????????????????????????????????????????
                if ($entire_instance->v250_task_order_sn) {
                    $v250_task_entire_instance = V250TaskEntireInstance::with([])
                        ->where('v250_task_order_sn', $entire_instance->v250_task_order_sn)
                        ->where('entire_instance_identity_code', $entire_instance->identity_code)
                        ->first();
                    if ($v250_task_entire_instance) {
                        $v250_task_entire_instance
                            ->fill([
                                'fixer_id' => $fixer->id,
                                'fixed_at' => $request->get('fixed_at'),
                                'checker_id' => $checker->id,
                                'checked_at' => $request->get('checked_at'),
                            ])
                            ->saveOrFail();
                    }

                    $overhaul_entire_instance = OverhaulEntireInstance::with([])->where('entire_instance_identity_code', $entire_instance->identity_code)->orderByDesc('id')->first();
                    if ($overhaul_entire_instance && @$overhaul_entire_instance->status->code == '0') {
                        # ???????????????????????????????????????????????????????????????
                        $overhaul_entire_instance
                            ->full([
                                'entire_instance_identity_code' => $entire_instance->identity_code,
                                'fixer_id' => $fixer->id,
                                'fixed_at' => $request->get('fixed_at'),
                                'checker_id' => $checker->id,
                                'checked_at' => $request->get('checked_at'),
                                'status' => strtotime($overhaul_entire_instance->deadline) < strtotime($request->get('fixed_at')) ? '2' : '1',
                            ])
                            ->saveOrFail();
                    } else {
                        # ???????????????????????????????????????????????????????????????????????????
                        OverhaulEntireInstance::with([])->create([
                            'entire_instance_identity_code' => $entire_instance->identity_code,
                            'fixer_id' => $fixer->id,
                            'fixed_at' => $request->get('fixed_at'),
                            'checker_id' => $checker->id,
                            'checked_at' => $request->get('checked_at'),
                            'allocate_at' => $request->get('fixed_at'),
                            'deadline' => $request->get('fixed_at'),
                            'status' => '1',
                        ]);
                    }
                }
                $current_row++;
            }

            DB::commit();

            return back()->with('success', "???????????????" . count($ret) . "???");
        } catch (FixWorkflowException $e) {
            // dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return back()->withInput()->with('danger', $e->getMessage());
        } catch (\Exception $e) {
            // dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return back()->withInput()->with('danger', '????????????');
        } catch (\Throwable $e) {
            // dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return back()->withInput()->with('danger', $e->getMessage());
        }
    }
}
