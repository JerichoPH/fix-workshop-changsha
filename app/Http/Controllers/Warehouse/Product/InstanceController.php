<?php

namespace App\Http\Controllers\Warehouse\Product;

use App\Http\Controllers\Controller;
use App\Model\WarehouseProductInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $warehouseProductInstances = WarehouseProductInstance::with(['warehouseProduct' => function ($query) {
            $query->orderByDesc('id');
        }, 'factory'])
            ->where('warehouse_product_unique_code', request('warehouseProductUniqueCode'))
            ->whereNotIn('status', ['SCRAP'])
            ->orderByDesc('id')
            ->paginate();
        return view($this->view('index'), ['warehouseProductInstances' => $warehouseProductInstances]);
    }

    private function view($viewName)
    {
        return "Warehouse.Product.Instance.{$viewName}";
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
        try {
            $warehouseProductInstance = WarehouseProductInstance::with([
                'warehouseProduct',
                'warehouseProduct.category',
                'factory',
                'fixWorkflows',
                'fixWorkflows.fixWorkflowProcesses',
                'fixWorkflows.fixWorkflowProcesses.processor',
                'fixWorkflows.fixWorkflowProcesses.measurement',
            ])->findOrFail($id);
//            dump($warehouseProductInstance);
            $fixWorkflows = $warehouseProductInstance->fixWorkflows->toArray();
            $fixWorkflowProcesses = [];
            foreach ($warehouseProductInstance->fixWorkflows->toArray() as $fixWorkflowKey => $fixWorkflow) {
                foreach ($fixWorkflow['fix_workflow_processes'] as $fixWorkflowProcess) {
                    $fixWorkflowProcesses[$fixWorkflowProcess['status']][] = $fixWorkflowProcess;
                }
                $fixWorkflows[$fixWorkflowKey]['fix_workflow_processes'] = $fixWorkflowProcesses;
            }
//            dump($fixWorkflows);

            return view($this->view('edit'), [
                'warehouseProductInstance' => $warehouseProductInstance,
                'fixWorkflows' => $fixWorkflows
            ]);
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
        //
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

    /**
     * ????????????
     * @param int $warehouseProductInstanceId ??????????????????
     * @return \Illuminate\Http\Response
     */
    public function getScrapWarehouseProductInstance($warehouseProductInstanceId)
    {
        try {
            DB::transaction(function () use ($warehouseProductInstanceId) {
                # ????????????
                DB::table('warehouse_product_instances')->where('id', $warehouseProductInstanceId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
                # ??????????????????
                DB::table('warehouse_product_plans')->where('warehouse_product_instance_id', $warehouseProductInstanceId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            });

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }

    /**
     * ?????????????????????????????????
     * @param int $warehouseProductInstanceId ?????????????????????????????????
     * @return \Illuminate\Http\Response
     */
    public function getWarehouseProductInstance($warehouseProductInstanceId)
    {
        try {
            $warehouseProductInstance = WarehouseProductInstance::findOrFail($warehouseProductInstanceId);
            # ??????????????????????????????????????????????????????
            DB::table('warehouse_product_instances')->where('maintain_unique_code', $warehouseProductInstance->maintain_unique_code)->update(['is_using' => 0]);
            # ?????????????????????????????????
            $warehouseProductInstance->fill(['is_using' => 1])->saveOrFail();

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????' . $exception->getMessage(), 500);
        }
    }
}
