@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            超期使用
            <small>类型</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">超期使用 类型</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3 class="box-title">超期使用</h3>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div id="echartsScraped" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">超期使用</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>名称</th>
                        <th>设备总数</th>
                        <th>超期总数</th>
                        <th>超期率</th>
                    </tr>
                    </thead>
                    <tbody id="tbodyScraped"></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let scrapedDevicesAsKind = JSON.parse('{!! $scrapedDevicesAsKindAsJson !!}');
        let propertyDevicesAsKind = JSON.parse('{!! $propertyDevicesAsKindAsJson !!}');
        let entireModels = {};
        let entireModelsAsFlip = {};

        /**
         * 生成超期使用图表
         */
        function fnMakeScrapedChart() {
            let series = [{
                name: '设备总数',
                type: 'bar',
                data: [],
                label: {
                    show: true,
                    position: 'top',
                },
            }, {
                name: '超期使用',
                type: 'bar',
                data: [],
                label: {
                    show: true,
                    position: 'top',
                },
            }];
            let legendData = ['设备总数', '超期使用'];
            $.each(scrapedDevicesAsKind, function (emu, item) {
                entireModels[emu] = item['name'];
                entireModelsAsFlip[item['name']] = emu;
                series[0]['data'].push(propertyDevicesAsKind[emu]['statistics']['device_total'])
                series[1]['data'].push(item['statistics']['scraped_device_count']);
            });
            let echartsScraped = echarts.init(document.getElementById('echartsScraped'));
            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow',
                        label: {show: true,},
                    },
                    formatter: function (params) {
                        let html = `${params[0].name}<br>`;
                        if (legendData.length === params.length) {
                            html += `${params[0]['seriesName']}:${params[0]['value']}<br>
${params[1]['seriesName']}:${params[1]['value']}<br>
超期使用率：${params[0].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(2) : 0}%`;
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
                    containLabel: true,
                },
                xAxis: [{
                    type: 'category',
                    data: Object.values(entireModels),
                }],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 100,
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
                series: series
            };
            echartsScraped.setOption(option);
            echartsScraped.on('click', function (params) {
            });
        }

        /**
         * 生成超期使用表格
         */
        function fnMakeScrapedTable() {
            let html = '';
            $.each(scrapedDevicesAsKind, function (emu, item) {
                html += `<tr><td><a href="/report/scrapedWithEntireModel/${emu}">${entireModels[emu]}</a></td>`;
                html += `<td><a href="/report/propertySubModel?category_unique_code=${emu.substr(0, 3)}&entire_model_unique_code=${emu}">${propertyDevicesAsKind[emu]['statistics']['device_total']}</a></td>`;
                html += `<td><a href="/report/scrapedWithSubModel/${emu}">${item['statistics']['scraped_device_count']}</a></td>`;
                html += `<td>${((item['statistics']['scraped_device_count'] / propertyDevicesAsKind[emu]['statistics']['device_total']) * 100).toFixed(2)}%</td></tr>`;
            });
            $('#tbodyScraped').html(html);
        }

        $(function () {
            let select2 = $('.select2');
            if (select2.length > 0) select2.select2();

            $('#data').daterangepicker();

            fnMakeScrapedChart();  // 超期使用图标
            fnMakeScrapedTable();  // 超期使用表格
        });
    </script>
@endsection
