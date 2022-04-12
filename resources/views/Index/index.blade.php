@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        {{--快捷入口&动态统计--}}
        <div class="row">
            {{--快捷入库--}}
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="bot-title">快捷入口</h3>
                    </div>
                    <div class="box-body" style="height: 350px;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-aqua" onclick="location.href='/query'" style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-search"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">设备查询</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 0"></div>
                                        </div>
                                        <span class="progress-description"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-green"
                                     onclick="location.href='{{url('report/cycleFixWithEntireModelAsPlan/S03')}}'"
                                     style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-wrench"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">周期修任务</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description" style="font-size: 12px;">
                                        <!--统计-->
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                {{--<div class="info-box bg-yellow" onclick="location.href='{{url('entire/instance')}}'"--}}
                                <div class="info-box bg-yellow" onclick="location.href='{{url('query')}}'"
                                     style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-briefcase"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">设备列表</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description" style="font-size: 12px;">
                                        <!--统计-->
                                    </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-teal" onclick="location.href='{{url('warehouse/report')}}'"
                                     style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-exchange"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">出入所单</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description" style="font-size: 12px;">
                                        <!--统计-->
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-red" onclick="location.href='{{url('storehouse/index/material')}}'"
                                     style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-cube"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">仓储设备</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                        <!--统计-->
                                    </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-purple" onclick="location.href='{{ url('storehouse/index/in') }}'"
                                     style="cursor: pointer;">
                                    <span class="info-box-icon"><i class="fa fa-caret-square-o-right"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text" style="font-size: 24px;">设备入库</span>
                                        <span class="info-box-number"></span>

                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description" style="font-size: 12px;">
                                        <!--统计-->
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{--动态统计--}}
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-6 col-md-6">
                                <h3>设备动态统计</h3>
                            </div>
                            <div class="form-group col-sm-6 col-md-6">
                                <select id="selDeviceDynamicByCategoryUniqueCode" class="form-control select2"
                                        style="width:100%;" onchange="fnMakeDeviceDynamicAsStatusChart(this.value)">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive" style="height: 350px;">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <div class="chart" id="echartsDeviceDynamicStatus"
                                     style="height: 300px; position: relative;"></div>
                            </div>
                            <div class="col-sm-4 col-md-4">
                                <p>&nbsp;</p>
                                <br>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusTotal">总数：</p>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusInstalled">上道使用：</p>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusInstalling">现场备品：</p>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusFixed">成品：</p>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusFixing">待修：</p>
                                <p style="font-size: 16px;" id="pDeviceDynamicStatusSendRepair">送修中：</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--出入所-->
        <div class="row" id="divWarehouse">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3>出入所</h3>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsWarehouse" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--资产管理-->
        <div class="row" id="divProperty">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3>资产管理</h3>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsProperty" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--周期修-->
        <div class="row" id="divCycleFix">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>
                                    周期修&nbsp;
                                    <a href="javascript:"
                                       onclick="location.href=`{{url('report/cycleFix')}}?date=${$('#selCycleFixDate').val()}`">详情</a>
                                </h3>
                            </div>
                            <div class="col-sm-4 col-md-4">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;">
                                            <input type="radio" value="year" id="rdoCycleFixYear"
                                                   onclick="fnChangeCycleFixDateType('year')"> 年视图
                                        </label>&nbsp;&nbsp;
                                        <label style="font-weight: normal;">
                                            <input type="radio" value="year" id="rdoCycleFixMonth"
                                                   onclick="fnChangeCycleFixDateType('month')"> 月视图
                                        </label>
                                    </div>
                                    <select id="selCycleFixDate" class="form-control select2" style="width:100%;"
                                            onchange="fnChangeCycleFixDate(this.value)"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsCycleFix" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--质量报告-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>
                                    质量报告&nbsp;
                                    <a href="javascript:"
                                       onclick="location.href=`{{url('report/quality')}}?year={{request('year',date('Y'))}}`;">详情</a>
                                </h3>
                            </div>
                            <div class="form-group col-sm-4 col-md-4">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;">
                                            <input type="radio" value="year" id="rdoQualityYear"
                                                   onclick="fnChangeQualityDateType('year')"> 年视图
                                        </label>&nbsp;&nbsp;
                                        <label style="font-weight: normal;">
                                            <input type="radio" value="year" id="rdoQualityMonth"
                                                   onclick="fnChangeQualityDateType('month')"> 月视图
                                        </label>
                                    </div>
                                    <select id="selQualityDate" class="form-control select2" style="width:100%;"
                                            onchange="fnChangeQualityDate(this.value)"></select>
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

        <!--一次过检-->
        <div class="row" style="display: none">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>
                                    一次过检&nbsp;
                                    <a href="javascript:"
                                       onclick="location.href=`{{url('report/ripeYear')}}?year={{request('year',date('Y'))}}`;">详情</a>
                                </h3>
                            </div>
                            <div class="form-group col-sm-4 col-md-4">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;">
                                            <input type="radio" name="ripe_date_type" value="year"
                                                   {{$ripeDateType === 'year' ? 'checked' : ''}}
                                                   onclick="fnChangeRipeDateType('year')">
                                            &nbsp;年视图
                                        </label>&nbsp;&nbsp;
                                        <label style="font-weight: normal;">
                                            <input type="radio" name="ripe_date_type" value="year"
                                                   {{$ripeDateType === 'month' ? 'checked' : ''}}
                                                   onclick="fnChangeRipeDateType('month')">
                                            &nbsp;月视图
                                        </label>
                                    </div>
                                    <select id="selRipeDate" name="ripe_date" class="form-control select2"
                                            style="width:100%;" onchange="fnCurrentPageWithRipe()">
                                        @if($ripeDateList)
                                            @foreach($ripeDateList as $ripeDate)
                                                <option value="{{$ripeDate}}"
                                                    {{request('ripeDate',\Carbon\Carbon::now()->format('Y-m')) == $ripeDate ? 'selected' : ''}}>
                                                    {{$ripeDate}}</option>
                                            @endforeach
                                        @else
                                            <option value="">尚无总结</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div class="chart" id="echartsRipe" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--超期使用-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>
                                    超期使用&nbsp;
                                    <a href="javascript:" onclick="location.href=`{{ url('report/scraped') }}`;">详情</a>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div id="echartsScraped" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--盘点统计-->
        <div class="row" style="display:{{ env ('ORGANIZATION_CODE') == 'B050' ? 'none' : 'block' }};">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>盘点统计</h3>
                            </div>
                            <div class="form-group col-sm-4 col-md-4">
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="box box-solid">
                            <div class="box-body material-message">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>时间</th>
                                        <th>位置名称</th>
                                        <th>种类</th>
                                        <th>库存</th>
                                        <th>实盘</th>
                                        <th>盘亏</th>
                                        <th>盘盈</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($takeStocks as $takeStockUniqueCode=>$takeStock)
                                        @foreach($takeStock['categories'] as $categoryUniqueCode=>$category)
                                            <tr>
                                                @if(!empty($category['takeStockUpdateAt']))
                                                    <td rowspan="{{ count($takeStock['categories']) }}"
                                                        style="vertical-align: middle;">{{ $category['takeStockUpdateAt'] ?? '' }}
                                                    </td>
                                                @endif
                                                @if(!empty($category['takeStockName']))
                                                    <td rowspan="{{ count($takeStock['categories']) }}"
                                                        style="vertical-align: middle;">{{ $category['takeStockName'] ?? '' }}</td>
                                                @endif
                                                <td>{{ $category['categoryName'] }}</td>
                                                <td>{{ $category['-'] + $category['='] }}</td>
                                                <td>{{ $category['='] + $category['+'] }}</td>
                                                <td>{{ $category['-'] }}</td>
                                                <td>{{ $category['+'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--台账-->
        <div class="row" style="display: block;">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>台账</h3>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive">
                        @foreach($sceneWorkshops as $scu => $scn)
                            <div class="col-md-4">
                                <a href="javascript:" style="color: black;">
                                    <div class="box box-success">
                                        <div class="box-header">
                                            <i class="fa fa-text-width"></i>
                                            <h3 class="box-title">{{ $scn }}
                                                <small id="spanSceneWorkshopTitle_{{ $scu }}"></small>
                                            </h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="chart" id="echartsMaintain_{{ $scu }}" style="height: 300px;"></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let select2 = $('.select2');
        let propertyDevicesAsKind = null;
        let qualityYears = [];
        let qualityMonths = [];
        let cycleFixYears = [];
        let cycleFixMonths = [];
        let $selDeviceDynamicByCategoryUniqueCode = $('#selDeviceDynamicByCategoryUniqueCode');
        let $selQualityDate = $('#selQualityDate');
        let $rdoQualityYear = $('#rdoQualityYear');
        let $rdoQualityMonth = $('#rdoQualityMonth');
        let $selCycleFixDate = $('#selCycleFixDate');
        let $rdoCycleFixYear = $('#rdoCycleFixYear');
        let $rdoCycleFixMonth = $('#rdoCycleFixMonth');
        let sceneWorkshops = JSON.parse('{!! $sceneWorkshopsAsJson !!}');

        /**
         * 动态统计
         */
        function fnMakeDeviceDynamicAsStatusChart(categoryUniqueCode = null) {
            let echartsDeviceDynamicStatus = echarts.init(document.getElementById('echartsDeviceDynamicStatus'));
            echartsDeviceDynamicStatus.showLoading();
            $.ajax({
                url: `{{ url('reportData') }}`,
                type: 'get',
                data: {type: 'deviceDynamicAsStatus'},
                async: true,
                success: function (res) {
                    {{--console.log('reportData:deviceDynamicAsStatus', `{{ url('reportData') }} success:`, res);--}}
                    let {statistics, statuses} = res.data;
                    let tmp = categoryUniqueCode ? statistics[categoryUniqueCode].subs : statistics;
                    let categories = {};
                    let categoriesAsFlip = {};
                    let statusesAsFlip = {};

                    // 状态对照
                    $.each(statuses, function (u, n) {
                        statusesAsFlip[n] = u;
                    });

                    // 种类对照
                    let html = '<option value="">全部</option>';
                    $.each(statistics, function (cu, cItem) {
                        html += `<option value="${cu}" ${categoryUniqueCode === cu ? 'selected' : ''}>${cItem['name']}</option>`;
                        $selDeviceDynamicByCategoryUniqueCode.html(html);
                        categories[cu] = cItem['name'];
                        categoriesAsFlip[cItem['name']] = cu;
                    });

                    // 重排统计
                    let seriesData = [
                        {name: '上道使用', value: 0},
                        {name: '现场备品', value: 0},
                        {name: '成品', value: 0},
                        {name: '待修', value: 0},
                        {name: '送修中', value: 0},
                    ];
                    let total = 0;
                    $.each(tmp, function (cu, cItem) {
                        total += cItem['statistics']['device_total'];
                        seriesData[0]['value'] += cItem['statistics'].hasOwnProperty('INSTALLED') ? cItem['statistics']['INSTALLED'] : 0;
                        seriesData[1]['value'] += cItem['statistics'].hasOwnProperty('INSTALLING') ? cItem['statistics']['INSTALLING'] : 0;
                        seriesData[2]['value'] += cItem['statistics'].hasOwnProperty('FIXED') ? cItem['statistics']['FIXED'] : 0;
                        seriesData[3]['value'] += cItem['statistics'].hasOwnProperty('FIXING') ? cItem['statistics']['FIXING'] : 0;
                        seriesData[4]['value'] += cItem['statistics'].hasOwnProperty('SEND_REPAIR') ? cItem['statistics']['SEND_REPAIR'] : 0;
                    });
                    $('#pDeviceDynamicStatusTotal').text(`总数：${total}`);
                    $('#pDeviceDynamicStatusInstalled').text(`上道使用：${seriesData[0]['value']}`);
                    $('#pDeviceDynamicStatusInstalling').text(`现场备品：${seriesData[1]['value']}`);
                    $('#pDeviceDynamicStatusFixed').text(`成品：${seriesData[2]['value']}`);
                    $('#pDeviceDynamicStatusFixing').text(`待修：${seriesData[3]['value']}`);
                    $('#pDeviceDynamicStatusSendRepair').text(`送修中：${seriesData[4]['value']}`);


                    let option = {
                        color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA', '#4B1378',],
                        tooltip: {trigger: 'item',},
                        legend: {
                            orient: 'vertical',
                            x: 'left',
                            data: ['上道使用', '现场备品', '成品', '待修', '送修中'],
                        },
                        series: [{
                            name: '设备动态统计',
                            type: 'pie',
                            radius: ['50%', '70%'],
                            avoidLabelOverlap: false,
                            label: {
                                normal: {
                                    show: false,
                                    position: 'center',
                                },
                                emphasis: {
                                    show: true,
                                    textStyle: {
                                        fontSize: '30',
                                        fontWeight: 'bold',
                                    }
                                }
                            },
                            labelLine: {normal: {show: true}},
                            data: seriesData,
                        }]
                    };

                    echartsDeviceDynamicStatus.setOption(option);
                    echartsDeviceDynamicStatus.on('click', function (params) {
                        let statuses = {
                            "成品": "FIXED",
                            "上道": "INSTALLED",
                            "备品": "INSTALLING",
                            "待修": "FIXING",
                            "送修中": "SEND_REPAIR",
                        };

                        let cu = $("#selDeviceDynamicByCategoryUniqueCode").val() ? $("#selDeviceDynamicByCategoryUniqueCode").val() : '';
                        let urlParams = {
                            category_unique_code: cu,
                            status_unique_code: statuses[params.name],
                        };
                        location.href = `{{ url('/entire/instance') }}?${$.param(urlParams)}`;
                    });
                    echartsDeviceDynamicStatus.hideLoading();
                },
                error: function (err) {
                    console.log(`{{ url('reportData') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 出入所
         */
        function fnMakeWarehouseReportChart() {
            let echartsWarehouse = echarts.init(document.getElementById('echartsWarehouse'));
            echartsWarehouse.showLoading();
            $.ajax({
                url: `{{ url('reportData') }}`,
                type: 'get',
                data: {type: 'warehouseReport'},
                async: true,
                success: function (res) {
                    {{--console.log('出入所', `{{ url('reportData') }} success:`, res);--}}
                    let {dateList, statistics, paragraph_code: paragraphCode} = res['data'];

                    let in1 = [];
                    let in2 = [];
                    let in3 = [];
                    let in4 = [];
                    let out1 = [];
                    let out2 = [];
                    let out3 = [];
                    let out4 = [];

                    for (let idx in statistics['转辙机(入所)']) in1.push(statistics['转辙机(入所)'][idx]);
                    for (let idx in statistics['转辙机(出所)']) out1.push(statistics['转辙机(出所)'][idx]);
                    for (let idx in statistics['继电器(入所)']) in2.push(statistics['继电器(入所)'][idx]);
                    for (let idx in statistics['继电器(出所)']) out2.push(statistics['继电器(出所)'][idx]);
                    for (let idx in statistics['综合(入所)']) in3.push(statistics['综合(入所)'][idx]);
                    for (let idx in statistics['综合(出所)']) out3.push(statistics['综合(出所)'][idx]);
                    for (let idx in statistics['电源屏(入所)']) in4.push(statistics['电源屏(入所)'][idx]);
                    for (let idx in statistics['电源屏(出所)']) out4.push(statistics['电源屏(出所)'][idx]);

                    let option = {
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                                type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                            }
                        },
                        legend: {
                            data: ['转辙机(入所)', '转辙机(出所)', '继电器(入所)', '继电器(出所)', '综合(入所)', '综合(出所)', '电源屏(入所)', '电源屏(出所)']
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        xAxis: [{
                            type: 'category',
                            data: dateList
                        }
                        ],
                        yAxis: [{type: 'value'}],
                        series: [
                            {
                                name: '转辙机(入所)',
                                color: ['#37A2DA'],
                                type: 'bar',
                                stack: '转辙机',
                                data: in1,
                            }, {
                                name: '转辙机(出所)',
                                color: ['#9FE6B8'],
                                type: 'bar',
                                stack: '转辙机',
                                data: out1,
                            }, {
                                name: '继电器(入所)',
                                color: ['#FFDB5C'],
                                type: 'bar',
                                stack: '继电器',
                                data: in2,
                            }, {
                                name: '继电器(出所)',
                                color: ['#FF9F7F'],
                                type: 'bar',
                                stack: '继电器',
                                data: out2,

                            }, {
                                name: '综合(入所)',
                                color: ['#FB7293'],
                                type: 'bar',
                                stack: '综合',
                                data: in3,
                            }, {
                                name: '综合(出所)',
                                color: ['#8378EA'],
                                type: 'bar',
                                stack: '综合',
                                data: out3,
                            }, {
                                name: '电源屏(入所)',
                                color: ['#0956AE'],
                                type: 'bar',
                                stack: '电源屏',
                                data: in4,
                            }, {
                                name: '电源屏(出所)',
                                color: ['#AE0956'],
                                type: 'bar',
                                stack: '电源屏',
                                data: out4,
                            },]
                    };
                    echartsWarehouse.setOption(option);
                    echartsWarehouse.on('click', function (params) {
                        let direction = params.seriesName.match(/\(.+?\)/g).indexOf('(出所)') >= 0 ? 'OUT' : 'IN';

                        let workAreaTypes = {'全部': '', '转辙机': 'pointSwitch', '继电器': 'replay', '综合': 'synthesize', '电源屏': 'powerSupplyPanel'};
                        let current_work_area_type = workAreaTypes[params.seriesName.split('(')[0]];

                        let queries = {
                            direction,
                            current_work_area: current_work_area_type,
                            updated_at: `${params.name}~${params.name}`,
                        }
                        let queryStr = $.param(queries);

                        location.href = `/warehouse/report?${queryStr}`;
                    });

                    echartsWarehouse.hideLoading();
                },
                error: function (err) {
                    console.log(`{{ url('reportData') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 资产管理
         */
        function fnMakePropertyChart() {
            let echartsProperty = echarts.init(document.getElementById('echartsProperty'));
            echartsProperty.showLoading();
            $.ajax({
                url: `{{ url('reportData') }}`,
                type: 'get',
                data: {type: 'property'},
                async: true,
                success: function (res) {
                    {{--console.log(`{{ url('reportData') }} success:`, res);--}}
                        propertyDevicesAsKind = res['data']['propertyDevicesAsKind'];
                    let categories = {};
                    let categoriesAsFlip = {};
                    let factories = {};
                    let tmpAsFactories = {};

                    // 基础数据（厂家、种类）
                    $.each(propertyDevicesAsKind, function (cu, item) {
                        categories[cu] = item['name'];
                        categoriesAsFlip[item['name']] = [cu];
                        $.each(item['factories'], function (fu, fItem) {
                            if (!factories.hasOwnProperty(fu)) factories[fu] = fItem['name'];
                        });
                    });

                    // 整理数据
                    $.each(categories, function (cu, cn) {
                        $.each(propertyDevicesAsKind[cu]['factories'], function (fu, fItem) {
                            if (!tmpAsFactories.hasOwnProperty(fu)) tmpAsFactories[fu] = {};
                            if (!tmpAsFactories[fu].hasOwnProperty(cu)) tmpAsFactories[fu][cu] = 0;
                            tmpAsFactories[fu][cu] = fItem['statistics']['device_total'];
                        });
                    });

                    // 填充数据
                    let series = [];
                    $.each(factories, function (fu, fn) {
                        let tmp = [];
                        $.each(categories, function (cu, cn) {
                            tmp.push(tmpAsFactories[fu].hasOwnProperty(cu) ? tmpAsFactories[fu][cu] : 0)
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

                    let factoriesNames = [];
                    $.each(factories, function (factoryUniqueCode, factoryName) {
                        factoriesNames.push(factoryName);
                    });

                    let categoriesNames = [];
                    $.each(categories, function (categoryUniqueCode, categoryName) {
                        categoriesNames.push(categoryName);
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
                        legend: {data: factoriesNames,},
                        grid: {
                            left: '3%',
                            right: '4%',
                            top: '30%',
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
                            data: categoriesNames,
                        }],
                        yAxis: [{type: 'value',}],
                        series: series,
                    };
                    echartsProperty.setOption(option);
                    echartsProperty.on('click', function (params) {
                        location.href = `{{url('report/propertyCategory')}}/${categoriesAsFlip[params['name']]}`;
                    });
                    echartsProperty.hideLoading();

                    fnMakeQualityChart(); // 质量报告 5
                    fnMakeScrapedChart(); // 超期使用 7
                },
                error: function (err) {
                    console.log(`{{ url('reportData') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 周期修
         */
        function fnMakeCycleFixChart(dateType = null, date = null) {
            let cycleFixECharts = echarts.init(document.getElementById('echartsCycleFix'));
            let dateHtml = '';
            cycleFixECharts.showLoading();
            $.ajax({
                url: `{{ url('reportData')  }}`,
                type: 'get',
                data: {
                    type: 'cycleFix',
                    dateType,
                    date,
                },
                async: true,
                success: function (res) {
                    {{--console.log(`{{ url('reportData')  }} success:`, res);--}}
                    let {cycleFixDate, statistics, missions} = res['data'];

                    cycleFixYears = res['data']['cycleFixYears'];
                    cycleFixMonths = res['data']['cycleFixMonths'];
                    let legendData = ['任务', '计划', '检修'];
                    let categories = {};
                    let categoriesAsFlip = {};
                    let tmp = {'mission': {}, 'plan': {}, 'fixed': {}};
                    $.each(statistics, (wai, workArea) => {
                        $.each(workArea['categories'], (cu, category) => {
                            categories[cu] = category['name'];
                            categoriesAsFlip[category['name']] = cu;
                            if (!tmp['mission'].hasOwnProperty(cu)) tmp['mission'][cu] = 0;
                            tmp['mission'][cu] += missions.hasOwnProperty(cu) ? parseInt(missions[cu]['aggregate']) : 0;
                            if (!tmp['plan'].hasOwnProperty(cu)) tmp['plan'][cu] = 0;
                            tmp['plan'][cu] += category['statistics']['plan_device_count'];
                            if (!tmp['fixed'].hasOwnProperty(cu)) tmp['fixed'][cu] = 0;
                            tmp['fixed'][cu] += category['statistics']['fixed_device_count'];
                        });
                    });

                    let missionTmp = [];
                    let planTmp = [];
                    let fixedTmp = [];
                    let categoryTmp = [];
                    for (let idx in tmp['mission']) {
                        missionTmp.push(tmp['mission'][idx]);
                    }
                    for (let idx in tmp['plan']) {
                        planTmp.push(tmp['plan'][idx]);
                    }
                    for (let idx in tmp['fixed']) {
                        planTmp.push(tmp['fixed'][idx]);
                    }
                    for (let idx in categories) {
                        categoryTmp.push(categories[idx]);
                    }

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
                                    html += `${params[0].seriesName}：${params[0].value}<br>`;
                                    html += `${params[1].seriesName}：${params[1].value}<br>`;
                                    html += `任务完成率：${params[0].value === 0 ? params[2].value / 1 : parseFloat(((params[2].value / params[0].value) * 100).toFixed(2))}%<br>`;
                                    html += `计划完成率：${params[1].value === 0 ? params[2].value / 1 : parseFloat(((params[2].value / params[1].value) * 100).toFixed(2))}%<br>`;
                                    html += `检修总计：${params[2].value}`;
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
                            itemGap: 5,
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '15%',
                            containLabel: true,
                        },
                        xAxis: [{
                            type: 'category',
                            data: categoryTmp,
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
                            left: '93%',
                        }],
                        series: [{
                            name: '任务',
                            type: 'bar',
                            data: missionTmp,
                            label: {
                                show: true,
                                position: 'top'
                            },
                        }, {
                            name: '计划',
                            type: 'bar',
                            data: planTmp,
                            label: {
                                show: true,
                                position: 'top'
                            },
                        }, {
                            name: '检修',
                            type: 'bar',
                            data: fixedTmp,
                            label: {
                                show: true,
                                position: 'top'
                            },
                        }]
                    };
                    cycleFixECharts.setOption(option);
                    cycleFixECharts.hideLoading();

                    switch (dateType) {
                        default:
                        case 'year':
                            if (cycleFixYears) {
                                for (let idx in cycleFixYears) {
                                    dateHtml += `<option value="${cycleFixYears[idx]}" ${cycleFixDate === cycleFixYears[idx] ? 'selected' : ''}>${cycleFixYears[idx]}</option>`;
                                }
                            }
                            $selCycleFixDate.html(dateHtml);

                            $rdoCycleFixYear.prop('checked', true);
                            $rdoCycleFixMonth.prop('checked', false);
                            break;
                        case 'month':
                            if (cycleFixMonths) {
                                for (let idx in cycleFixMonths) {
                                    dateHtml += `<option value="${cycleFixMonths[idx]}" ${cycleFixDate === cycleFixMonths[idx] ? 'selected' : ''}>${cycleFixMonths[idx]}</option>`;
                                }
                            }
                            $selCycleFixDate.html(dateHtml);

                            $rdoCycleFixYear.prop('checked', false);
                            $rdoCycleFixMonth.prop('checked', true);
                            break;
                    }

                    cycleFixECharts.on('click', function (params) {
                        let categoryUniqueCode = categoriesAsFlip[params['name']] ? categoriesAsFlip[params['name']] : '';
                        if (categoryUniqueCode) {
                            location.href = `/report/cycleFixWithCategory/${categoryUniqueCode}`;
                        }
                    });
                },
                error: function (err) {
                    {{--console.log(`{{ url('reportData')  }} fail:`, err);--}}
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 质量报告
         */
        function fnMakeQualityChart(dateType = null, date = null) {
            let qualityECharts = echarts.init(document.getElementById('echartsQuality'));
            qualityECharts.showLoading();
            $.ajax({
                url: `{{ url('reportData') }}`,
                type: 'get',
                data: {
                    type: 'quality',
                    dateType: dateType ? dateType : 'year',
                    date: date ? date : '{{ date('Y') }}',
                },
                async: true,
                success: function (res) {
                    {{--console.log(`{{ url('reportData') }} success:`, res);--}}
                    let {qualities, qualityDevices, qualityDate} = res['data'];
                    qualityYears = res['data']['qualityYears'];
                    qualityMonths = res['data']['qualityMonths'];
                    let factories = {};
                    let qualityDeviceSeries = [];
                    let qualitySeries = [];
                    let factoryNames = [];

                    // 时间类型选择
                    let html = '';
                    switch (dateType) {
                        default:
                        case 'year':
                            if (qualityYears) {
                                $.each(qualityYears, function (k, date) {
                                    html += `<option value="${date}" ${qualityDate === date ? 'selected' : ''}>${date}</option>`;
                                });
                            } else {
                                html = '<option value="">尚无报告</option>';
                            }
                            $selQualityDate.html(html);

                            $rdoQualityYear.prop('checked', true);
                            $rdoQualityMonth.prop('checked', false);
                            break;
                        case 'month':
                            if (qualityMonths) {
                                $.each(qualityMonths, function (k, date) {
                                    html += `<option value="${date}" ${qualityDate === date ? 'selected' : ''}>${date}</option>`;
                                });
                            } else {
                                html = '<option value="">尚无报告</option>';
                            }
                            $selQualityDate.html(html);

                            $rdoQualityYear.prop('checked', false);
                            $rdoQualityMonth.prop('checked', true);
                            break;
                    }

                    $.each(qualities, function (factory_code, value) {
                        if (!factories.hasOwnProperty(factory_code)) factories[factory_code] = value['name'];
                    });
                    $.each(qualityDevices, function (factory_code, item) {
                        if (!factories.hasOwnProperty(factory_code)) factories[factory_code] = item['name'];
                    });

                    $.each(factories, function (code, name) {
                        factoryNames.push(name);
                        if (qualities.hasOwnProperty(code)) {
                            qualitySeries.push(qualities[code]['statistics']['breakdown_device_count']);
                        } else {
                            qualitySeries.push(0);
                        }
                        if (qualityDevices.hasOwnProperty(code)) {
                            qualityDeviceSeries.push(qualityDevices[code]['statistics']['device_total']);
                        } else {
                            qualityDeviceSeries.push(0);
                        }
                    });

                    let legendData = ['设备', '返修'];
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
                                    html += `${params[0].seriesName}：${params[0].value}<br>`;
                                    html += `${params[1].seriesName}：${params[1].value}<br>`;
                                    html += `返修率：${params[0].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(4) : 0}%`;
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
                            itemGap: 5,
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '15%',
                            containLabel: true,
                        },
                        xAxis: [{
                            type: 'category',
                            data: factoryNames,
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
                            left: '93%',
                        }],
                        series: [{
                            name: '设备',
                            type: 'bar',
                            data: qualityDeviceSeries,
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
                    qualityECharts.setOption(option);
                    qualityECharts.on('click', function (params) {
                        location.href = `{{ url('report/quality') }}?year={{ request('year',date('Y')) }}`;
                    });
                    qualityECharts.hideLoading();
                },
                error: function (err) {
                    {{--console.log(`{{ url('reportData') }} fail:`, err);--}}
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 一次过检 ❌
         */
        function fnMakeRipeChart() {
            let echartsRipe = echarts.init(document.getElementById('echartsRipe'));
            echartsRipe.showLoading();
            let ripeStatistics = JSON.parse('{!! $ripeStatistics !!}');
            let ripeCategories = JSON.parse('{!! $ripeCategoriesAsJson !!}');

            let ripeCategoryNamesSeries = [];
            let ripeFixedSeries = [];
            let ripeRipeSeries = [];
            $.each(ripeStatistics, (categoryName, item) => {
                ripeCategoryNamesSeries.push(categoryName);
                ripeFixedSeries.push(item["fixed"]);
                ripeRipeSeries.push(item["ripe"]);
            });
            let legendData = ['设备', '一次过检'];

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
                            html += `${params[0].seriesName}：${params[0].value}<br>`;
                            html += `${params[1].seriesName}：${params[1].value}<br>`;
                            html += `检修率：${params[0].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(2) : 0}%`;
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
                    itemGap: 5,
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true,
                },
                xAxis: [{
                    type: 'category',
                    data: ripeCategoryNamesSeries,
                }],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 100,
                }, {
                    type: 'inside',
                    start: 100,
                    end: 100,
                }, {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '80%',
                    showDataShadow: false,
                    left: '93%',
                }],
                series: [{
                    name: '设备',
                    type: 'bar',
                    data: ripeFixedSeries,
                }, {
                    name: '一次过检',
                    type: 'bar',
                    data: ripeRipeSeries,
                },]
            };
            echartsRipe.setOption(option);
            echartsRipe.hideLoading();
            echartsRipe.on('click', function (params) {
                location.href = `{{ url('report/ripeCategoryYear') }}/${ripeCategories[params.name]}?year={{ request('year',date('Y')) }}`;
            });
        }

        /**
         * 超期使用
         */
        function fnMakeScrapedChart() {
            let echartsScraped = echarts.init(document.getElementById('echartsScraped'));
            echartsScraped.showLoading();
            $.ajax({
                url: `{{ url('reportData') }}`,
                type: 'get',
                data: {type: 'scraped'},
                async: true,
                success: function (res) {
                    {{--console.log(`{{ url('reportData') }} success:`, res);--}}
                    let {scrapedDevicesAsKind} = res['data'];
                    let series = [
                        {
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
                    let categories = {};
                    let categoriesAsFlip = {};
                    let legendData = ['设备总数', '超期使用'];
                    $.each(scrapedDevicesAsKind, function (cu, item) {
                        categories[cu] = item['name'];
                        categoriesAsFlip[item['name']] = cu;
                        let deviceTotal = 0;
                        if (propertyDevicesAsKind.hasOwnProperty(cu)) {
                            if (propertyDevicesAsKind[cu].hasOwnProperty('statistics')) {
                                series[0]['data'].push(propertyDevicesAsKind[cu]['statistics']['device_total'] ? propertyDevicesAsKind[cu]['statistics']['device_total'] : 0)
                            } else {
                                series[0]['data'].push(0);
                            }
                        } else {
                            series[0]['data'].push(0);
                        }
                        series[1]['data'].push(item['statistics']['scraped_device_count']);
                    });

                    let categorieNames = [];
                    $.each(categories, function (categoryUniqueCode, categoryName) {
                        categorieNames.push(categoryName);
                    });

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
                                    html += `${params[0]['seriesName']}:${params[0]['value']}<br>`;
                                    html += `${params[1]['seriesName']}:${params[1]['value']}<br>`;
                                    html += `超期使用率：${params[0].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(2) : 0}%`;
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
                            data: categorieNames,
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
                        location.href = `{{url('report/scrapedWithCategory')}}/${categoriesAsFlip[params['name']]}`;
                    });
                    echartsScraped.hideLoading();
                },
                error: function (err) {
                    {{--console.log(`{{ url('reportData') }} fail:`, err);--}}
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 台账
         */
        function fnMakeMaintainChart() {
            $.each(sceneWorkshops, function (scu, scn) {
                let echartsMaintain = echarts.init(document.getElementById(`echartsMaintain_${scu}`));

                echartsMaintain.showLoading();
                $.ajax({
                    url: `{{ url('reportData') }}`,
                    type: 'get',
                    data: {type: 'maintain', sceneWorkshopUniqueCode: scu},
                    async: true,
                    success: function (res) {
                        {{--console.log(`maintain {{ url('reportData') }}${scu} success:`, res);--}}
                        let {maintain} = res.data;

                        $(`#spanSceneWorkshopTitle_${scu}`).text(`总数：${maintain['statistics']['device_total']}`);

                        let makeOption = function (name, installed, installing, transferOut, transferIn) {
                            return {
                                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                                title: {
                                    text: '',
                                    subtext: '',
                                    x: 'center'
                                },
                                tooltip: {
                                    trigger: 'item',
                                    formatter: "{a} <br/>{b} : {c} ({d}%)"
                                },
                                legend: {
                                    orient: 'vertical',
                                    x: 'left',
                                    data: [`上道使用：${installed}`, `现场备品：${installing}`, ]
                                },
                                toolbox: {
                                    show: true,
                                    feature: {
                                        magicType: {
                                            show: true,
                                            type: ['pie', 'funnel'],
                                            option: {
                                                funnel: {
                                                    x: '25%',
                                                    width: '50%',
                                                    funnelAlign: 'left',
                                                    max: 1548
                                                }
                                            }
                                        },
                                    }
                                },
                                calculable: true,
                                series: [{
                                    name: name,
                                    type: 'pie',
                                    radius: '50%',
                                    center: ['60%', '60%'],
                                    data: [{
                                        value: installed,
                                        name: `上道使用：${installed}`,
                                    }, {
                                        value: installing,
                                        name: `现场备品：${installing}`,
                                    }, ]
                                }]
                            };
                        };

                        echartsMaintain.setOption(makeOption(
                            scn,
                            maintain['statistics'].hasOwnProperty('INSTALLED') ? maintain['statistics']['INSTALLED'] : 0,
                            maintain['statistics'].hasOwnProperty('INSTALLING') ? maintain['statistics']['INSTALLING'] : 0,
                            maintain['statistics'].hasOwnProperty('TRANSFER_OUT') ? maintain['statistics']['TRANSFER_OUT'] : 0,
                            maintain['statistics'].hasOwnProperty('TRANSFER_IN') ? maintain['statistics']['TRANSFER_IN'] : 0
                        ));
                        echartsMaintain.on('click', function (params) {
                            // location.href = `/report/sceneWorkshopWithAllCategory2/${scu}?status=${params.name}`;
                            location.href = `/report/stationsWithSceneWorkshop/${scu}`;
                        });
                        echartsMaintain.hideLoading();
                    },
                    error: function (err) {
                        {{--console.log(`{{ url('reportData') }} fail:`, err);--}}
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            });
        }

        $(function () {
            fnMakeDeviceDynamicAsStatusChart();  // 动态统计 1
            fnMakeWarehouseReportChart();  // 出入所 2
            fnMakeCycleFixChart(); // 周期修 4
            fnMakeRipeChart();  // 一次过检 6 ❌
            fnMakeMaintainChart(); // 生成台账 8
            fnMakePropertyChart();  // 资产管理 3
            if (select2.length > 0) select2.select2();
        });

        /**
         * 切换质量报告时间类型
         */
        function fnChangeQualityDateType(type) {
            let html = '';
            let currentDate = 0;
            switch (type) {
                default:
                case 'year':
                    if (qualityYears) {
                        $.each(qualityYears, function (k, date) {
                            if (date == '{{ date('Y') }}') currentDate = k;
                            html += `<option value="${date}" ${'{{ date('Y') }}' === date ? 'selected' : ''}>${date}</option>`;
                        });
                    } else {
                        html = '<option value="" selected>尚无报告</option>';
                    }
                    $selQualityDate.html(html);

                    $rdoQualityYear.prop('checked', true);
                    $rdoQualityMonth.prop('checked', false);

                    fnMakeQualityChart(type, qualityYears[currentDate]);  // 刷新质量报告报表
                    break;
                case 'month':
                    if (qualityMonths) {
                        $.each(qualityMonths, function (k, date) {
                            if (date == '{{ date('Y-m') }}') currentDate = k;
                            html += `<option value="${date}" ${'{{ date('Y-m') }}' == date ? 'selected' : ''}>${date}</option>`;
                        });
                    } else {
                        html = '<option value="" selected>尚无报告</option>';
                    }
                    $selQualityDate.html(html);

                    $rdoQualityYear.prop('checked', false);
                    $rdoQualityMonth.prop('checked', true);

                    fnMakeQualityChart(type, qualityMonths[currentDate]);  // 刷新质量报告报表
                    break;
            }
        }

        /**
         * 切换质量报告日期
         */
        function fnChangeQualityDate(qualityDate) {
            fnMakeQualityChart($rdoQualityYear.prop('checked') ? 'year' : 'month', qualityDate);
        }

        /**
         * 切换周期修报告类型
         */
        function fnChangeCycleFixDateType(type) {
            let html = '';
            let currentDate = 0;
            switch (type) {
                default:
                case 'year':
                    if (cycleFixYears) {
                        $.each(cycleFixYears, function (k, date) {
                            if (date == '{{ date('Y') }}') currentDate = k;
                            html += `<option value="${date}" ${'{{ date('Y') }}' === date ? 'selected' : ''}>${date}</option>`;
                        });
                    } else {
                        html = '<option value="" selected>尚无报告</option>';
                    }
                    $selCycleFixDate.html(html);

                    $rdoCycleFixYear.prop('checked', true);
                    $rdoCycleFixMonth.prop('checked', false);

                    fnMakeCycleFixChart(type, cycleFixYears[currentDate]);  // 刷新质量报告报表
                    break;
                case 'month':
                    if (cycleFixMonths) {
                        $.each(cycleFixMonths, function (k, date) {
                            if (date == '{{ date('Y-m') }}') currentDate = k;
                            html += `<option value="${date}" ${'{{ date('Y-m') }}' == date ? 'selected' : ''}>${date}</option>`;
                        });
                    } else {
                        html = '<option value="" selected>尚无报告</option>';
                    }
                    $selCycleFixDate.html(html);

                    $rdoCycleFixYear.prop('checked', false);
                    $rdoCycleFixMonth.prop('checked', true);

                    fnMakeCycleFixChart(type, cycleFixMonths[currentDate]);  // 刷新质量报告报表
                    break;
            }
        }

        /**
         * 切换周期修报告日期
         */
        function fnChangeCycleFixDate(cycleFixDate) {
            fnMakeCycleFixChart($rdoCycleFixYear.prop('checked') ? 'year' : 'month', cycleFixDate);
        }

        /**
         * 切换一次过检时间类型格式
         */
        function fnChangeRipeDateType(ripeDateType) {
            location.href = `?ripeDateType=${ripeDateType}&cycleFixDateType={{request("ripeDateType","year")}}&qualityDateType={{request("qualityDateType","year")}}`;
        }

        /**
         * 刷新本页数据（一次过检）
         */
        function fnCurrentPageWithRipe() {
            location.href = `?ripeDate=${$('#selRipeDate').val()}&ripeDateType={{request('ripeDateType')}}&cycleFixDate={{request('cycleFixDate','year')}}&qualityDateType={{request('qualityDateType','year')}}`;
        }
    </script>
@endsection
