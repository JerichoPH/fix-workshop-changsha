@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">新建{{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">新建{{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <form id="frmCreate">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <label for="selMaintainStation" class="input-group-addon">车站</label>
                                <select name="maintain_station_unique_code" id="selMaintainStation" class="input-group select2" style="width: 100%;">
                                    <option value="" selected disabled>请选择</option>
                                    @foreach($stations as $station)
                                        <option value="{{ $station->unique_code }}">{{ $station->name }}</option>
                                    @endforeach
                                </select>
{{--                                <div class="input-group-btn">--}}
{{--                                    <a href="javascript:" class="btn btn-flat btn-success" onclick="$('#modalCreateStation').modal('show')">新建车站</a>--}}
{{--                                </div>--}}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <label class="input-group-addon">截止日期</label>
                                <input type="text" class="form-control pull-right" name="expiring_at" id="dpExpiringAt" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <label for="selWorkArea" class="input-group-addon">工区</label>
                                <select name="work_area_unique_code" id="selWorkArea" class="input-group select2" style="width: 100%;">
                                    <option value="" disabled selected>请选择</option>
                                    @foreach($workAreas as $workArea)
                                        <option value="{{ $workArea->unique_code }}" {{ session('account.work_area_by_unique_code.unique_code') == $workArea->unique_code ? 'selected' : 'disabled' }}>{{ $workArea->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-flat btn-success" onclick="fnStore()">保存</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
{{--                <div class="table-responsive">--}}
{{--                    <table class="table table-hover table-striped table-condensed" id="table">--}}
{{--                        <thead>--}}
{{--                        <tr>--}}
{{--                            <th>设备编号</th>--}}
{{--                            <th>所编号</th>--}}
{{--                            <th>型号</th>--}}
{{--                            <th>厂家</th>--}}
{{--                            <th>厂编号</th>--}}
{{--                            <th>生产日期</th>--}}
{{--                            <th>出所日期</th>--}}
{{--                            <th>上道位置</th>--}}
{{--                            <th>检测人</th>--}}
{{--                            <th>检测时间</th>--}}
{{--                            <th>检修人</th>--}}
{{--                            <th>检修时间</th>--}}
{{--                            <th>验收人</th>--}}
{{--                            <th>验收时间</th>--}}
{{--                            <th>抽验人</th>--}}
{{--                            <th>抽验时间</th>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
            </div>
        </div>
    </section>

{{--    <section class="content">--}}
{{--        <!--新建车站-->--}}
{{--        <div class="modal fade" id="modalCreateStation">--}}
{{--            <div class="modal-dialog">--}}
{{--                <div class="modal-content">--}}
{{--                    <div class="modal-header">--}}
{{--                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">--}}
{{--                            <span aria-hidden="true">&times;</span></button>--}}
{{--                        <h4 class="modal-title">新建车站</h4>--}}
{{--                    </div>--}}
{{--                    <div class="modal-body form-horizontal">--}}
{{--                        <iframe src="{{ url('maintain/create') }}?page=1&type=station&is_iframe=1" frameborder="0" style="width: 100%; height: 500px;"></iframe>--}}
{{--                    </div>--}}
{{--                    <div class="modal-footer">--}}
{{--                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </section>--}}

@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            $('#modalCreateStation').on('hidden.bs.modal', function (e) {
                // 关闭窗口时刷新
                location.reload();
            });

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
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

            $('#reservation').daterangepicker({
                locale: {
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
                },
                startDate: originAt,
                endDate: finishAt
            });

            var date = new Date();
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
                startDate: date.toLocaleDateString(),
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#dpExpiringAt').datepicker(datepickerOption);
        });

        /**
         * 新建任务
         */
        function fnStore() {
            let data = $('#frmCreate').serialize();
            $.ajax({
                url: `{{ url('v250UnCycleFix') }}?type={{ request('type') }}`,
                type: 'POST',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250UnCycleFix') }}?type={{ request('type') }} success:`, res);
                    location.href = `/v250UnCycleFix/${res.data.task_order.serial_number}/edit?type={{ request('type') }}`;
                },
                error: function (err) {
                    console.log(`{{ url('v250UnCycleFix') }}?type={{ request('type') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
