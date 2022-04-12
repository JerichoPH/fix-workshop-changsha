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
            一次过检
            <small>{{$current_category_name}} 季度</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="/report/ripeQuarter?year={{request('year',date('Y'))}}&quarter={{request('quarter','1季度')}}">一次过检 全部种类</a></li>--}}
{{--            <li class="active">一次过检 {{$curent_category_name}}</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')

        {{--统计图表--}}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-9 col-md-9">
                                <h3 class="box-title">一次过检图</h3>
                            </div>
                            <div class="form-group col-sm-3 col-md-3">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;"><input type="radio" name="date_type" onclick="fnCheckDateType('Year')">年度</label>
                                        <label style="font-weight: normal;"><input type="radio" name="date_type" onclick="fnCheckDateType('Quarter')" checked>季度</label>
                                        <label style="font-weight: normal;"><input type="radio" name="date_type" onclick="fnCheckDateType('Month')">月度</label>
                                    </div>
                                    <select
                                        id="selYear"
                                        name="year"
                                        class="form-control select2"
                                        style="width:100%;"
                                        onchange="fnSearch(this.value)"
                                    >
                                        @if($years)
                                            @foreach($years as $year)
                                                <option value="{{$year}}" {{request('year',date('Y')) == $year ? 'selected' : ''}}>{{$year}}年</option>
                                            @endforeach
                                        @else
                                            <option value="">尚无总结</option>
                                        @endif
                                    </select>
                                    <select
                                        name="quarter"
                                        id="selQuarter"
                                        class="form-control select2"
                                        style="width:100%;"
                                        onchange="fnSearch(this.value)"
                                    >
                                        @if($quarters)
                                            @foreach($quarters  as $quarter)
                                                <option value="{{$quarter}}" {{request('quarter','1季度') == $quarter ? 'selected':''}}>第{{$quarter}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div id="echartsRipe" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{--统计报表--}}
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="{{request('tab',null) == null || request('tab',null) == 'tabCategory' ? 'active' : ''}}"><a href="#tabCategory" data-toggle="tab">种类统计</a></li>
                        <li class="{{request('tab',null) == 'tabAccount' ? 'active' : ''}}"><a href="#tabAccount" data-toggle="tab">人员统计</a></li>
                    </ul>
                    <div class="tab-content">
                        {{--种类统计--}}
                        <div class="{{request('tab',null) == null || request('tab',null) == 'tabCategory' ? 'active' : ''}} tab-pane" id="tabCategory">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>一次过检<small>&nbsp;{{$current_category_name}}</small></h4>
                                </div>
                                <div class="col-md-4"></div>
                            </div>
                            <hr>
                            <table class="table table-hover table-condensed" id="table">
                                <thead>
                                <tr>
                                    <th>种类名称</th>
                                    <th>检修</th>
                                    <th>一次过检</th>
                                    <th>一次过检率</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($statistics_with_entire_models as $entire_model_name => $item)
                                    <tr>
                                        <td><a href="javascript:" onclick="fnToEntireModel('{{$entire_model_name}}')">{{$entire_model_name}}</a></td>
                                        <td>
                                            @if($item['fixed']>0)
                                                <a href="javascript:" onclick="fnToQualityEntireInstance('{{$entire_model_name}}')">{{$item['fixed']}}</a>
                                            @else
                                                {{$item['fixed']}}
                                            @endif
                                        </td>
                                        <td>
                                            @if($item['ripe'])
                                                <a href="javascript:" onclick="fnToRipeEntireInstance('{{$entire_model_name}}')">{{$item['ripe']}}</a>
                                            @else
                                                {{$item['ripe']}}
                                            @endif
                                        </td>
                                        <td>{{$item['rate']}}%</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{--人员统计--}}
                        <div class="{{request('tab',null) == 'tabAccount' ? 'active' : ''}} tab-pane" id="tabAccount">
                            <h4>一次过检<small>&nbsp;{{$current_work_area_name}}</small></h4>
                            <hr>
                            <table class="table table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th>人员</th>
                                    <th>检修</th>
                                    <th>一次过检</th>
                                    <th>一次过检率</th>
                                </tr>
                                </thead>
                                <tbody>
                                {{--转辙机--}}
                                @foreach($statistics_with_account as $account_nickname => $item)
                                    <tr>
                                        <td>{{$account_nickname}}</td>
                                        <td>{{$item['fixed']}}</td>
                                        <td>{{$item['ripe']}}</td>
                                        <td>{{$item['rate']}}%</td>
                                    </tr>
                                @endforeach
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
        let $select2 = $('.select2');
        let categories = JSON.parse('{!! $entire_models_as_json !!}');
        let entireModels = JSON.parse('{!! $entire_models_as_json !!}');

        /**
         * 生成一次过检图
         */
        function fnMakeRipeChart() {
            let statistics = JSON.parse('{!! $statistics_with_entire_models_as_json !!}');

            let ripeEntireModelNames = [];
            let ripeFixedSeries = [];
            let ripeRipeSeries = [];

            $.each(statistics, (index, item) => {
                ripeEntireModelNames.push(index);
                ripeFixedSeries.push(item['fixed']);
                ripeRipeSeries.push(item['ripe']);
            });
            let legendData = ['检修', '一次过检'];

            let echartsQuality = echarts.init(document.getElementById('echartsRipe'));
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
返修率：${params[1].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(3) : 0}%`;
                        } else {
                            $.each(params.reverse(), function (idx, item) {
                                if (item.value > 0) html += `${item.seriesName}:${item.value}<br>`;
                            });
                        }
                        return html;
                    }
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
                    containLabel: true,
                },
                xAxis: [{
                    type: 'category',
                    data: ripeEntireModelNames
                }],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 100
                }, {
                    type: 'inside',
                    start: 94,
                    end: 100
                }, {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '80%',
                    showDataShadow: false,
                    left: '93%'
                }],
                series: [{
                    name: '检修',
                    type: 'bar',
                    data: ripeFixedSeries
                }, {
                    name: '一次过检',
                    type: 'bar',
                    data: ripeRipeSeries
                }]
            };
            echartsQuality.setOption(option);
            echartsQuality.on('click', function (params) {
                fnToEntireModel(params.name);
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnMakeRipeChart();
        });

        /**
         * 切换日期类型
         * @param dateType
         */
        function fnCheckDateType(dateType) {
            location.href = `/report/ripeCategory${dateType}/{{$current_category_unique_code}}?year={{request('year',date('Y'))}}&quarter={{request('quarter','1季度')}}`;
        }

        /**
         * 搜索
         * @param {string} quarter
         */
        function fnSearch(quarter) {
            location.href = `?year=${$('#selYear').val()}&quarter=${quarter}`;
        }

        /**
         * 跳转到种类页面
         * @param entireModelName
         */
        function fnToEntireModel(entireModelName) {
            location.href = `/report/ripeEntireModelQuarter/${entireModels[entireModelName]}?year=${$('#selYear').val()}&quarter=${$('#selQuarter').val()}`;
        }

        /**
         * 跳转到质量报告列表
         * @param {string} entireModelName
         */
        function fnToQualityEntireInstance(entireModelName) {
            let entireModelUniqueCode = entireModels[entireModelName];
            let categoryUniqueCode = entireModelUniqueCode.substr(0, 3);
            location.href = `/report/qualityEntireInstance?category_unique_code=${categoryUniqueCode}&entire_model_unique_code=${entireModelUniqueCode}&year=${$('#selYear').val()}&type=quarter&quarter=${$('#selQuarter').val()}`;
        }

        /**
         * 跳转到一次过检列表
         * @param {string} entireModelName
         */
        function fnToRipeEntireInstance(entireModelName) {
            let entireModelUniqueCode = entireModels[entireModelName];
            let categoryUniqueCode = entireModelUniqueCode.substr(0, 3);
            location.href = `/report/ripeEntireInstance?category_unique_code=${categoryUniqueCode}&entire_model_unique_code=${entireModelUniqueCode}&year=${$('#selYear').val()}&type=quarter&quarter=${$('#selQuarter').val()}`;
        }
    </script>
@endsection
