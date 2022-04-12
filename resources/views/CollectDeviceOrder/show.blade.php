@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            数据采集单
            <small>设备列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('collectDeviceOrder') }}?page={{ request('page',1) }}">数据采集单列表</a></li>--}}
{{--            <li class="active">数据采集单设备列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">数据采集单 设备列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ url('collectDeviceOrder') }}?page={{ request('page',1) }}" class="btn btn-flat btn-sm btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>安装日期</th>
                            <th>所编号</th>
                            <th>厂编号</th>
                            <th>供应商</th>
                            <th>型号</th>
                            <th>状态</th>
                            <th>出厂日期/首次入所日期</th>
                            <th>上次入所日期/上次检修日期</th>
                            <th>周期修年限</th>
                            <th>使用寿命</th>
                            <th>到期日期</th>
                            <th>车站</th>
                            <th>上道位置</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($collectDeviceOrderEntireInstances as $collectDeviceOrderEntireInstance)
                            <tr>
                                <td>{{ $collectDeviceOrderEntireInstance->last_installed_time ? date('Y-m-d',strtotime($collectDeviceOrderEntireInstance->last_installed_time)) : '-' }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->entire_instance_serial_number }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->factory_device_code }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->factory_name }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->model_name }}</td>
                                <td>{{ \App\Model\EntireInstance::$STATUSS[$collectDeviceOrderEntireInstance->status] ?? '-' }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->made_at ? date('Y-m-d',strtotime($collectDeviceOrderEntireInstance->made_at)) : '-' }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->last_out_at ? date('Y-m-d',strtotime($collectDeviceOrderEntireInstance->last_out_at)) : '-' }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->cycle_fix_value }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->life_year }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->scarping_at ? date('Y-m-d',strtotime($collectDeviceOrderEntireInstance->scarping_at)) : '-' }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->maintain_station_name }}</td>
                                <td>{{ $collectDeviceOrderEntireInstance->maintain_location_code }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($collectDeviceOrderEntireInstances->hasPages())
                <div class="box-footer">
                    {{ $collectDeviceOrderEntireInstances->appends(['page'=>request('page', 1)])->links() }}
                </div>
            @endif
        </div>
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
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
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
        });
    </script>
@endsection
