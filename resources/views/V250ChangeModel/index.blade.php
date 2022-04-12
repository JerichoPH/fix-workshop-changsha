@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{ \App\Model\V250TaskOrder::$TYPES[request('type')] ?? '' }}任务单
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">任务单列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">{{ \App\Model\V250TaskOrder::$TYPES[request('type')] ?? '' }}任务列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group btn-group-sm">
                        <a href="{{ url('v250ChangeModel/create') }}?type={{ request('type') }}" class="btn btn-flat btn-success">新建</a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>编号</th>
                                <th>车站</th>
                                <th>截止/完成日期</th>
                                <th>状态</th>
                                <th>负责人</th>
                                <th>所属工区</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($taskOrders as $taskOrder)
                                <tr>
                                    <td>
                                        <a href="{{ url('v250ChangeModel',$taskOrder->serial_number) }}/edit?page={{ request('page', 1) }}&type={{ request('type')}}" class="label label-primary">
                                            {{ $taskOrder->serial_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $taskOrder->SceneWorkshop ? $taskOrder->SceneWorkshop->name : '' }}
                                        {{ $taskOrder->MaintainStation ? $taskOrder->MaintainStation->name : '' }}
                                    </td>
                                    <td style="color: {{ strtotime($taskOrder->expiring_at) < time() ? 'red;' : '' }}">
                                        {{ $taskOrder->expiring_at ? date('Y-m-d',strtotime($taskOrder->expiring_at)) : '' }}
                                        {{ $taskOrder->finished_at ? '/'.date('Y-m-d',strtotime($taskOrder->finished_at)) : '' }}
                                    </td>
                                    <td>{{ $taskOrder->status ? $taskOrder->status['name'] : '' }}</td>
                                    <td>{{ $taskOrder->Principal ? $taskOrder->Principal->nickname : ''}}</td>
                                    <td>{{ $taskOrder->WorkAreaByUniqueCode ? $taskOrder->WorkAreaByUniqueCode->name : '' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('{{ $taskOrder->serial_number }}')">删除</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($taskOrders->hasPages())
                    <div class="box-footer">
                        {{ $taskOrders->appends(['page'=>request('page',1),'type'=>request('type')])->links() }}
                    </div>
                @endif
            </div>
            <input type="hidden" name="type" value="{{ request('type') }}">
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
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
                    autoWidth: true,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
                    iDisplayLength: "{{ env('PAGE_SIZE', 15) }}",  // 默认分页数
                    aLengthMenu: [15],  // 分页下拉框选项
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
        });

        /**
         * 删除
         * @param sn 编号
         */
        function fnDelete(sn) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('v250ChangeModel') }}/${sn}`,
                    type: 'delete',
                    data: {id: sn},
                    success: function (res) {
                        console.log(`{{ url('v250ChangeModel')}}/${sn} success:`, res);
                        alert(res.msg);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('v250ChangeModel')}}/${sn} fail:`, err);
                        if (err.responseText === 401) location.href = "{{ url('login') }}";
                        if (err['responseJSON']['msg'].constructor === Object) {
                            let message = '';
                            for (let msg of err['responseJSON']['msg']) message += `${msg}\r\n`;
                            alert(message);
                            return;
                        }
                        alert(err['responseJSON']['msg']);
                    }
                });
        }
    </script>
@endsection
