@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备/器材赋码管理
            <small></small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form action="">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">搜索</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group btn-group-sm"></div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-addon">操作时间</div>
                                <input id="dpCreatedAt" name="created_at" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-addon">工区</div>
                                <select name="work_area_unique_code" id="selWorkArea" class="select2 form-control" disabled style="width: 100%;">
                                    <option value="{{ session('account.work_area_by_unique_code.unique_code') }}">{{ session('account.work_area_by_unique_code.name') }}</option>
                                </select>
                                <div class="input-group-addon">操作人</div>
                                <select name="processor_id" id="selProcessor" class="select2 form-control" style="width: 100%;">
                                    @foreach($processors as $processor)
                                        <option value="{{ $processor->id }}">{{ $processor->nickname }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-flat btn-default"><i class="fa fa-search">&nbsp;</i>搜索</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">设备/器材赋码管理</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>赋码时间</th>
                            <th>操作人</th>
                            <th>所属工区</th>
                            <th>错误报告</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entire_instance_excel_tagging_reports as $entire_instance_excel_tagging_report)
                            <tr>
                                <td>{{ $entire_instance_excel_tagging_report->created_at }}</td>
                                <td>{{ $entire_instance_excel_tagging_report->Processor ? $entire_instance_excel_tagging_report->Processor->nickname : '' }}</td>
                                <td>{{ $entire_instance_excel_tagging_report->work_area_type->name }}</td>
                                @if($entire_instance_excel_tagging_report->is_upload_create_device_excel_error)
                                    <td>
                                        <a href="{{ url('entire/tagging',$entire_instance_excel_tagging_report->serial_number) }}/downloadCreateDeviceErrorExcel?{{ http_build_query(['path'=>"{$create_device_error_dir}/{$entire_instance_excel_tagging_report->serial_number}.xls"]) }}" target="_blank">下载错误报告</a>
                                    </td>
                                @else
                                    <td>无</td>
                                @endif
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ url('entire/tagging',$entire_instance_excel_tagging_report->serial_number) }}/uploadCreateDeviceReport?page={{ request('page',1) }}" class="btn btn-default btn-flat"><i class="fa fa-eye">&nbsp;</i>查看详情</a>
                                        {{--<a href="{{ url('entire/tagging/reportShow',$entire_instance_excel_tagging_report->id) }}?page={{ request('page',1) }}" class="btn btn-default btn-flat"><i class="fa fa-eye">&nbsp;</i>查看详情</a>--}}
                                        <a href="javascript:" class="btn btn-danger btn-flat" onclick="fnRollback({{ $entire_instance_excel_tagging_report->id }})"><i class="fa fa-repeat">&nbsp;</i>回退</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($entire_instance_excel_tagging_reports->hasPages())
                <div class="box-footer">
                    {{ $entire_instance_excel_tagging_reports->appends(['page'=>request('page',1),'created_at'=>request('created_at'),'work_area_unique_code'=>request('work_area_unique_code'),'processor_id'=>request('processor_id'),])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    columnDefs: [
                        {
                            orderable: false,
                            targets: 0,  // 清除第一列排序
                        }
                    ],
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

            $('#dpCreatedAt').daterangepicker({
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
                startDate: '{{ $origin_at }}',
                endDate: '{{ $finish_at }}',
            });
        });

        /**
         * 回退
         * @param {int} id 编号
         */
        function fnRollback(id) {
            if (confirm('回退操作不可恢复，是否确定？')) {
                $.ajax({
                    url: `{{ url('entire/tagging/rollback') }}/${id}`,
                    type: 'post',
                    data: {},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('entire/tagging/rollback') }}/${id} success:`, res);

                        alert(res["msg"]);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('entire/tagging/rollback') }}/${id} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseJSON.msg);
                    }
                });
            }
        }
    </script>
@endsection
