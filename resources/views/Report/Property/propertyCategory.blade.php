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
            报表
            <small>资产管理</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('report/property') }}">资产管理</a></li>--}}
{{--            <li class="active">资产管理(种类)</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--资产管理--}}
        <div class="row" id="divProperty">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">资产管理（供应商）</h3>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsProperty" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">资产管理（型号）</h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive table-responsive-lg table-responsive-sm">
                            <table class="table table-condensed table-hover">
                                <thead>
                                <tr id="theadProperty">
                                    <th>型号</th>

                                </tr>
                                </thead>
                                <tbody id="tbodyProperty"></tbody>
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
        let $theadProperty = $('#theadProperty');
        let $tbodyProperty = $('#tbodyProperty');
        let devicesAsModel = JSON.parse('{!! $devicesAsModelAsJson !!}');
        let models = {};
        let modelsAsFlip = {};
        let factories = {};
        let factoriesAsFlip = {};
        let tmpAsFactories = {};
        let series = [];

        /**
         * 准备资产管理需要的数据
         */
        function _preparedPropertyData() {
            // 基础数据（厂家、型号）
            $.each(devicesAsModel, function (mu, item) {
                models[mu] = item['name'];
                modelsAsFlip[item['name']] = mu;
                $.each(item['factories'], function (fu, fItem) {
                    if (!factories.hasOwnProperty(fu)) factories[fu] = fItem['name'];
                    if (!factoriesAsFlip.hasOwnProperty(fItem['name'])) factoriesAsFlip[fItem['name']] = fu;
                });
            });

            // 整理数据
            $.each(models, function (mu, mn) {
                $.each(devicesAsModel[mu]['factories'], function (fu, fItem) {
                    if (!tmpAsFactories.hasOwnProperty(fu)) tmpAsFactories[fu] = {};
                    if (!tmpAsFactories[fu].hasOwnProperty(mu)) tmpAsFactories[fu][mu] = 0;
                    tmpAsFactories[fu][mu] = fItem['statistics']['device_total'];
                });
            });
        }

        /**
         * 渲染资产管理图表
         */
        function fnMakePropertyChart() {
            // 准备图表数据
            $.each(factories, function (fu, fn) {
                let tmp = [];
                $.each(models, function (mu, cn) {
                    tmp.push(tmpAsFactories[fu].hasOwnProperty(mu) ? tmpAsFactories[fu][mu] : 0)
                });
                series.push(tmp ? {
                    name: fn,
                    type: 'bar',
                    stack: '总数',
                    data: tmp,
                    label: {
                        show: false,
                        position: 'bottom'
                    }
                } : {});
            });

            // 渲染数据
            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { // 坐标轴指示器，坐标轴触发有效
                        type: 'shadow', // 默认为直线，可选为：'line' | 'shadow'
                    },
                    formatter: params => {
                        let html = '';
                        $.each(params.reverse(), function (idx, item) {
                            if (item.value > 0) html += `${item.seriesName}:${item.value}<br>`;
                        });
                        return html;
                    },
                },
                legend: {data: Object.values(factories),},
                grid: {
                    left: '3%',
                    right: '4%',
                    top: '20%',
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
                    data: Object.values(models),
                }],
                yAxis: [{type: 'value',}],
                series: series,
            };

            let echartsProperty = echarts.init(document.getElementById('echartsProperty'));
            echartsProperty.setOption(option);
            echartsProperty.on('click', function (params) {
                // let url = `/report/propertySubModel?category_unique_code=${modelsAsFlip[params['name']].slice(0, 3)}&entire_model_unique_code=${modelsAsFlip[params['name']].slice(0, 5)}&sub_model_unique_code=${modelsAsFlip[params['name']]}&factory_unique_code=${factoriesAsFlip[params['seriesName']]}`;
                // location.href = url;
            });
        }

        /**
         * 渲染资产管理表格
         */
        function fnMakePropertyTable() {
            let html = '<td>型号</td>';
            $.each(factories, function (fu, fn) {
                html += `<td>${fn}</td>`;
            });
            $theadProperty.html(html + '<td>合计</td>');
            html = '';
            // 准备图表数据
            let tmp = {};
            $.each(factories, function (fu, fn) {
                $.each(models, function (mu, mn) {
                    if (!tmp.hasOwnProperty(mu)) tmp[mu] = {};
                    if (!tmp[mu].hasOwnProperty(fu)) tmp[mu][fu] = 0;
                    tmp[mu][fu] = tmpAsFactories.hasOwnProperty(fu)
                        ? (tmpAsFactories[fu].hasOwnProperty(mu) ? tmpAsFactories[fu][mu] : 0)
                        : 0;
                });
            });

            // 合计数据
            let totalAsRow = {};  // 合计行
            let totalAsCol = {};  // 合计列
            let total = 0;  // 总计
            $.each(tmp, function (mu, mItem) {
                $.each(mItem, function (fu, v) {
                    if (!totalAsRow.hasOwnProperty(mu)) totalAsRow[mu] = 0;
                    if (!totalAsCol.hasOwnProperty(fu)) totalAsCol[fu] = 0;
                    totalAsRow[mu] += v;
                    totalAsCol[fu] += v;
                    total += v;
                });
            });

            // 渲染主体数据
            $.each(tmp, function (mu, mItem) {
                html += `<tr><td>${models[mu]}</td>`;
                $.each(mItem, function (fu, v) {
                    let url = `/report/propertySubModel?category_unique_code=${mu.slice(0, 3)}&entire_model_unique_code=${mu.length >= 5 ? mu.slice(0, 5) : ''}&sub_model_unique_code=${mu.length >= 5 ? mu : ''}&factory_unique_code=${fu}`;
                    let span = v > 0 ? `<span><a href="${url}">${v}</a></span>` : `<span>${v}</span>`;
                    html += `<td>${span}</td>`;
                });
                html += `<td>${totalAsRow[mu]}</td>`;
                html += `</tr>`;
            });

            // 合计行
            html += '<tr><td>合计</td>';
            $.each(totalAsCol,function(k,v){
                html += `<td>${v}</td>`;
            });
            html += `<td>${total}</td></tr>`;

            $tbodyProperty.html(html);
        }

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
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    applyLabel: "确定",
                    cancelLabel: "取消",
                    fromLabel: "开始时间",
                    toLabel: "结束时间",
                    customRangeLabel: "自定义",
                    weekLabel: "W",
                }
            });

            _preparedPropertyData();  // 准备资产管理数据
            fnMakePropertyChart();  // 渲染资产管理图表
            fnMakePropertyTable();  // 渲染资产管理表格
        });
    </script>
@endsection
