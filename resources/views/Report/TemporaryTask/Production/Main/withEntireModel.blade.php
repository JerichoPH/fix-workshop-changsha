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
            临时检修任务
            <small>报表 {{ $current_entire_model_name }}</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li>--}}
{{--                <a href="{{ url('report/temporaryTask/production/withCategory',$current_category_name) }}">--}}
{{--                    种类 {{ $current_category_name }}--}}
{{--                </a>--}}
{{--            </li>--}}
{{--            <li class="active">临时检修任务 {{ $current_entire_model_name }}</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--临时检修任务--}}
        <div class="row" id="divFixingAndFixed">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">临时检修任务</h3>
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsTemporaryTaskProductionMain" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">临时检修任务</h3>
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body form-horizontal">
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed text-sm">
                                <thead>
                                <tr>
                                    <th>种类/时间</th>
                                    <th></th>
                                    <th>合计</th>
                                    @for($i = 1; $i <= 12; $i++)
                                        <th>{{ $year.'-'.str_pad($i,2,'0',STR_PAD_LEFT) }}</th>
                                    @endfor
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($ttpm_mission_with_year['items'][$current_category_name] as $entire_model_name
                                => $mission)
                                    <tr
                                        onclick="location.href='{{ url('/report/temporaryTask/production/main/withEntireModel',$ttpm_entire_models_flip[$entire_model_name]) }}?year={{ request('year',date('Y')) }}'">
                                        <td>{{ $entire_model_name }}</td>
                                        <td>
                                            <p class="text-sm">
                                                任务<br>
                                                计划<br>
                                                检修总计
                                            </p>
                                        </td>
                                        <td>
                                            <p class="text-sm text-center">
                                                {{ $mission }}<br>
                                                ---<br>
                                                {{ isset($ttpm_fixed_with_year['items'][$current_category_name][$entire_model_name]) ? $ttpm_fixed_with_year['items'][$current_category_name][$entire_model_name] : 0 }}
                                            </p>
                                        </td>
                                        @for($i=0;$i<12;$i++)
                                            <td>
                                                <p class="text-sm text-center">
                                                    {{ isset($ttpm_mission_with_month[$i]['items'][$current_category_name][$entire_model_name]) ? $ttpm_mission_with_month[$i]['items'][$current_category_name][$entire_model_name] : 0 }}<br>
                                                    ---<br>
                                                    {{ isset($ttpm_fixed_with_month[$i]['items'][$current_category_name][$entire_model_name]) ? $ttpm_fixed_with_month[$i]['items'][$current_category_name][$entire_model_name] : 0 }}
                                                </p>
                                            </td>
                                        @endfor
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>合计</td>
                                    <td>
                                        <p class="text-sm">
                                            任务<br>
                                            计划<br>
                                            检修总计
                                        </p>
                                    </td>
                                    <td></td>
                                    @for($i = 0; $i < 12; $i++)
                                        <td>
                                            <p class="text-sm text-center">
                                                {{ $ttpm_mission_with_month[$i]['mission'] }}<br>
                                                ---<br>
                                                {{ $ttpm_fixed_with_month[$i]['fixed'] }}
                                            </p>
                                        </td>
                                    @endfor
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 生成本月检修任务与检修结果报告图
         */
        let fnMakePlanAndFinishChart = () => {
            let ttpmECharts = echarts.init(document.getElementById('echartsTemporaryTaskProductionMain'));
            let ttpmCategoryAsNames = [];
            let ttpmMissionWithYear = JSON.parse('{!! $ttpm_mission_with_year_as_json !!}')['items']['{{ $current_category_name }}'];
            let ttpmFixedWithYear = JSON.parse('{!! $ttpm_fixed_with_year_as_json !!}')['items']['{{ $current_category_name }}'];
            let currentCategoryName = "{{ $current_category_name }}";
            let currentCategoryUniqueCode = "{{ $current_category_unique_code }}";

            let missionWithCategoryAsValues = [];
            let planWithCategoryAsValues = [];
            let fixedWithCategoryAsValues = [];
            $.each(ttpmMissionWithYear,
                (idx, item) => {
                    ttpmCategoryAsNames.push(idx);
                    missionWithCategoryAsValues.push(item);  // 任务数
                    planWithCategoryAsValues.push(0);  // 暂时没有计划功能，所有计划都为0
                    fixedWithCategoryAsValues.push(ttpmFixedWithYear[idx] ? ttpmFixedWithYear[idx] : 0);  // 检修数
                }
            );
            let legendData = ['任务', '计划', '检修总计'];

            let option = {
                color: ['#003366', '#006699', '#4cabce'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow',
                        label: {show: true,},
                    },
                    formatter: params => {
                        let html = `${params[0].name}<br>`;
                        if (legendData.length === params.length) {
                            html += `${params[0].seriesName}：${params[0].value}<br>
${params[1].seriesName}：${params[1].value}<br>
任务完成率：${params[0].value === 0 ? 0 : parseFloat(((params[2].value / params[0].value) * 100).toFixed(2))}%<br>
计划完成率：${params[1].value === 0 ? 0 : parseFloat(((params[2].value / params[1].value) * 100).toFixed(2))}%<br>
检修总计：${params[2].value}`;
                        } else {
                            $.each(params.reverse(), function (idx, item) {
                                if (item.value > 0) html += `${item.seriesName}:${item.value}<br>`;
                            });
                        }
                        return html;
                    },
                },
                calculable: true,
                legend: {
                    data: legendData,
                    itemGap: 5
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true
                },
                xAxis: [{
                    type: 'category',
                    data: ttpmCategoryAsNames
                }],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 75,
                }, {
                    type: 'inside',
                    start: 94,
                    end: 100
                }, {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '65%',
                    showDataShadow: false,
                    left: '0%'
                }],
                series: [{
                    name: '任务',
                    type: 'bar',
                    data: missionWithCategoryAsValues
                }, {
                    name: '计划',
                    type: 'bar',
                    data: planWithCategoryAsValues
                }, {
                    name: '检修总计',
                    type: 'bar',
                    data: fixedWithCategoryAsValues
                }]
            };

            ttpmECharts.setOption(option);
            // 鼠标点击事件
            ttpmECharts.on('click', function (params) {
                let cycleFixCategories = JSON.parse('{!! $ttpm_categories_flip_as_json !!}');
                fnToCategory(cycleFixCategories[params.name]);
            });
        };

        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            //Date picker
            $('#date').daterangepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"]
                }
            });

            fnMakePlanAndFinishChart();  // 生成临时检修任务任务图表
        });

        /**
         * 跳转到以种为视角的页面
         * @param {string} categoryUniqueCode
         */
        function fnToCategory(categoryUniqueCode) {
            location.href = `/report/temporaryTask/production/main/withEntireModel/${categoryUniqueCode}?year={{$year}}`;
        }
    </script>
@endsection
