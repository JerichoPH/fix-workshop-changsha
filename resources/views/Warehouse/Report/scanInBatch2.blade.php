@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{--出入所管理--}}
            {{ $title }}
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">{{ $title }}</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form action="" method="post">
            <div class="box">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-4">
                            <h3 class="box-title">
                                待{{ $title }}设备列表
                                <small>设备总数：{{ @$entire_instances->count() ?: 0 }}</small>
                            </h3>
                        </div>
                        <div class="col-md-8">
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm">
                                <div class="input-group">
                                    <div class="input-group-btn">
                                        {{--<a href="{{ url('entire/instance/upload') }}?download=1" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-upload">&nbsp;</i>批量上传</a>--}}
                                        {{--<a href="{{ url('entire/instance/upload') }}" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-upload">&nbsp;</i>批量上传</a>--}}
                                    </div>
                                    <div class="input-group-addon">唯一/所编号</div>
                                    <input
                                        type="text"
                                        name="code"
                                        id="txtCode"
                                        class="form-control"
                                        autofocus
                                        required
                                    >
                                    <div class="input-group-btn">
                                        <button type="submit" style="display: none;"></button>
                                        <a href="javascript:" class="btn btn-flat btn-default" onclick="fnPrintQrCode(1)">打印设备标签(35*20)</a>
                                        <a href="javascript:" class="btn btn-flat btn-default" onclick="fnPrintQrCode(2)">打印设备标签(20*12)</a>
                                        <a href="javascript:" class="btn btn-flat btn-default" onclick="fnPrintQrCode(3)">打印设备标签(40*25)</a>
                                        <a href="javascript:" onclick="fnDeleteAll()" class="btn btn-danger btn-flat"><i class="fa fa-times">&nbsp;</i>清空</a>
                                        <a href="javascript:" onclick="modalCreateWarehouse()" class="btn btn-primary btn-flat"><i class="fa fa-sign-{{ strtolower(request('direction')) }}">&nbsp;</i>{{ $title }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>唯一编号</th>
                                <th>所编号</th>
                                <th>型号</th>
                                @if(request('direction') == 'OUT')
                                    <th>安装位置</th>
                                @endif
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($entire_instances as $entire_instance)
                                <tr>
                                    <td>
                                        <input type="hidden" name="identityCodesForPrint" value="{{ @$entire_instance->entire_instance_identity_code }}">
                                        {{ @$entire_instance->entire_instance_identity_code }}
                                    </td>
                                    <td>{{ @$entire_instance->EntireInstance->serial_number ?? '' }}</td>
                                    <td>{{ @$entire_instance->EntireInstance->model_name }}</td>
                                    @if(request('direction') == 'OUT')
                                        <td>
                                            {{ @$entire_instance->EntireInstance->maintain_station_name }}
                                            {{ @$entire_instance->EntireInstance->maintain_location_code }}
                                            {{ @$entire_instance->EntireInstance->scrossroad_number }}
                                            {{ @$entire_instance->EntireInstance->traction }}
                                            {{ @$entire_instance->EntireInstance->line_name }}
                                            {{ @$entire_instance->EntireInstance->crossroad_type }}
                                            {{ @$entire_instance->EntireInstance->point_switch_group_type }}
                                            {{ @$entire_instance->EntireInstance->open_direction }}
                                            {{ @$entire_instance->EntireInstance->said_rod }}
                                        </td>
                                    @endif
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('{{ @$entire_instance->id }}')"><i class="fa fa-times"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                {{--@if($entire_instances->hasPages())--}}
                {{--    <div class="box-footer">--}}
                {{--        {{ $entire_instances->appends(['page'=>request('page', 1)])->links() }}--}}
                {{--    </div>--}}
                {{--@endif--}}
            </div>
        </form>

        <!--设备入所模态框-->
        <div class="modal fade" id="modalCreateWarehouse">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备{{ $title }}</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreWarehouse">
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
                                <label class="col-sm-3 col-md-3 control-label">{{ request('direction') == 'IN' ? '入' : '出'}}所时间：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-addon">日期</div>
                                        <input type="text" class="form-control pull-right disabled" id="dpProcessedDate" value="{{ date('Y-m-d') }}" disabled>
                                        <div class="input-group-addon">时间</div>
                                        <input type="text" class="form-control timepicker disabled" id="tpProcessedTime" value="{{ date('H:i') }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreWarehouse()"><i class="fa fa-sign-{{ strtolower(request('direction')) }}">&nbsp;</i>{{ request('direction') == 'IN' ? '入' : '出'}}所</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $txtConnectionName = $('#txtConnectionName');
        let $txtConnectionPhone = $('#txtConnectionPhone');
        let $dpProcessedDate = $('#dpProcessedDate');
        let $tpProcessedTime = $('#tpProcessedTime');

        /**
         * 光标定位闪烁
         */
        $(function () {
            var inputBox = document.getElementById('txtCode');
            inputBox.selectionStart = inputBox.value.length - 2;
            inputBox.selectionEnd = inputBox.value.length;
            inputBox.focus();
        });

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: false,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
                    iDisplayLength: "{{ env('PAGE_SIZE', 50) }}",  // 默认分页数
                    aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }

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
            $('#dpProcessedDate').datepicker(datepickerOption);

            // 入所时间
            $tpProcessedTime.timepicker({
                showInputs: true,
                showMeridian: false,
            });
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('warehouse/report/scanBatch') }}/${id}?direction={{ request('direction') }}`,
                    type: 'delete',
                    data: {},
                    success: function (res) {
                        console.log(`{{ url('warehouse/report/scanBatch')}}/${id}?direction={{ request('direction') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('warehouse/report/scanBatch')}}/${id}?direction={{ request('direction') }} fail:`, err);
                        if (err.responseText === 401) location.href = "{{ url('login') }}";
                        if (err['responseJSON']['message'].constructor === Object) {
                            let message = '';
                            for (let msg in err['responseJSON']['message']) message += `${msg}\r\n`;
                            alert(message);
                            return;
                        }
                        alert(err['responseJSON']['message']);
                    }
                });
        }

        /**
         * 清空所有已扫码设备
         */
        function fnDeleteAll() {
            $.ajax({
                url: `{{ url('warehouse/report/scanBatch') }}/0?direction={{ request('direction') }}`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('warehouse/report/scanBatch') }}/0?direction={{ request('direction') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('warehouse/report/scanBatch') }}/0?direction={{ request('direction') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打开出入所窗口
         */
        function modalCreateWarehouse() {
            $('#modalCreateWarehouse').modal('show');
        }

        /**
         * 设备入所
         */
        function fnStoreWarehouse() {
            let data = {
                connectionName: $txtConnectionName.val(),
                connectionPhone: $($txtConnectionPhone).val(),
                direction: '{{ request('direction') }}',
                processedDate: $dpProcessedDate.val(),
                processedTime: $tpProcessedTime.val(),
            };
            $.ajax({
                url: `{{ url('warehouse/report/scanBatchWarehouse') }}`,
                type: 'post',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('warehouse/report/scanBatchWarehouse') }} success:`, res);
                    location.href = `{{ url('warehouse/report') }}/${res['warehouse_report_sn']}?direction={{ request('direction') }}&show_type=D`;
                },
                error: function (err) {
                    console.log(`{{ url('warehouse/report/scanBatchWarehouse') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打印设备标签
         * @param {int} sizeType
         */
        function fnPrintQrCode(sizeType = 1) {
            // 处理数据
            let identityCodes = [];

            $("input[type='hidden'][name='identityCodesForPrint']").each(function (index, item) {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });

            if (identityCodes.length <= 0) {
                alert('请选择打印标签设备');
                return false;
            }

            // 保存需要打印的数据
            $.ajax({
                url: `{{ url('/warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes},
                async: false,
                success: function (res) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} success:`, res);
                    let params = $.param({size_type: sizeType})
                    window.open(`{{url('qrcode/printQrCode')}}?${params}`, '_blank');
                },
                error: function (err) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection
