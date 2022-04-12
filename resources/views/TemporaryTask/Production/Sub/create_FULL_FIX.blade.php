@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            新站
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
        <!--任务描述-->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4"><h3 class="box-title">分配子任务</h3></div>
                            <div class="col-md-8">
                                <div class="input-group pull-right">
                                    <div class="input-group-addon">现场车间</div>
                                    <label for="selSceneWorkshop" style="display: none;"></label>
                                    <select name="scene_workshop_unique_code" id="selSceneWorkshop" class="select2 form-control" onchange="fnFillStations()" style="width: 100%;"></select>
                                    <div class="input-group-addon">车站</div>
                                    <label for="selStation" style="display: none;"></label>
                                    <select
                                        name="station_unique_code"
                                        id="selStation"
                                        class="select2 form-control"
                                        style="width: 100%;"
                                    ></select>
                                    <div class="input-group-btn">
                                        <a href="javascript:" id="btnSelectStation" class="btn btn-info btn-flat"><i class="fa fa-check"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        @if($full_fix_models)
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>型号名称</th>
                                        <th>设备数量</th>
                                        <th>查看</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($full_fix_models as $full_fix_model)
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="model_unique_code"
                                                    id="chkModelUniqueCode_{{ $full_fix_model->id }}" {{ $full_fix_model->picked ? 'checked' : '' }}
                                                    value="{{ $full_fix_model->id }}:{{$full_fix_model->model_unique_code}}"
                                                    class="select-model-unique-code"
                                                />
                                            </td>
                                            <td>{{ $full_fix_model->model_name }}</td>
                                            <td>{{ $full_fix_model->number }}</td>
                                            <td>
                                                <a
                                                    href="javascript:"
                                                    class="btn btn-default btn-flat btn-xs {{ $full_fix_model->picked ? '' : 'disabled' }}"
                                                    {{ $full_fix_model->picked ? '' : 'disabled' }}
                                                    id="btnGetModelEntireInstances_{{ $full_fix_model->id }}"
                                                    onclick="fnGetModelEntireInstances('{{ $full_fix_model->model_unique_code }}')"
                                                ><i class="fa fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
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
                            <div class="col-md-8">
                                <h3 class="box-title">
                                    大修入所和报废<br>
                                    <small class="text-danger">选中设备入所，没有选中设备报废</small>
                                </h3>
                            </div>
                            <!--右侧最小化按钮-->
                            <div class="col-md-4"></div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover" id="tableEntireInstances">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th>编号</th>
                                    <th>型号</th>
                                    <th>到期时间</th>
                                    <th>安装位置</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyEntireInstances"></tbody>
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
        let sceneWorkshops = JSON.parse('{!! $scene_workshops_as_json !!}');
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');
        let $divModels = $('#divModels');
        let $spanCreate = $('#spanCreate');
        let $btnCreate = $('#btnCreate');
        let $btnSelectStation = $('#btnSelectStation');
        let $selectModelUniqueCode = $('.select-model-unique-code');
        let $tbodyEntireInstances = $('#tbodyEntireInstances');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillSceneWorkshop();  // 填充现场车间
            fnFillStations();  // 填充车站
            fnSelectCategory();  // 填充类型
        });

        /**
         * 根据选择型号，填充设备列表
         */
        function _fnFillModelEntireInstance(entireInstances) {
            let html = '';
            $.each(entireInstances, function (index, item) {
                html += `<tr class="${item['scraping_time'] > moment().unix() ? 'bg-success' : 'bg-danger'}">`;
                html += `<td>
<input
    type="checkbox"
    name="identity_code"
    value="${item['id']}"
    class="entire-instance-identity-code" ${item['picked'] ? 'checked' : ''}
    id="chkEntireInstance_${item['id']}"
    onclick="fnSelectEntireInstance('${item['id']}')"
/>
</td>`;
                html += `<td><a href="/search/${item['old_entire_instance_identity_code']}" target="_blank">${item['old_entire_instance_identity_code']}</a></td>`;
                html += `<td>${item['model_name']}</td>`;
                html += `<td>${item['scraping_at'] ? item['scraping_at'].split(' ')[0] : ''}</td>`;
                html += `<td>${item['maintain_location_code']}
${item['crossroad_number']}
</td>`;
                html += '</tr>';
            });
            $tbodyEntireInstances.html(html);
        }

        /**
         * 查看该型号下绑定的设备
         */
        function fnGetModelEntireInstances(modelUniqueCode) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/model') }}`,
                type: 'get',
                data: {
                    modelUniqueCode,
                    type: 'FULL_FIX',
                    mainTaskId: '{{ request('mainTaskId') }}'
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} success:`, res);
                    _fnFillModelEntireInstance(res['entire_instances']);
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择型号
         */
        $selectModelUniqueCode.on('click', function () {
            let id = '';
            let modelUniqueCode = '';
            let val = $(this).val().split(':');
            id = val[0];
            modelUniqueCode = val[1];
            let isChecked = $(this).is(':checked');
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/model') }}`,
                type: isChecked ? 'post' : 'delete',
                data: {
                    id,
                    modelUniqueCode,
                    type: 'FULL_FIX',
                    mainTaskId: '{{ request('mainTaskId') }}',
                    stationUniqueCode: $selStation.val(),
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} success:`, res);
                    isChecked ? _fnFillModelEntireInstance(res['entire_instances']) : _fnFillModelEntireInstance([]);
                    let $btnGetModelEntireInstances = $(`#btnGetModelEntireInstances_${id}`);
                    if (isChecked) {
                        $btnGetModelEntireInstances.removeAttr('disabled');
                        $btnGetModelEntireInstances.removeClass('disabled');
                    } else {
                        $btnGetModelEntireInstances.attr('disabled');
                        $btnGetModelEntireInstances.addClass('disabled');
                    }
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        });

        /**
         * 选择设备
         */
        function fnSelectEntireInstance(id) {
            let $this = $(`#chkEntireInstance_${id}`);
            let isChecked = $this.is(':checked');
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/entireInstance') }}`,
                type: isChecked ? 'post' : 'delete',
                data: {
                    id: $this.val(),
                    type: 'FULL_FIX',
                    mainTaskId: '{{ request('mainTaskId') }}',
                    stationUniqueCode: $selStation.val(),
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} success:`, res);
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 切换车站
         */
        $btnSelectStation.on('click', function () {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/changeStation') }}`,
                type: 'post',
                data: {
                    type: 'FULL_FIX',
                    mainTaskId: '{{ request('mainTaskId') }}',
                    stationUniqueCode: $selStation.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/main/changeStation') }} success:`, res);
                    // 记录当前选择
                    localStorage.setItem('temporary_task_production_create_sub:full_fix__scene_workshop_code', $selSceneWorkshop.val());
                    localStorage.setItem('temporary_task_production_create_sub:full_fix__station_code', $selStation.val());
                    // location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/main/changeStation') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        });

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
         * 填充现场车间
         */
        function fnFillSceneWorkshop() {
            let html = '';
            let sceneWorkshopCode = localStorage.getItem('temporary_task_production_create_sub:full_fix__scene_workshop_code');

            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshopName) {
                html += `<option value="${sceneWorkshopUniqueCode}" ${sceneWorkshopCode === sceneWorkshopUniqueCode ? 'selected' : ''}>${sceneWorkshopName}</option>`;
            });
            $selSceneWorkshop.html(html);
        }

        /**
         * 选择现场车间填充车站
         */
        function fnFillStations() {
            let html = '';
            let stationCode = localStorage.getItem('temporary_task_production_create_sub:full_fix__station_code');
            if ($selSceneWorkshop.val()) {
                $.each(stations[$selSceneWorkshop.val()], function (index, item) {
                    html += `<option value="${item['station_unique_code']}" ${item['station_unique_code'] === stationCode ? 'selected' : ''}>${item['station_name']}</option>`;
                });
            }
            $selStation.html(html);
        }

        /**
         * 添加到列表
         */
        function fnAdd() {
            // 获取当前选择的型号编号，并获取名称，跳过未选择
            let modelUniqueCode = $selModel.val();  // 当前选中的型号

            if (!modelUniqueCode) {
                alert('没有对应型号');
                return;
            }

            // 判断这个型号是否已经存在列表
            if (modelUniqueCodes.indexOf(modelUniqueCode) > -1) {
                alert('不能重复添加');
                return null;
            }

            let number = prompt('请输入数量', 1);  // 获取数据量，数量必须大于0
            if (number > 0) {
                $.ajax({
                    url: `{{ url('temporaryTask/production/sub/model') }}`,
                    type: 'post',
                    data: {
                        type: 'FULL_FIX',
                        mainTaskId: '{{ $main_task['id'] }}',
                        modelUniqueCode,
                        number,
                    },
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('temporaryTask/production/sub/model') }} success:`, res);
                        let html = '';
                        html += `<div class="input-group">`;
                        html += `<div class="input-group-addon" style="border: none;">${res['data']['model_name']}</div>`;
                        html += `<input type="number" name="number" step="1" min="1" value="${number}" onchange="fnChange('${res['data']['id']}',this.value)" class="form-control"/>`;
                        html += `<div class="input-group-btn"><a href="javascript:" onclick="fnCut('${res['data']['id']}')" class="btn btn-flat btn-danger"><i class="fa fa-minus"></i></a></div>`;
                        html += `</div>`;
                        html = `<div class="col-md-4" id="div_${res['data']['id']}">${html}</div>`;
                        $divModels.html($divModels.html() + html);
                    },
                    error: function (err) {
                        console.log(`{{ url('temporaryTask/production/sub/model') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 从列表中去掉
         * @param {int} id
         */
        function fnCut(id) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/model') }}`,
                type: 'delete',
                data: {
                    type: 'FULL_FIX',
                    id,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} success:`, res);
                    $(`#div_${id}`).remove();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
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
                    type: 'FULL_FIX',
                    mainTaskId: '{{ $main_task['id'] }}',
                    sceneWorkshopCode: $selSceneWorkshop.val(),
                    stationCode: $selStation.val(),
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
