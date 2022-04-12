@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            高频/状态修
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
        <form action="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}" method="POST">
            <input type="hidden" name="_method" value="put">
            <div class="row">
                <div class="col-md-12">
                    @include('Layout.alert')
                </div>
                <!--任务描述-->
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">分配子任务 高频/状态修</h3>
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
                                    <dd>{{ date('Y-m-d', strtotime($tempTask->expire_at)) }}</dd>
                                @endif
                                @if($tempTask->finish_at)
                                    <dt>完成时间：</dt>
                                    @if($tempTask->expire_at)
                                        <dd><span {!! \Carbon\Carbon::parse($tempTask->finish_at)->startOfDay()->timestamp > \Carbon\Carbon::parse($tempTask->expire_at)->startOfDay()->timestamp ? 'style="color: red;"' : '' !!}>{{ \Carbon\Carbon::parse($tempTask->finish_at) ? \Carbon\Carbon::parse($tempTask->finish_at)->format('Y-m-d') : '' }}</span></dd>
                                    @else
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
                                <dt>车间：</dt>
                                <dd><select name="scene_workshop_unique_code" id="selSceneWorkshop" class="select2" style="width: 100%;" onchange="fnFillStation(this.value)"></select></dd>
                                <dt>车站：</dt>
                                <dd><select name="maintain_station_unique_code" id="selStation" class="select2" style="width: 100%;"></select></dd>
                            </dl>
                        </div>
                        <div class="box-footer">
                            <a href="{{ url('tempTask', $tempTask->id) }}" class="btn btn-sm btn-default btn-flat">
                                <i class="fa fa-arrow-left">&nbsp;</i>返回
                            </a>
                            <button type="submit" class="btn btn-warning btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>保存</button>
                        </div>
                    </div>
                </div>

                <!--任务内容-->
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header ">
                            <h3 class="box-title">临时生产任务：高频/状态修</h3>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="input-group">
                                <div class="input-group-addon">种类</div>
                                <select id="selCategory" class="form-control select2" style="width: 100%;" onchange="fnFillModel(this.value)"></select>
                                <div class="input-group-addon">型号</div>
                                <select id="selModel" class="form-control select2" style="width: 100%;"></select>
                                <div class="input-group-btn">
                                    <a class="btn btn-info btn-flat" onclick="fnAdd()"><i class="fa fa-plus"></i></a>
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
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let sceneWorkshops = JSON.parse('{!! $sceneWorkshopsAsJson !!}');
        let models = JSON.parse('{!! $modelsAsJson !!}');
        let $selCategory = $('#selCategory');
        let $selModel = $('#selModel');
        let thisCategoryUniqueCode = '';
        let modelUniqueCodes = [];
        let models2 = {};
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
            $selCategory.select2();
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
            $selModel.select2();
        }

        /**
         * 填充现场车间
         */
        function fnFillSceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshop) {
                html += `<option value="${sceneWorkshopUniqueCode}" ${'{{ old('scene_workshop_unique_code', $tempTaskSubOrder->scene_workshop_unique_code) }}' === sceneWorkshopUniqueCode ? 'selected' : ''}>${sceneWorkshop['name']}</option>`;
            });
            $selSceneWorkshop.html(html);
            $selSceneWorkshop.select2();
            fnFillStation();
        }

        /**
         * 选择现场车间填充车站
         * @param {string} sceneWorkshopUniqueCode
         */
        function fnFillStation(sceneWorkshopUniqueCode = '') {
            let html = '';
            if (sceneWorkshopUniqueCode) {
                $.each(sceneWorkshops[sceneWorkshopUniqueCode]['subs'], function (stationUniqueCode, station) {
                    html += `<option value="${station['unique_code']}" ${'{{ old('maintain_station_unique_code', $tempTaskSubOrder->maintain_station_unique_code) }}' === stationUniqueCode ? 'selected' : ''}>${station['name']}</option>`
                });
            } else {
                $.each(sceneWorkshops, function (uniqueCode, sceneWorkshop) {
                    $.each(sceneWorkshop['subs'], function (stationUniqueCode, station) {
                        html += `<option value="${stationUniqueCode}" ${'{{ old('maintain_station_unique_code', $tempTaskSubOrder->maintain_station_unique_code) }}' === stationUniqueCode ? 'selected' : ''}>${station['name']}</option>`
                    });
                });
            }
            $selStation.html(html);
            $selStation.select2();
        }

        $(function () {
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
                        type: 'HIGH_FREQUENCY',
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
                        let {temp_task_sub_order_model: tempTaskSubOrderModel} = res['data'];
                        let html = '';
                        html += `<div class="input-group">`;
                        html += `<div class="input-group-addon" style="border: none;">${tempTaskSubOrderModel['model_name']}</div>`;
                        html += `<input type="number" name="number" step="1" min="1" value="${number}" onchange="fnChange('${tempTaskSubOrderModel['id']}',this.value)" class="form-control"/>`;
                        html += `<div class="input-group-btn"><a href="javascript:" onclick="fnCut('${tempTaskSubOrderModel['id']}')" class="btn btn-flat btn-danger"><i class="fa fa-minus"></i></a></div>`;
                        html += `</div>`;
                        html = `<div class="col-md-6" id="div_${tempTaskSubOrderModel['id']}">${html}</div>`;
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
                url: `{{ url('tempTaskSubOrderModel') }}/${id}`,
                type: 'delete',
                data: {
                    type: 'HIGH_FREQUENCY',
                    id,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderModel') }}/${id} success:`, res);
                    $(`#div_${id}`).remove();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderModel') }}/${id} fail:`, err);
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
                url: `{{ url('tempTaskSubOrderModel') }}/${id}`,
                type: 'put',
                data: {number},
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderModel') }}/${id} success:`, res);
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderModel') }}/${id} fail:`, err);
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
                    type: 'HIGH_FREQUENCY',
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
