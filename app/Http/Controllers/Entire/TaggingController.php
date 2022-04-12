<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\ExcelInException;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceExcelTaggingIdentityCode;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\Factory;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;
use Throwable;

class TaggingController extends Controller
{
    /**
     * 下载批量赋码模板Excel
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getDownloadUploadCreateDeviceExcelTemplate()
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', request('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            return EntireInstanceFacade::downloadUploadCreateDeviceExcelTemplate($work_area);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 赋码页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getUploadCreateDevice()
    {
        try {
            $work_areas = WorkArea::with([])->get();

            return view('Entire.Tagging.uploadCreateDevice', [
                'work_areas' => $work_areas,
            ]);
        } catch (\Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 上传设备赋码Excel
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadCreateDevice(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

            $work_area = WorkArea::with([])->where('unique_code', $request->get('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            return EntireInstanceFacade::uploadCreateDevice($request, $work_area->type, $work_area->unique_code);
        } catch (ExcelInException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 上传设备赋码Excel报告
     * @param string $serial_number
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getUploadCreateDeviceReport(string $serial_number)
    {
        try {
            // 获取基础数据
            $factories = Factory::with([])->get();
            $scene_workshops = DB::table('maintains as sc')->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))->where('sc.type', 'SCENE_WORKSHOP')->get();
            $stations = DB::table('maintains as s')->where('s.type', 'STATION')->get()->groupBy('parent_unique_code');

            // 获取赋码上传记录
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->first();

            // 获取本次入所单的设备
            $entire_instances = EntireInstanceExcelTaggingIdentityCode::with([
                'EntireInstance',
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
                'EntireInstance.Factory',
            ])
                ->where('entire_instance_excel_tagging_report_sn', $serial_number)
                ->paginate(200);

            // 计算当前页容量
            $total = $entire_instances->total();
            $current_page = $entire_instances->currentPage();
            $pre_page = $entire_instances->perPage();
            $last_page = $entire_instances->lastPage();
            if ($last_page === 1) {
                $current_total = $total;
            } else {
                if ($last_page === $current_page) {
                    $page = $last_page - 1;
                    $current_total = $total - $page * $pre_page;
                } else {
                    $current_total = $pre_page;
                }
            }

            // 检查是否有错误（设备赋码）
            $has_create_device_error = false;
            $create_device_error_filename = null;
            if ($entire_instance_excel_tagging_report->is_upload_create_device_excel_error) {
                $create_device_error_dir = 'entireInstanceTagging/upload/errorExcels/createDevice';
                $create_device_error_filename = "{$create_device_error_dir}/{$serial_number}.xls";
                if (!is_dir(storage_path($create_device_error_dir))) FileSystem::init(storage_path($create_device_error_dir))->makeDir();
                if (file_exists(storage_path($create_device_error_filename))) $has_create_device_error = true;
            }

            return view('Entire.Tagging.uploadCreateDeviceReport', [
                'entireInstanceExcelTaggingReport' => $entire_instance_excel_tagging_report,
                'entireInstances' => $entire_instances,
                'hasCreateDeviceError' => $has_create_device_error,
                'createDeviceErrorFilename' => $create_device_error_filename,
                'factories_as_json' => $factories->toJson(),
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'current_total' => $current_total,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 下载设备赋码Excel错误报告
     * @param string $serial_number
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadCreateDeviceErrorExcel(string $serial_number)
    {
        try {
            EntireInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->firstOrFail();

            $filename = storage_path(request('path'));
            if (!file_exists($filename)) return back()->with('danger', '文件不存在');

            return response()->download($filename, '上传设备赋码错误报告.xls');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备/器材赋码 记录列表
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getReport(int $id = 0)
    {
        try {
            // 当前时间
            list($origin_at, $finish_at) = explode('~', request('created_at') ?? join('~', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')]));

            // 操作人列表
            $accounts = Account::with([])->where('work_area_unique_code', session('account.work_area_unique_code'))->get();

            // 赋码记录表
            $entire_instance_excel_tagging_reports = ModelBuilderFacade::init(
                request(),
                EntireInstanceExcelTaggingReport::with([]),
                ['created_at']
            )
                ->extension(
                    function ($entire_instance_excel_tagging_report) use ($origin_at, $finish_at) {
                        return $entire_instance_excel_tagging_report
                            ->where('work_area_unique_code', session('account.work_area_unique_code'))
                            ->whereBetween('created_at', [Carbon::parse($origin_at)->startOfDay()->format('Y-m-d H:i:s'), Carbon::parse($finish_at)->endOfDay()->format('Y-m-d H:i:s')])
                            ->orderByDesc('created_at');
                    })
                ->pagination(50);
            $create_device_error_dir = 'entireInstanceTagging/upload/errorExcels/createDevice';

            return view('Entire.Tagging.report', [
                'entire_instance_excel_tagging_reports' => $entire_instance_excel_tagging_reports,
                'create_device_error_dir' => $create_device_error_dir,
                'processors' => $accounts,
                'origin_at' => $origin_at,
                'finish_at' => $finish_at,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备/器材赋码 详情
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getReportShow(int $id)
    {
        try {
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([
                'EntireInstanceIdentityCodes',
                'EntireInstanceIdentityCodes.EntireInstance',
                'EntireInstanceIdentityCodes.EntireInstance.SubModel',
                'EntireInstanceIdentityCodes.EntireInstance.PartModel',
            ])
                ->where('id', $id)
                ->firstOrFail();

            return view('Entire.Tagging.reportShow', [
                'entire_instance_excel_tagging_report' => $entire_instance_excel_tagging_report,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备/器材赋码 回退
     * @param Request $request
     * @param int $id
     */
    final public function postRollback(Request $request, int $id)
    {
        try {
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])->where('id', $id)->firstOrFail();
            $identity_codes = EntireInstanceExcelTaggingIdentityCode::with([])->where('entire_instance_excel_tagging_report_sn', $entire_instance_excel_tagging_report->serial_number)->pluck('entire_instance_identity_code')->toArray();  // 获取赋码设备/器材唯一编号组

            $entire_instance_excel_tagging_report->forceDelete();  // 删除设备/器材赋码记录单
            EntireInstanceExcelTaggingIdentityCode::with([])->where('entire_instance_excel_tagging_report_sn', $entire_instance_excel_tagging_report->serial_number)->forceDelete();  // 删除设备/器材赋码唯一编号记录
            EntireInstance::with([])->whereIn('identity_code', $identity_codes)->forceDelete();  // 删除设备/器材
            EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除设备/器材日志

            $fix_workflow_serial_numbers = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->pluck('serial_number')->toArray();  // 获取检修单编号组
            $fix_workflow_process_serial_numbers = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->pluck('serial_number')->toArray();  // 获取检修过程单编号组
            FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_process_serial_numbers)->forceDelete();  // 删除检修记录值
            FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->forceDelete();  // 删除检测过程单
            FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除检修单

            WarehouseReportEntireInstance::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete(); // 删除出入所设备

            EntireInstanceLock::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除设备锁

            $entire_instance_excel_tagging_report->forceDelete();  // 删除赋码批次单

            return JsonResponseFacade::deleted([], '回退成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

}
