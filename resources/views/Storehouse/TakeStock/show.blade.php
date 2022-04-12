@extends('Layout.index')
@section('style')

@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{ $takeStock->name }}
            盘点详情
            <small></small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">盘点详情</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row" id="divProperty">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">盘点差异图表（种类）</h3>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsTakeStock" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        {{--当前种类差异表--}}
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="{{request('tab',null) == null || request('tab',null) == 'tabDiff' ? 'active' : ''}}"><a href="#tabDiff" data-toggle="tab">差异表</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="{{request('tab',null) == null || request('tab',null) == 'tabDiff' ? 'active' : ''}} tab-pane" id="tabDiff">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>差异表&nbsp;<small id="title"></small></h4>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 table-responsive">
                                    <table class="table table-hover table-condensed text-sm" id="tableDiff">
                                        <thead>
                                        <tr>
                                            <th style="width: 10%">型号</th>
                                            <th style="width: 10%">盘亏</th>
                                            <th style="width: 10%">盘盈</th>
                                            <th style="width: 10%">库存</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
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
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let takeStockInstances = JSON.parse(`{!! $takeStockInstances !!}`);
        let xAxisData = [];
        let categories = {};
        let zdata = [];
        let fdata = [];
        let stock_data = [];
        let real_stock_data = [];

        $(function () {
            fnInit();
            fnMakeTackStockChart();  // 生成盘点图表
            let category_name = Object.keys(categories)[0];
            showList(categories[category_name], category_name);
        });

        /**
         * 初始化数据
         */
        function fnInit() {
            let tmpData = {};
            if (takeStockInstances.length > 0) {
                $.each(takeStockInstances, function (k, value) {
                    if (xAxisData.indexOf(value.category_name) === -1) {
                        xAxisData.push(value.category_name);
                        categories[value.category_name] = value.category_unique_code;
                    }
                    tmpData[value.category_name + value.difference] = value.count;
                });
                $.each(xAxisData, function (k, category_name) {
                    let jia = tmpData.hasOwnProperty(category_name + '+') ? tmpData[category_name + '+'] : 0;
                    let jian = tmpData.hasOwnProperty(category_name + '-') ? tmpData[category_name + '-'] : 0;
                    let dengyu = tmpData.hasOwnProperty(category_name + '=') ? tmpData[category_name + '='] : 0;
                    stock_data.push(jian + dengyu);
                    real_stock_data.push(dengyu + jia);
                    zdata.push(jia);
                    fdata.push(-jian);
                });
            }
        }

        /**
         * 生成盘点图表
         */
        function fnMakeTackStockChart() {
            let echartsTakeStock = echarts.init(document.getElementById('echartsTakeStock'));

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
                        $.each(params, function (idx, item) {
                            if (item.value > 0) {
                                html += `${item.seriesName}:${item.value}<br>`;
                            } else if (item.value < 0) {
                                html += `${item.seriesName}:${-item.value}<br>`;
                            }
                        });
                        return html;
                    },
                },
                calculable: true,
                legend: {
                    data: ['库存', '盘点', '盘亏', '盘盈'],
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
                    data: xAxisData
                }],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
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
                    height: '65%',
                    showDataShadow: false,
                    left: '0%'
                }],
                series: [
                    {
                        name: '库存',
                        type: 'bar',
                        label: {
                            show: true,
                            position: 'top'
                        },
                        data: stock_data
                    }, {
                        name: '盘点',
                        type: 'bar',
                        label: {
                            show: true,
                            position: 'top'
                        },
                        data: real_stock_data
                    }, {
                        name: '盘亏',
                        type: 'bar',
                        label: {
                            show: true,
                            position: 'top'
                        },
                        data: fdata
                    }, {
                        name: '盘盈',
                        type: 'bar',
                        label: {
                            show: true,
                            position: 'top'
                        },
                        data: zdata
                    }
                ]
            };

            echartsTakeStock.setOption(option);
            echartsTakeStock.on('click', function (params) {
                showList(categories[params.name], params.name)
            });
        }

        /**
         * 展示列表
         * @param category_unique_code
         * @param category_name
         */
        function showList(category_unique_code, category_name) {
            let loading = layer.load(2, {shade: false});
            $.ajax({
                url: `{{url('storehouse/takeStock/showWithSubModel',$currentTakeStockUniqueCode)}}/${category_unique_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(` success:`, response);
                    if (response.status === 200) {
                        $("#title").text(category_name);
                        if (response.data.length > 0) {
                            let subModels = {};
                            let subTmpData = {};
                            $.each(response.data, function (k, value) {
                                if (!subModels.hasOwnProperty(value.sub_model_unique_code)) subModels[value.sub_model_unique_code] = value.sub_model_name;
                                subTmpData[value.sub_model_name + value.difference] = value.count;
                            });
                            let html = ``;
                            $.each(subModels, function (sub_model_unique_code, sub_model_name) {
                                html += `<tr><td><a href="{{url('storehouse/takeStock/showWithMaterial',$currentTakeStockUniqueCode)}}/${sub_model_unique_code}">${sub_model_name}</a></td>`;
                                let stock = 0;

                                if (subTmpData.hasOwnProperty(sub_model_name + '-')) {
                                    html += `<td>${subTmpData[sub_model_name + '-']}</td>`;
                                    stock += subTmpData[sub_model_name + '-'];
                                } else {
                                    html += `<td>0</td>`;
                                }
                                if (subTmpData.hasOwnProperty(sub_model_name + '+')) {
                                    html += `<td style="color: red">${subTmpData[sub_model_name + '+']}</td>`;
                                } else {
                                    html += `<td>0</td>`;
                                }
                                if (subTmpData.hasOwnProperty(sub_model_name + '=')) {
                                    stock += subTmpData[sub_model_name + '='];
                                }
                                html += `<td>${stock}</td>`;
                                html += '</tr>';
                            });
                            $("#tableDiff tbody").html(html)
                        }
                    } else {
                        alert(response.message);
                    }
                    layer.close(loading);
                },
                error: error => {
                    console.log(` fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

    </script>
@endsection
