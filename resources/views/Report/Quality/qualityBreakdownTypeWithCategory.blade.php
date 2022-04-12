@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            质量报告 故障类型
            <small>
                {{ $categoryName }}
                @switch($qualityDateType)
                    @case('year')
                    年度
                    @break
                    @case('month')
                    月度
                    @break
                    @case('quarter')
                    季度
                    @break
                    @default
                    默认
                @endswitch
            </small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">质量报告</li>--}}
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
                                <h3 class="box-title">质量报告图 <small>故障类型</small></h3>
                            </div>
                            <div class="form-group col-sm-3 col-md-3">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'year' ? 'checked' : ''}} onclick="fnChangeQualityDateType('year')">年度</label>
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'quarter' ? 'checked' : ''}} onclick="fnChangeQualityDateType('quarter')">季度</label>
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'month' ? 'checked' : ''}} onclick="fnChangeQualityDateType('month')">月度</label>
                                    </div>
                                    <select id="qualityDate" class="form-control select2" style="width:100%;" onchange="fnSearch(this.value)">
                                        @if($qualityDateList)
                                            @foreach($qualityDateList as $date)
                                                <option value="{{$date}}" {{$qualityDate == $date ? 'selected' : ''}}>{{$date}}</option>
                                            @endforeach
                                        @else
                                            <option value="">尚无总结</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div id="echartsQuality" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--故障类型 供应商-->
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">质量报告 故障类型 {{ $categoryName }} <small>供应商</small></h3>
                <div class="box-tools pull-right">
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>供应商</th>
                        <th>故障类型</th>
                        <th>故障数量</th>
                    </tr>
                    </thead>
                    <tbody id="factoryTbody">

                    </tbody>
                </table>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let breakdownTypeWithFactories = JSON.parse(`{!! $breakdownTypeWithFactories !!}`);

        $(function () {
            if ($select2.length > 0) $select2.select2();
            fnMakeChart();
            fnMakeBreakdownTypeTable();
        });

        function fnChangeQualityDateType(type) {
            location.href = `{{ url('report/qualityBreakdownTypeWithCategory',$categoryUniqueCode) }}?qualityDateType=${type}`;
        }

        function fnSearch(qualityDate) {
            location.href = `{{ url('report/qualityBreakdownTypeWithCategory',$categoryUniqueCode) }}?qualityDateType={{ $qualityDateType }}&qualityDate=${qualityDate}`;
        }


        /**
         * 生成质量报告 故障类型 图
         */
        function fnMakeChart() {
            let xAxisData = [];
            let legendData = [];
            let types = {};

            $.each(breakdownTypeWithFactories, function (code, value) {
                xAxisData.push(value['name']);
                $.each(value['statistics'], function (typeName, typeCount) {
                    if ($.inArray(typeName, legendData) === -1) {
                        legendData.push(typeName);
                        types[typeName] = [];
                    }
                });
            });
            $.each(legendData, function (k, typeName) {
                let tmp = [];
                $.each(breakdownTypeWithFactories, function (code, item) {
                    if (item['statistics'].hasOwnProperty(typeName)) {
                        tmp.push(item['statistics'][typeName])
                    } else {
                        tmp.push(0);
                    }
                });
                types[typeName] = tmp;
            });

            let series = [];
            $.each(types, function (idx, item) {
                series.push({
                    name: idx,
                    type: 'bar',
                    stack: '总数',
                    data: item,
                    label: {
                        show: false,
                        position: 'bottom'
                    }
                });
            });

            let echartsQuality = echarts.init(document.getElementById('echartsQuality'));
            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { // 坐标轴指示器，坐标轴触发有效
                        type: 'shadow', // 默认为直线，可选为：'line' | 'shadow'
                    },
                    formatter: params => {
                        let html = `${params[0].name}<br>`;
                        $.each(params.reverse(), function (idx, item) {
                            if (item.value > 0) html += `${item.seriesName}:${item.value}<br>`;
                        });
                        return html;
                    },
                },
                legend: {data: legendData,},
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true,
                },
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 100,
                }, {
                    type: 'inside',
                    start: 94,
                    end: 100,
                }, {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '65%',
                    showDataShadow: false,
                    left: '0%',
                }],
                xAxis: [{
                    type: 'category',
                    data: xAxisData,
                }],
                yAxis: [{type: 'value',}],
                series: series,
            };
            echartsQuality.setOption(option);
            echartsQuality.on('click', function (params) {

            });
        }

        /**
         * 生成质量报告 故障类型
         */
        function fnMakeBreakdownTypeTable() {
            let html = ``;
            $.each(breakdownTypeWithFactories, function (code, value) {
                $.each(value['statistics'], function (typeName, typeCount) {
                    html += `<tr>
                    <td>${value['name']}</td>
                    <td>${typeName}</td>
                    <td>${typeCount}</td>
                    </tr>`
                });
            });
            $('#factoryTbody').html(html);
        }

    </script>
@endsection
