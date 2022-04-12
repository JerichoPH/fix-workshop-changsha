@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            出入所
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">出入所</li>--}}
        {{--</ol>--}}
    </section>
    {{--查询--}}
    <form>
        <section class="content-header">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">查询</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="echartsWarehouse" style="height: 300px;"></div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <div class="input-group-addon">方向：</div>
                            <select name="direction" class="form-control select2" style="width:100%;">
                                <option value="">全部</option>
                                <option value="IN" {{ request('direction') == 'IN' ? 'selected' : '' }}>入所
                                </option>
                                <option value="OUT" {{ request('direction') == 'OUT' ? "selected" : '' }}>出所
                                </option>
                            </select>
                            {{--<div class="input-group-addon">工区：</div>--}}
                            {{--<select name="current_work_area" class="form-control select2" style="width:100%;">--}}
                            {{--    @foreach($work_areas as $work_area_id => $work_area_name)--}}
                            {{--        <option value="{{ $work_area_id }}" {{ $work_area_id == request('current_work_area') ? 'selected' : ''}}>{{ $work_area_name }}</option>--}}
                            {{--    @endforeach--}}
                            {{--</select>--}}
                            <div class="input-group-addon">车间：</div>
                            <select name="current_scene_workshop_name" id="selSceneWorkshop" class="form-control select2" style="width:100%;" onchange="fnSelectSceneWorkshop()">
                                <option value="">全部</option>
                                @foreach($stations as $scene_workshop_name => $station_name)
                                    <option value="{{ $scene_workshop_name }}" {{ $scene_workshop_name == request('current_scene_workshop_name') ? 'selected' : ''}}>{{ $scene_workshop_name }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-addon">车站：</div>
                            <select name="current_station_name" id="selStation" class="form-control select2" style="width:100%;"></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <div class="input-group-addon">时间：</div>
                            <input name="updated_at" type="text" class="form-control pull-right" id="reservation"
                                   value="{{ request('updated_at') }}">
                            <div class="input-group-btn">
                                <button class="btn btn-info btn-flat">查询</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        {{--出入所单列表--}}
        <section class="content">
            @include('Layout.alert')
            <div class="box box-solid">
                <div class="box-header">
                    <h1 class="box-title">出入所单列表</h1>
                    {{--右侧最小化按钮--}}
                    <div class="pull-right btn-group-sm btn-sm"></div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>序列号</th>
                            <th>操作人</th>
                            <th>操作时间</th>
                            <th>联系人/电话</th>
                            <th>类型</th>
                            <th>方向</th>
                            <th>位置</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($warehouse_reports as $warehouse_report)
                            <tr>
                                <td>{{ substr($warehouse_report->serial_number,-6,6) }}</td>
                                <td>{{ $warehouse_report->nickname ? $warehouse_report->nickname : '' }}</td>
                                <td>{{ $warehouse_report->processed_at }}</td>
                                <td>{{ $warehouse_report->connection_name }}&nbsp;&nbsp;{{ $warehouse_report->connection_phone }}</td>
                                <td>{{ @$types[$warehouse_report->type] }}</td>
                                <td>{{ $directions[$warehouse_report->direction] }}</td>
                                <td>{{ $warehouse_report->scene_workshop_name ? $warehouse_report->scene_workshop_name : '' }} {{ $warehouse_report->station_name ? $warehouse_report->station_name : '' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        {{--<a href="{{ url('warehouse/report',$warehouse_report->serial_number) }}?show_type=D&page={{ request('page',1) }}&direction={{ @$warehouse_report->direction }}&updated_at={{ request('updated_at') }}&billId={{$warehouse_report->bill_id}}"--}}
                                        {{--class="btn btn-primary btn-flat">{{$warehouse_report->direction == 'IN'? '入' : '出'}}所单详情</a>--}}
                                        {{--<a href="{{ url('warehouse/report',$warehouse_report->serial_number) }}?show_type=E&page={{ request('page',1) }}&direction={{ @$warehouse_report->direction }}&updated_at={{ request('updated_at') }}&billId={{$warehouse_report->bill_id}}"--}}
                                        <a href="{{ url('warehouse/report',$warehouse_report->serial_number) }}?show_type=D&page={{ request('page',1) }}&current_work_area={{ request('current_work_area') }}&direction={{ $warehouse_report->direction }}&updated_at={{ request('updated_at') }}"
                                           class="btn btn-primary btn-flat">{{$warehouse_report->direction == 'IN'? '入' : '出'}}所单详情</a>
                                        <a href="{{ url('warehouse/report',$warehouse_report->serial_number) }}?show_type=E&page={{ request('page',1) }}&current_work_area={{ request('current_work_area') }}&direction={{ $warehouse_report->direction }}&updated_at={{ request('updated_at') }}"
                                           class="btn btn-info btn-flat">设备列表</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($warehouse_reports->hasPages())
                    <div class="box-footer">
                        {{
                            $warehouse_reports
                            ->appends([
                                'show_type'=>request('show_type'),
                                'current_work_area'=>request('current_work_area'),
                                'direction'=>request('direction'),
                                'updated_at'=>request('updated_at'),
                                'current_scene_workshop_name'=>request('scene_workshop_name'),
                                'current_station_name'=>request('station_name'),
                            ])
                            ->links()
                        }}
                    </div>
                @endif
            </div>
        </section>
    </form>
@endsection
@section('script')
    <script>
        let stations = JSON.parse('{!! $stations_as_json !!}');

        /**
         * 出入所
         */
        function fnMakeWarehouseChart() {
            let echartsWarehouse = echarts.init(document.getElementById('echartsWarehouse'));
            let dateList = JSON.parse('{!! $warehouse_statistics_date_list_as_json !!}');
            let statistics = JSON.parse('{!! $warehouse_statistics_as_json !!}');

            let option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                        type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                    }
                },
                legend: {
                    data: ['转辙机(入所)', '转辙机(出所)', '继电器(入所)', '继电器(出所)', '综合(入所)', '综合(出所)']
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: [
                    {
                        type: 'category',
                        data: dateList
                    }
                ],
                yAxis: [{type: 'value'}],
                series: [
                    {
                        name: '转辙机(入所)',
                        color: ['#37A2DA'],
                        type: 'bar',
                        stack: '转辙机',
                        data: Object.values(statistics['转辙机(入所)']),
                    },
                    {
                        name: '转辙机(出所)',
                        color: ['#9FE6B8'],
                        type: 'bar',
                        stack: '转辙机',
                        data: Object.values(statistics['转辙机(出所)']),
                    },
                    {
                        name: '继电器(入所)',
                        color: ['#FFDB5C'],
                        type: 'bar',
                        stack: '继电器',
                        data: Object.values(statistics['继电器(入所)']),
                    },
                    {
                        name: '继电器(出所)',
                        color: ['#FF9F7F'],
                        type: 'bar',
                        stack: '继电器',
                        data: Object.values(statistics['继电器(出所)']),

                    },
                    {
                        name: '综合(入所)',
                        color: ['#FB7293'],
                        type: 'bar',
                        stack: '综合',
                        data: Object.values(statistics['综合(入所)']),
                    },
                    {
                        name: '综合(出所)',
                        color: ['#8378EA'],
                        type: 'bar',
                        stack: '综合',
                        data: Object.values(statistics['综合(出所)']),
                    },
                ]
            };

            echartsWarehouse.setOption(option);
            echartsWarehouse.on('click', function (params) {
                let direction = params.seriesName.match(/\(.+?\)/g).indexOf('(出所)') >= 0 ? 'OUT' : 'IN';

                let workAreaTypes = {'全部': '', '转辙机': 'pointSwitch', '继电器': 'replay', '综合': 'synthesize', '电源屏工区': 'powerSupplyPanel'};
                let current_work_area_type = workAreaTypes[params.seriesName.split('(')[0]];

                let queries = {
                    direction,
                    current_work_area: current_work_area_type,
                    updated_at: `${params.name}~${params.name}`,
                }
                let queryStr = $.param(queries);

                location.href = `/warehouse/report?${queryStr}`;
            });
        }

        /**
         * 选择现场车间
         */
        function fnSelectSceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(stations[$('#selSceneWorkshop').val()], function (index, item) {
                html += `<option value="${item}">${item}</option>`;
            });
            $('#selStation').html(html);
        }

        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

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
                startDate: "{{ $origin_at }}",
                endDate: "{{ $finish_at }}"
            });

            fnMakeWarehouseChart();
            fnSelectSceneWorkshop();
        });
    </script>
@endsection
