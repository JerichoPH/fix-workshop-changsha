@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修管理
            <small>故障修出所单</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/breakdownOrder') }}?page={{ request('page',1) }}&direction={{ request('direction','OUT') }}"><i class="fa fa-users">&nbsp;</i>故障修管理</a></li>--}}
{{--            <li class="active">绑定设备</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-8">
                    </div>
                    <div class="col-md-4">
                        <!--右侧最小化按钮-->
                        <div class="input-group pull-right">
{{--                            <div class="input-group-btn"><a href="{{ url('repairBase/breakdownOrder') }}?direction=OUT&page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a></div>--}}
                            <div class="input-group-btn"><a href="#" onclick="javascript :history.back(-1);" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a></div>
                            <div class="input-group-addon">扫码</div>
                            <input type="text" name="identity_code" id="txtIdentityCode" class="form-control" onkeydown="if(event.keyCode===13){fnOutScan(this.value);}" {{ array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] === 'DONE' ? 'disabled' : ''}}>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="box-body">
                <div class="table-responsive">
                    <div class="pull-left">
                        任务总数：<span id="spanPlanSum">{{ $plan_sum }}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                        出所总数：<span id="spanWarehouseSum">{{ $warehouse_sum }}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                        状态：<span id="spanStatus">{{ $breakdown_order->status }}</span>
                    </div>
                    <div class="btn-group pull-right">
                        @if(array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] !== 'DONE')
{{--                            <a href="{{ url('repairBase/breakdownOrder/printLabel',$breakdown_order->serial_number) }}?direction=OUT" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="javascript:" onclick="$('#modalWarehouse').modal('show');" class="btn btn-primary btn-flat"><i class="fa fa-sign-out">&nbsp;</i>出所</a>
                        @endif
                        @if(array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] === 'SATISFY')
                            <a href="javascript:" onclick="fnDone('{{ $breakdown_order->serial_number }}')" class="btn btn-success btn-flat"><i class="fa fa-check">&nbsp;</i>确认完成</a>
                        @endif
                    </div>
                    <table class="table table-condensed">
                        <thead>
                        <tr>
                            <th>唯一/所编号(故障)</th>
                            <th>型号</th>
                            <th>组合位置/道岔号</th>
                            <th>唯一/所编号(成品)</th>
                            <th>已出所</th>
                            @if(array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] !== 'DONE')
                                <th>操作</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($breakdown_order->OutEntireInstances as $entire_instance)
                            <tr id="tr_{{ @$entire_instance->OldEntireInstance->identity_code }}" class="{{ @$entire_instance->out_scan ? 'bg-success' : '' }}">
                                <td>{{ @$entire_instance->OldEntireInstance->identity_code }}/{{ @$entire_instance->OldEntireInstance->serial_number }}</td>
                                <td>{{ @$entire_instance->OldEntireInstance->model_name }}</td>
                                <td>
                                    {{ @$entire_instance->maintain_station_name }}
                                    {{ @$entire_instance->maintain_location_code }}
                                    {{ @$entire_instance->crossroad_number }}
                                </td>
                                <td>{{ @$entire_instance->NewEntireInstance->identity_code }}/{{ @$entire_instance->NewEntireInstance->serial_number }}</td>
                                <td>{!! @$entire_instance->out_warehouse_sn ? '<a href="/warehouse/report/'.@$entire_instance->out_warehouse_sn.'?show_type=D" target="_blank">查看详情</a>' : '否' !!}</td>
                                @if(array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] !== 'DONE')
                                    <td>
                                        @if($entire_instance->out_scan)
                                            <a href="javascript:" onclick="fnDeleteScan('{{ @$entire_instance->OldEntireInstance->identity_code }}')" class="btn btn-danger btn-flat btn-sm {{ @$entire_instance->out_warehouse_sn ? 'disabled' : '' }}"><i class="fa fa-times"></i></a>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!--出所模态框-->
    <section class="content">
        <div class="modal fade" id="modalWarehouse">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备出所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmWarehouse">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系电话：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">经办人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="processor_id" id="selProcessorId" class="form-control disabled" style="width: 100%;" disabled>
                                        <option value="{{ session('account.id') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">出所时间：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-addon">日期</div>
                                        <input type="text" class="form-control pull-right" id="dpProcessedDate" value="{{ date('Y-m-d') }}">
                                        <div class="input-group-addon">时间</div>
                                        <input type="text" class="form-control timepicker" id="tpProcessedTime">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" onclick="fnWarehouse()" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确认出所</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $dpProcessedDate = $('#dpProcessedDate');
        let $tpProcessedTime = $('#tpProcessedTime');
        let $breakdownOrderTempCheckbox = $('.breakdown-order-temp-checkbox');
        let $breakdownTypeCheckbox = $('.breakdown-type-checkbox');

        $(function () {
            // 入所日期
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
            $dpProcessedDate.datepicker(datepickerOption);

            // 入所时间
            $tpProcessedTime.timepicker({
                showInputs: true,
                showMeridian: false,
            });
        });

        /**
         * 扫码
         */
        function fnOutScan(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/scanEntireInstances') }}`,
                type: 'post',
                data: {
                    identityCode,
                    breakdownOrderSn: '{{ $breakdown_order->serial_number }}',
                    direction: 'OUT',
                },
                async: true,
                success: function (res) {
                    console.log(` success:`, res);
                    let html = '';
                    $.each(res['data'], function (index, item) {
                        html += `<tr id="${item['old_entire_instance_identity_code']}" ${item['out_scan'] ? 'class="bg-success"' : ''}>`;
                        html += `<td>${item['old_entire_instance_identity_code']}/${item['old_entire_instance'] ? item['old_entire_instance']['serial_number'] : ''}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['maintain_station_name'] ? item['maintain_station_name'] : ''} ${item['maintain_location_code'] ? item['maintain_location_code'] : ''} ${item['crossroad_number'] ? item['crossroad_number'] : ''}</td>`;
                        html += `<td>${item.hasOwnProperty('new_entire_instance_identity_code') ? item['new_entire_instance_identity_code'] : ''}/${item['new_entire_instance'] ? item['new_entire_instance']['serial_number'] : ''}</td>`;
                        html += `<td>${item['out_warehouse_sn'] ? '<a href="/warehouse/report/' + item['out_warehouse_sn'] + '?show_type=D" target="_blank">查看详情</a>' : '否'}</td>`;
                        if (item['out_scan']) {
                            html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm ${item['out_warehouse_sn'] ? 'disabled' : ''}" onclick="fnDeleteScan('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times"></i></a></td>`;
                        } else {
                            html += `<td></td>`;
                        }
                        html += '</tr>';
                    });
                    $('#tbody').html(html);
                    $('#txtIdentityCode').val('');
                },
                error: function (err) {
                    console.log(` fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 移除扫码标记
         * @param identityCode
         */
        function fnDeleteScan(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/scanEntireInstances') }}`,
                type: 'delete',
                data: {
                    identityCode,
                    breakdownOrderSn: '{{ $breakdown_order->serial_number }}',
                    direction: 'OUT',
                },
                async: true,
                success: function (res) {
                    console.log(` success:`, res);

                    let {data} = res;
                    let html = '';

                    $.each(data, function (index, item) {
                        html += `<tr id="${item['old_entire_instance']['identity_code']}" ${item['out_scan'] ? 'class="bg-success"' : ''}>`;
                        html += `<td>${item['old_entire_instance']['identity_code']}/${item['old_entire_instance']['serial_number']}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['maintain_station_name'] ? item['maintain_station_name'] : ''} ${item['maintain_location_code'] ? item['maintain_location_code'] : ''} ${item['crossroad_number'] ? item['crossroad_number'] : ''}</td>`;
                        html += `<td>${item.hasOwnProperty('new_entire_instance_identity_code') ? item['new_entire_instance_identity_code'] : ''}/${item['new_entire_instance'] ? item['new_entire_instance']['serial_number'] : ''}</td>`;
                        html += `<td>${item['out_warehouse_sn'] ? '<a href="/warehouse/report/' + item['out_warehouse_sn'] + '?show_type=D" target="_blank">查看详情</a>' : '否'}</td>`;
                        if (item['out_scan']) {
                            html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDeleteScan('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times"></i></a></td>`;
                        } else {
                            html += `<td></td>`;
                        }
                        html += '</tr>';
                    });
                    $('#tbody').html(html);
                    $('#spanStatus').text(res['status']);
                    $('#spanPlanSum').text(res['plan_sum']);
                },
                error: function (err) {
                    console.log(` fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 出所
         */
        function fnWarehouse() {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/warehouse',$breakdown_order->serial_number) }}?direction=OUT`,
                type: 'post',
                data: {
                    processedDate: $dpProcessedDate.val(),
                    processedTime: $tpProcessedTime.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/warehouse',$breakdown_order->serial_number) }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/warehouse',$breakdown_order->serial_number) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 确认出所
         */
        function fnDone() {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/done',$breakdown_order->serial_number) }}?direction=OUT`,
                type: 'put',
                data: $('#frmWarehouse').serialize(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder',$breakdown_order->serial_number) }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder',$breakdown_order->serial_number) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection
