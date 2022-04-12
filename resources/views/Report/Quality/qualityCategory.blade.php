@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            质量报告
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

        <!--统计图表-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-9 col-md-9">
                                <h3 class="box-title">质量报告图</h3>
                            </div>
                            <div class="form-group col-sm-3 col-md-3">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'year' ? 'checked' : ''}} onclick="fnChangeQualityDateType('year')">年度</label>
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'quarter' ? 'checked' : ''}} onclick="fnChangeQualityDateType('quarter')">季度</label>
                                        <label style="font-weight: normal;"><input type="radio" {{ $qualityDateType === 'month' ? 'checked' : ''}} onclick="fnChangeQualityDateType('month')">月度</label>
                                    </div>
                                    @switch($qualityDateType)
                                        @case('year')
                                        <select id="qualityYear" class="form-control select2" style="width:100%;" onchange="fnSearch(this.value)">
                                            @if($qualityYearList)
                                                @foreach($qualityYearList as $year)
                                                    <option value="{{ $year }}" {{ $qualityYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                @endforeach
                                            @else
                                                <option value="">尚无总结</option>
                                            @endif
                                        </select>
                                        @break
                                        @case('quarter')
                                        <select id="qualityYear" class="form-control select2" style="width:100%;" onchange="fnSearch(this.value)">
                                            @if($qualityYearList)
                                                @foreach($qualityYearList as $year)
                                                    <option value="{{ $year }}" {{ $qualityYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                @endforeach
                                            @else
                                                <option value="">尚无总结</option>
                                            @endif
                                        </select>
                                        <select id="qualityDate" class="form-control select2" style="width:100%;" onchange="fnSearch(this.value)">
                                            @if($qualityDateList)
                                                @foreach($qualityDateList as $date)
                                                    <option value="{{ $date }}" {{ $qualityDate == $date ? 'selected' : '' }}>{{ $date }}</option>
                                                @endforeach
                                            @else
                                                <option value="">尚无总结</option>
                                            @endif
                                        </select>
                                        @break
                                        @case('month')
                                        <select id="qualityDate" class="form-control select2" style="width:100%;" onchange="fnSearch(this.value)">
                                            @if($qualityDateList)
                                                @foreach($qualityDateList as $date)
                                                    <option value="{{ $date }}" {{ $qualityDate == $date ? 'selected' : '' }}>{{ $date }}</option>
                                                @endforeach
                                            @else
                                                <option value="">尚无总结</option>
                                            @endif
                                        </select>
                                        @break
                                    @endswitch
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

        <!--供应商-->
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">质量报告 {{ $categoryName }}</h3>
                <div class="box-tools pull-right">
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>供应商</th>
                        <th>设备</th>
                        <th>返修</th>
                        <th>返修率</th>
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
        let deviceWithFactories = JSON.parse(`{!! $deviceWithFactories !!}`);
        let breakdownWithFactories = JSON.parse(`{!! $breakdownWithFactories !!}`);
        let qualityDateType = `{!! $qualityDateType !!}`;
        let qualityDate = `{!! $qualityDate !!}`;
        let factories = {};
        let factoryNames = {};

        $(function () {
            if ($select2.length > 0) $select2.select2();
            fnInit();
            fnMakeQualityChart();
            fnMakeQualityCategoryTable();
        });

        function fnInit() {
            $.each(deviceWithFactories, function (code, value) {
                if (!factories.hasOwnProperty(code)) {
                    factories[code] = value['name'];
                    factoryNames[value['name']] = code;
                }
            });
            $.each(breakdownWithFactories, function (code, value) {
                if (!factories.hasOwnProperty(code)) {
                    factories[code] = value['name'];
                    factoryNames[value['name']] = code;
                }
            });

        }

        function fnChangeQualityDateType(type) {
            location.href = `{{ url('report/qualityCategory', $categoryUniqueCode) }}?qualityDateType=${type}&year={{ request('year', date('Y')) }}`;
        }

        function fnSearch(qualityDate) {
            location.href = `{{ url('report/qualityCategory', $categoryUniqueCode) }}?qualityDateType={{ $qualityDateType }}&qualityDate=${qualityDate}&year={{ request('year', date('Y')) }}`;
        }

        /**
         * 生成质量报告图
         */
        function fnMakeQualityChart() {
            let deviceSeries = [];
            let qualitySeries = [];
            let xAxisData = [];

            $.each(factories, function (code, name) {
                xAxisData.push(name);
                if (deviceWithFactories.hasOwnProperty(code)) {
                    deviceSeries.push(deviceWithFactories[code]['statistics']['device_total']);
                } else {
                    deviceSeries.push(0);
                }
                if (breakdownWithFactories.hasOwnProperty(code)) {
                    qualitySeries.push(breakdownWithFactories[code]['statistics']['breakdown_device_count']);
                } else {
                    qualitySeries.push(0);
                }
            });

            let legendData = ['设备', '返修'];

            let echartsQuality = echarts.init(document.getElementById('echartsQuality'));
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
返修率：${params[1].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(4) : 0}%`;
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
                    data: xAxisData
                }],
                yAxis: [{type: 'value'}],
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
                    height: '80%',
                    showDataShadow: false,
                    left: '93%'
                }],
                series: [{
                    name: '设备',
                    type: 'bar',
                    data: deviceSeries,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }, {
                    name: '返修',
                    type: 'bar',
                    data: qualitySeries,
                    label: {
                        show: true,
                        position: 'top',
                    },
                }]
            };
            echartsQuality.setOption(option);
            echartsQuality.on('click', function (params) {
                if (params.seriesName === '返修') {
                    {{--location.href = `{{ url('report/qualityEntireInstance') }}?categoryUniqueCode={{$categoryUniqueCode}}&factoryUniqueCode=${factoryNames[params.name]}&qualityDateType=${qualityDateType}&qualityDate=${qualityDate}`;--}}
                }
            });
        }

        /**
         * 生成质量报告表格
         */
        function fnMakeQualityCategoryTable() {
            let html = ``;
            $.each(factories, function (code, name) {
                let deviceCount = 0;
                let breakdownCount = 0;
                let rate = '-';
                if (deviceWithFactories.hasOwnProperty(code)) deviceCount = deviceWithFactories[code]['statistics']['device_total'];
                if (breakdownWithFactories.hasOwnProperty(code)) breakdownCount = breakdownWithFactories[code]['statistics']['breakdown_device_count'];
                if (deviceCount > 0 && breakdownCount > 0) rate = ((breakdownCount / deviceCount) * 100).toFixed(4) + '%';
                html += `<tr>`;
                html += `<td>${name}</td>`;
                if (deviceCount) {
                    html += `<td><a href="/report/qualityEntireInstance?categoryUniqueCode={{$categoryUniqueCode}}&factoryUniqueCode=${code}&qualityDateType=${qualityDateType}&qualityDate=${qualityDate}&type=device">${deviceCount}</a></td>`;
                } else {
                    html += `<td>${deviceCount}</td>`;
                }
                if (breakdownCount > 0) {
                    html += `<td><a href="/report/qualityEntireInstance?categoryUniqueCode={{$categoryUniqueCode}}&factoryUniqueCode=${code}&qualityDateType=${qualityDateType}&qualityDate=${qualityDate}&type=breakdown">${breakdownCount}</a></td>`;
                } else {
                    html += `<td>${breakdownCount}</td>`;
                }
                html += `<td>${rate}</td>`
                html += `</tr>`
            });

            $('#factoryTbody').html(html);
        }


    </script>
@endsection
