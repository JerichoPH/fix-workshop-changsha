<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Http\Requests\BreakdownTypeStoreRequest;
use App\Model\Account;
use App\Model\BreakdownType;
use App\Model\Category;
use App\Model\WorkArea;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Jericho\ValidateHelper;

class BreakdownTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    final public function index()
    {
        if (request()->ajax()) {
            try {
                $work_area = array_flip(Account::$WORK_AREAS)[session('account.work_area')];
                $breakdown_types = BreakdownType::with([])
                    ->when(
                        $work_area > 0,
                        function ($query) use ($work_area) {
                            return $query->where('work_area', $work_area);
                        }
                    )
                    ->get();

                return response()->json(['message' => '读取成功', 'breakdown_types' => $breakdown_types]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['msg' => '数据不存在'], 404);
            } catch (\Throwable $th) {
                return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
            }
        } else {
            $breakdown_types = BreakdownType::with(['Category'])
                ->orderByDesc('id')
                ->paginate();

            $categories = Category::with([])->get();

            return view('BreakdownType.index', [
                'breakdown_types' => $breakdown_types,
                'work_area_types' => BreakdownType::$WORK_AREA_TYPES,
                'categories' => $categories,
                'work_area_types_as_json' => json_encode(BreakdownType::$WORK_AREA_TYPES, 256),
                'categories_as_json' => $categories->toJson()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new BreakdownTypeStoreRequest());
            if (!$v) return JsonResponseFacade::errorValidate($v);
            $category = Category::with([])->where('unique_code', $request->get('category_unique_code'))->first();
            if (!$category) return JsonResponseFacade::errorEmpty('种类不存在');

            $breakdown_type = BreakdownType::with([])->create([
                'name' => $request->get('name'),
                'category_unique_code' => $category->unique_code,
                'category_name' => $category->name,
                'work_area' => $request->get('work_area_type'),
            ]);

            return JsonResponseFacade::created(['breakdown_type' => $breakdown_type]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        try {
            $breakdown_type = BreakdownType::with([])->where('id', $id)->firstOrFail();

            return JsonResponseFacade::data(['breakdown_type' => $breakdown_type]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorValidate('故障类型名称不能为空');
            $category = Category::with([])->where('unique_code', $request->get('category_unique_code'))->first();
            if (!$category) return JsonResponseFacade::errorEmpty('种类不存在');

            $breakdown_type = BreakdownType::with([])->where('id', $id)->firstOrFail();
            $breakdown_type->fill([
                'name' => $request->get('name'),
                'category_unique_code' => $category->unique_code,
                'category_name' => $category->name,
                'work_area' => $request->get('work_area_type'),
            ])
                ->saveOrFail();

            return JsonResponseFacade::updated(['breakdown_type' => $breakdown_type]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy($id)
    {
        try {
            BreakdownType::with([])->where('id', $id)->delete();

            $breakdown_types = BreakdownType::with([])
                ->whereIn('work_area', [0, array_flip(Account::$WORK_AREAS)[session('account.work_area')]])
                ->get();

            return response()->json(['message' => '删除成功', 'breakdown_types' => $breakdown_types]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }
}
