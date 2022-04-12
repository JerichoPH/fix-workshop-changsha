@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            状态修理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">状态修列表</h3>
                {{--右侧最小化按钮--}}
                <div class="pull-right">
                    <a href="{{ url('repairBase/fixOut/create') }}" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>新建</a>
                </div>
            </div>
            <br>
            <div class="box-body table-responsive">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                            <tr>

                            </tr>
                        </thead>
                        <tbody>
                        @foreach($bills as $bill)
                            <tr>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('repairBase/fixOut.edit',$bill['id']) }}" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                        <a class="btn btn-sm btn-danger btn-flat" onclick="fnDelete({{ $bill['id'] }})"><i class="fa fa-times">&nbsp;</i>删除</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                 </div>
            </div>
            @if($bills->hasPages())
                <div class="box-footer">
                    {{ $bills->appends(['page'=>request('page',1)])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function(){
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: true,
                    language: {
                        sProcessing: "正在加载中...",
                        info: "显示第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "显示 _MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "查询：",
                        paginate: {sFirst: "首页", sLast: "末页", sPrevious: "上一页 ", sNext: "下一页"}
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
         * @param {int} id 编号
         */
        function fnDelete (id) {
            $.ajax({
                url: `{{ url('repairBase/fixOut') }}/${id}`,
                type: "delete",
                data: {id: id},
                success: function (response) {
                    // console.log('success:', response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection
