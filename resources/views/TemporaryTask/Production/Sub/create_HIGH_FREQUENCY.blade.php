@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            高频修
            <small>分配工区子任务</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('temporaryTask/production/main') }}?page={{ request('page',1) }}">--}}
{{--                    <i class="fa fa-users">&nbsp;</i>分配工区子任务</a>--}}
{{--            </li>--}}
{{--            <li class="active">分配工区子任务</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="row">
            <!--任务描述-->
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header ">
                        <h3 class="box-title">分配子任务</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>任务编号：</dt>
                            <dd>{{ $main_task['serial_num'] }}</dd>
                            <dt>任务标题：</dt>
                            <dd>{{ $main_task['title'] }}</dd>
                            <dt>工区负责人：</dt>
                            <dd>{{ $main_task['paragraph_name'] }}:{{ $main_task['paragraph_principal_name'] }}</dd>
                            <dt>说明：</dt>
                            <dd>{!! $main_task['content'] !!}</dd>
                        </dl>
                        <hr>
                        <div class="table-responsive">
                            <h3><small>已添加设备</small></h3>
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>唯一编号</th>
                                    <th>型号</th>
                                    <th>安装位置</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody id="tbody2"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer">
{{--                        <a href="{{ url('temporaryTask/production/main',$main_task['id']) }}" class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <a href="javascript:" id="btnCreate" onclick="fnCreate()" class="btn btn-success pull-right btn-flat btn-sm">
                            <i class="fa fa-check">&nbsp;</i><span id="spanCreate">确定</span>
                        </a>
                    </div>
                </div>
            </div>

            <!--任务内容-->
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header ">
                        <div class="row">
                            <div class="col-md-4"><h3 class="box-title">搜索设备</h3></div>
                            <!--右侧最小化按钮-->
                            <div class="col-md-8">
                                <div class="input-group pull-right">
                                    <div class="input-group-addon">现场车间</div>
                                    <select name="scene_workshop_unique_code" id="selSceneWorkshop" class="select2 form-control" onchange="fnFillStations()" style="width: 100%;">
                                        @foreach($scene_workshops as $scene_workshop_unique_code => $scene_workshop_name)
                                            <option value="{{ $scene_workshop_unique_code }}">{{ $scene_workshop_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-addon">车站</div>
                                    <select name="station_unique_code" id="selStation" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group">
                            <div class="input-group-addon">唯一/所编号</div>
                            <input type="text" name="code" id="txtCode" class="form-control" onkeyup="if (event.keyCode === 13) fnSearchByCode()">
                            <div class="input-group-addon">组合/道岔位置</div>
                            <input type="text" name="location" id="txtLocation" class="form-control" onkeyup="if(event.keyCode === 13) fnSearchByLocation()">
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>唯一编号</th>
                                    <th>型号</th>
                                    <th>安装位置</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody id="tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let entireModels = JSON.parse('{!! $entire_models_as_json !!}');
        let models = JSON.parse('{!! $models_as_json !!}');
        let $selCategory = $('#selCategory');
        let $selEntireModel = $('#selEntireModel');
        let $selModel = $('#selModel');
        let thisCategoryUniqueCode = '';
        let modelUniqueCodes = [];
        let stations = JSON.parse('{!! $stations_as_json !!}');
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');
        let $divEntireInstances = $('#divEntireInstances');
        let $spanCreate = $('#spanCreate');
        let $btnCreate = $('#btnCreate');
        let $txtCode = $('#txtCode');
        let $txtLocation = $('#txtLocation');
        let addEntireInstances = null;
        let $tbody = $('#tbody');
        let $tbody2 = $('#tbody2');
        let $frmCreate = $('#frmCreate');
        let entireInstances = [];

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            // 填充车站
            fnFillStations();
            // 填充类型
            fnSelectCategory();
        });

        /**
         * 填充左侧列表
         */
        function _addEntireInstances(entireInstances) {
            addEntireInstances = entireInstances;
            let html = '';
            $.each(entireInstances, function (idx, item) {
                html += `<td>${item['identity_code']}</td>`;
                html += `<td>${item['model_name']}</td>`;
                html += `<td>${item['maintain_location_code'] ? item['maintain_location_code'] : ''}`;
                html += `${item['source_crossroad_number'] ? item['source_crossroad_number'] : ''}`;
                html += `${item['traction'] ? item['traction'] : ''}`;
                html += `${item['line_name'] ? item['line_name'] : ''}`;
                html += `${item['open_direction'] ? item['open_direction'] : ''}`;
                html += `${item['said_rod'] ? item['said_rod'] : ''}`;
                html += `${item['crossroad_type'] ? item['crossroad_type'] : ''}`;
                html += `${item['point_switch_group_type'] ? item['point_switch_group_type'] : ''}</td>`;
                html += `<td><a href="javascript:" class="btn btn-default btn-flat btn-sm" onclick="fnAdd(${idx})"><i class="fa fa-plus"></i></a></td>`;
                html = `<tr>${html}</tr>`;
            });
            $tbody.html(html);
        }

        /**
         * 通过唯一/所编号查询设备
         */
        function fnSearchByCode() {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/entireInstance') }}`,
                type: 'get',
                data: {
                    type: 'HIGH_FREQUENCY',
                    searchType: 'CODE',
                    searchCondition: $txtCode.val(),
                    stationCode: $selStation.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} success:`, res);
                    _addEntireInstances(res['entire_instances']);
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 添加设备
         */
        function fnAdd(idx) {
            let html = '';
            html += `<td>${addEntireInstances[idx]['identity_code']}</td>`;
            html += `<td>${addEntireInstances[idx]['model_name']}</td>`;
            html += `<td>${addEntireInstances[idx]['maintain_location_code'] ? addEntireInstances[idx]['maintain_location_code'] : ''}`;
            html += `${addEntireInstances[idx]['source_crossroad_number'] ? addEntireInstances[idx]['source_crossroad_number'] : ''}`;
            html += `${addEntireInstances[idx]['traction'] ? addEntireInstances[idx]['traction'] : ''}`;
            html += `${addEntireInstances[idx]['line_name'] ? addEntireInstances[idx]['line_name'] : ''}`;
            html += `${addEntireInstances[idx]['open_direction'] ? addEntireInstances[idx]['open_direction'] : ''}`;
            html += `${addEntireInstances[idx]['said_rod'] ? addEntireInstances[idx]['said_rod'] : ''}`;
            html += `${addEntireInstances[idx]['crossroad_type'] ? addEntireInstances[idx]['crossroad_type'] : ''}`;
            html += `${addEntireInstances[idx]['point_switch_group_type'] ? addEntireInstances[idx]['point_switch_group_type'] : ''}</td>`;
            html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('${addEntireInstances[idx]['identity_code']}')"><i class="fa fa-times"></i></a></td>`;
            html = `<tr id="tr_${addEntireInstances[idx]['identity_code']}">${html}</tr>`;
            $tbody2.html(html += $tbody2.html());
            $tbody.children().remove();
            $txtCode.val('');
            $txtLocation.val('');
            entireInstances.push(addEntireInstances[idx]['identity_code']);
        }

        /**
         * 删除设备
         */
        function fnDelete(identityCode) {
            let idx = entireInstances.indexOf(identityCode);
            if (idx > -1) entireInstances.splice(idx, 1);
            $(`#tr_${identityCode}`).remove();
        }

        /**
         * 通过组合/道岔位置查询设备
         */
        function fnSearchByLocation() {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/entireInstance') }}`,
                type: 'get',
                data: {
                    type: 'HIGH_FREQUENCY',
                    searchType: 'LOCATION',
                    searchCondition: $txtLocation.val(),
                    stationCode: $selStation.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} success:`, res);
                    _addEntireInstances(res['entire_instances']);
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择种类填充类型列表
         */
        function fnSelectCategory() {
            let html = '';
            $.each(entireModels[$selCategory.val()], function (index, item) {
                html += `<option value="${item['entire_model_unique_code']}">${item['entire_model_name']}</option>`;
            });
            $selEntireModel.html(html);
            fnSelectEntireModel();
            thisCategoryUniqueCode = $selCategory.val();
        }

        /**
         * 选择类型填充子类或部件类型列表
         */
        function fnSelectEntireModel() {
            let html = '';
            $.each(models[$selEntireModel.val()], function (index, item) {
                html += `<option value="${item['model_unique_code']}">${item['model_name']}</option>`;
            });
            $selModel.html(html);
        }

        /**
         * 选择现场车间填充车站
         */
        function fnFillStations() {
            let html = '';
            $.each(stations[$selSceneWorkshop.val()], function (index, item) {
                html += `<option value="${item['station_unique_code']}">${item['station_name']}</option>`;
            });
            $selStation.html(html);
        }

        /**
         * 创建工区子任务
         */
        function fnCreate() {
            $spanCreate.text('保存中请等待……');
            $btnCreate.attr('disabled', 'disabled');
            $.ajax({
                url: `{{ url('temporaryTask/production/sub') }}`,
                type: 'post',
                data: {
                    type: 'HIGH_FREQUENCY',
                    mainTaskId: '{{ $main_task['id'] }}',
                    sceneWorkshopCode: $selSceneWorkshop.val(),
                    stationCode: $selStation.val(),
                    entireInstances,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub') }} success:`, res);
                    $spanCreate.text('确定');
                    $btnCreate.removeAttr('disabled');
                    location.href = `{{ url('temporaryTask/production/main',$main_task['id']) }}`;
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                    $spanCreate.text('确定');
                    $btnCreate.removeAttr('disabled');
                }
            });
        }
    </script>
@endsection
