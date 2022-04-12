@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <!-- 面包屑 -->
    <section onclick="document.getElementById('txtIdentityCode').focus();" class="content-header">
        <h1>
            高频/状态修
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
                        <h3 class="box-title">设备列表</h3>
                        <div class="pull-right">
                            <div class="input-group">
                                <div class="input-group-addon">种类</div>
                                <select name="category_unique_code" id="selCategory" class="select2 form-control" onchange="fnFillModel(this.value)" style="width: 100%;"></select>
                                <div class="input-group-addon">型号</div>
                                <select name="model_unique_code" id="selModel" class="select2 form-control" style="width: 100%;"></select>
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-default btn-flat" id="btnSearch" onclick="modalSearch()"><span id="spanSearch"><i class="fa fa-search"></i></span></a>
                                </div>
                            </div>
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <input type="checkbox" name="use_made_at" id="chkUseMadeAt" value="1">
                                    <label for="chkUseMadeAt" style="font-weight: normal;">生产日期</label>
                                </div>
                                <input id="dpMadeAt" name="made_at" type="text" class="form-control">
                                <div class="input-group-addon">
                                    <input type="checkbox" name="use_scarping_at" id="chkUseScarpingAt" value="1">
                                    <label for="chkUseScarpingAt" style="font-weight: normal;">到期日期</label>
                                </div>
                                <input id="dpScarpingAt" name="scarping_at" type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="table-responsive">
                            <div class="btn-group btn-group-sm pull-right">
                                {{--<a href="http://changsha-input.zhongchengkeshi.com" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-sign-in">&nbsp;</i>导入</a>--}}
                                <a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/bindEntireInstance?type=HIGH_FREQUENCY" class="btn btn-default btn-flat"><i class="fa fa-link">&nbsp;</i>绑定设备</a>
                                <a href="{{ url('measurement/fixWorkflow/batchUploadFixerAndChecker') }}" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-wrench">&nbsp;</i>导入检修人和验收人</a>
                                <a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=HIGH_FREQUENCY&direction=IN" class="btn btn-flat btn-default"><i class="fa fa-sign-in">&nbsp;</i>添加入所单</a>
                                <a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/warehouse?type=HIGH_FREQUENCY&direction=OUT" class="btn btn-flat btn-default"><i class="fa fa-sign-out">&nbsp;</i>添加出所单</a>
                                <a href="javascript:" class="btn btn-flat btn-danger" onclick="fnCut()"><i class="fa fa-times">&nbsp;</i>删除</a>
                            </div>
                            <table class="table table-striped table-condensed">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" class="checkbox-toggle" id="chkCheckAll" onchange="fnCheckAll(this)"></th>
                                    <th>型号</th>
                                    <th>唯一编号(待下道)</th>
                                    <th>唯一编号(成品)</th>
                                    <th>检修人/验收人</th>
                                    <th>入所</th>
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
                                        <td>{{ $tempTaskSubOrderEntireInstance->new_entire_instance_identity_code ?? '✖' }}</td>
                                        <td>{!! $tempTaskSubOrderEntireInstance->fixer_id ? '<a href="/measurement/fixWorkflow/' . $tempTaskSubOrderEntireInstance->fix_workflow_sn . '/edit" target="_blank">' . $tempTaskSubOrderEntireInstance->fixer_nickname . '/' . $tempTaskSubOrderEntireInstance->checker_nickname . '</a>' : '✖' !!}</td>
                                        <td>{!! $tempTaskSubOrderEntireInstance->in_warehouse_sn ? '<a href="/warehouse/report/' . $tempTaskSubOrderEntireInstance->in_warehouse_sn . '?show_type=D&page=1&current_work_area=&direction=IN&updated_at=">查看详情</a>' : '✖' !!}</td>
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

        <!--搜索设备-->
        <div class="modal fade bs-example-modal-lg" id="modalSearch">
            <div class="modal-dialog modal-lg" style="width: 1000px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">搜索设备</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmSearch">
                            <input type="hidden" name="temp_task_id" value="{{ $tempTask->id }}">
                            <input type="hidden" name="temp_task_title" value="{{ $tempTask->title }}">
                            <input type="hidden" name="temp_task_serial_number" value="{{ $tempTask->serial_number }}">
                            <input type="hidden" name="temp_task_sub_order_id" value="{{ $tempTaskSubOrder->id }}">
                            <input type="hidden" name="temp_task_sub_order_work_area_id" value="{{ $tempTaskSubOrder->work_area_id }}">
                            <input type="hidden" name="temp_task_sub_order_maintain_station_name" value="{{ $tempTaskSubOrder->maintain_station_name }}">
                            <input type="hidden" name="type" value="HIGH_FREQUENCY_OLD">
                            <input type="hidden" name="lock_name" value="HIGH_FREQUENCY">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-condensed" id="table_Search">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>唯一编号</th>
                                        <th>型号</th>
                                        <th>车站</th>
                                        <th>上道位置</th>
                                        <th>生产日期</th>
                                        <th>到期日期</th>
                                        <th>状态</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbodyEntireInstance_Search"></tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" class="btn btn-success btn-flat btn-sm" onclick="fnAddEntireInstance()"><i class="fa fa-check">&nbsp;</i>添加</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let models = JSON.parse('{!! $modelsAsJson !!}');

        let $select2 = $('.select2');
        let $tbodyTempTaskSubOrderEntireInstances = $('#tbodyTempTaskSubOrderEntireInstances');
        let $txtIdentityCode = $('#txtIdentityCode');
        let $chkCheckAll = $('#chkCheckAll');
        let $modelTitle_Warehouse = $('#modelTitle_Warehouse');
        let $btnSearch = $('#btnSearch');
        let $txaDelivery = $('#txaDelivery');
        let $dpDeliveryAt_Delivery = $('#dpDeliveryAt_Delivery');
        let $selCategory = $('#selCategory');
        let $selModel = $('#selModel');
        let $spanSearch = $('#spanSearch');
        let $table_Search = $('#table_Search');
        let $tbodyEntireInstance_Search = $('#tbodyEntireInstance_Search');
        let $frmSearch = $('#frmSearch');
        let $dpMadeAt = $('#dpMadeAt');
        let $dpScarpingAt = $('#dpScarpingAt');
        let $chkUseMadeAt = $('#chkUseMadeAt');
        let $chkUseScarpingAt = $('#chkUseScarpingAt');

        /**
         * 填充种类下拉列表
         */
        function fnFillCategory() {
            let html = '<option value="">全部</option>';
            $.each(models, function (categoryUniqueCode, category) {
                html += `<option value="${categoryUniqueCode}">${category['name']}</option>`;
            });
            $selCategory.html(html);
            fnFillModel(); // 填充型号下拉列表
        }

        /**
         * 填充型号下拉列表
         * @params {string} categoryUniqueCode
         */
        function fnFillModel(categoryUniqueCode = '') {
            let html = `<option value="" disabled selected>请选择</option>`;
            if (categoryUniqueCode) {
                // 选择了种类
                $.each(models[categoryUniqueCode]['subs'], function (modelUniqueCode, model) {
                    html += `<option value="${modelUniqueCode}">${model['name']}</option>`;
                });
            } else {
                // 没选种类
                $.each(models, function (categoryUniqueCode, category) {
                    $.each(category['subs'], function (modelUniqueCode, model) {
                        html += `<option value="${modelUniqueCode}">${model['name']}</option>`;
                    });
                });
            }
            $selModel.html(html);
        }

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

        /**
         * 填充jqueryTable
         * @params {string} idName
         * @params {array} data
         */
        function fnFillTable_Search($table, data = []) {
            $table.DataTable({
                data,
                paging: true,  // 分页器
                lengthChange: true,
                searching: true,  // 搜索框
                ordering: false,  // 列排序
                info: true,
                autoWidth: false,  // 自动宽度
                order: [[4, 'asc']],  // 排序依据
                iDisplayLength: 15,  // 默认分页数
                aLengthMenu: [15],  // 分页下拉框选项
                processing: true,
                destroy: true,
                columns: [
                    {data: 'id', type: "",},
                    {data: 'identity_code',},
                    {data: 'model_name',},
                    {data: 'maintain_station_name',},
                    {data: 'location_code',},
                    {data: 'made_at',},
                    {data: 'scarping_at',},
                    {data: 'status',}
                ],
                language: {
                    processing: '加载中……',
                    sInfoFiltered: "从_MAX_中过滤",
                    sProcessing: "正在加载中...",
                    // info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                    info: "",
                    sLengthMenu: "每页显示_MENU_条记录",
                    zeroRecords: "没有符合条件的记录",
                    infoEmpty: " ",
                    emptyTable: "没有符合条件的记录",
                    search: "筛选：",
                    paginate: {sFirst: "首页", sLast: "末页 ", sPrevious: "上一页", sNext: "下一页"}
                }
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

            if (document.getElementById('table_Search')) fnFillTable_Search($table_Search);

            onTempTaskSubOrderEntireInstances();  // 给.temp-task-sub-order-entire-instances的input:check 添加监听
            fnFillCategory();  // 填充种类下拉列表

            // 初始化时间
            let locale = {
                format: "YYYY-MM-DD",
                separator: "~",
                daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                applyLabel: "确定",
                cancelLabel: "取消",
                fromLabel: "开始时间",
                toLabel: "结束时间",
                customRangeLabel: "自定义",
                weekLabel: "W",
            };
            let madeAt = "{{ implode('~',[\Carbon\Carbon::now()->firstOfMonth()->format('Y-m-d'),\Carbon\Carbon::now()->endOfMonth()->format('Y-m-d')]) }}".split('~');
            let scarpingAt = "{{ implode('~',[\Carbon\Carbon::now()->firstOfMonth()->format('Y-m-d'),\Carbon\Carbon::now()->endOfMonth()->format('Y-m-d')]) }}".split('~');
            $dpMadeAt.daterangepicker({locale, startDate: madeAt[0], endDate: madeAt[1]});
            $dpScarpingAt.daterangepicker({locale, startDate: scarpingAt[0], endDate: scarpingAt[1]});
        });

        /**
         * 添加设备
         */
        function fnAdd(identityCode) {
            let data = {
                temp_task_id: '{{ $tempTaskSubOrder->temp_task_id }}',
                temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                entire_instance_identity_code: identityCode,
                type: 'HIGH_FREQUENCY',
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
<td>×</td>
<td>×</td>
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
                        type: 'HIGH_FREQUENCY',
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

        /**
         * 根据型号搜索设备（附加条件：车站）
         */
        function modalSearch() {
            if (!$selModel.val()) {
                alert('先选择型号再进行搜索');
                return;
            }

            let data = {
                maintain_station_unique_code: '{{ $tempTaskSubOrder->maintain_station_unique_code }}',
                model_unique_code: $selModel.val(),
                status: ['INSTALLED', 'INSTALLING'],
                type: 'HIGH_FREQUENCY_OLD',
                temp_task_id: 0,
                temp_task_sub_order_id: 0,
                use_made_at: $chkUseMadeAt.prop('checked') ? 1 : 0,
                use_scarping_at: $chkUseScarpingAt.prop('checked') ? 1 : 0,
                made_at: $dpMadeAt.val(),
                scarping_at: $dpScarpingAt.val(),
            };

            $spanSearch.text('搜索中，请稍后……').addClass('disabled').prop('disabled', true);

            $.ajax({
                url: `{{ url('tempTaskSubOrderEntireInstance/entireInstances') }}`,
                type: 'get',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} success:`, res);

                    let {entire_instances: entireInstances} = res['data'];
                    let tableData = [];

                    $.each(entireInstances, function (index, entireInstance) {
                        let {
                            identity_code,
                            model_name,
                            maintain_station_name,
                            maintain_location_code,
                            crossroad_number,
                            made_at,
                            scarping_at,
                            status
                        } = entireInstance;
                        made_at = made_at.split(' ')[0];
                        scarping_at = scarping_at.split(' ')[0];
                        let tableDatum = {
                            id: `<input type="checkbox" name="entireInstanceIdentityCode_Search[]" value="${identity_code}"/>`,
                            identity_code,
                            model_name,
                            maintain_station_name,
                            location_code: `${maintain_location_code + crossroad_number}`,
                            made_at,
                            scarping_at,
                            status
                        };
                        tableData.push(tableDatum);
                    });
                    fnFillTable_Search($table_Search, tableData);

                    $('#modalSearch').modal('show');
                    $spanSearch.html(`<i class="fa fa-search"></i>`).removeClass('disabled').prop('disabled', false);
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    $spanSearch.html(`<i class="fa fa-search"></i>`).removeClass('disabled').prop('disabled', false);
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 添加任务设备
         */
        function fnAddEntireInstance() {
            $.ajax({
                url: `{{ url('tempTaskSubOrderEntireInstance/entireInstances') }}`,
                type: 'post',
                data: $frmSearch.serializeArray(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} success:`, res);
                    alert(res['msg']);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrderEntireInstance/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
