@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务管理
            <small>详情</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('tempTask') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>临时生产任务管理</a></li>--}}
{{--            <li class="active">详情</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-12">
            @include('Layout.alert')
        </div>
        <div class="col-md-6">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">{{ $tempTask->title }}</h3>
                        {{--右侧最小化按钮--}}
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
                        </dl>
                    </div>
                    <div class="box-footer">
{{--                        <a href="{{ url('tempTask') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <div class="btn-group btn-group-sm pull-right">
                            @if(array_flip($tempTaskStatuses)[$tempTask->status] == '101_UN_PUBLISH')
                                <a
                                    href="{{ url('tempTask',$tempTask->id) }}/edit?page={{ request('page',1) }}"
                                    class="btn btn-warning btn-flat pull-right"
                                >
                                    <i class="fa fa-pencil">&nbsp;</i> 编辑
                                </a>
                                <a href="javascript:" class="btn btn-default btn-flat" onclick="fnPublish({{ $tempTask->id }})"><i class="fa fa-share-alt">&nbsp;</i>发布</a>
                            @endif
                            @if(array_flip($tempTaskModes)[$tempTask->mode] == 'PARAGRAPH_TO_PARAGRAPH' && !$tempTask->is_finished)
                                @if(!$tempTask->is_finished)
                                    @if(session('account.id') == $tempTask->principal_paragraph_original_id)
                                        <a class="btn btn-success btn-flat pull-right" onclick="modalCAA()"><i class="fa fa-check">&nbsp;</i>电务段验收</a>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">统计报表</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div id="echarts" style="height: 300px;"></div>
                    </div>
                </div>

                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">子任务清单</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            @if('WorkshopEngineer' == array_flip(\App\Model\Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')])
                                <a href="javascript:" class="btn btn-success btn-flat" onclick="modalCreateSubTaskOrder()"><i class="fa fa-plus">&nbsp;</i>新建子任务</a>
                            @endif
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        @if($tempTask->temp_task_sub_orders)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>工区</th>
                                        <th>车站</th>
                                        <th>负责人</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($tempTask->temp_task_sub_orders as $tempTaskSubOrder)
                                        <tr>
                                            <td>{{ \App\Model\Account::$WORK_AREAS[$tempTaskSubOrder->work_area_id] }}</td>
                                            <td>{{ $tempTaskSubOrder->scene_workshop_name }} {{ $tempTaskSubOrder->maintain_station_name }}</td>
                                            <td>{{ $tempTaskSubOrder->principal->nickname }}</td>
                                            <td>{{ $tempTaskSubOrder->status }}</td>
                                            <td>
                                                @if('WorkshopEngineer' == array_flip(\App\Model\Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')])
                                                    <a href="{{ url('tempTaskSubOrderModel', $tempTaskSubOrder->id) }}/edit">分配任务</a>&emsp;
                                                    <a href="javascript:" class="text-danger" onclick="fnDeleteTempTaskSubOrder({{ $tempTaskSubOrder->id }})"><i class="fa fa-times"></i></a>
                                                @elseif('WorkshopWorkArea' == array_flip(\App\Model\Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')])
                                                    <a href="{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }}/edit">执行任务</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content">
        <div id="divModalSubTaskReject"></div>
        <!--创建临时生产子任务-->
        <div id="divModalCreateSubTaskOrder">
            <form action="{{ url('tempTaskSubOrder') }}" method="post">
                <input type="hidden" name="temp_task_id" value="{{ $tempTask->id }}">
                <div class="modal fade" id="modalCreateSubTaskOrder">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="modalMessageTitle">新建子任务</h4>
                            </div>
                            <div class="modal-body form-horizontal" id="modalCreateSubTaskOrder">
                                <div class="form-group">
                                    <label class="col-md-3 control-label">工区：</label>
                                    <div class="col-md-8">
                                        <select
                                            name="work_area_id"
                                            id="selCreateSubTaskOrder_workArea"
                                            class="form-control select2"
                                            style="width: 100%;"
                                            onchange="fnFillCreateSubTaskOrder_principal(this.value)"
                                        ></select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">负责人：</label>
                                    <div class="col-md-8">
                                        <select
                                            name="principal_paragraph_original_id"
                                            id="selCreateSubTaskOrder_principal"
                                            class="form-control select2"
                                            style="width: 100%;"
                                        ></select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">现场车间：</label>
                                    <div class="col-md-8">
                                        <select
                                            name="scene_workshop_unique_code"
                                            id="selCreateSubTaskOrder_sceneWorkshop"
                                            class="select2 form-control"
                                            onchange="fnFillCreateSubTaskOrder_station(this.value)"
                                            style="width: 100%;"></select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">车站：</label>
                                    <div class="col-md-8">
                                        <select
                                            name="maintain_station_unique_code"
                                            id="selCreateSubTaskOrder_station"
                                            class="select2 form-control"
                                            style="width: 100%;"></select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-flat btn-sm pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                                <button type="submit" class="btn btn-success btn-sm btn-flat pull-right"><i class="fa fa-plus">&nbsp;</i>新建</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!--电务段验收-->
        <div id="divModalCAA">
            <div class="modal fade" id="modalCAA">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="modalCAATitle">电务段验收</h4>
                        </div>
                        <div class="modal-body form-horizontal" id="modalCAAContent">
                            <div class="form-group">
                                <div class="col-sm-12 col-md-12">
                                    <textarea class="form-control" id="txaParagraphCAAMessage" name="paragraph_caa_message" placeholder="验收总结" rows="15"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat btn-sm pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                            <a href="javascript:" class="btn btn-success btn-flat btn-sm" onclick="fnCAA()"><i class="fa fa-check">&nbsp;</i>确定验收</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="divModalMessage">
            <div class="modal fade" id="modalMessage">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="modalMessageTitle"></h4>
                        </div>
                        <div class="modal-body form-horizontal" id="modalMessageContent"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat btn-sm" data-dismiss="modal">
                                <i class="fa fa-times">&nbsp;</i>关闭
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let workAreas = JSON.parse('{!! $workAreasAsJson !!}');
        let workAreaPrincipals = JSON.parse('{!! $workAreaPrincipalsAsJson !!}');
        let sceneWorkshops = JSON.parse('{!! $sceneWorkshopsAsJson !!}');
        let $modalCreateSubTaskOrder = $('#modalCreateSubTaskOrder');
        let $selCreateSubTaskOrder_workArea = $('#selCreateSubTaskOrder_workArea');
        let $selCreateSubTaskOrder_principal = $('#selCreateSubTaskOrder_principal');
        let $selCreateSubTaskOrder_sceneWorkshop = $('#selCreateSubTaskOrder_sceneWorkshop');
        let $selCreateSubTaskOrder_station = $('#selCreateSubTaskOrder_station');
        let $txaParagraphCAAMessage = $('#txaParagraphCAAMessage');

        /**
         * 统计图表
         */
        function fnMakeEcharts() {
            let e = echarts.init(document.getElementById('echarts'));
            let posList = [
                'left', 'right', 'top', 'bottom',
                'inside',
                'insideTop', 'insideLeft', 'insideRight', 'insideBottom',
                'insideTopLeft', 'insideTopRight', 'insideBottomLeft', 'insideBottomRight'
            ];

            e.configParameters = {
                rotate: {
                    min: -90,
                    max: 90
                },
                align: {
                    options: {
                        left: 'left',
                        center: 'center',
                        right: 'right'
                    }
                },
                verticalAlign: {
                    options: {
                        top: 'top',
                        middle: 'middle',
                        bottom: 'bottom'
                    }
                },
                position: {
                    options: echarts.util.reduce(posList, function (map, pos) {
                        map[pos] = pos;
                        return map;
                    }, {})
                },
                distance: {
                    min: 0,
                    max: 100
                }
            };

            e.config = {
                rotate: 90,
                align: 'left',
                verticalAlign: 'middle',
                position: 'insideBottom',
                distance: 15,
                onChange: function () {
                    var labelOption = {
                        normal: {
                            rotate: e.config.rotate,
                            align: e.config.align,
                            verticalAlign: e.config.verticalAlign,
                            position: e.config.position,
                            distance: e.config.distance
                        }
                    };
                    myChart.setOption({
                        series: [{
                            label: labelOption
                        }, {
                            label: labelOption
                        }, {
                            label: labelOption
                        }, {
                            label: labelOption
                        }]
                    });
                }
            };


            let labelOption = {
                show: true,
                position: e.config.position,
                distance: e.config.distance,
                align: e.config.align,
                verticalAlign: e.config.verticalAlign,
                rotate: e.config.rotate,
                formatter: '{c}  {name|{a}}',
                fontSize: 16,
                rich: {
                    name: {
                        textBorderColor: '#fff'
                    }
                }
            };

            let option = {
                color: ['#006699', '#4cabce', '#e5323e'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                legend: {
                    data: ['任务', '完成', '逾期']
                },
                toolbox: {
                    show: true,
                    orient: 'vertical',
                    left: 'right',
                    top: 'center'
                },
                xAxis: [
                    {
                        type: 'category',
                        axisTick: {show: false},
                        data: ['转辙机', '继电器', '综合']
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                        name: '任务',
                        type: 'bar',
                        barGap: 0,
                        label: labelOption,
                        data: [320, 220, 120]
                    },
                    {
                        name: '完成',
                        type: 'bar',
                        label: labelOption,
                        data: [220, 200, 100]
                    },
                    {
                        name: '逾期',
                        type: 'bar',
                        label: labelOption,
                        data: [100, 20, 10]
                    }
                ]
            };

            e.setOption(option);
            e.on('click', function (params) {
            });
        }

        /**
         * 填充工区下拉菜单（新建临时生产子任务）
         */
        function fnFillCreateSubTaskOrder_workArea() {
            let html = '';
            $.each(workAreas, function (workAreaId, workAreaName) {
                html += `<option value="${workAreaId}">${workAreaName}</option>`;
            });
            $selCreateSubTaskOrder_workArea.html(html);
            $selCreateSubTaskOrder_workArea.select2();
        }

        /**
         * 填充工区工长下拉菜单（临时生产子任务）
         */
        function fnFillCreateSubTaskOrder_principal(workAreaId = 0) {
            let html = '';
            if (workAreaId) {
                $.each(workAreaPrincipals[`${workAreas[workAreaId]}`], function (principalKey, principal) {
                    html += `<option value="${principal['id']}">${principal['nickname']}</option>`;
                });
            }
            $selCreateSubTaskOrder_principal.html(html);
            $selCreateSubTaskOrder_principal.select2();
        }

        /**
         * 填充现场车间
         */
        function fnFillCreateSubTaskOrder_sceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshop) {
                html += `<option value="${sceneWorkshopUniqueCode}">${sceneWorkshop['name']}</option>`;
            });
            $selCreateSubTaskOrder_sceneWorkshop.html(html);
            $selCreateSubTaskOrder_sceneWorkshop.select2();
            fnFillCreateSubTaskOrder_station();
        }

        /**
         * 选择现场车间填充车站
         * @param {string} sceneWorkshopUniqueCode
         */
        function fnFillCreateSubTaskOrder_station(sceneWorkshopUniqueCode = '') {
            let html = '';
            if (sceneWorkshopUniqueCode) {

                $.each(sceneWorkshops[sceneWorkshopUniqueCode]['subs'], function (stationUniqueCode, station) {
                    html += `<option value="${stationUniqueCode}">${station['name']}</option>`
                });
            } else {
                $.each(sceneWorkshops, function (uniqueCode, sceneWorkshop) {
                    $.each(sceneWorkshop['subs'], function (stationUniqueCode, station) {
                        html += `<option value="${stationUniqueCode}">${station['name']}</option>`
                    });
                });
            }
            $selCreateSubTaskOrder_station.html(html);
            $selCreateSubTaskOrder_station.select2();
        }

        $(function () {
            fnMakeEcharts();  // 生成图表
            fnFillCreateSubTaskOrder_workArea();  // 填充工区下拉菜单（新建临时生产子任务）
            fnFillCreateSubTaskOrder_principal();  // 填充工区工长下拉菜单（临时生产子任务）
            fnFillCreateSubTaskOrder_sceneWorkshop();  // 填充现场车间
        });

        /**
         * 打开电务段验收窗口
         */
        function modalCAA() {
            $('#modalCAA').modal('show');
        }

        /**
         * 电务段验收
         */
        function fnCAA() {
            let paragraphCAAMessage = $txaParagraphCAAMessage.val().replaceAll('\r\n', '<br>').replaceAll('\r', '<br>').replaceAll('\n', '<br>');

            $.ajax({
                url: `{{ url('tempTask', $tempTask->id) }}/caa`,
                type: 'put',
                data: {paragraphCAAMessage},
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTask', $tempTask->id) }}/caa success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTask', $tempTask->id) }}/caa fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 发布
         */
        function fnPublish() {
            if (confirm('发布后不可撤回，是否确认发布'))
                $.ajax({
                    url: `{{ url('tempTask',$tempTask->id) }}/publish`,
                    type: 'PUT',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTask',$tempTask->id) }}/publish success:`, res);
                        alert(res['msg']);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTask',$tempTask->id) }}/publish fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 电务部确认任务完成
         */
        function fnFinish() {
            $.ajax({
                url: `{{ url('tempTask/finish',$tempTask->id) }}`,
                type: "put",
                data: {},
                async: false,
                success: function (res) {
                    console.log('success:', res);
                    // alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                },
            });
        }

        /**
         * 新建临时生产子任务单
         */
        function fnStoreTempTaskSubOrder() {
            $.ajax({
                url: `{{ url('tempTaskSubOrder') }}`,
                type: 'POST',
                data: {
                    "temp_task_id": '{{ $tempTask->id }}',
                    "principal_id": $selCreateSubTaskOrder_principal.val(),
                    "work_area_id": $selCreateSubTaskOrder_workArea.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder') }} success:`, res);
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 临时生产子任务 模态框
         */
        function modalCreateSubTaskOrder() {
            $modalCreateSubTaskOrder.modal('show');
        }

        /**
         * 删除工区子任务
         * @param id
         */
        function fnDeleteTempTaskSubOrder(id) {
            $.ajax({
                url: `{{ url('tempTaskSubOrder') }}/${id}`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder') }}/${id} success:`, res);
                    alert(res['msg']);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder') }}/${id} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
