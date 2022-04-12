@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <!-- 面包屑 -->
    <section onclick="document.getElementById('txtIdentityCode').focus();" class="content-header">
        <h1>
            新站
            <small>子任务详情</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('tempTask', $tempTaskSubOrder->temp_task_id) }}">--}}
{{--                    <i class="fa fa-users">&nbsp;</i>主任务</a>--}}
{{--            </li>--}}
{{--            <li class="active">子任务详情</li>--}}
{{--        </ol>--}}
    </section>

    <!--任务描述-->
    <div class="row">
        <div class="col-md-5">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header ">
                        <h3 class="box-title">任务详情</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm">
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnHelp()"><i class="fa fa-question">&nbsp;</i>帮助</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>主任务&emsp;</dt>
                            <dd></dd>
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
                            <dt>主任务内容：</dt>
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
                            <dt>子任务编号：</dt>
                            <dd>{{ $tempTaskSubOrder->serial_number }}</dd>
                            <dt>车间：</dt>
                            <dd>{{ $tempTaskSubOrder->scene_workshop_name }}</dd>
                            <dt>车站：</dt>
                            <dd>{{ $tempTaskSubOrder->maintain_station_name }}</dd>
                            <dt>状态：</dt>
                            <dd>{{ $tempTaskSubOrder->status }}</dd>
                            @if(in_array(array_flip($tempTaskSubOrderStatuses)[$tempTaskSubOrder->status],['DELIVERY','DONE']))
                                <dt>交付总结：</dt>
                                <dd>{!! $tempTaskSubOrder->delivery_message !!}</dd>
                            @endif
                            <dt>子任务详情：</dt>
                            <dd>
                                <div class="table-responsive">
                                    <table class="table table-condensed table-hover table-bordered">
                                        <thead>
                                        <tr>
                                            <th>型号</th>
                                            <th>数量</th>
                                            <th>完成</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($tempTaskSubOrderModels as $tempTaskSubOrderModel)
                                            <tr class="{{ ($statistics[$tempTaskSubOrderModel->model_name] ?? 0) == $tempTaskSubOrderModel->number ? 'bg-success' : '' }}">
                                                <td>{{ $tempTaskSubOrderModel->model_name }}</td>
                                                <td>{{ $tempTaskSubOrderModel->number }}</td>
                                                <td>{{ $statistics[$tempTaskSubOrderModel->model_name] ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </dd>
                        </dl>
                    </div>
                    <div class="box-footer">
                        <a href="{{ url('tempTask', $tempTaskSubOrder->temp_task_id) }}" class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        @if(in_array(array_flip($tempTaskSubOrderStatuses)[$tempTaskSubOrder->status],['UNDONE','PROCESSING']))
                            <a href="javascript:" class="btn btn-sm btn-success btn-flat pull-right" onclick="modalDelivery()"><i class="fa fa-check">&nbsp;</i>任务交付</a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
        <div class="col-md-7">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4">
                                <h3 class="box-title">设备列表</h3>
                            </div>
                            <div class="col-md-8">
                                <div class="pull-right input-group">
                                    <div class="input-group-addon">唯一编号</div>
                                    <input type="text" name="identity_code" id="txtIdentityCode" class="form-control" autofocus onkeydown="if(event.keyCode===13) fnAdd(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="table-responsive">
                            <div class="btn-group btn-group-sm pull-right">
                                {{--<a href="javascript:" class="btn btn-flat btn-primary" onclick="modalWarehouse('In')"><i class="fa fa-sign-in">&nbsp;</i>入所</a>--}}
                                <a href="http://changsha-input.zhongchengkeshi.com" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-sign-in">&nbsp;</i>导入</a>
                                <a href="{{ url('measurement/fixWorkflow/batchUploadFixerAndChecker') }}" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-wrench">&nbsp;</i>导入检修人和验收人</a>
                                <a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=NEW_STATION&direction=OUT" class="btn btn-flat btn-default"><i class="fa fa-sign-out">&nbsp;</i>添加出所单</a>
                                <a href="javascript:" class="btn btn-flat btn-danger" onclick="fnCut()"><i class="fa fa-times">&nbsp;</i>删除</a>
                            </div>
                            <table class="table table-striped table-condensed">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" class="checkbox-toggle" id="chkCheckAll" onchange="fnCheckAll(this)"></th>
                                    <th>型号</th>
                                    <th>唯一编号</th>
                                    {{--<th>入所</th>--}}
                                    <th>检修人/验收人</th>
                                    <th>出所</th>
                                    <th>状态</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyTempTaskSubOrderEntireInstances">
                                @foreach($tempTaskSubOrderEntireInstances as $tempTaskSubOrderEntireInstance)
                                    <tr>
                                        <td><input type="checkbox" name="id[]" class="temp-task-sub-order-entire-instances" value="{{ $tempTaskSubOrderEntireInstance->old_entire_instance_identity_code }}" id="chkId_{{ $tempTaskSubOrderEntireInstance->id }}" {{ $tempTaskSubOrderEntireInstance->is_finished ? 'disabled' : '' }}></td>
                                        <td>{{ $tempTaskSubOrderEntireInstance->model_name }}</td>
                                        <td>{{ $tempTaskSubOrderEntireInstance->old_entire_instance_identity_code }}</td>
                                        {{--<td>{!! $tempTaskSubOrderEntireInstance->in_warehouse_sn ? '<a href="/warehouse/report/' . $tempTaskSubOrderEntireInstance->in_warehouse_sn . '?show_type=D&page=1&current_work_area=&direction=IN&updated_at=">查看详情</a>' : '✖' !!}</td>--}}
                                        <td>{!! $tempTaskSubOrderEntireInstance->fixer_id ? '<a href="/measurement/fixWorkflow/' . $tempTaskSubOrderEntireInstance->fix_workflow_sn . '/edit" target="_blank">' . $tempTaskSubOrderEntireInstance->fixer_nickname . '/' . $tempTaskSubOrderEntireInstance->checker_nickname . '</a>' : '✖' !!}</td>
                                        <td>{!! $tempTaskSubOrderEntireInstance->out_warehouse_sn ? '<a href="/warehouse/report/' . $tempTaskSubOrderEntireInstance->out_warehouse_sn . '?show_type=D&page=1&current_work_area=&direction=OUT&updated_at=" target="_blank">查看详情</a>' : '✖' !!}</td>
                                        <td>{{ $tempTaskSubOrderEntireInstance->is_finished ? '已完成' : '未完成' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!--模态框-->
    <section class="content">
        <!--任务总结-->
        <div class="modal fade" id="modalDelivery">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">任务交付</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmFinish">
                            <div class="form-group">
                                <div class="col-sm-12 col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">交付日期</div>
                                        <input type="text" class="form-control pull-right" name="delivery_at" id="dpDeliveryAt_Delivery" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 col-md-12">
                                    <textarea class="form-control" id="txaDelivery" name="delivery_message" placeholder="交付总结" rows="15"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnDelivery()"><i class="fa fa-check">&nbsp;</i>确定交付</button>
                    </div>
                </div>
            </div>
        </div>

        <!--检修单-->
        <div class="modal fade" id="modalFixWorkflow">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设置检修人和验收人</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmFixWorkflow">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">检修人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="fixer_id" id="selFixer" class="form-control disabled" style="width: 100%;">
                                        <option value="{{ session('account.id') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">入所时间：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-addon">日期</div>
                                        <input type="text" class="form-control pull-right" name="processed_date" id="dpProcessedDate_WarehouseIn" value="{{ date('Y-m-d') }}">
                                        <div class="input-group-addon">时间</div>
                                        <input type="text" class="form-control timepicker" name="processed_time" id="tpProcessedTime_WarehouseIn">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" class="btn btn-success btn-flat btn-sm" onclick="fnWarehouse('In')"><i class="fa fa-sign-in">&nbsp;</i>入所</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $tbodyTempTaskSubOrderEntireInstances = $('#tbodyTempTaskSubOrderEntireInstances');
        let $txtIdentityCode = $('#txtIdentityCode');
        let $chkCheckAll = $('#chkCheckAll');
        let $modelTitle_Warehouse = $('#modelTitle_Warehouse');

        let $txaDelivery = $('#txaDelivery');
        let $dpDeliveryAt_Delivery = $('#dpDeliveryAt_Delivery');

        /**
         * 全选
         */
        function fnCheckAll(obj) {
            $.each($('.temp-task-sub-order-entire-instances'), function (index, item) {
                if (!item.disabled) $(item).prop('checked', obj.checked);
            });
        }


        /**
         * 给.temp-task-sub-order-entire-instances的input:check 添加监听
         */
        function onTempTaskSubOrderEntireInstances() {
            $('.temp-task-sub-order-entire-instances').on('change', function () {
                let all = 0;
                let checked = 0;

                $.each($('.temp-task-sub-order-entire-instances'), function (index, item) {
                    if (!item.disabled) {
                        all += 1;
                        if (item.checked) checked += 1;
                    }
                });

                $chkCheckAll.prop('checked', checked === all);
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            // 日期选择器
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
            $dpDeliveryAt_Delivery.datepicker(datepickerOption);  // 任务完结日期

            onTempTaskSubOrderEntireInstances();  // 给.temp-task-sub-order-entire-instances的input:check 添加监听
        });

        /**
         * 添加设备
         */
        function fnAdd(identityCode) {
            let data = {
                temp_task_id: '{{ $tempTaskSubOrder->temp_task_id }}',
                temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                entire_instance_identity_code: identityCode,
                type: 'NEW_STATION',
            };

            $.ajax({
                url: `{{ url('tempTaskSubOrderEntireInstance') }}`,
                type: 'post',
                data: data,
                async: true,
                success: function (res) {
                        {{--console.log(`{{ url('tempTaskSubOrderEntireInstance') }} success:`, res);--}}
                    let {temp_task_sub_order_entire_instance: tempTaskSubOrderEntireInstance} = res['data'];
                    let html = `
<tr>
<td><input type="checkbox" name="id[]" class="temp-task-sub-order-entire-instances" value="${tempTaskSubOrderEntireInstance['old_entire_instance_identity_code']}" id="chkId_${tempTaskSubOrderEntireInstance['id']}" ${tempTaskSubOrderEntireInstance['is_finished'] ? 'disabled' : ''}></td>
<td>${tempTaskSubOrderEntireInstance['old_entire_instance_identity_code']}</td>
<td>${tempTaskSubOrderEntireInstance['model_name']}</td>
<td>✖</td>
<td>✖</td>
<td>${tempTaskSubOrderEntireInstance['is_finished'] ? '已完成' : '未完成'}</td>
</tr>
                `;
                    $tbodyTempTaskSubOrderEntireInstances.html(html + $tbodyTempTaskSubOrderEntireInstances.html());
                    $txtIdentityCode.val('');

                    onTempTaskSubOrderEntireInstances();  // 给.temp-task-sub-order-entire-instances的input:check 添加监听
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 删除设备
         */
        function fnCut() {
            let tempTaskSubOrderEntireInstanceIdentityCodes = [];
            $.each($('.temp-task-sub-order-entire-instances:checked'), function (index, item) {
                tempTaskSubOrderEntireInstanceIdentityCodes.push($(item).val());
            });

            if (tempTaskSubOrderEntireInstanceIdentityCodes.length > 0)
                $.ajax({
                    url: `{{ url('tempTaskSubOrderEntireInstance/entireInstances') }}`,
                    type: 'delete',
                    data: {
                        identityCodes: tempTaskSubOrderEntireInstanceIdentityCodes,
                        type: 'NEW_STATION',
                    },
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 打开任务交付窗口
         */
        function modalDelivery() {
            $('#modalDelivery').modal('show');
        }

        /**
         * 任务交付
         */
        function fnDelivery() {
            $.ajax({
                url: `{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }}/delivery`,
                type: 'put',
                data: {
                    delivery_at: $dpDeliveryAt_Delivery.val(),
                    delivery_message: $txaDelivery.val().replaceAll('\r\n', '<br>').replaceAll('\r', '<br>').replaceAll('\n', '<br>'),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 帮助说明
         */
        function fnHelp() {
            $.ajax({
                url: `{{ url('tempTaskSubOrder/help') }}`,
                type: 'get',
                data: {
                    type: '{{ array_flip($tempTaskTypes)[$tempTask->type] }}',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder') }} success:`, res);
                    alert(res['data']['message']);
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
