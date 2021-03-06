<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\CombinationLocationRow;
use App\Model\EntireInstance;
use App\Model\EquipmentCabinet;
use App\Model\Maintain;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use \Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;
use \Throwable;

class EquipmentCabinetController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|RedirectResponse|View
     */
    final public function index()
    {
        try {
            $equipment_cabinets = ModelBuilderFacade::init(
                request(),
                EquipmentCabinet::with([
                    'EntireInstance',
                    'Station',
                ]),
                ['equipment_cabinet_unique_code',]
            );
            if (request()->ajax()) return JsonResponseFacade::data(['equipment_cabinets' => $equipment_cabinets->all()]);

            $current_equipment_cabinet_unique_code = request('equipment_cabinet_unique_code', $equipment_cabinets->all()->first() ? $equipment_cabinets->all()->first()->unique_code : '');
            $combination_locations = CombinationLocationRow::with([])->where('equipment_cabinet_unique_code', $current_equipment_cabinet_unique_code)->orderBy('row')->orderBy('column')->get();

            return view('EquipmentCabinet.index', [
                'equipment_cabinets' => $equipment_cabinets->pagination(),
                'equipment_cabinet_room_types_as_json' => json_encode(EquipmentCabinet::$ROOM_TYPES, 256),
                'combination_locations' => $combination_locations,
                'current_equipment_cabinet_unique_code' => $current_equipment_cabinet_unique_code,
            ]);
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function create()
    {
        return view('EquipmentCabinet.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    final public function store(Request $request)
    {
        try {
            if (!$request->get('name')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('??????????????????');
                return back()->withInput()->with('danger', '??????????????????');
            }
            if (!$request->get('room_type')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('????????????????????????');
                return back()->withInput()->with('danger', '????????????????????????');
            }
            if (!$request->get('maintain_station_unique_code')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('????????????????????????');
                return back()->withInput()->with('danger', '????????????????????????');
            }
            $row = intval($request->get('row', 0) ?? 0);
            if (!$row) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('?????????????????????');
                return back()->withInput()->with('danger', '?????????????????????');
            }

            if (EquipmentCabinet::with([])->where('name', $request->get('name'))->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))->first()) {
                if (!$request->ajax()) return JsonResponseFacade::errorForbidden('???????????????');
                return back()->withInput()->with('danger', '???????????????');
            }

            $station = Maintain::with([])->where('unique_code', $request->get('maintain_station_unique_code'))->first();
            if (!$station) return JsonResponseFacade::errorForbidden('?????????????????????');

            $title = $row . '???' . $request->get('name') . '???';
            $unique_code = EquipmentCabinet::generateUniqueCode($request->get('room_type'), $request->get('maintain_station_unique_code'), $row);

            $equipment_cabinet = EquipmentCabinet::with([])->create(
                array_merge(
                    ['title' => $title, 'unique_code' => $unique_code,],
                    $request->all()
                )
            );

            return JsonResponseFacade::created(['equipment_cabinet' => $equipment_cabinet]);
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Display the specified resource.
     * @param int $id
     * @return RedirectResponse
     */
    final public function show(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])->where('id', $id)->firstOrFail();

            return JsonResponseFacade::data(['equipment_cabinet' => $equipment_cabinet]);
        } catch (\Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Factory|Application|RedirectResponse|View
     */
    final public function edit(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])
                ->where('id', $id)
                ->firstOrFail();

            if (request()->ajax()) return JsonResponseFacade::data(['equipment_cabinet' => $equipment_cabinet]);
            return view('EquipmentCabinet.edit', [
                'equipment_cabinet' => $equipment_cabinet,
            ]);
        } catch (\Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws Throwable
     */
    final public function update(Request $request, $id)
    {
        try {
            if (!$request->get('name')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('??????????????????');
                return back()->withInput()->with('danger', '??????????????????');
            }
            if (!$request->get('room_type')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('????????????????????????');
                return back()->withInput()->with('danger', '????????????????????????');
            }
            if (!$request->get('maintain_station_unique_code')) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('????????????????????????');
                return back()->withInput()->with('danger', '????????????????????????');
            }
            $row = intval($request->get('row', 0) ?? 0);
            if (!$row) {
                if ($request->ajax()) return JsonResponseFacade::errorValidate('?????????????????????');
                return back()->withInput()->with('danger', '?????????????????????');
            }

            if (EquipmentCabinet::with([])->where('id', '<>', $id)->where('name', $request->get('name'))->where('maintain_station_unique_code', $request->get('maintain_station_unique_code'))->first()) {
                if (!$request->ajax()) return JsonResponseFacade::errorForbidden('???????????????');
                return back()->withInput()->with('danger', '???????????????');
            }

            $title = $row . '???' . $request->get('name') . '???';

            $equipment_cabinet = EquipmentCabinet::with([
                'EntireInstance',
                'Station',
                'CombinationLocations',
            ])
                ->where('id', $id)
                ->firstOrFail();
            $equipment_cabinet->fill(
                array_merge(
                    ['title' => $title,],
                    $request->all()
                )
            )->saveOrFail();

            return JsonResponseFacade::updated(['equipment_cabinet' => $equipment_cabinet]);
        } catch (\Exception $e) {
            if ($request->ajax()) return JsonResponseFacade::errorException($e);
            Commonfacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    final public function destroy(int $id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $id)->firstOrFail();
            if (!$equipment_cabinet->unique_code) return JsonResponseFacade::errorForbidden('??????????????????????????????');
            $entire_instances_S = EntireInstance::with([])->where('equipment_cabinet_unique_code', $equipment_cabinet->unique_code)->get();
            if ($entire_instances_S->isNotEmpty()) return JsonResponseFacade::errorForbidden('???????????????????????????????????????????????????<br>' . implode("<br>", $entire_instances_S->pluck('identity_code')->toArray()));
            $combination_locations = CombinationLocation::with([])->where('equipment_cabinet_unique_code', $equipment_cabinet->unique_code)->get();
            if ($combination_locations->isNotEmpty()) return JsonResponseFacade::errorForbidden('?????????????????????????????????????????????');
            $equipment_cabinet->forceDelete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            if (request()->ajax()) return JsonResponseFacade::errorEmpty();
            return back()->with('danger', '???????????????');
        } catch (Exception $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '????????????');
        }
    }

    /**
     * ????????????????????????
     * @param int $equipment_cabinet_id
     * @return mixed
     */
    final public function getBindEntireInstance(int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();

            $entire_instances = EntireInstance::with([])
                ->where('category_unique_code', 'like', 'S%')
                ->where('maintain_station_name', $equipment_cabinet->Station->name)
                ->get();

            return JsonResponseFacade::data([
                'entire_instances' => $entire_instances,
                'current_entire_instance_identity_code' => $equipment_cabinet->entire_instance_identity_code,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????
     * @param Request $request
     * @param int $equipment_cabinet_id
     * @return mixed
     */
    final public function postBindEntireInstance(Request $request, int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();
            $equipment_cabinet->fill(['entire_instance_identity_code' => $request->get('entireInstanceIdentityCode')])->saveOrFail();
            return JsonResponseFacade::created(['equipment_cabinet' => $equipment_cabinet], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @param int $equipment_cabinet_id
     */
    final public function deleteBindEntireInstance(int $equipment_cabinet_id)
    {
        try {
            $equipment_cabinet = EquipmentCabinet::with([])->where('id', $equipment_cabinet_id)->firstOrFail();
            $equipment_cabinet->fill(['entire_instance_identity_code' => ''])->saveOrFail();
            return JsonResponseFacade::deleted([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
