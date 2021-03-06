<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Model\Category;
use App\Model\EntireModel;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class KindQController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Factory|RedirectResponse|View
     */
    final public function index()
    {
        // 获取器材种类
        $categories = Category::with([])->where('unique_code', 'like', 'Q%')->get();
        $current_category_unique_code = request('category_unique_code', $categories->isNotEmpty() ? $categories->first()->unique_code : '');
        // 获取器材类型
        $entire_models = EntireModel::with([])->where('category_unique_code', $current_category_unique_code)->where('is_sub_model', false)->get();
        $current_entire_model_unique_code = request('entire_model_unique_code', $entire_models->isNotEmpty() ? $entire_models->first()->unique_code : '');
        // 获取器材型号
        $sub_models = EntireModel::with([])->where('parent_unique_code', $current_entire_model_unique_code)->where('is_sub_model', true)->get();
        $current_sub_model_unique_code = request('sub_model_unique_code', $sub_models->isNotEmpty() ? $sub_models->first()->unqieu_code : '');
        if (request()->ajax()) {
            try {
                return JsonResponseFacade::data([
                    'categories' => $categories,
                    'entire_models' => $entire_models,
                    'sub_models' => $sub_models,
                    'current_category_unique_code' => $current_category_unique_code,
                    'current_entire_model_unique_code' => $current_entire_model_unique_code,
                    'current_sub_model_unique_code' => $current_sub_model_unique_code,
                ]);
            } catch (Exception $e) {
                return JsonResponseFacade::errorException($e);
            }
        } else {
            try {
                return view('KindQ.index', [
                    'categories_as_json' => $categories->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy(['category_unique_code'])->toJson(),
                    'sub_models_as_json' => $sub_models->groupBy(['parent_unique_code'])->toJson(),
                    'entire_models' => $entire_models,
                    'sub_models' => $sub_models,
                    'current_category_unique_code' => $current_category_unique_code,
                    'current_entire_model_unique_code' => $current_entire_model_unique_code,
                    'current_sub_model_unique_code' => $current_sub_model_unique_code,
                ]);
            } catch (\Exception $e) {
                \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
                return back()->with('danger', '意外错误');
            }
        }
    }

    /**
     * 获取种类
     * @param string $unique_code
     */
    final public function getCategory(string $unique_code)
    {
        try {
            $category = Category::with([])->where('unique_code', $unique_code)->first();

            return JsonResponseFacade::data(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加种类
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCategory(Request $request): JsonResponse
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_category = Category::with([])->where('name', $request->get('name'))->first();
            if ($repeat_category) return JsonResponseFacade::errorForbidden('名称重复');

            $category = Category::with([])->create([
                'unique_code' => Category::generateUniqueCode('Q'),
                'name' => $request->get('name'),
            ]);

            return JsonResponseFacade::created(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑种类
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function putCategory(Request $request): JsonResponse
    {
        try {
            $category = Category::with([])->where('unique_code', $request->get('unique_code'))->first();

            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_category = Category::with([])->where('id', '<>', $category->id)->where('name', $request->get('name'))->first();
            if ($repeat_category) return JsonResponseFacade::errorForbidden('名称重复');

            $category->fill(['name' => $request->get('name')])->saveOrFail();

            return JsonResponseFacade::updated(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取类型数据
     * @param string $unique_code
     */
    final public function getEntireModel(string $unique_code)
    {
        try {
            $entire_model = EntireModel::with([])
                ->where('is_sub_model', false)
                ->where('unique_code', $unique_code)
                ->first();

            return JsonResponseFacade::data(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加类型
     * @param Request $request
     * @return JsonResponse
     */
    final public function postEntireModel(Request $request): JsonResponse
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_entire_model = EntireModel::with([])
                ->where('category_unique_code', $request->get('category_unique_code'))
                ->where('is_sub_model', false)
                ->where('name', $request->get('name'))
                ->first();
            if ($repeat_entire_model) return JsonResponseFacade::errorForbidden('名称重复');
            if ($request->get('fix_cycle_value') < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $entire_model = EntireModel::with([])->create([
                'is_sub_model' => false,
                'unique_code' => EntireModel::generateEntireModelUniqueCode($request->get('category_unique_code')),
                'name' => $request->get('name'),
                'category_unique_code' => $request->get('category_unique_code'),
                'fix_cycle_value' => $request->get('fix_cycle_value', 0),
            ]);

            return JsonResponseFacade::created(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑类型
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function putEntireModel(Request $request): JsonResponse
    {
        try {
            $entire_model = EntireModel::with([])->where('unique_code', $request->get('unique_code'))->where('is_sub_model', false)->first();

            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_entire_model = EntireModel::with([])->where('id', '<>', $entire_model->id)->where('name', $request->get('name'))->first();
            if ($repeat_entire_model) return JsonResponseFacade::errorForbidden('名称重复');

            if ($request->get('fix_cycle_value') < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $entire_model
                ->fill([
                    'name' => $request->get('name'),
                    'fix_cycle_value' => $request->get('fix_cycle_value', 0)
                ])
                ->saveOrFail();

            return JsonResponseFacade::updated(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取型号数据
     * @param string $unique_code
     * @return mixed
     */
    final public function getSubModel(string $unique_code)
    {
        try {
            $sub_model = EntireModel::with([])
                ->where('is_sub_model', true)
                ->where('unique_code', $unique_code)
                ->first();

            return JsonResponseFacade::data(['sub_model' => $sub_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加子类
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSubModel(Request $request): JsonResponse
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_sub_model = EntireModel::with([])
                ->where('parent_unique_code', $request->get('entire_model_unique_code'))
                ->where('is_sub_model', true)->where('name', $request->get('name'))
                ->first();
            if ($repeat_sub_model) return JsonResponseFacade::errorForbidden('名称重复');
            if ($request->get('fix_cycle_value', 0) < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $sub_model = EntireModel::with([])
                ->create([
                    'category_unique_code' => substr($request->get('entire_model_unique_code'), 0, 3),
                    'parent_unique_code' => $request->get('entire_model_unique_code'),
                    'is_sub_model' => true,
                    'unique_code' => EntireModel::generateSubModelUniqueCode($request->get('entire_model_unique_code')),
                    'name' => $request->get('name'),
                    'fix_cycle_value' => $request->get('fix_cycle_value', 0),
                ]);

            return JsonResponseFacade::created(['sub_model' => $sub_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑子类
     * @param Request $request
     * @param string $sub_model_unique_code
     * @return JsonResponse
     * @throws Throwable
     */
    final public function putSubModel(Request $request): JsonResponse
    {
        try {
            $sub_model = EntireModel::with([])->where('unique_code', $request->get('unique_code'))->where('is_sub_model', true)->first();

            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_sub_model = EntireModel::with([])->where('id', '<>', $sub_model->id)->where('name', $request->get('name'))->first();
            if ($repeat_sub_model) return JsonResponseFacade::errorForbidden('名称重复');

            if ($request->get('fix_cycle_value') < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $sub_model
                ->fill([
                    'name' => $request->get('name'),
                    'fix_cycle_value' => $request->get('fix_cycle_value')
                ])
                ->saveOrFail();

            return JsonResponseFacade::updated(['sub_model' => $sub_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
