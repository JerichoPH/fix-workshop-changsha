@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            台账
            <small>种类</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">台账</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4 col-sm-4 col-xs-4"><h3 class="box-title">{{$sceneWorkshopName}}</h3></div>
                            <div class="col-md-8 col-sm-8 col-xs-8"></div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive">
                        <div class="chart" id="echartsDeviceStatus" style="height: 300px;"></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed" id="table">
                                <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>上道</th>
                                    <th>备品</th>
                                    <th>在修</th>
                                    <th>成品</th>
                                    <th>送修</th>
                                    <th>合计</th>
                                </tr>
                                </thead>
                                <tbody id="tbody"></tbody>
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
        let statistics = JSON.parse('{!! $statistics !!}');
        let categories = JSON.parse('{!! $categories !!}');
        let categoryNames = JSON.parse('{!! $categoryNames !!}');

        /**
         * 生成基础数据
         */
        let fnMakeDataWithCategory = () => {
            let tmp = {};
            $.each(categoryNames, (idx, item) => {
                tmp[item] = {installed: 0, installing: 0, fixing: 0, fixed: 0, return_factory: 0};
            });

            $.each(statistics, (idx, item) => {
                if (idx !== 'statistics') {
                    $.each(item, (k, v) => {
                        if (k !== 'statistics') {
                            tmp[k].installed += v.statistics.installed;
                            tmp[k].installing += v.statistics.installing;
                            tmp[k].fixing += v.statistics.fixing;
                            tmp[k].fixed += v.statistics.fixed;
                            tmp[k].return_factory += v.statistics.return_factory;
                        }
                    });
                }
            });

            return {categoryNames, categories, tmp};
        };

        /**
         * 生成台账表
         */
        let fnMakeMaintainTable = () => {
            let data = fnMakeDataWithCategory();
            let tmp = data.tmp;
            let totalRow = {
                installed: 0,
                fixed: 0,
                fixing: 0,
                return_factory: 0,
                installing: 0,
            };

            let totalColumns = 0;

            let html = '';
            $.each(tmp, (categoryName, item) => {
                let totalColumn = item.installed + item.fixed + item.fixing + item.return_factory + item.installing;
                let url = `{{url('report/sceneWorkshop2',$sceneWorkshopUniqueCode)}}?categoryUniqueCode=${categories[categoryName]}`;
                html += `<tr>`;
                html += `
<td>${categoryName}</td>
<td>${item.installed > 0 ? '<a href="' + url + '&status=上道">' + item.installed + '</a>' : item.installed}</td>
<td>${item.installing > 0 ? '<a href="' + url + '&status=备品">' + item.installing + '</a>' : item.installing}</td>
<td>${item.fixing > 0 ? '<a href="' + url + '&status=在修">' + item.fixing + '</a>' : item.fixing}</td>
<td>${item.fixed > 0 ? '<a href="' + url + '&status=成品">' + item.fixed + '</a>' : item.fixed}</td>
<td>${item.return_factory > 0 ? '<a href="' + url + '&status=送修">' + item.return_factory + '</a>' : item.return_factory}</td>
<td>${totalColumn}</td>`;
                html += '</tr>';
                totalRow.installed += item.installed;
                totalRow.fixed += item.fixed;
                totalRow.fixing += item.fixing;
                totalRow.return_factory += item.return_factory;
                totalRow.installing += item.installing;
                totalColumns += totalColumn;
            });
            html += `
<tr>
<td>合计</td>
<td>${totalRow.installed}</td>
<td>${totalRow.installing}</td>
<td>${totalRow.fixing}</td>
<td>${totalRow.fixed}</td>
<td>${totalRow.return_factory}</td>
<td>${totalColumns}</td>
</tr>
`;
            $('#tbody').html(html);
        };

        /**
         * 生成台账图表
         */
        let fnMakeMaintainChart = () => {
            // 基于准备好的dom，初始化echarts实例
            let myChart = echarts.init(document.getElementById('echartsDeviceStatus'));

            let data = fnMakeDataWithCategory();

            let categories = data.categories;
            let categoryNames = data.categoryNames;
            let tmp = data.tmp;

            let series = [
                {name: '上道', type: 'bar', data: [], label: {show: true, position: 'top',},},
                {name: '备品', type: 'bar', data: [], label: {show: true, position: 'top',},},
                {name: '在修', type: 'bar', data: [], label: {show: true, position: 'top',},},
                {name: '成品', type: 'bar', data: [], label: {show: true, position: 'top',},},
                {name: '送修', type: 'bar', data: [], label: {show: true, position: 'top',},},
            ];


            $.each(tmp, (idx, item) => {
                series[0]['data'].push(item.installed);
                series[1]['data'].push(item.installing);
                series[2]['data'].push(item.fixing);
                series[3]['data'].push(item.fixed);
                series[4]['data'].push(item.return_factory);
            });

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption({
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['上道', '备品', '在修', '成品', '送修'],
                    selected: JSON.parse('{!! $legendSelected !!}')
                },
                calculable: true,
                xAxis: {data: categoryNames},
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
                yAxis: {},
                series: series
            });

            myChart.on('click', function (params) {
                location.href = `{{url('report/sceneWorkshop2',$sceneWorkshopUniqueCode)}}?categoryUniqueCode=${categories[params.name]}&status={{request('status')}}`;
            });
        };

        $(function () {
            // 刷新站列表
            $('.select2').select2();

            fnMakeMaintainChart();  // 生成台账图
            fnMakeMaintainTable();  // 生成台账表
        });
    </script>
@endsection
