<?php

namespace App\Http\Controllers\Part;

use App\Facades\CodeFacade;
use App\Facades\ExcelReader;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Factory;
use App\Model\Measurement;
use App\Model\PartFixWorkflowRecord;
use App\Model\PartInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\TextHelper;

class InstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            $type = request('type');
            $status = request('status', ['BUY_IN', 'INSTALLING', 'INSTALLED', 'FIXING', 'FIXED', 'RETURN_FACTORY', 'FACTORY_RETURN', 'SCRAP']);
//            $partInstances = PartInstance::where('part_model_unique_code', request($type))->whereIn('status', $status)->where('entire_instance_identity_code',request('entire_instance_identity_code',null))->get();
            $partInstances = PartInstance::with(['Category', 'EntireModel', 'PartModel', 'PartModel.EntireModels'])
                ->where('part_model_unique_code', request($type))
                ->where('entire_instance_identity_code', request('entire_instance_identity_code', null))
                ->whereIn('status', $status)
                ->get();
            return Response::json($partInstances);
        }
        $partInstances = PartInstance::with(['Category', 'EntireModel', 'PartModel', 'PartModel.EntireModels'])->orderByDesc('id')->paginate();
        return view('Part.Instance.index')
            ->with('partInstances', $partInstances);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $identityCode
     */
    public function update(Request $request, $identityCode)
    {
        # ?????????????????????????????????
        $partFixWorkflowRecords = PartFixWorkflowRecord::where('part_instance_identity_code', $identityCode)->get();

        # ????????????????????????????????????
        $fixWorkflowProcessIsAllow = true;
        foreach ($partFixWorkflowRecords as $partFixWorkflowRecord) {
            # ??????????????????
            if (!$partFixWorkflowRecord->is_allow) {
                $fixWorkflowProcessIsAllow = false;
            }
            break;
        }

        # ??????????????????
        DB::table('part_instances')
            ->where('identity_code', $identityCode)
            ->update(['status' => $fixWorkflowProcessIsAllow ? 'FIXED' : 'FIXING']);

        # ??????????????????????????????
        DB::table('part_fix_workflow_records')->where('part_instance_identity_code', $request->get('part_instance_identity_code'))->update([
            'updated_at' => date('Y-m-d H:i:s'),
            'processor_id' => $request->get('processor_id'),
            'processed_at' => $request->get('processed_at')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getBatch()
    {
        return view('Part.Instance.batch')
            ->with('factories', Factory::all())
            ->with('categories', Category::all());
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postBatch(Request $request)
    {
        if ($request->hasFile('part')) {
            $inputFileName = $request->file('part');
            // ??????excel??????
            try {
                $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }

            $repeat = [];

            foreach ($objPHPExcel->getSheetNames() as $sheetName) {
                $sheet = $objPHPExcel->getSheetByName($sheetName);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $insertData = [];
                $partModelUniqueCodes = DB::table('part_models')->pluck('name', 'unique_code');
                // ?????????????????????
                for ($row = 2; $row <= $highestRow; $row++) {
                    list($createdAt,
                        $scrapedAt,
                        $entireInstanceIdentityCode,
                        $factoryDeviceCode)
                        = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

                    # ?????????????????????????????????
                    if (DB::table('part_instances')->where('factory_device_code', $factoryDeviceCode)->first()) {
                        $repeat[] = $factoryDeviceCode;
                        continue;
                    }

                    # ??????????????????????????????????????????
                    if ($entireInstanceIdentityCode) {
                        if (DB::table('entire_instances')->where('identity_code', $entireInstanceIdentityCode)->first()) continue;
                    }

                    if ($factoryDeviceCode == null) continue;  # ???????????????????????????????????????????????????

                    $insertData[] = [
                        'created_at' => $createdAt ?: date('Y-m-d H:i:s'),
                        'updated_at' => $createdAt ?: date('Y-m-d H:i:s'),
                        'part_model_unique_code' => $request->get('part_model_unique_code'),
                        'part_model_name' => $partModelUniqueCodes[$request->get('part_model_unique_code')],
                        'entire_instance_identity_code' => $entireInstanceIdentityCode,
                        'status' => 'FIXING',
                        'factory_name' => $request->get('factory_name'),
                        'factory_device_code' => $factoryDeviceCode,
                        'identity_code' => CodeFacade::makePartInstanceIdentityCode($request->get('part_model_unique_code')),
                        'entire_instance_serial_number' => null,
                        'category_unique_code' => $request->get('category_unique_code'),
                        'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                    ];
                }
                if (count($insertData) > 0) DB::table('part_instances')->insert($insertData);
            }

            $repeatNumber = count($repeat);
            $message = '????????????';
            if ($repeatNumber) $message .= "???????????????{$repeatNumber}???";

            return back()->with('success', $message);
        }
        return back()->with('error', '?????????????????????');
    }

    /**
     * ??????????????????
     * @param $identityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getFixWorkflowRecode($identityCode)
    {
        $fixWorkflowRecords = [];
        try {
            $partInstance = PartInstance::with(['PartModel', 'PartModel.Measurements'])->where('identity_code', $identityCode)->firstOrFail();
            if (!DB::table('part_fix_workflow_records')->where('part_instance_identity_code', $identityCode)->first()) {
                # ????????????????????????????????????
                foreach ($partInstance->PartModel->Measurements as $measurement) {
                    $fixWorkflowRecords[] = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'part_instance_identity_code' => $partInstance->identity_code,
                        'measurement_identity_code' => $measurement->identity_code,
                    ];
                }
                DB::table('part_fix_workflow_records')->insert($fixWorkflowRecords);
            }
            # ??????????????????
            $partFixWorkflowRecords = PartFixWorkflowRecord::with([
                'PartInstance',
                'PartInstance.PartModel',
                'PartInstance.PartModel.Measurements'
            ])
                ->where('part_instance_identity_code', $identityCode)
                ->get();

            return view('Part.Instance.fixWorkflowRecord')
                ->with('partFixWorkflowRecords', $partFixWorkflowRecords);
        } catch (ModelNotFoundException $exception) {
            dd('error1', $exception->getMessage());
            return back()->with('error', '???????????????');
        } catch (\Exception $exception) {
            dd('error2', $exception->getMessage(), $exception->getFile(), $exception->getLine());
            return back()->with('error', '????????????');
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param $partInstanceIdentityCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function saveMeasuredValue(Request $request, $partInstanceIdentityCode)
    {
        try {
            $partFixWorkflowRecords = null;
            DB::transaction(function () use ($request, &$partFixWorkflowRecords, $partInstanceIdentityCode) {
                # ????????????????????????
                $measurement = Measurement::with([])->where('identity_code', $request->get('measurement_identity_code'))->firstOrFail();

                # ????????????????????????????????????
                $isAllow = false;
                if ($measurement->allow_min == null && $measurement->allow_max == null) {
                    throw new \Exception(0);
                } else {
                    if ($measurement->allow_min == $measurement->allow_max) {
                        $isAllow = floatval($request->get('measured_value')) == floatval($measurement->allow_min);
                    } elseif ($measurement->allow_min == null && $measurement->allow_max != null) {
                        $isAllow = floatval($request->get('measured_value')) <= floatval($measurement->allow_max);
                    } elseif ($measurement->allow_min != null && $measurement->allow_max == null) {
                        $isAllow = floatval($request->get('measured_value')) >= floatval($measurement->allow_min);
//                        throw new \Exception(json_encode([intval($isAllow), floatval($request->get('measuredValue')), floatval($measurement->allow_min)]));
                    } else {
                        $isAllow = floatval($request->get('measured_value')) >= floatval($measurement->allow_min) && floatval($request->get('measured_value')) <= floatval($measurement->allow_max);
                    }
                }

                # ???????????????
                $fixWorkflowRecord = PartFixWorkflowRecord::with([])->where('id', $request->get('id'))->firstOrfail();
                $fixWorkflowRecord->measured_value = $request->get('measured_value');
                $fixWorkflowRecord->is_allow = $isAllow;
                $fixWorkflowRecord->saveOrFail();

                # ?????????????????????????????????
                $partFixWorkflowRecords = PartFixWorkflowRecord::with([])->where('part_instance_identity_code', $partInstanceIdentityCode)->get();

                # ????????????????????????????????????
                $fixWorkflowProcessIsAllow = true;
                foreach ($partFixWorkflowRecords as $partFixWorkflowRecord) {
                    if (!$partFixWorkflowRecord->is_allow) {
                        $fixWorkflowProcessIsAllow = false;
                    }
                    break;
                }
                DB::table('part_instances')->where('identity_code', $partInstanceIdentityCode)->update(['status' => $fixWorkflowProcessIsAllow ? 'FIXED' : 'FIXING']);
            });
            return \response()->json($partFixWorkflowRecords);
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
    }

    /**
     * ?????????????????? ??????
     */
    public function getBuyIn()
    {
        return view('Part.Instance.buyIn');
    }

    /**
     * ??????????????????
     */
    public function postBuyIn(Request $request)
    {
        try {
            $current_time = date('Y-m-d H:i:s');
            $save_dir = storage_path('/partInstanceUpload');
            if(!is_dir($save_dir)) mkdir($save_dir);

            if (is_file("{$save_dir}partInstance.xlsx")) unlink("{$save_dir}partInstance.xlsx");

            $file = $request->file('part');
            if (!$file) return back()->with('danger', '??????????????????');

            $file->move($save_dir, 'partInstance.xlsx');

            # ????????????????????????
            $count_id = DB::table('part_instances')->count();

            $excel = ExcelReader::file("{$save_dir}/partInstance.xlsx")->readSheetByIndex(0, 2, 0);

            $insert = [];
            $success_count = 0;

            foreach ($excel['success'] as $row => $success) {
                $r = $row + 1;
                $count_id++;
                list($part_category_name, $part_model_name, $self_model, $factory_device_code, $factory_name, $made_at, $live_year) = $success;
                if (empty($part_model_name) || !$part_model_name) return back()->with('danger', "????????????????????????{$r}???");
                $part_model = DB::table('part_models')->where('deleted_at', null)->where('name', $part_model_name)->first();
                if (is_null($part_model) || empty($part_model)) return back()->with('danger', "????????????????????????{$r}???,{$part_model_name}");

                if (empty($part_category_name) || !$part_category_name) return back()->with('danger', "????????????????????????{$r}???");
                $part_category = DB::table('part_categories')->where('deleted_at', null)->where('name', $part_category_name)->first();
                if (is_null($part_category) || empty($part_category)) return back()->with('danger', "????????????????????????{$r}??????{$part_category_name}");

                if (empty($factory_device_code) || is_null($factory_device_code)) return back()->with('danger', "????????????????????????{$r}???");
                $factory_device_code_repeat = DB::table('part_instances')->where('deleted_at', null)->where('factory_device_code', $factory_device_code)->where('factory_name', $factory_name)->first();
                if ($factory_device_code_repeat) return back()->with('danger', "?????????????????????{$r}??????{$factory_name} {$factory_device_code}");

                if ($made_at) {
                    $made_timestamp = $this->_getTimestamp($made_at);  # ????????????????????????
                    $made_carbon = Carbon::createFromTimestamp($made_timestamp);  # ???????????????
                    $made_date = $made_carbon->format('Y-m-d');  # ??????????????????????????????
                    $scraping_at = $live_year ? $made_carbon->addYear($live_year)->format('Y-m-d') : null;
                } else {
                    $made_date = null;
                    $scraping_at = null;
                }
                $insert[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'part_model_unique_code' => $part_model->unique_code,
                    'part_model_name' => $part_model->name,
                    'status' => 'FIXED',
                    'factory_name' => $factory_name,
                    'factory_device_code' => $factory_device_code,
                    'identity_code' => str_pad(TextHelper::to32($count_id), 8, '0', STR_PAD_LEFT),
                    'category_unique_code' => substr($part_model->unique_code, 0, 3),
                    'entire_model_unique_code' => substr($part_model->unique_code, 0, 5),
                    'self_model' => $self_model,
                    'part_category_id' => $part_category->id,
                    'is_need_detection' => true,
                    'made_at' => $made_date,
                    'scraping_at' => $scraping_at,
                ];
            }

            # ???????????????
            DB::transaction(function () use ($insert) {
                DB::table('part_instances')->insert($insert);
            });

            return back()->with('success', '???????????????' . count($insert) . '???');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            dd($msg, $file, $line);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * excel????????????????????????
     * @param int $t
     * @return int
     */
    final private function _getTimestamp(int $t): int
    {
        return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24)))->timestamp;
    }

    /**
     * excel???????????????????????????
     * @param int $t
     * @return string
     */
    final private function _getDatetime(int $t): string
    {
        return gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24));
    }
}
