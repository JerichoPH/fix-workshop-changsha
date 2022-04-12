@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <section class="content">
        @include('Layout.alert')
        <form id="frmQuery" action="">
            <div class="box box-solid">
                <div class="box-header">
                    <!--右侧最小化按钮-->
                    <div class="box-tools pull-right">
                        <a href="{{ url('query') }}" class="btn btn-flat btn-default">清除</a>
                    </div>
                </div>
                <div class="box-body">
                    <!--编号-->
                    <div class="row">
                        <div class="col-md-6 col-md-offset-3">
                            <div class="form-group">
                                <label for="txtCodeValue">
                                    <label style="font-weight: normal;">
                                        <input type="radio" class="minimal" value="identity_code" name="code_type"
                                            {{ request('code_type','identity_code') == 'identity_code' ? 'checked' : '' }}>&nbsp;唯一编号
                                    </label>&nbsp;&nbsp;
                                    <label style="font-weight: normal;">
                                        <input type="radio" class="minimal" value="factory_device_code" name="code_type"
                                            {{ request('code_type') == 'factory_device_code' ? 'checked' : '' }}>&nbsp;厂编号
                                    </label>&nbsp;&nbsp;
                                    <label style="font-weight: normal;">
                                        <input type="radio" class="minimal" value="serial_number" name="code_type"
                                            {{ request('code_type') == 'serial_number' ? 'checked' : '' }}>&nbsp;所编号
                                    </label>
                                    <span> 丨 </span>
                                    <label style="font-weight: normal;">
                                        <input type="radio" class="minimal" value="all" name="is_scraped"
                                            {{ request('is_scraped','all') == 'all' ? 'checked' : '' }}>&nbsp;全部
                                    </label>&nbsp;&nbsp;
                                    <label style="font-weight: normal; display: none;">
                                        <input type="radio" class="minimal" value="out" name="is_scraped"
                                            {{ request('is_scraped') == 'out' ? 'checked' : '' }}>&nbsp;不看超期设备
                                    </label>&nbsp;&nbsp;
                                    <label style="font-weight: normal;">
                                        <input type="radio" class="minimal" value="in" name="is_scraped"
                                            {{ request('is_scraped') == 'in' ? 'checked' : '' }}>&nbsp;超期
                                    </label>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <input type="checkbox" name="ici" id="chkIsCodeIndistinct" value="1"
                                            {{ request('ici',1) == 1 ? 'checked' : '' }}>
                                        模糊查询
                                    </div>
                                    <input type="text" class="form-control input-lg" name="code_value" id="txtCodeValue"
                                           onkeyup="fnCodeValueOnChange(this.value)"
                                           onkeydown="if(event.keyCode===13) fnQuery()"
                                           value="{{ request('code_value') }}"/>
                                    <div class="input-group-btn">
                                        <a href="javascript:" onclick="fnQuery()"
                                           class="btn btn-flat btn-primary btn-lg">搜索</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--种类型-->
                    <div class="row">
                        <div class="col-md-2">
                            <label for="selCategory">种类</label>
                            <select id="selCategory" name="category_unique_code" class="select2 form-control"
                                    onchange="fnSelectCategory(this.value)">
                                <option value="">全部</option>
                                @foreach($categories as $categoryUniqueCode=>$categoryName)
                                    <option value="{{ $categoryUniqueCode }}"
                                        {{ request('category_unique_code') == $categoryUniqueCode ? 'selected' : '' }}>
                                        {{ $categoryName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selEntireModel">类型</label>
                            <select id="selEntireModel" name="entire_model_unique_code" class="select2 form-control"
                                    onchange="fnSelectEntireModel(this.value)">
                                <option value="">全部</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selSubModel">型号或子类</label>
                            <select id="selSubModel" name="sub_model_unique_code" class="select2 form-control">
                                <option value="">全部</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selStatus">状态</label>
                            <select id="selStatus" name="status" class="select2 form-control">
                                <option value="">全部</option>
                                @foreach($statuses as $statusCode=>$statusName)
                                    <option value="{{ $statusCode }}"
                                        {{ request('status') == $statusCode ? 'selected' : '' }}>{{ $statusName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selFactory">供应商</label>
                            <select id="selFactory" name="factory" class="select2 form-control">
                                <option value="">全部</option>
                                @foreach($factories as $factory)
                                    <option value="{{ $factory }}"
                                        {{ request('factory') == $factory ? 'selected' : '' }}>{{ $factory }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <br>
                    <!--位置-->
                    <div class="row">
                        <div class="col-md-2">
                            <label for="selSceneWorkshop">现场车间</label>
                            <select id="selSceneWorkshop" name="scene_workshop_unique_code" class="select2 form-control" onchange="fnSelectSceneWorkshop(this.value)">
                                <option value="">全部</option>
                                @foreach($sceneWorkshops as $sceneWorkshopUniqueCode => $sceneWorkshopName)
                                    <option
                                        value="{{ $sceneWorkshopUniqueCode }}"
                                        {{ request('scene_workshop_unique_code') == $sceneWorkshopUniqueCode ? 'selected' : '' }}
                                    >
                                        {{ $sceneWorkshopName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selLine">线别</label>
                            <select id="selLine" name="line_unique_code" class="select2 form-control" onchange="fnSelectLine(this.value)">
                                <option value="">全部</option>
                                @foreach($lines as $line_unique_code => $line_name)
                                    <option
                                        value="{{ $line_unique_code }}"
                                        {{ request('line_unique_code') == $line_unique_code ? 'selected' : '' }}
                                    >
                                        {{ $line_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="selStation">车站</label>
                            <select id="selStation" name="station_name" class="select2 form-control">
                                <option value="">全部</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>
                                位置码&nbsp;
                                <input type="checkbox" name="maintain_location_code_use_indistinct"
                                       id="chkMaintainLocationCodeIndistinct" class="minimal" value="1"
                                    {{ request('maintain_location_code_use_indistinct') == '1' ? 'checked' : '' }}>
                                <span style="font-weight: normal;">模糊查询</span>
                            </label>
                            <div class="form-group">
                                <input type="text" id="txtMaintainLocationCode" name="maintain_location_code"
                                       class="form-control" value="{{ request('maintain_location_code') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                道岔号&nbsp;
                                <input type="checkbox" name="crossroad_number_use_indistinct"
                                       id="chkCrossroadNumberIndistinct" class="minimal" value="1"
                                    {{ request('crossroad_number_use_indistinct') == '1' ? 'checked' : '' }}>
                                <span style="font-weight: normal;">模糊查询</span>
                            </label>
                            <div class="form-group">
                                <input type="text" id="txtCrossroadNumber" name="crossroad_number" class="form-control"
                                       value="{{ request('crossroad_number') }}">
                            </div>
                        </div>
                    </div>
                    <!--时间-->
                    <div class="row">
                        <div class="col-md-3" style=";">
                            <label>
                                <input type="checkbox" name="use_created_at" id="useCreatedAt" class="minimal" value="1"
                                    {{ request('use_created_at') == '1' ? 'checked' : '' }}>
                                添加时间
                            </label>
                            <div class="form-group">
                                <input id="dateCreatedAt" name="created_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_made_at" id="useMadeAt" class="minimal" value="1"
                                    {{ request('use_made_at') == '1' ? 'checked' : '' }}>
                                出厂时间
                            </label>
                            <div class="form-group">
                                <input id="dateMadeAt" name="made_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_out_at" id="useOutAt" class="minimal" value="1"
                                    {{ request('use_out_at') == '1' ? 'checked' : '' }}>
                                出所时间
                            </label>
                            <div class="form-group">
                                <input id="dateOutAt" name="out_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_installed_at" id="useInstalledAt" class="minimal" value="1"
                                    {{ request('use_installed_at') == '1' ? 'checked' : '' }}>
                                上道时间
                            </label>
                            <div class="form-group">
                                <input id="dateInstalledAt" name="installed_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_scarping_at" id="useScarpingAt" class="minimal" value="1"
                                    {{ request('use_scarping_at') == '1' ? 'checked' : '' }}>
                                到期时间
                            </label>
                            <div class="form-group">
                                <input id="dateScarpingAt" name="scarping_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_next_fixing_day" id="useNextFixingDay" class="minimal"
                                       value="1" {{ request('use_next_fixing_day') == '1' ? 'checked' : '' }}>
                                下次周期修时间
                            </label>
                            <div class="form-group">
                                <input id="dateNextFixingDay" name="next_fixing_day" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_fixed_at" id="useFixedAt" class="minimal" value="1"
                                    {{ request('use_fixed_at') == '1' ? 'checked' : '' }}>
                                检修时间
                            </label>
                            <div class="form-group">
                                <input id="dateFixedAt" name="fixed_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <input type="checkbox" name="use_warehousein_at" id="useWarehouseinAt" class="minimal"
                                       value="1" {{ request('use_warehousein_at') == '1' ? 'checked' : '' }}>
                                入库时间
                            </label>
                            <div class="form-group">
                                <input id="dateWarehouseinAt" name="warehousein_at" type="text" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!--人员-->
                    {{--<div class="row" style="display: none;">--}}
                    {{--<div class="col-md-3">--}}
                    {{--<label for="selBehaviorType">操作类型</label>--}}
                    {{--<select id="selBehaviorType" name="behavior_type" class="select2 form-control">--}}
                    {{--<option value="">全部</option>--}}
                    {{--<option value="IN" {{ request('behavior_type') == 'IN' ? 'selected' : '' }}>入所</option>--}}
                    {{--<option value="OUT" {{ request('behavior_type') == 'OUT' ? 'selected' : '' }}>出所</option>--}}
                    {{--<option value="INSTALL" {{ request('behavior_type') == 'INSTALL' ? 'selected' : '' }}>上道
                    </option>--}}
                    {{--<option value="FIX_BEFORE" {{ request('behavior_type') == 'FIX_BEFORE' ? 'selected' : '' }}>修前检
                    </option>--}}
                    {{--<option value="FIX_AFTER" {{ request('behavior_type') == 'FIX_AFTER' ? 'selected' : '' }}>修后检
                    </option>--}}
                    {{--<option value="CHECKED" {{ request('behavior_type') == 'CHECKED' ? 'selected' : '' }}>验收
                    </option>--}}
                    {{--<option value="WORKSHOP" {{ request('behavior_type') == 'WORKSHOP' ? 'selected' : '' }}>车间抽验
                    </option>--}}
                    {{--<option value="SECTION" {{ request('behavior_type') == 'SECTION' ? 'selected' : '' }}>段抽验
                    </option>--}}
                    {{--<option value="SECTION_CHIEF" {{ request('behavior_type') == 'SECTION_CHIEF' ? 'selected' : '' }}>工长抽验
                    </option>--}}
                    {{--</select>--}}
                    {{--</div>--}}
                    {{--<div class="col-md-3">--}}
                    {{--<label for="selWorkArea">工区</label>--}}
                    {{--<select--}}
                    {{--id="selWorkArea"--}}
                    {{--name="work_area"--}}
                    {{--class="select2 form-control"--}}
                    {{--onchange="fnSelectWorkArea(this.value)"--}}
                    {{-->--}}
                    {{--<option value="0" {{ request('work_area') == '0' ? 'selected' : '' }}>全部</option>--}}
                    {{--<option value="1" {{ request('work_area') == '1' ? 'selected' : '' }}>转辙机</option>--}}
                    {{--<option value="2" {{ request('work_area') == '2' ? 'selected' : '' }}>继电器</option>--}}
                    {{--<option value="3" {{ request('work_area') == '3' ? 'selected' : '' }}>综合</option>--}}
                    {{--</select>--}}
                    {{--</div>--}}
                    {{--<div class="col-md-3">--}}
                    {{--<label for="selAccount">人员</label>--}}
                    {{--<select id="selAccount" name="account_id" class="select2 form-control">--}}
                    {{--<option value="">全部</option>--}}
                    {{--</select>--}}
                    {{--</div>--}}
                    {{--</div>--}}
                </div>
            </div>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">
                        搜索结果&nbsp;共：{{ !empty($entireInstances) ? $entireInstances->total() : 0 }}</h3>
                    <div class="btn-group btn-group-sm pull-right">
                        {{--<a href="javascript:" onclick="fnFixMission()" class="btn btn-default btn-flat">检修任务分配</a>--}}
                        <a href="javascript:" onclick="printLabel('identity_code',1)" class="btn btn-default btn-flat">打印编码标签(35*20)</a>
                        <a href="javascript:" onclick="printLabel('identity_code',2)" class="btn btn-default btn-flat">打印编码标签(20*12)</a>
                        <a href="javascript:" onclick="printLabel('identity_code',3)" class="btn btn-default btn-flat">打印编码标签(40*25)</a>
                        <a href="javascript:" onclick="printLabel('location')" class="btn btn-default btn-flat">打印位置标签</a>
                        <a href="javascript:" onclick="fnDelete()" class="btn btn-danger btn-flat"><i class="fa fa-exclamation">&nbsp;</i>删除</a>
                        <a href="{{ url('entire/instance/trashed') }}" class="btn btn-default btn-flat"><i class="fa fa-refresh">&nbsp;</i>回收站</a>
                        <a href="javascript:" onclick="fnDownload()" class="btn btn-default btn-flat"><i class="fa fa-download">&nbsp;</i>下载</a>
                    </div>

                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed" id="table">
                                @if(!empty($entireInstances))
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" class="checkbox-toggle"></th>
                                        <th>唯一编号<br>(所编号)</th>
                                        <th>供应商<br>(厂编号)</th>
                                        <th>状态</th>
                                        <th>种类型</th>
                                        <th>安装日期</th>
                                        <th>安装位置<br>(故障次数)</th>
                                        @if(request('category_unique_code') == '' || request('category_unique_code') == 'S03')
                                            <th>开向</th>
                                            <th>表示杆特征</th>
                                        @endif
                                        <th>仓库位置</th>
                                        <th>检修日期<br>(下次周期修)</th>
                                        <th>到期日期</th>
                                        @if(request('behavior_type'))
                                            <th>操作人</th>
                                        @endif
                                        <th>所属设备</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($entireInstances as $entireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="labelChecked" value="{{ $entireInstance->identity_code }}"/></td>
                                            <td>
                                                <a href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a>
                                                @if($entireInstance->serial_number)
                                                    <br>({{ $entireInstance->serial_number }})
                                                @endif
                                            </td>
                                            <td>
                                                {{ $entireInstance->factory_name }}
                                                @if($entireInstance->factory_device_code)
                                                    <br>({{ $entireInstance->factory_device_code }})
                                                @endif
                                            </td>
                                            <td>{{ $statuses[$entireInstance->status] }}</td>
                                            <td>
                                                {{ $entireInstance->category_name }}<br>
                                                {{ $entireInstance->model_name }}
                                            </td>
                                            <td>
                                                {{ empty($entireInstance->last_installed_time) ? '' : date('Y-m-d',$entireInstance->last_installed_time) }}
                                            </td>
                                            @if($entireInstance->maintain_station_name)
                                                <td>
                                                    {{ $entireInstance->maintain_station_name }}<br>
                                                    {{ $entireInstance->maintain_location_code }}
                                                    {{ $entireInstance->line_unique_code }}
                                                    {{ $entireInstance->crossroad_number }}
                                                    {{ $entireInstance->traction }}
                                                    ({{ $breakdownCounts[$entireInstance->maintain_station_name.$entireInstance->maintain_location_code.$entireInstance->crossroad_number] ?? 0 }})
                                                </td>
                                            @else
                                                <td>

                                                </td>
                                            @endif
                                            @if(request('category_unique_code') == '' || request('category_unique_code') == 'S03')
                                                <td>{{ $entireInstance->open_direction }}</td>
                                                <td>{{ $entireInstance->said_rod }}</td>
                                            @endif
                                            @if(@$entireInstance->position_name)
                                                <td>
                                                    <a href="javascript:" onclick="fnLocation(`{{ $entireInstance->identity_code }}`)">
                                                        <i class="fa fa-location-arrow"></i>
                                                        {{ @$entireInstance->location_unique_code ? @$entireInstance->storehous_name . @$entireInstance->area_name : ''}}<br>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;{{ @$entireInstance->location_unique_code ? @$entireInstance->platoon_name . @$entireInstance->shelf_name . @$entireInstance->tier_name . @$entireInstance->position_name : ''}}
                                                    </a>
                                                </td>
                                            @else
                                                <td>

                                                </td>
                                            @endif
                                            <td>
                                                {{ @$entireInstance->fw_updated_at ? \Carbon\Carbon::parse($entireInstance->fw_updated_at)->toDateString() : '' }}<br>
                                                @if($entireInstance->ei_fix_cycle_value == 0 && $entireInstance->model_fix_cycle_value == 0)
                                                    (状态修设备)
                                                @elseif(@$entireInstance->status != 'SCRAP')
                                                    (<font color="{{ $entireInstance->next_fixing_time < time() ? 'red' :'' }}">
                                                        {{ empty($entireInstance->next_fixing_time) ? '' : date('Y-m-d',$entireInstance->next_fixing_time) }}
                                                    </font>)
                                                @endif
                                            </td>
                                            {{--@if($entireInstance->ei_fix_cycle_value == 0 && $entireInstance->model_fix_cycle_value == 0)--}}
                                            {{--<td>状态修设备</td>--}}
                                            {{--@else--}}
                                            {{--<td style="{{ $entireInstance->next_fixing_time < time() ? 'color: red;' :'' }}">--}}
                                            {{--    @if(@$entireInstance->status != 'SCRAP')--}}
                                            {{--    {{ empty($entireInstance->next_fixing_time) ? '' : date('Y-m-d',$entireInstance->next_fixing_time) }}--}}
                                            {{--    @endif--}}
                                            {{--</td>--}}
                                            {{--@endif--}}
                                            <td style="{{ @$entireInstance->scarping_at ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $entireInstance->scarping_at)->timestamp < time() ? 'color: red;' : '') : ''}}">
                                                {{ @$entireInstance->scarping_at ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$entireInstance->scarping_at)->toDateString() : '' }}
                                            </td>
                                            @if(request('behavior_type'))
                                                <td>{{ property_exists($entireInstance,'nickname') ? $entireInstance->nickname : '' }}
                                                </td>
                                            @endif
                                            <td>
                                                @if($entireInstance->bind_device_code)
                                                    <a href="{{ url('search/bindDevice', $entireInstance->bind_device_code) }}">
                                                        {{ $entireInstance->bind_crossroad_number }}
                                                        {{ $entireInstance->bind_device_type_name }}
                                                    </a>
                                                @elseif($entireInstance->bind_crossroad_number)
                                                    <a href="{{ url('search/bindCrossroadNumber', $entireInstance->bind_crossroad_number) }}">
                                                        {{ $entireInstance->bind_crossroad_number }}
                                                        {{ $entireInstance->bind_device_type_name }}
                                                    </a>
                                                @else
                                                    {{ $entireInstance->bind_crossroad_number }}
                                                    {{ $entireInstance->bind_device_type_name }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                @endif
                            </table>
                        </div>
                    </div>
                    @if(!empty($entireInstances))
                        <div class="box-footer">
                            {{
                                        $entireInstances
                                            ->appends([
                                                        'ici'=>request('ici'),
                                                        'code_type'=>request('code_type'),
                                                        'is_scraped'=>request('is_scraped'),
                                                        'code_value'=>request('code_value'),
                                                        'category_unique_code'=>request('category_unique_code'),
                                                        'entire_model_unique_code'=>request('entire_model_unique_code'),
                                                        'sub_model_unique_code'=>request('sub_model_unique_code'),
                                                        'status'=>request('status'),
                                                        'factory'=>request('factory'),
                                                        'scene_workshop_unique_code'=>request('scene_workshop_unique_code'),
                                                        'station_name'=>request('station_name'),
                                                        'maintain_location_code'=>request('maintain_location_code'),
                                                        'maintain_location_code_use_indistinct'=>request('maintain_location_code_use_indistinct'),
                                                        'crossroad_number'=>request('crossroad_number'),
                                                        'crossroad_number_use_indistinct'=>request('crossroad_number_use_indistinct'),
                                                        'created_at'=>request('created_at'),
                                                        'use_made_at'=>request('use_made_at'),
                                                        'made_at'=>request('made_at'),
                                                        'use_created_at'=>request('use_created_at'),
                                                        'use_warehousein_at'=>request('use_warehousein_at'),
                                                        'use_out_at'=>request('use_out_at'),
                                                        'out_at'=>request('out_at'),
                                                        'installed_at'=>request('installed_at'),
                                                        'use_installed_at'=>request('use_installed_at'),
                                                        'use_scarping_at'=>request('use_scarping_at'),
                                                        'use_fixed_at'=>request('use_fixed_at'),
                                                        'next_fixing_day'=>request('next_fixing_day'),
                                                        'use_next_fixing_day'=>request('use_next_fixing_day'),
                                                        'behavior_type'=>request('behavior_type'),
                                                        'work_area'=>request('work_area'),
                                                        'account_id'=>request('account_id'),
                                                        'page'=>request('page'),
                                                        ])
                                                        ->links()
                                    }}
                        </div>
                    @endif
                </div>
            </div>
        </form>
        <!--仓库图片弹窗-->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
             id="locationShow">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">位置：<span id="title"></span></h4>
                    </div>
                    <div class="modal-body">
                        <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                        <div class="spot"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="modal fade" id="fixMission">
            <div class="modal-dialog">
                <form action="" id="maintenanceTask">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">检修任务分配</h4>
                        </div>
                        <div class="modal-body form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">月份：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select id="dates" name="dates" class="select2 form-control" style="width: 100%;">
                                        @foreach($dates as $v)
                                            <option value="{{ $v }}">{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="selWorkArea" class="col-sm-3 col-md-3 control-label">工区：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select id="selWorkArea" name="work_area" class="select2 form-control"
                                            style="width: 100%;" onchange="fnSelectWorkArea(this.value)">
                                        <option value="1" {{ request('work_area') == '1' ? 'selected' : '' }}>
                                            转辙机
                                        </option>
                                        <option value="2" {{ request('work_area') == '2' ? 'selected' : '' }}>
                                            继电器
                                        </option>
                                        <option value="3" {{ request('work_area') == '3' ? 'selected' : '' }}>
                                            综合
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">检修人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select id="selAccount" name="account_id" class="select2 form-control"
                                            style="width: 100%;">
                                        {{--<option value="">全部</option>--}}
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">截至日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input id="deadLine" name="fixed_at" type="text" class="form-control"
                                           autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i
                                    class="fa fa-times">&nbsp;</i>关闭
                            </button>
                            <a href="javascript:" onclick="fnStore()" class="btn btn-success btn-sm btn-flat"><i
                                    class="fa fa-check">&nbsp;</i>确定</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let categories = JSON.parse('{!! $categoriesAsJson !!}');
        let categoryUniqueCodes = JSON.parse('{!! $categoryUniqueCodes !!}');
        let selEntireModel = $('#selEntireModel');
        let selSubModel = $('#selSubModel');
        let selStation = $('#selStation');
        let selAccount = $('#selAccount');
        let selCategory = $('#selCategory');
        let $txtCodeValue = $('#txtCodeValue');
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selLine = $('#selLine');

        $(function () {
            let select2 = $('.select2');
            if (select2.length > 0) select2.select2();

            // iCheck for checkbox and radio inputs
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $("#table input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $("#table input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            let locale = {
                format: "YYYY-MM-DD",
                separator: "~",
                daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                applyLabel: "确定",
                cancelLabel: "取消",
                fromLabel: "开始时间",
                toLabel: "结束时间",
                customRangeLabel: "自定义",
                weekLabel: "W",
            };
            let createAt = "{{request('created_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let madeAt = "{{request('made_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let outAt = "{{request('out_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let installedAt = "{{request('installed_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let scarpingAt = "{{request('scarping_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let nextFixingDay = "{{request('next_fixing_day',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let fixedAt = "{{request('fixed_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            let warehouseinAt = "{{request('warehousein_at',implode('~',[date('Y-m-d'),date('Y-m-d')]))}}".split('~');
            $('#dateCreatedAt').daterangepicker({locale: locale, startDate: createAt[0], endDate: createAt[1]});
            $('#dateMadeAt').daterangepicker({locale: locale, startDate: madeAt[0], endDate: madeAt[1]});
            $('#dateOutAt').daterangepicker({locale: locale, startDate: outAt[0], endDate: outAt[1]});
            $('#dateInstalledAt').daterangepicker({locale: locale, startDate: installedAt[0], endDate: installedAt[1]});
            $('#dateScarpingAt').daterangepicker({locale: locale, startDate: scarpingAt[0], endDate: scarpingAt[1]});
            $('#dateWarehouseinAt').daterangepicker({locale: locale, startDate: warehouseinAt[0], endDate: warehouseinAt[1]});
            $('#dateNextFixingDay').daterangepicker({
                locale: locale,
                startDate: nextFixingDay[0],
                endDate: nextFixingDay[1]
            });
            $('#dateFixedAt').daterangepicker({locale: locale, startDate: fixedAt[0], endDate: fixedAt[1]});

            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#deadLine').datepicker(datepickerOption);

            async function fnInitData() {
                await fnSelectCategory($('#selCategory').val());
                await fnSelectSceneWorkshop($('#selSceneWorkshop').val());
                await fnSelectWorkArea($('#selWorkArea').val());
            }

            fnInitData();

            @if(!empty($entireInstances))
            if (document.getElementById('table')) {
                $('#table').DataTable({
                    columnDefs: [
                        {
                            orderable: false,
                            targets: 0,  // 清除第一列排序
                        }
                    ],
                    paging: false,  // 分页器
                    lengthChange: false,
                    searching: false,  // 搜索框
                    ordering: true,  // 列排序
                    order: [[1, 'asc']],
                    info: true,
                    autoWidth: true,  // 自动宽度
                    iDisplayLength: 15,  // 默认分页数
                    aLengthMenu: [15, 30, 50, 100],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }
            @endif

            $txtCodeValue.focus();
        });

        /**
         * 检修任务分配页面
         */
        function fnFixMission() {
            //处理数据
            let selected_for_print = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_print.push(new_code);
            });
            if (selected_for_print.length <= 0) {
                alert('请选择设备');
                return false;
            }
            $('#fixMission').modal('show');
        }

        /**
         * 检修任务分配
         */
        function fnStore() {
            let selecteds = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selecteds.push(new_code);
            });
            let dates = document.getElementById('dates').value;
            let selWorkArea = document.getElementById('selWorkArea').value;
            let selAccount = document.getElementById('selAccount').value;
            let deadLine = document.getElementById('deadLine').value;
            if (!deadLine) {
                alert('请选择截止日期');
                return false;
            }
            $.ajax({
                url: `{{ url('fixMissionOrder') }}`,
                type: 'post',
                data: {
                    'selecteds': selecteds,
                    'dates': dates,
                    'selWorkArea': selWorkArea,
                    'selAccount': selAccount,
                    'deadLine': deadLine
                },
                async: true,
                success: function (res) {
                    // console.log(`{{ url('fixMissionOrder') }} success:`, res);
                    alert(res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('fixMissionOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err);
                }
            });
        }

        /**
         * 查找位置
         * @param identity_code
         */
        function fnLocation(identity_code) {
            $.ajax({
                url: `{{url('storehouse/location/getImg')}}/${identity_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        // console.log(response);
                        $('#title').text(response.data.location_full_name);
                        let location_img = response.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            alert('请联系管理员，绑定位置图片');
                            // location.reload();
                        }
                    } else {
                        alert(response.msg);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.msg);
                    // location.reload();
                }
            });
        }

        /**
         * 删除
         */
        function fnDelete() {
            if (confirm('是否确定删除？')) {
                let selectedForDelete = [];
                $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                    let newCode = $(item).val();
                    if (newCode !== '') selectedForDelete.push(newCode);
                });

                if (selectedForDelete.length <= 0) {
                    alert('没有选择设备');
                    return;
                }

                $.ajax({
                    url: `{{ url('entire/instance/delete') }}`,
                    type: 'post',
                    data: {identityCodes: selectedForDelete},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('entire/instance/delete') }} success:`, res);
                        alert(res['message']);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('entire/instance/delete') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 打印标签
         */
        function printLabel(type, sizeType = 1) {
            //处理数据
            let identityCodes = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function (index, item) {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });

            if (identityCodes.length <= 0) {
                alert('请选择打印标签设备');
                return false;
            }

            // 保存需要打印的数据
            $.ajax({
                url: `{{ url('/warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes},
                async: false,
                success: function (res) {
                    // console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} success:`, res);
                    // 跳转到具体的打印页面
                    let params = $.param({size_type: sizeType});
                    if (type === 'identity_code') window.open(`{{url('qrcode/printQrCode')}}?${params}`, '_blank');
                    if (type === 'location') window.open(`{{url('qrcode/printLabel')}}?${params}`, '_blank');
                },
                error: function (err) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择种类，获取类型列表
         * @param {string} categoryUniqueCode
         */
        function fnSelectCategory(categoryUniqueCode) {
            let html = '<option value="">全部<option>';
            if (categoryUniqueCode !== '') {
                $.ajax({
                    url: `/query/entireModels/${categoryUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: res => {
                        // console.log(`query/entireModels/${categoryUniqueCode} success:`, res);
                        $.each(res, (entireModelUniqueCode, entireModelName) => {
                            html += `<option value=${entireModelUniqueCode} ${"{{request('entire_model_unique_code')}}" === entireModelUniqueCode ? 'selected' : ''}>${entireModelName}</option>`;
                        });
                        selEntireModel.html(html);
                        fnSelectEntireModel(selEntireModel.val());
                    },
                    error: err => {
                        console.log(`query/entireModels/${categoryUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selEntireModel.html(html);
            }
        }

        /**
         * 根据类型，获取型号列表
         * @param {string} entireModelUniqueCode
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            let html = `<option value="">全部<option>`;
            if (entireModelUniqueCode !== '') {
                $.ajax({
                    url: `/query/subModels/${entireModelUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: res => {
                        // console.log(`query/subModels/${entireModelUniqueCode} success:`, res);
                        $.each(res, (subModelUniqueCode, subModelName) => {
                            html += `<option value=${subModelUniqueCode} ${"{{request('sub_model_unique_code')}}" === subModelUniqueCode ? 'selected' : ''}>${subModelName}</option>`;
                        });
                        selSubModel.html(html);
                    },
                    error: err => {
                        console.log(`query/subModels/${entireModelUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selSubModel.html(html);
            }
        }

        /**
         * 根据现场车间获取车站列表
         * @param {string} sceneWorkshopUniqueCode
         */
        function fnSelectSceneWorkshop(sceneWorkshopUniqueCode) {
            if (sceneWorkshopUniqueCode !== '') {
                if ($selLine.val() !== '') $selLine.val('').trigger('change');
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {sceneWorkshopUniqueCode},
                    async: false,
                    success: res => {
                        let html = '<option value="">全部</option>';
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        selStation.html(html);
                    },
                    error: err => {
                        console.log(err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: res => {
                        let html = '<option value="">全部</option>';
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        selStation.html(html);
                    },
                    error: err => {
                        console.log(err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            }
        }

        /**
         * 根据线别获取车站
         */
        function fnSelectLine(lineUniqueCode) {
            if (lineUniqueCode !== '') {
                if ($selSceneWorkshop.val() !== '') $selSceneWorkshop.val('').trigger('change');
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {lineUniqueCode},
                    async: false,
                    success: function (res) {
                        let html = '<option value="">全部</option>';
                        console.log(`{{ url('query/stations') }} success:`, res);
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        selStation.html(html);
                    },
                    error: function (err) {
                        console.log(`{{ url('query/stations') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            } else {
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: function (res) {
                        let html = '<option value="">全部</option>';
                        console.log(`{{ url('query/stations') }} success:`, res);
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        selStation.html(html);
                    },
                    error: function (err) {
                        console.log(`{{ url('query/stations') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 根据工区获取人员列表
         * @param {string} workArea
         */
        function fnSelectWorkArea(workArea) {
            let html = '';
            if (workArea > 0) {
                $.ajax({
                    url: `/query/accounts/${workArea}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: res => {
                        // console.log(`/query/accounts/${workArea} success:`, res);
                        $.each(res, (idx, item) => {
                            html += `<option value="${idx}" ${"{{request('account_id')}}" === idx ? 'selected' : ''}>${item}</option>`;
                        });
                        selAccount.html(html);
                    },
                    error: err => {
                        console.log(`/query/accounts/${workArea} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selAccount.html(html);
            }
        }

        /**
         * 当编号代码输入时，判断同步前三位到相同的种类
         * @param {string} value
         */
        function fnCodeValueOnChange(value) {
            let currentCategoryUniqueCode = selCategory.val();
            let newCategories = '';
            if (value.length >= 3) {
                let val = value.substr(0, 3);
                if (categoryUniqueCodes.some(v => {
                    return v === val;
                })) {
                    $.each(categories, (categoryUniqueCode, categoryName) => {
                        newCategories += `<option value="${categoryUniqueCode}" ${categoryUniqueCode === val ? 'selected' : ''}>${categoryName}</option>`;
                    });
                    selCategory.html(newCategories);
                }
                if (currentCategoryUniqueCode !== selCategory.val()) fnSelectCategory(selCategory.val());
            }
        }

        /**
         * 通过搜索按钮提交
         * @returns {boolean}
         */
        function fnQuery() {
            let urlParams = $('#frmQuery').serialize().split('&');
            let arr = urlParams[urlParams.length - 1];
            if (arr.substr(0, 4) === 'page') {
                let urlPop = urlParams.pop();
            }
            let urlJoin = urlParams.join('&');
            location.href = `{{ url('query') }}` + '?' + urlJoin;
        }

        /**
         * 下载Excel
         */
        function fnDownload() {
            let urlParams = $('#frmQuery').serialize().split('&');
            let arr = urlParams[urlParams.length - 1];
            if (arr.substr(0, 4) === 'page') {
                let urlPop = urlParams.pop();
            }
            let urlJoin = urlParams.join('&') + '&d=1';

            window.open(`{{ url('query') }}` + '?' + urlJoin, '_blank');
        }
    </script>
@endsection
