@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修管理
            <small>新建</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li><a href="{{ url('repairBase/breakdownOrder') }}?page={{ request('page',1) }}&direction=IN"><i class="fa fa-users">&nbsp;</i>故障修管理</a></li>--}}
        {{--            <li class="active">新建</li>--}}
        {{--        </ol>--}}
    </section>
    <div class="row">
        <form id="frmUpdateBreakdownLog">
            <!--左侧-->
            <div class="col-md-12">
                <section class="content">
                    <div class="box box-solid">
                        <div class="box-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="box-title">新建故障修入所</h3>
                                </div>
                                <div class="col-md-6">
                                    <div class="pull-right">
                                        <div class="btn-group">
                                            <a href="{{ url('repairBase/breakdownOrder') }}?page={{ request('page',1) }}&direction=IN" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                            <a href="javascript:" onclick="modalWarehouse()" class="btn btn-flat btn-success"><i class="fa fa-sign-in">&nbsp;</i>入所</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">唯一/所编号</div>
                                        <label for="txtNo" style="display: none;"></label>
                                        <input type="text" name="no" id="txtNo" class="form-control" onkeydown="if(event.keyCode === 13) {fnSearch();}">
                                        <div class="input-group-addon">组合位置/道岔号</div>
                                        <label for="txtLocation" style="display: none;"></label>
                                        <input type="text" name="location" id="txtLocation" class="form-control" onkeydown="if(event.keyCode === 13) {fnSearch();}">
                                    </div>
                                    <p class="help-block">搜索：二选一&emsp;&emsp;入所时间：{{ date('Y-m-d') }}&emsp;&emsp;任务数量：<span id="spanEntireInstancesTotal">{{ $temp_entire_instance_count }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th><label for="chkAllCheck"></label><input type="checkbox" id="chkAllCheck"></th>
                                        <th>唯一/所编号</th>
                                        <th>型号</th>
                                        <th>组合位置/道岔号</th>
                                        <th>现场故障<br>现象</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbodyInOrder">
                                    @foreach($temp_entire_instances as $entire_instance)
                                        <tr onclick="fnGetBreakdownLog('{{ $entire_instance->id }}')">
                                            <td>
                                                <label for="chkBreakdownOrderTempId_{{ $entire_instance->id }}"></label>
                                                <input type="checkbox" class="breakdown-order-temp-checkbox" name="breakdown_order_temp_ids[]" id="chkBreakdownOrderTempId_{{ $entire_instance->id }}" value="{{ $entire_instance->id }}">
                                            </td>
                                            <td>{{ @$entire_instance->EntireInstance->identity_code }}/{{ $entire_instance->EntireInstance->serial_number }}</td>
                                            <td>{{ @$entire_instance->EntireInstance->model_name }}</td>
                                            <td>
                                                {{ @$entire_instance->EntireInstance->maintain_station_name }}
                                                {{ @$entire_instance->EntireInstance->maintain_location_code }}
                                                {{ @$entire_instance->EntireInstance->crossroad_number }}
                                            </td>
                                            <td>
                                                @if($entire_instance->station_breakdown_explain)
                                                    <a href="javascript:" onclick="fnModalStationBreakdownExplain({{ $entire_instance->id }})">
                                                        @if(strlen($entire_instance->station_breakdown_explain) > 20)
                                                            {{ substr($entire_instance->station_breakdown_explain,0,20) }}……
                                                        @else
                                                            {{ $entire_instance->station_breakdown_explain }}
                                                        @endif
                                                    </a>
                                                @else
                                                    <a href="javascript:" onclick="fnModalStationBreakdownExplain({{ $entire_instance->id }})">无</a>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('{{ $entire_instance->EntireInstance->identity_code }}')"><i class="fa fa-times"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <!--右侧-->
            {{--<div class="col-md-5">--}}
            {{--    <section class="content">--}}
            {{--        <div class="box box-solid">--}}
            {{--            <div class="box-header">--}}
            {{--                <h3 class="box-title">现场故障现象</h3>--}}
            {{--                <!--右侧最小化按钮-->--}}
            {{--                <div class="pull-right">--}}
            {{--                    <a href="javascript:" class="btn btn-flat btn-warning btn-sm" onclick="fnUpdateBreakdownLog()"><i class="fa fa-check">&nbsp;</i>保存</a>--}}
            {{--                    --}}{{--<a href="javascript:" onclick="modalEditBreakdownType()" class="btn btn-warning btn-flat"><i class="fa fa-wrench">&nbsp;</i>故障类型管理</a>--}}
            {{--                </div>--}}
            {{--            </div>--}}
            {{--            <div class="box-body">--}}
            {{--                --}}{{--@if(!empty($breakdown_types))--}}
            {{--                --}}{{--    <div class="table-responsive">--}}
            {{--                --}}{{--        <table class="table table-condensed table-striped">--}}
            {{--                --}}{{--            <tbody id="tbodyBreakdownTypes">--}}
            {{--                --}}{{--            @foreach($breakdown_types as $chunk)--}}
            {{--                --}}{{--                <tr>--}}
            {{--                --}}{{--                    @foreach($chunk as $breakdown_type_id => $breakdown_type_name)--}}
            {{--                --}}{{--                        <td>--}}
            {{--                --}}{{--                            <input type="checkbox" name="breakdown_type_ids[]" class="breakdown-type-checkbox" id="chkBreakdownTypeId_{{ $breakdown_type_id }}" value="{{ $breakdown_type_id }}">--}}
            {{--                --}}{{--                            <label for="chkBreakdownTypeId_{{ $breakdown_type_id }}" class="control-label">{{ $breakdown_type_name }}</label>--}}
            {{--                --}}{{--                        </td>--}}
            {{--                --}}{{--                    @endforeach--}}
            {{--                --}}{{--                </tr>--}}
            {{--                --}}{{--            @endforeach--}}
            {{--                --}}{{--            </tbody>--}}
            {{--                --}}{{--        </table>--}}
            {{--                --}}{{--    </div>--}}
            {{--                --}}{{--@endif--}}
            {{--                <textarea name="explain" id="txaExplain" cols="30" rows="5" class="form-control"></textarea>--}}
            {{--            </div>--}}
            {{--            --}}{{--<div class="box-footer"></div>--}}
            {{--        </div>--}}
            {{--    </section>--}}
            {{--</div>--}}
        </form>
        <!--模态框-->
        <section class="content">
            <!--入所模态框-->
            <div class="modal fade" id="modalWarehouse">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">设备入所</h4>
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
                                    <label class="col-sm-3 col-md-3 control-label">入所时间：</label>
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
                            <a href="javascript:" onclick="fnStore()" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确定</a>
                        </div>
                    </div>
                </div>
            </div>

            <!--故障类型列表模态框-->
            <div class="modal fade" id="modalEditBreakdownType">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">编辑故障类型</h4>
                        </div>
                        <div class="modal-body">
                            <form id="frmUpdateBreakdownType">
                                <div class="table-responsive">
                                    <table class="table table-condensed">
                                        <thead>
                                        <tr>
                                            <td>
                                                <div class="input-group">
                                                    <div class="input-group-addon">新建故障类型</div>
                                                    <input type="text" name="name" id="txtNewBreakdownTypeName" class="form-control">
                                                    <div class="input-group-btn">
                                                        <a href="javascript:" class="btn btn-success btn-flat" onclick="fnStoreBreakdownType()"><i class="fa fa-check"></i></a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        </thead>
                                        <tbody id="tbodyBreakdownType"></tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                            {{--<button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateBreakdownType()"><i class="fa fa-check">&nbsp;</i>确定</button>--}}
                        </div>
                    </div>
                </div>
            </div>

            <!--现场故障现象-->
            <div class="modal fade" id="modalStationBreakdownExplain">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">现场故障现象</h4>
                        </div>
                        <div class="modal-body form-horizontal">
                            <input type="hidden" name="" id="hdnBreakdownOrderEntireInstanceId">
                            <textarea name="station_breakdown_explain" id="txaStationBreakdownExplain" cols="30" rows="5" maxlength="150" class="form-control"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                            <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreStationBreakdownExplain()"><i class="fa fa-check">&nbsp;</i>确定</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@section('script')
    <script>
        let $txtNo = $('#txtNo');
        let $txtLocation = $('#txtLocation');
        let $txtConnectionName = $('#txtConnectionName');
        let $txtConnectionPhone = $('#txtConnectionPhone');
        let $dpProcessedDate = $('#dpProcessedDate');
        let $tpProcessedTime = $('#tpProcessedTime');
        let $frmUpdateBreakdownLog = $('#frmUpdateBreakdownLog');
        let $breakdownOrderTempCheckbox = $('.breakdown-order-temp-checkbox');
        let $breakdownTypeCheckbox = $('.breakdown-type-checkbox');
        let $tbodyBreakdownType = $('#tbodyBreakdownType');
        let $modalEditBreakdownType = $('#modalEditBreakdownType');
        let $txtNewBreakdownTypeName = $('#txtNewBreakdownTypeName');
        let $modalStationBreakdownExplain = $('#modalStationBreakdownExplain');
        let $txaStationBreakdownExplain = $('#txaStationBreakdownExplain');
        let $hdnBreakdownOrderEntireInstanceId = $('#hdnBreakdownOrderEntireInstanceId');

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
         function __fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(`checkClassName:not(:disabled)`).prop('checked', $(allCheckId).prop('checked'));
            });
            $(checkClassName).on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked:not(:disabled)`).length === $(`checkClassName:not(:disabled)`).length);
            });
        }

        __fnAllCheckBind('#chkAllCheck', '.breakdown-order-temp-checkbox');

        /**
         * 模态框关闭后，刷新页面
         */
        $modalEditBreakdownType.on('hidden.bs.modal', function () {
            location.reload();
        });

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
                weekStart: 0,
            };
            $('#dpProcessedDate').datepicker(datepickerOption);

            // 入所时间
            $tpProcessedTime.timepicker({
                showInputs: true,
                showMeridian: false,
            });
        });

        /**
         * 打开入所窗口
         */
        function modalWarehouse() {
            $('#modalWarehouse').modal('show');
        }

        /**
         * 入所&保存
         */
        function fnStore() {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder') }}`,
                type: 'post',
                data: {
                    connectionName: $txtConnectionName.val(),
                    connectionPhone: $txtConnectionPhone.val(),
                    direction: 'IN',
                    processedDate: $dpProcessedDate.val(),
                    processedTime: $tpProcessedTime.val(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder') }} success:`, res);
                    {{--location.href = `{{ url('warehouse/report') }}/${res['in_warehouse_sn']}?direction=IN&show_type=E`;--}}
                    location.href=`{{ url('repairBase/breakdownOrder/printLabel') }}/${res['new_breakdown_out_sn']}?page=1&direction=OUT`;
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 搜索设备
         */
        function fnSearch() {
            if ($txtNo.val() || $txtLocation.val()) {
                $.ajax({
                    url: `{{ url('repairBase/breakdownOrder/entireInstances') }}`,
                    type: 'post',
                    data: {
                        no: $txtNo.val(),
                        location: $txtLocation.val(),
                        direction: 'IN',
                    },
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 添加到故障修计划表
         * @param identityCode
         */
        function fnAdd(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/entireInstances') }}`,
                type: 'post',
                data: {
                    no: $txtNo.val(),
                    location: $txtLocation.val(),
                    direction: 'IN',
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除入所计划表中设备
         * @param identityCode
         */
        function fnDelete(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/entireInstances') }}`,
                type: 'delete',
                data: {
                    identityCode,
                    direction: 'IN',
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 勾选设备获取设备绑定故障类型和故障补充说明信息
         */
        {{--$breakdownOrderTempCheckbox.on('change', function () {--}}
        {{--    if ($('.breakdown-order-temp-checkbox:checked').length === 1) {--}}
        {{--        let tmpId = $('.breakdown-order-temp-checkbox:checked').val();--}}
        {{--        // 单选状态，重新渲染故障类型勾选和故障补充描述--}}
        {{--        $.ajax({--}}
        {{--            url: `{{ url('repairBase/breakdownOrder/breakdownLog') }}`,--}}
        {{--            type: 'get',--}}
        {{--            data: {breakdownOrderTempId: tmpId},--}}
        {{--            async: true,--}}
        {{--            success: function (res) {--}}
        {{--                console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} success:`, res);--}}
        {{--                _fnFillBreakdownTypeCheckboxAndBreakdownExplain(res['checked_breakdown_type_ids'], res['explain']);--}}
        {{--            },--}}
        {{--            error: function (err) {--}}
        {{--                console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} fail:`, err);--}}
        {{--                if (err.status === 401) location.href = "{{ url('login') }}";--}}
        {{--                alert(err['responseJSON']['message']);--}}
        {{--            }--}}
        {{--        });--}}
        {{--    } else {--}}
        {{--        // 多选或未选择，清空故障类型勾选和故障补充描述--}}
        {{--        _fnCleanBreakdownTypeCheckboxAndBreakdownExplain();--}}
        {{--    }--}}
        {{--});--}}

        /**
         * 清空故障类型勾选和故障补充描述
         */
        function _fnCleanBreakdownTypeCheckboxAndBreakdownExplain() {
            $breakdownTypeCheckbox.prop('checked', false);
            $txaExplain.html('');
        }

        /**
         * 填充故障类型勾选和故障补充描述
         */
        function _fnFillBreakdownTypeCheckboxAndBreakdownExplain(checkedBreakdownTypeIds, explain) {
            // 重新渲染已经选择的故障类型
            for (let checkedBreakdownTypeId of checkedBreakdownTypeIds)
                $(`#chkBreakdownTypeId_${checkedBreakdownTypeId}`).prop('checked', true);
            // 渲染补充说明
            $txaExplain.html(explain);
        }

        /**
         * 更新故障日志
         */
        function fnUpdateBreakdownLog() {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/breakdownLog') }}`,
                type: 'put',
                data: $frmUpdateBreakdownLog.serializeArray(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} success:`, res);
                    alert(res['message']);
                    // location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 获取临时设备对应故障类型和故障额外描述
         * @param breakdownOrderTempId
         */
        function fnGetBreakdownLog(breakdownOrderTempId) {
            {{--if ($('.breakdown-order-temp-checkbox:checked').length === 1) {--}}
            {{--    // 单选状态，重新渲染故障类型勾选和故障补充描述--}}
            {{--    $.ajax({--}}
            {{--        url: `{{ url('repairBase/breakdownOrder/breakdownLog') }}`,--}}
            {{--        type: 'get',--}}
            {{--        data: {breakdownOrderTempId},--}}
            {{--        async: true,--}}
            {{--        success: function (res) {--}}
            {{--            console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} success:`, res);--}}
            {{--            _fnFillBreakdownTypeCheckboxAndBreakdownExplain(res['checked_breakdown_type_ids'], res['explain']);--}}
            {{--        },--}}
            {{--        error: function (err) {--}}
            {{--            console.log(`{{ url('repairBase/breakdownOrder/breakdownLog') }} fail:`, err);--}}
            {{--            if (err.status === 401) location.href = "{{ url('login') }}";--}}
            {{--            alert(err['responseJSON']['message']);--}}
            {{--        }--}}
            {{--    });--}}
            {{--} else {--}}
            {{--    console.log(2);--}}
            {{--    // 多选或未选择，清空故障类型勾选和故障补充描述--}}
            {{--    _fnCleanBreakdownTypeCheckboxAndBreakdownExplain();--}}
            {{--}--}}
        }

        /**
         * 填充一行故障类型
         */
        function _fnFillBreakdownType(id, name) {
            let html = '';
            html += `<tr>`;
            html += '<td>';
            html += '<div class="input-group">';
            html += `<input type="text" class="form-control disabled" name="breakdown_type_name" id="txtBreakdownTypeName_${id}" value="${name}" disabled>`;
            html += '<div class="input-group-btn">';
            html += `<a href="javascript:" class="btn btn-default btn-flat" onclick="fnOpenEditBreakdownTypeName(${id})" id="btnEditBreakdownTypeName_${id}"><i class="fa fa-pencil"></i></a>`;
            html += `<a href="javascript:" class="btn btn-warning btn-flat" onclick="fnUpdateBreakdownType(${id})" id="btnUpdateBreakdownTypeName_${id}" style="display: none;"><i class="fa fa-save"></i></a>`;
            html += `<a href="javascript:" class="btn btn-danger btn-flat" onclick="fnDeleteBreakdownType(${id})" id="btnDeleteBreakdownTypeName_${id}"><i class="fa fa-trash"></i></a>`;
            html += '</div>';
            html += '</div>';
            html += '</td>';
            html += `</tr>`;
            return html;
        }

        /**
         * 打开编辑故障类型窗口
         */
        function modalEditBreakdownType() {
            $.ajax({
                url: `{{ url('breakdownType') }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }} success:`, res);
                    let html = '';
                    for (let item of res['breakdown_types']) {
                        html += _fnFillBreakdownType(item['id'], item['name']);
                    }
                    $tbodyBreakdownType.html(html);
                    $modalEditBreakdownType.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 编辑故障类型
         */
        function fnUpdateBreakdownType(breakdownTypeId) {
            $.ajax({
                url: `{{ url('breakdownType') }}/${breakdownTypeId}`,
                type: 'put',
                data: {name: $(`#txtBreakdownTypeName_${breakdownTypeId}`).val()},
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }}/${breakdownTypeId} success:`, res);
                    alert(res['message']);
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }}/${breakdownTypeId} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err['responseJSON']['msg'].constructor === Object) {
                        let message = '';
                        for (let k in err['responseJSON']['msg']) message += `${err['responseJSON']['msg'][k]}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 新建故障类型
         */
        function fnStoreBreakdownType() {
            $.ajax({
                url: `{{ url('breakdownType') }}`,
                type: 'post',
                data: {name: $txtNewBreakdownTypeName.val()},
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }} success:`, res);
                    let html = _fnFillBreakdownType(res['breakdown_type']['id'], res['breakdown_type']['name']);
                    let oldHtml = $tbodyBreakdownType.html();
                    $tbodyBreakdownType.html(html + oldHtml);
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err['responseJSON']['message'].constructor === Object) {
                        let message = '';
                        for (let msg of err['responseJSON']['message']) message += `${msg}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除故障类型
         */
        function fnDeleteBreakdownType(breakdownTypeId) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: `{{ url('breakdownType') }}/${breakdownTypeId}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('breakdownType') }}/${breakdownTypeId} success:`, res);
                        let html = '';
                        for (let item of res['breakdown_types']) {
                            html += _fnFillBreakdownType(item['id'], item['name']);
                        }
                        $tbodyBreakdownType.html(html);
                        $modalEditBreakdownType.modal('show');
                    },
                    error: function (err) {
                        console.log(`{{ url('breakdownType') }}/${breakdownTypeId} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
        }

        /**
         * 打开文本编辑器
         */
        function fnOpenEditBreakdownTypeName(breakdownTypeId) {
            let $currentTxtBreakdownTypeName = $(`#txtBreakdownTypeName_${breakdownTypeId}`);
            $currentTxtBreakdownTypeName.removeAttr('disabled');
            $currentTxtBreakdownTypeName.removeClass('disabled');
            $currentTxtBreakdownTypeName.focus();
            $(`#btnEditBreakdownTypeName_${breakdownTypeId}`).hide();
            $(`#btnUpdateBreakdownTypeName_${breakdownTypeId}`).show();
        }

        /**
         * 打开现场故障现象模态框
         * @param {int} id
         */
        function fnModalStationBreakdownExplain(id) {
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/tmpEntireInstance') }}/${id}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/tmpEntireInstance') }}/${id} success:`, res);
                    $hdnBreakdownOrderEntireInstanceId.val(id);
                    $txaStationBreakdownExplain.val(res.data.entire_instance.station_breakdown_explain);
                    $modalStationBreakdownExplain.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/tmpEntireInstance') }}/${id} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 保存现场故障描述
         */
        function fnStoreStationBreakdownExplain() {
            let id = $hdnBreakdownOrderEntireInstanceId.val();
            let station_breakdown_explain = $txaStationBreakdownExplain.val();

            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/stationBreakdownExplain') }}/${id}`,
                type: 'post',
                data: {station_breakdown_explain},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/stationBreakdownExplain') }}/${id} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/stationBreakdownExplain') }}/${id} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
