@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            周期修
            <small>型号</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="/report/cycleFixWithCategory/{{substr($currentEntireModelUniqueCode,0,3)}}?year={{$year}}"> 周期修(种类）</a></li>--}}
{{--            <li class="active">周期修（类型）</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--周期修图表-->
        <div class="row" style="display: block;">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">周期修任务</h3>
                        <div class="box-tools pull-right"><a href="?download=1" target="_blank"><i class="fa fa-download">&nbsp;</i>下载Excel</a></div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsCycleFix" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--周期修计划与周期修完成报表-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-md-9">
                                <h1 class="box-title">周期修任务、计划、轮修、全部检修情况报表</h1>
                            </div>
                            <div class="col-md-3">
                                <select
                                    name="entire_model_unique_code"
                                    id="selEntireModelUniqueCode"
                                    class="select2 pull-right"
                                    style="width: 100%;"
                                    onchange="location.href=`${this.value}?year={{$year}}`"
                                >
                                    @foreach($currentEntireModelsWithCategoryName as $entireModelUniqueCode => $entireModelName)
                                        <option value="{{$entireModelUniqueCode}}" {{$currentEntireModelUniqueCode == $entireModelUniqueCode ? 'selected' : ''}}>{{$entireModelName}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-hover table-condensed text-sm">
                            <thead>
                            <tr>
                                <th>检修工作项目<br>或轮修设备名称</th>
                                <th>轮修<br>周期</th>
                                <th>工区管内<br>器材总数</th>
                                <th>工区轮修<br>用器材数</th>
                                <th>工区年轮<br>修器材合计数</th>
                                @for($m=1;$m<13;$m++)
                                    <th>{{$m}}<br>月</th>
                                @endfor
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($cycleFixSubModels as $subModelUniqueCode => $subModelName)
                                <tr>
                                    <td>{{$subModelName}}</td>
                                    <td>{{$cycleFixValues[$subModelName]}}</td>
                                    <td>{{$cycleFixTotal[$subModelName]}}</td>
                                    <td>{{is_numeric($cycleFixFixedTotal[$subModelName]) ? $cycleFixFixedTotal[$subModelName] : 0}}</td>
                                    <td>
                                        <p class="text-sm">
                                            任务：{{$missionWithSubModelAsColumn[$subModelName]}}<br>
                                            计划：{{$planWithSubModelAsColumn[$subModelName]}}<br>
                                            检修总计：{{$realWithSubModelAsColumn[$subModelName]}}
                                        </p>
                                    </td>
                                    @for($m=1;$m<13;$m++)
                                        <td class="text-center">
                                            <?php $rowCount = 0?>
                                            <p class="text-sm">
                                                @foreach($missionWithSubModelAsMonthForStation[$m-1][$subModelName] as $stationName => $mission)
                                                    <?php $rowCount += $mission?>
                                                @endforeach
                                                <a href="/report/cycleFixWithEntireModelAsPlan/{{ substr($currentEntireModelUniqueCode,0,3) }}?date={{ $year }}-{{ str_pad($m,2,'0',STR_PAD_LEFT) }}">{{ $rowCount }}</a><br>
                                                {{ $planWithSubModelAsMonth[$m-1][$subModelName] }}<br>
                                                {{ $realWithSubModelAsMonth[$m-1][$subModelName] }}
                                            </p>
                                            @if(!empty($missionWithSubModelAsMonthForStation[$m-1][$subModelName]))
                                                <p>
                                                <hr>
                                                @foreach($missionWithSubModelAsMonthForStation[$m-1][$subModelName] as $stationName => $mission)
                                                    {{ $stationName }}：{{ $mission }}<br>
                                                    @endforeach
                                                    </p>
                                                    @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
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
            let cycleFixECharts = echarts.init(document.getElementById('echartsCycleFix'));
            let missionWithSubModelAsNames = JSON.parse('{!! $missionWithSubModelAsNames !!}');

            let planWithSubModelAsValues = [];
            $.each(JSON.parse('{!! json_encode($planWithSubModelAsColumn) !!}'),
                (idx, item) => {
                    planWithSubModelAsValues.push(item);
                }
            );

            let missionWithSubModelAsValues = [];
            $.each(JSON.parse('{!! json_encode($missionWithSubModelAsColumn) !!}'),
                (idx, item) => {
                    missionWithSubModelAsValues.push(item);
                }
            );

            let fixedWithSubModelAsValues = [];
            $.each(JSON.parse('{!! json_encode($fixedWithSubModelAsColumn) !!}'),
                (idx, item) => {
                    fixedWithSubModelAsValues.push(item);
                }
            );

            let realWithSubModelAsValues = [];
            $.each(JSON.parse('{!! json_encode($realWithSubModelAsColumn) !!}'),
                (idx, item) => {
                    realWithSubModelAsValues.push(item);
                }
            );
            let legendData = ['任务', '计划', '检修总计'];

            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
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
                xAxis: [
                    {
                        type: 'category',
                        data: missionWithSubModelAsNames
                    }
                ],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 50,
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
                    data: missionWithSubModelAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }, {
                    name: '计划',
                    type: 'bar',
                    data: planWithSubModelAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }, {
                    name: '检修总计',
                    type: 'bar',
                    data: realWithSubModelAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }]
            };

            cycleFixECharts.setOption(option);

            if (self != top) {setTimeout(() => {
                cycleFixECharts.resize()
            }, 700);
            }
            // 鼠标点击事件
            cycleFixECharts.on('click', function (params) {
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

            fnMakePlanAndFinishChart();
        });
    </script>
@endsection
