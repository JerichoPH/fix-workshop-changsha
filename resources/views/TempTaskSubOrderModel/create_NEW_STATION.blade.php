@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            新站
            <small>分配工区子任务</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('tempTask') }}?page={{ request('page', 1) }}">--}}
{{--                    <i class="fa fa-users">&nbsp;</i>分配工区子任务</a>--}}
{{--            </li>--}}
{{--            <li class="active">分配工区子任务</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @include('Layout.alert')
            </div>
            <!--任务描述-->
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header ">
                        <h3 class="box-title">分配子任务 新站</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>任务标题：</dt>
                            <dd>[{{ $tempTask->serial_number }}] {{ $tempTask->title }}</dd>
                            <dt>发起人：</dt>
                            <dd>{{ $tempTask->initiator->nickname }}</dd>
                            <dt>电务段：</dt>
                            <dd>{{ $tempTask->receive_paragraph->name }}</dd>
                            <dt>负责人：</dt>
                            <dd>{{ $tempTask->principal->nickname }}</dd>
                            <dt>状态：</dt>
                            <dd>{{ $tempTask->status }}</dd>
                            <dt>类型：</dt>
                            <dd>{{ $tempTask->type }}</dd>
                            @if($tempTask->expire_at)
                                <dt>截止日期：</dt>
                                @if(\Carbon\Carbon::parse($tempTask->expire_at)->timestamp < time())
                                    <dd><span style="color: red;">{{ $tempTask->expire_at ? \Carbon\Carbon::parse($tempTask->expire_at)->format('Y-m-d') : '' }}</span></dd>
                                @else
                                    <dd>{{ $tempTask->expire_at ? \Carbon\Carbon::parse($tempTask->expire_at)->format('Y-m-d') : '' }}</dd>
                                @endif
                            @endif
                            @if($tempTask->finish_at)
                                <dt>完成时间：</dt>
                                @if($tempTask->expire_at)
                                    @if(\Carbon\Carbon::parse($tempTask->finish_at) < \Carbon\Carbon::parse($tempTask->expire_at))
                                        <dd><span style="color: red;">{{ \Carbon\Carbon::parse($tempTask->finish_at) ? \Carbon\Carbon::parse($tempTask->finish_at)->format('Y-m-d') : '' }}</span></dd>
                                    @endif
                                    <dd>{{ \Carbon\Carbon::parse($tempTask->finish_at) ? \Carbon\Carbon::parse($tempTask->finish_at)->format('Y-m-d') : '' }}</dd>
                                @endif
                            @endif
                            <dt>任务内容：</dt>
                            <dd>{!! $tempTask->description !!}</dd>
                            @if($tempTaskAccessories)
                                <dt>附件：</dt>
                                <dd>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-condensed table-condensed">
                                            @foreach($tempTaskAccessories as $tempTaskAccessory)
                                                <tr>
                                                    <td>{{ $tempTaskAccessory->name }}</td>
                                                    <td><a href="{{ url('tempTaskAccessory/download',$tempTaskAccessory->id) }}" class="text-primary" target="_blank"><i class="fa fa-download"></i></a></td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </dd>
                            @endif
                            <dt>工区：</dt>
                            <dd>{{ $workAreas[$tempTaskSubOrder->work_area_id] }}</dd>
                            <dd><select name="scene_workshop_unique_code" id="selSceneWorkshop" class="select2" style="width: 100%;" onchange="fnFillStation(this.value)"></select></dd>
                            <dt>车站：</dt>
                            <dd><select name="maintain_station_unique_code" id="selStation" class="select2" style="width: 100%;"></select></dd>
                        </dl>
                    </div>
                    <div class="box-footer">
                        <a href="{{ url('tempTask', $tempTask->id) }}" class="btn btn-sm btn-default btn-flat">
                            <i class="fa fa-arrow-left">&nbsp;</i>返回
                        </a>
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
                                    <select name="scene_workshop_unique_code" id="selSceneWorkshop" class="select2 form-control" onchange="fnFillStation(this.value)" style="width: 100%;"></select>
                                    <div class="input-group-addon">车站</div>
                                    <select name="station_unique_code" id="selStation" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="form-horizontal">
                            <div class="input-group">
                                <div class="input-group-addon">种类</div>
                                <select id="selCategory" class="form-control select2" style="width: 100%;" onchange="fnFillModel(this.value)"></select>
                                <div class="input-group-addon">型号</div>
                                <select id="selModel" class="form-control select2" style="width: 100%;"></select>
                                <div class="input-group input-group-btn">
                                    <a class="btn btn-info btn-flat" onclick="fnAdd()"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row" id="divModels">
                            @foreach($tempTaskSubOrderModels as $tempTaskSubOrderModel)
                                <div class="col-md-6" id="div_{{ $tempTaskSubOrderModel->id }}">
                                    <div class="input-group">
                                        <div class="input-group-addon" style="border: none;">{{ $tempTaskSubOrderModel->model_name }}</div>
                                        <input
                                            type="number"
                                            name="number"
                                            step="1"
                                            min="1"
                                            value="{{ $tempTaskSubOrderModel->number }}"
                                            onchange="fnChange('{{ $tempTaskSubOrderModel->id }}',this.value)"
                                            class="form-control"
                                        />
                                        <div class="input-group-btn">
                                            <a
                                                href="javascript:"
                                                onclick="fnCut('{{ $tempTaskSubOrderModel->id }}')"
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
        let sceneWorkshops = JSON.parse('{!! $sceneWorkshopsAsJson !!}');
        let models = JSON.parse('{!! $modelsAsJson !!}');
        let models2 = {};
        let $selCategory = $('#selCategory');
        let $selModel = $('#selModel');
        let thisCategoryUniqueCode = '';
        let modelUniqueCodes = [];
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');
        let $divModels = $('#divModels');
        let $spanCreate = $('#spanCreate');
        let $btnCreate = $('#btnCreate');

        /**
         * 填充种类
         */
        function fnFillCategory() {
            let html = '<option value="">全部</option>';
            $.each(models, function (categoryUniqueCode, category) {
                html += `<option value="${categoryUniqueCode}">${category['name']}</option>`;
            });
            $selCategory.html(html);
            fnFillModel();
        }

        /**
         * 填充子类和型号
         * @params {string} categoryUniqueCode
         */
        function fnFillModel(categoryUniqueCode = '') {
            let html = '';
            models2 = {};
            if (categoryUniqueCode) {
                $.each(models[categoryUniqueCode]['subs'], function (modelUniqueCode, model) {
                    html += `<option value="${modelUniqueCode}">${model['name']}</option>`;
                    models2[modelUniqueCode] = model['name'];
                });
            } else {
                $.each(models, function (categoryUniqueCode, category) {
                    $.each(category['subs'], function (modelUniqueCode, model) {
                        html += `<option value="${modelUniqueCode}">${model['name']}</option>`;
                        models2[modelUniqueCode] = model['name'];
                    });
                });
            }
            $selModel.html(html);
        }

        /**
         * 填充现场车间
         */
        function fnFillSceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshop) {
                html += `<option value="${sceneWorkshopUniqueCode}">${sceneWorkshop['name']}</option>`;
            });
            $selSceneWorkshop.html(html);
            fnFillStation();
        }

        /**
         * 选择现场车间填充车站
         * @param {string} sceneWorkshopUniqueCode
         */
        function fnFillStation(sceneWorkshopUniqueCode = '') {
            let html = '';
            if (sceneWorkshopUniqueCode) {
                $.each(sceneWorkshops[sceneWorkshopUniqueCode]['subs'], function (index, station) {
                    html += `<option value="${station['unique_code']}">${station['name']}</option>`
                });
            } else {
                $.each(sceneWorkshops, function (uniqueCode, sceneWorkshop) {
                    $.each(sceneWorkshop['subs'], function (stationUniqueCode, station) {
                        html += `<option value="${stationUniqueCode}">${station['name']}</option>`
                    });
                });
            }
            $selStation.html(html);
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillSceneWorkshop();  // 填充现场车间
            fnFillCategory();  // 填充类型
        });

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
                    url: `{{ url('tempTaskSubOrderModel') }}`,
                    type: 'post',
                    data: {
                        type: 'NEW_STATION',
                        temp_task_id: '{{ $tempTask->id }}',
                        temp_task_sub_order_serial_number: '{{ $tempTaskSubOrder->serial_number }}',
                        model_unique_code: modelUniqueCode,
                        model_name: models2[modelUniqueCode],
                        number,
                        work_area_id: '{{ $tempTaskSubOrder->work_area_id }}',
                    },
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrderModel') }} success:`, res);
                        let {data} = res;
                        let html = '';
                        html += `<div class="input-group">`;
                        html += `<div class="input-group-addon" style="border: none;">${data['model_name']}</div>`;
                        html += `<input type="number" name="number" step="1" min="1" value="${number}" onchange="fnChange('${data['id']}',this.value)" class="form-control"/>`;
                        html += `<div class="input-group-btn"><a href="javascript:" onclick="fnCut('${data['id']}')" class="btn btn-flat btn-danger"><i class="fa fa-minus"></i></a></div>`;
                        html += `</div>`;
                        html = `<div class="col-md-4" id="div_${data['id']}">${html}</div>`;
                        $divModels.html($divModels.html() + html);
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrderModel') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
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
                url: `{{ url('tempTaskSubOrderModel') }}`,
                type: 'delete',
                data: {
                    type: 'NEW_STATION',
                    id,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderModel') }} success:`, res);
                    $(`#div_${id}`).remove();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
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
                url: `{{ url('tempTaskSubOrderModel') }}`,
                type: 'put',
                data: {number},
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderModel') }} success:`, res);
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
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
                url: `{{ url('tempTaskSubOrder') }}`,
                type: 'post',
                data: {
                    type: 'NEW_STATION',
                    mainTaskId: '{{ $tempTask->id }}',
                    sceneWorkshopCode: $selSceneWorkshop.val(),
                    stationCode: $selStation.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder') }} success:`, res);
                    $spanCreate.text('确定');
                    $btnCreate.removeAttr('disabled');
                    location.href = `{{ url('tempTask',$tempTask->id) }}`;
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                    $spanCreate.text('确定');
                    $btnCreate.removeAttr('disabled');
                }
            });
        }
    </script>
@endsection
