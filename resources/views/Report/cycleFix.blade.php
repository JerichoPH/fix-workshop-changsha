@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            报表
            <small>周期修</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">周期修</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--周期修--}}
        <div class="row" id="divFixingAndFixed">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">周期修任务</h3>
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsCycleFix" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="{{request('tab',null) == null || request('tab',null) == 'tabCategory' ? 'active' : ''}}"><a href="#tabCategory" data-toggle="tab">种类统计</a></li>
                        <li class="{{request('tab',null) == 'tabAccount' ? 'active' : ''}}"><a href="#tabAccount" data-toggle="tab">人员统计</a></li>
                    </ul>
                    <div class="tab-content">
                        {{--周期修任务、计划、轮修、全部检修情况报表--}}
                        <div class="{{request('tab',null) == null || request('tab',null) == 'tabCategory' ? 'active' : ''}} tab-pane" id="tabCategory">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>周期修任务、计划、轮修、全部检修情况报表<small>&nbsp;全部种类</small></h4>
                                </div>
                                <div class="col-md-4">
                                    <a href="?date={{date('Y-m')}}&download=1" target="_blank" class="pull-right"><i class="fa fa-download">&nbsp;</i>下载Excel</a>
                                </div>
                            </div>
                            <hr>
                            <table class="table table-hover table-condensed text-sm">
                                <thead>
                                <tr>
                                    <th>种类/时间</th>
                                    <th></th>
                                    <th>合计</th>
                                    @for($i = 1; $i <= 12; $i++)
                                        <th>{{$year.'-'.str_pad($i,2,'0',STR_PAD_LEFT)}}</th>
                                    @endfor
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($cycleFixCategories as $categoryUniqueCode => $categoryName)
                                    <tr onclick="location.href=`/report/cycleFixWithCategory/{{$categoryUniqueCode}}?year={{$year}}`">
                                        <td>{{$categoryName}}</td>
                                        <td>
                                            <p class="text-sm">
                                                任务<br>
                                                计划<br>
                                                检修总计
                                            </p>
                                        </td>
                                        {{--合计列--}}
                                        <td>
                                            <p class="text-sm text-center">
                                                {{$missionWithCategoryAsColumn[$categoryName]}}<br>
                                                {{$planWithCategoryAsColumn[$categoryName]}}<br/>
                                                {{$realWithCategoryAsColumn[$categoryName]}}
                                            </p>
                                        </td>
                                        @for($i = 0; $i < 12; $i++)
                                            <td>
                                                <p class="text-sm text-center">
                                                    {{$missionWithCategoryAsMonth[$i][$categoryName]}}<br>
                                                    {{$planWithCategoryAsMonth[$i][$categoryName]['count']}}<br>
                                                    {{$realWithCategoryAsMonth[$i][$categoryName]}}
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
                                                {{$missionWithCategoryAsRow[$i]}}<br>
                                                {{$planWithCategoryAsRow[$i]}}<br>
                                                {{$realWithCategoryAsRow[$i]}}
                                            </p>
                                        </td>
                                    @endfor
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        {{--人员工作量统计--}}
                        <div class="{{request('tab',null) == 'tabAccount' ? 'active' : ''}} tab-pane" id="tabAccount">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="{{request('accountTab',null) == null || request('accountTab',null) == 'tabPointSwitch' ? 'active' : ''}}"><a href="#tabPointSwitch" data-toggle="tab">转辙机工区</a></li>
                                    <li class="{{request('accountTab',null) == 'tabRelay' ? 'active' : ''}}"><a href="#tabRelay" data-toggle="tab">继电器工区</a></li>
                                    <li class="{{request('accountTab',null) == 'tabSynthesize' ? 'active' : ''}}"><a href="#tabSynthesize" data-toggle="tab">综合工区</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h4>人员工作量统计</h4>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="?date={{date('Y-m')}}&download=2" target="_blank" class="pull-right"><i class="fa fa-download">&nbsp;</i>下载Excel</a>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="{{request('accountTab',null) == null || request('accountTab',null) == 'tabPointSwitch' ? 'active' : ''}} tab-pane table-responsive" id="tabPointSwitch">
                                        <table class="table table-hover table-condensed text-sm">
                                            <thead>
                                            <tr>
                                                <th>人员/时间</th>
                                                <th></th>
                                                <th>合计</th>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <th class="text-center">{{$year.'-'.str_pad($i,2,'0',STR_PAD_LEFT)}}</th>
                                                @endfor
                                            </tr>
                                            </thead>
                                            <tbody>
                                            {{--转辙机--}}
                                            @foreach($accounts[1] as $accountNickname)
                                                <tr>
                                                    <td>{{$accountNickname}}</td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            计划<br>
                                                            完成
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            {{$statisticsAsAccount[$accountNickname]['plan']}}<br>
                                                            {{$statisticsAsAccount[$accountNickname]['real']}}<br>
                                                        </p>
                                                    </td>
                                                    @for($i=0;$i<12;$i++)
                                                        <td>
                                                            <p class="text-sm text-center">
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['plan']}}<br>
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['real']}}<br>
                                                            </p>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="{{request('accountTab',null) == 'tabRelay' ? 'active' : ''}} tab-pane  table-responsive" id="tabRelay">
                                        <table class="table table-hover table-condensed text-sm">
                                            <thead>
                                            <tr>
                                                <th>人员/时间</th>
                                                <th></th>
                                                <th>合计</th>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <th class="text-center">{{$year.'-'.str_pad($i,2,'0',STR_PAD_LEFT)}}</th>
                                                @endfor
                                            </tr>
                                            </thead>
                                            <tbody>
                                            {{--继电器工区--}}
                                            @foreach($accounts[2] as $accountNickname)
                                                <tr>
                                                    <td>{{$accountNickname}}</td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            计划<br>
                                                            完成
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            {{$statisticsAsAccount[$accountNickname]['plan']}}<br>
                                                            {{$statisticsAsAccount[$accountNickname]['real']}}<br>
                                                        </p>
                                                    </td>
                                                    @for($i=0;$i<12;$i++)
                                                        <td>
                                                            <p class="text-sm text-center">
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['plan']}}<br>
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['real']}}<br>
                                                            </p>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="{{request('accountTab',null) == 'tabSynthesize' ? 'active' : ''}} tab-pane table-responsive" id="tabSynthesize">
                                        <table class="table table-hover table-condensed text-sm">
                                            <thead>
                                            <tr>
                                                <th>人员/时间</th>
                                                <th></th>
                                                <th>合计</th>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <th class="text-center">{{$year.'-'.str_pad($i,2,'0',STR_PAD_LEFT)}}</th>
                                                @endfor
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($accounts[3] as $accountNickname)
                                                <tr>
                                                    <td>{{$accountNickname}}</td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            计划<br>
                                                            完成
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <p class="text-sm text-center">
                                                            {{$statisticsAsAccount[$accountNickname]['plan']}}<br>
                                                            {{$statisticsAsAccount[$accountNickname]['real']}}<br>
                                                        </p>
                                                    </td>
                                                    @for($i=0;$i<12;$i++)
                                                        <td>
                                                            <p class="text-sm text-center">
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['plan']}}<br>
                                                                {{$statisticsWithMonthAsAccount[$i][$accountNickname]['real']}}<br>
                                                            </p>
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
            let cycleFixCategoryAsNames = JSON.parse('{!! $cycleFixCategoryAsNames !!}');

            let planWithCategoryAsValues = [];
            $.each(JSON.parse('{!! json_encode($planWithCategoryAsColumn) !!}'),
                function (idx, item) {
                    planWithCategoryAsValues.push(item);
                }
            );

            let missionWithCategoryAsValues = [];
            $.each(JSON.parse('{!! json_encode($missionWithCategoryAsColumn) !!}'),
                (idx, item) => {
                    missionWithCategoryAsValues.push(item);
                }
            );

            let fixedWithCategoryAsValues = [];
            $.each(JSON.parse('{!! json_encode($fixedWithCategoryAsColumn) !!}'),
                function (idx, item) {
                    fixedWithCategoryAsValues.push(item);
                }
            );

            let realWithCategoryAsValues = [];
            $.each(JSON.parse('{!! json_encode($realWithCategoryAsColumn) !!}'),
                function (idx, item) {
                    realWithCategoryAsValues.push(item);
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
                xAxis: [{
                    type: 'category',
                    data: cycleFixCategoryAsNames
                }],
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
                    data: missionWithCategoryAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }, {
                    name: '计划',
                    type: 'bar',
                    data: planWithCategoryAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }, {
                    name: '检修总计',
                    type: 'bar',
                    data: realWithCategoryAsValues,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }]
            };

            cycleFixECharts.setOption(option);
            // if (self != top) {setTimeout(() => {
            //     cycleFixECharts.hide()
            // }, 700);
            // }
            // 鼠标点击事件
            cycleFixECharts.on('click', function (params) {
                let cycleFixCategories = JSON.parse('{!! collect($cycleFixCategories)->flip()->toJson() !!}');
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

            self != top?$('#divFixingAndFixed').hide():fnMakePlanAndFinishChart();  // 生成周期修任务图表


        });

        /**
         * 跳转到以种为视角的页面
         * @param {string} categoryUniqueCode
         */
        function fnToCategory(categoryUniqueCode) {
            location.href = `/report/cycleFixWithCategory/${categoryUniqueCode}?year={{$year}}`;
        }
    </script>
@endsection
