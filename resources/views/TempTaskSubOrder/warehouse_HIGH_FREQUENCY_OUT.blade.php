@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务管理
            <small>高频/状态修任务出所单</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/edit"><i class="fa fa-users">&nbsp;</i>高频/状态修任务</a></li>--}}
{{--            <li class="active">添加出所单</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-7">
                    </div>
                    <div class="col-md-5">
                        <!--右侧最小化按钮-->
                        <div class="input-group pull-right">
                            <div class="input-group-btn"><a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/edit" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a></div>
                            <div class="input-group-addon">扫码</div>
                            <input type="text" name="identity_code" id="txtIdentityCode" class="form-control" onkeydown="if(event.keyCode===13){fnOutScan(this.value);}" {{ array_flip($tempTaskSubOrderStatuses)[$tempTaskSubOrder->status] === 'DONE' ? 'disabled' : ''}}>
                            @if(array_flip($tempTaskSubOrderStatuses)[$tempTaskSubOrder->status] !== 'DONE')
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-flat btn-primary" onclick="modalWarehouse('Out')"><i class="fa fa-sign-out">&nbsp;</i>出所</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="box-body">
                <div class="table-responsive">
                    <div class="btn-group pull-right"></div>
                    <table class="table table-condensed">
                        <thead>
                        <tr>
                            <th>唯一编号</th>
                            <th>型号</th>
                            <th>组合位置/道岔号</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($tempTaskSubOrderEntireInstances as $entireInstance)
                            <tr id="tr_{{ @$entireInstance->id }}" class="{{ $entireInstance->out_scan ? 'bg-success' : '' }}">
                                <td>{{ @$entireInstance->NewEntireInstance->identity_code }}</td>
                                <td>{{ @$entireInstance->NewEntireInstance->model_name }}</td>
                                <td>
                                    {{ @$entireInstance->NewEntireInstance->maintain_location_code }}
                                    {{ @$entireInstance->NewEntireInstance->crossroad_number }}
                                </td>
                                <td><a href="javascript:" class="btn btn-flat btn-danger btn-sm" onclick="fnDeleteScan('{{ $entireInstance->id }}')"><i class="fa fa-times"></i></a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!--模态框-->
    <section class="content">
        <!--设备入所-->
        <div class="modal fade" id="modalWarehouseIn">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备入所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmWarehouseIn">
                            <input type="hidden" name="processor_id" value="{{ session('account.id') }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName_WarehouseIn" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系电话：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone_WarehouseIn" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">经办人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="processor_id" id="selProcessorId_WarehouseIn" class="form-control disabled" style="width: 100%;" disabled>
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

        <!--设备出所-->
        <div class="modal fade" id="modalWarehouseOut">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备出所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmWarehouseOut">
                            <input type="hidden" name="processor_id" value="{{ session('account.id') }}">
                            <input type="hidden" name="type" value="HIGH_FREQUENCY">
                            <input type="hidden" name="temp_task_id" value="{{ $tempTask->id }}">
                            <input type="hidden" name="temp_task_sub_order_id" value="{{ $tempTaskSubOrder->id }}">
                            <input type="hidden" name="scene_workshop_name" value="{{ $tempTaskSubOrder->scene_workshop_name }}">
                            <input type="hidden" name="scene_workshop_unique_code" value="{{ $tempTaskSubOrder->scene_workshop_unique_code }}">
                            <input type="hidden" name="maintain_station_name" value="{{ $tempTaskSubOrder->maintain_station_name }}">
                            <input type="hidden" name="maintain_station_unique_code" value="{{ $tempTaskSubOrder->maintain_station_unique_code }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName_WarehouseOut" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系电话：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone_WarehouseOut" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">经办人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="processor_id" id="selProcessorId_WarehouseOut" class="form-control disabled" style="width: 100%;" disabled>
                                        <option value="{{ session('account.id') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">出所时间：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-addon">日期</div>
                                        <input type="text" class="form-control pull-right" name="processed_date" id="dpProcessedDate_WarehouseOut" value="{{ date('Y-m-d') }}">
                                        <div class="input-group-addon">时间</div>
                                        <input type="text" class="form-control timepicker" name="processed_time" id="tpProcessedTime_WarehouseOut">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" class="btn btn-success btn-flat btn-sm" onclick="fnWarehouse('Out')"><i class="fa fa-sign-out">&nbsp;</i>出所</a>
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
        let $dpProcessedDate_WarehouseIn = $('#dpProcessedDate_WarehouseIn');
        let $dpProcessedData_WarehouseOut = $('#dpProcessedData_WarehouseOut');
        let $tpProcessedTime_WarehouseIn = $('#tpProcessedTime_WarehouseIn');
        let $tpProcessedTime_WarehouseOut = $('#tpProcessedTime_WarehouseOut');

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
            $dpProcessedDate_WarehouseIn.datepicker(datepickerOption);  // 入所日期
            $dpProcessedData_WarehouseOut.datepicker(datepickerOption);  // 出所日期

            // 时间选择器
            $tpProcessedTime_WarehouseIn.timepicker({
                showInputs: true,
                showMeridian: false,
            });  // 入所时间
            $tpProcessedTime_WarehouseOut.timepicker({
                showInputs: true,
                showMeridian: false,
            });  // 出所时间
        });

        /**
         * 扫码
         */
        function fnOutScan(identityCode) {
            $.ajax({
                url: `{{ url('tempTaskSubOrderEntireInstance') }}/${identityCode}/scanForWarehouse`,
                type: 'post',
                data: {
                    type: 'HIGH_FREQUENCY',
                    direction: 'OUT',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance') }}/${identityCode}/scanForWarehouse success:`, res);

                    let {temp_task_sub_order_entire_instance: tempTaskSubOrderEntireInstance} = res['data'];

                    $(`#tr_${tempTaskSubOrderEntireInstance['id']}`).addClass('bg-success');
                    $('#txtIdentityCode').val('');
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance') }}/${identityCode}/scanForWarehouse fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 移除扫码标记
         */
        function fnDeleteScan(id) {
            $.ajax({
                url: `{{ url('tempTaskSubOrderEntireInstance') }}/${id}/scanForWarehouse`,
                type: 'delete',
                data: {
                    type: 'HIGH_FREQUENCY',
                    direction: 'OUT',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance') }}/${id}/scanForWarehouse success:`, res);

                    let {temp_task_sub_order_entire_instance: tempTaskSubOrderEntireInstance} = res['data'];
                    $(`#tr_${tempTaskSubOrderEntireInstance['id']}`).removeClass('bg-success');
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance') }}/${id}/scanForWarehouse fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 打开出入所模态框
         */
        function modalWarehouse(type) {
            $(`#modalWarehouse${type}`).modal('show');
        }

        /**
         * 执行出入所
         */
        function fnWarehouse(type) {
            let data = $(`#frmWarehouse${type}`).serializeArray();

            $.ajax({
                url: `{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=HIGH_FREQUENCY&direction=OUT`,
                type: 'post',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=HIGH_FREQUENCY&direction=OUT success:`, res);
                    // location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=HIGH_FREQUENCY&direction=OUT fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
