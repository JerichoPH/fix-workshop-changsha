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
                            <div class="col-md-4"><h3 class="box-title">新购出入所任务</h3></div>
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
                        <div class="form-horizontal">
                            <label for="selCategory">种</label>
                            <select id="selCategory" class="form-control select2" style="width: 100%;" onchange="fnSelectCategory()">
                                @foreach($categories as $category_unique_code => $category_name)
                                    <option value="{{ $category_unique_code }}">{{ $category_name }}</option>
                                @endforeach
                            </select>
                            <label for="selEntireModel">类</label>
                            <select id="selEntireModel" class="form-control select2" style="width: 100%;" onchange="fnSelectEntireModel(this.value)">
                                <option value="">未选择</option>
                            </select>
                            <label for="selModel">型</label>
                            <div class="input-group">
                                <select id="selModel" class="form-control select2" style="width: 100%;">
                                    <option value="">未选择</option>
                                </select>
                                <div class="input-group input-group-btn">
                                    <a class="btn btn-info btn-flat" onclick="fnAdd()"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row" id="divModels">
                            @foreach($new_station_models as $new_station_model)
                                <div class="col-md-4" id="div_{{ $new_station_model->id }}">
                                    <div class="input-group">
                                        <div class="input-group-addon" style="border: none;">{{ $new_station_model['model_name'] }}</div>
                                        <input
                                            type="number"
                                            name="number"
                                            step="1"
                                            min="1"
                                            value="{{ $new_station_model->number }}"
                                            onchange="fnChange('{{ $new_station_model->id }}',this.value)"
                                            class="form-control"
                                        />
                                        <div class="input-group-btn">
                                            <a
                                                href="javascript:"
                                                onclick="fnCut('{{ $new_station_model->id }}')"
                                                class="btn btn-flat btn-danger"
                                            >
                                                <i class="fa fa-minus"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
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
        let $divModels = $('#divModels');
        let $spanCreate = $('#spanCreate');
        let $btnCreate = $('#btnCreate');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            // 填充车站
            fnFillStations();
            // 填充类型
            fnSelectCategory();
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
                        type: 'NEW_STATION',
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
                    type: 'NEW_STATION',
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
         * 修改型号数字
         * @param {int} id
         * @param {number} number
         */
        function fnChange(id, number) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/model') }}`,
                type: 'put',
                data: {
                    type: 'NEW_STATION',
                    id,
                    number
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/model') }} success:`, res);
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
                    type: 'NEW_STATION',
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
