@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            报表
            <small>设备状态统计</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">设备状态统计</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--查询--}}
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h1 class="box-title">查询</h1>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">现场车间</div>
                                        <select
                                            id="selSceneWorkshop"
                                            name="scene_workshop_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectSceneWorkshop(this.value)"
                                        >
                                        </select>
                                        <div class="input-group-addon">线别</div>
                                        <select
                                            id="selLine"
                                            name="line_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectLine(this.value)"
                                        >
                                        </select>
                                        <div class="input-group-addon">车站</div>
                                        <select
                                            id="selStation"
                                            name="station_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            id="selCategory"
                                            name="category_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">状态</div>
                                        <select
                                            id="selStatus"
                                            name="status_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="box box-solid">
                    <div class="box-header">
                        {{--<h3 class="box-title">{{ $currentSceneWorkshopName }}</h3>--}}
                        <div class="box-body">
                            <ul class="products-list product-list-in-box" id="products-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4 col-sm-4 col-xs-4"><h3 class="box-title">统计报表</h3></div>
                            <div class="col-md-8 col-sm-8 col-xs-8"></div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive">
                        <div class="chart" id="echartsMaintain" style="height: 300px;"></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>上道使用</th>
                                    <th>现场备品</th>
                                    <th>合计</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyMaintain"></tbody>
                            </table>
                            <h3>设备列表</h3>
                            <table class="table table-hover table-condensed" id="tblEntireInstance">
                                <thead>
                                <tr>
                                    <th>设备编号</th>
                                    <th>型号</th>
                                    <th>状态</th>
                                    <th>位置</th>
                                    <th>下次检修时间</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyEntireInstance">
                                @foreach($entireInstances as $entireInstance)
                                    <tr>
                                        <td>
                                            <a href="{{ url('search', $entireInstance->identity_code) }}">
                                                {{ $entireInstance->identity_code }}
                                            </a>
                                        </td>
                                        <td>{{ $entireInstance->smn }}</td>
                                        <td>{{ $statuses[$entireInstance->eis] ?? '-' }}</td>
                                        <td>
                                            @if(!$entireInstance->maintain_location_code && !$entireInstance->crossroad_number)
                                                -
                                            @else
                                                {{ $entireInstance->maintain_location_code }}
                                                {{ $entireInstance->crossroad_number }}
                                            @endif
                                        </td>
                                        <td>{{ @$entireInstance->next_fixing_time ? date('Y-m-d',$entireInstance->next_fixing_time) : '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if(!empty($entireInstances))
                            @if($entireInstances->hasPages())
                                <div>
                                    {{$entireInstances->appends([
                                                                'category_unique_code'=>request('category_unique_code'),
                                                                'entire_model_unique_code'=>request('entire_model_unique_code'),
                                                                'model_unique_code'=>request('model_unique_code'),
                                                                'status_unique_code'=>request('status_unique_code'),
                                                                'station_unique_code'=>request('station_unique_code'),
                                                                'scene_workshop_unique_code'=>request('scene_workshop_unique_code'),
                                                                'install_room_unique_code'=>request('install_room_unique_code'),
                                                                'install_platoon_unique_code'=>request('install_platoon_unique_code'),
                                                                'install_shelf_unique_code'=>request('install_shelf_unique_code'),
                                                                ])
                                                                ->fragment('tblEntireInstances')
                                                                ->links('vendor.pagination.no_jump') }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $select2 = $('.select2');
        let lines = {!! $linesAsJson !!};
        let maintains = {!! $maintainsAsJson !!};
        let kinds = {!! $kindsAsJson !!};
        let statuses = {!! $statusesAsJson !!};
        let statistics = {!! $statisticsAsJson !!};
        let categories = {!! $categoriesAsJson !!};
        let entireModels = {!! $entireModelsAsJson !!};
        let subModels = {!! $subModelsAsJson !!};

        let statisticsForTable = {};
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selLine = $('#selLine');
        let $selStation = $('#selStation');
        let $selCategory = $('#selCategory');
        let $selStatus = $('#selStatus');
        let $productsList = $('#products-list');
        let $tbodyMaintain = $('#tbodyMaintain');
        let $tbodyEntireInstance = $('#tbodyEntireInstance');

        /**
         * 渲染现场车间
         */
        function fnFillSceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(maintains, (scu, sceneWorkshop) => {
                html += `<option value="${scu}" ${"{{ $currentSceneWorkshopUniqueCode }}" === scu ? 'selected' : ''}>${sceneWorkshop['name']}</option>`;
            });
            $selSceneWorkshop.html(html);
        }

        /**
         * 填充线别列表
         */
        function fnFillLine() {
            let html = '<option value="">全部</option>';
            $.each(lines, function (key, line) {
                html += `<option value="${line['unique_code']}" ${'{{ request('line_unique_code') }}' === line['unique_code'] ? 'selected' : ''}>${line['name']}</option>`
            });
            $selLine.html(html);
        }

        /**
         * 选择线别渲染车站列表
         * @param {string} lineUniqueCode
         */
        function fnSelectLine(lineUniqueCode = '') {
            let html = '';
            if (lineUniqueCode !== '') {
                if ($selSceneWorkshop.val() !== '') $selSceneWorkshop.val('').trigger('change');
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {lineUniqueCode},
                    async: false,
                    success: function (res) {
                        let html = '<option value="">全部</option>';
                        {{--console.log(`{{ url('query/stations') }} success:`, res);--}}
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        $selStation.html(html);
                    },
                    error: function (err) {
                        console.log(`{{ url('query/stations') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            } else {
                $.ajax({
                    url: `{{ url('query/stations') }}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: function (res) {
                        let html = '<option value="">全部</option>';
                        {{--console.log(`{{ url('query/stations') }} success:`, res);--}}
                        $.each(res, (stationUniqueCode, stationName) => {
                            html += `<option value="${stationName}" ${"{{ request('station_name') }}" === stationName ? 'selected' : ''}>${stationName}</option>`;
                        });
                        $selStation.html(html);
                    },
                    error: function (err) {
                        console.log(`{{ url('query/stations') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 选择线别渲染车站列表
         */
        function fnSelectSceneWorkshop(sceneWorkshopUniqueCode = null) {
            let html = '';
            let tmp = sceneWorkshopUniqueCode ? {[sceneWorkshopUniqueCode]: maintains[sceneWorkshopUniqueCode]['subs']} : maintains;

            if ($selLine.val() !== '') $selLine.val('').trigger('change');

            $.each(tmp, (scu, sceneWorkshop) => {
                $.each(sceneWorkshop, (su, station) => {
                    html += `<option value="${su}" ${'{{ $currentStationUniqueCode }}' === su ? 'selected' : ''}>${station['name']}</option>`;
                });
            });
            $selStation.html(html);
        }

        /**
         * 渲染种类
         */
        function fnFillCategory() {
            let html = '';
            html = '<option value="">全部</option>';

            $.each(categories, function (cu, cn) {
                html += `<option value="${cu}" ${'{{ $currentCategoryUniqueCode }}' === cu ? 'selected' : ''}>${cn}</option>`;
            });

            $selCategory.html(html);
        }

        /**
         * 渲染类型
         */
        function fnFillEntireModel() {
            let html = '';
            // console.log('渲染类型：', statistics);
            $.each(statistics, (k, v) => {
                $.each(v['categories'], (cu, category) => {
                    $.each(category['subs'], (emu, entireModel) => {
                        html += `<li class="item">`;
                        html += `<div class="production-info">`;
                        html += `<a href="javascript:" onclick="fnScreenWithEntireModel('${emu}')" style="${'{{ $currentEntireModelUniqueCode }}' === emu ? 'color: green;' : ''}">${entireModel['name']}</a>`;
                        html += `<p class="product-description">`;
                        html += `上道使用：${entireModel['statistics']['INSTALLED']} `;
                        html += `现场备品：${entireModel['statistics']['INSTALLING']} `;
                        html += `</p>`;
                        html += `</li>`;
                    });
                });
            });
            $productsList.html(html);
        }

        /**
         * 渲染统计表格
         */
        function fnFillMaintainTable() {
            let html = '';
            $.each(statistics, (k, v) => {
                if (v['categories'].hasOwnProperty('{{ $currentCategoryUniqueCode }}')) {
                    $.each(v['categories']['{{ $currentCategoryUniqueCode }}']['subs']['{{ $currentEntireModelUniqueCode }}']['subs'], (mu, model) => {
                        if (!statisticsForTable.hasOwnProperty(mu)) statisticsForTable[mu] = {name: '', INSTALLED: 0, INSTALLING: 0, TRANSFER_OUT: 0, TRANSFER_IN: 0, device_total: 0};
                        statisticsForTable[mu]['name'] = model.name;
                        statisticsForTable[mu]['INSTALLED'] += model.statistics.INSTALLED;
                        statisticsForTable[mu]['INSTALLING'] += model.statistics.INSTALLING;
                        statisticsForTable[mu]['device_total'] += model.statistics.device_total;
                    });
                }
            });

            $.each(statisticsForTable, (mu, model) => {
                html += `<tr ${'{{ request('model_unique_code') }}' === mu ? 'class="bg-green"' : ''} onclick="fnScreenWithModel('${mu}')">`;
                html += `<td>${model['name']}</td>`;
                html += `<td>${model['INSTALLED']}</td>`;
                html += `<td>${model['INSTALLING']}</td>`;
                html += `<td>${model['device_total']}</td>`;
                html += `</tr>`;
            });
            $tbodyMaintain.html(html);
        }

        /**
         * 渲染统计图表
         */
        function fnFillMaintainECharts() {
            let echartsMaintain = echarts.init(document.getElementById('echartsMaintain'));
            echartsMaintain.showLoading();
            let models = {};
            let modelsAsFlip = {};
            let legendData = ['上道使用', '现场备品',];
            let series = [{
                name: '上道使用',
                type: 'bar',
                data: [],
                label: {show: true, position: 'top',},
            }, {
                name: '现场备品',
                type: 'bar',
                data: [],
                label: {show: true, position: 'top',},
            },];
            $.each(statisticsForTable, function (mu, model) {
                models[mu] = model['name'];
                modelsAsFlip[model['name']] = mu;
                series[0]['data'].push(model['INSTALLED']);
                series[1]['data'].push(model['INSTALLING']);
            });
            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {type: 'shadow', label: {show: true,},},
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
                legend: {data: legendData, itemGap: 5},
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true,
                },
                xAxis: [{type: 'category', data: Object.values(models),}],
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
            echartsMaintain.setOption(option);
            echartsMaintain.on('click', function (params) {
                console.log(params);
                // fnScreenWithModel(modelsAsFlip[params.name]);
            });
            echartsMaintain.hideLoading();
        }

        /**
         * 渲染状态
         */
        function fnFillStatus() {
            let tmp = {INSTALLED: '上道使用', INSTALLING: '现场备品', TRANSFER_OUT: '出所在途', TRANSFER_IN: '入所在途'};
            let html = '';
            html = '<option value="">全部</option>';
            $.each(tmp, (u, n) => {
                html += `<option value="${u}" ${'{{ request('status_unique_code')}}' === u ? 'selected' : ''}>${n}</option>`;
            });
            $selStatus.html(html);
        }

        $(function () {
            $select2.select2();

            fnFillSceneWorkshop();  // 渲染现场车间
            fnSelectSceneWorkshop('{{ $currentSceneWorkshopUniqueCode }}');  // 渲染车站
            fnFillLine(); // 填充线别列表
            fnFillCategory();  // 渲染种类
            fnFillEntireModel();  // 渲染类型
            fnFillStatus();  // 渲染状态
            fnFillMaintainTable();  // 渲染统计表格
            fnFillMaintainECharts();  // 渲染统计图表

        });

        /**
         * 根据类型切换
         */
        function fnScreenWithEntireModel(entireModelUniqueCode) {
            let route = '{{ url('/report/maintainEntireInstances') }}';

            let params = {
                scene_workshop_unique_code: $selSceneWorkshop.val(),
                station_unique_code: $selStation.val(),
                category_unique_code: $selCategory.val(),
                status_unique_code: $selStatus.val(),
                entire_model_unique_code: entireModelUniqueCode,
            };
            location.href = `${route}?${$.param(params)}`;
        }

        /**
         * 根据型号切换
         */
        function fnScreenWithModel(modelUniqueCode) {
            let route = '{{ url('/report/maintainEntireInstances') }}';

            let params = {
                scene_workshop_unique_code: $selSceneWorkshop.val(),
                station_unique_code: $selStation.val(),
                category_unique_code: $selCategory.val(),
                status_unique_code: $selStatus.val(),
                entire_model_unique_code: modelUniqueCode.substr(0, 5),
                model_unique_code: modelUniqueCode,
            };
            location.href = `${route}?${$.param(params)}`;
        }

        /**
         * 搜索
         */
        function fnScreen() {
            let route = '{{ url('/report/maintainEntireInstances') }}';

            let params = {
                scene_workshop_unique_code: $selSceneWorkshop.val(),
                station_unique_code: $selStation.val(),
                category_unique_code: $selCategory.val(),
                status_unique_code: $selStatus.val(),
            };
            location.href = `${route}?${$.param(params)}`;
        }
    </script>
@endsection
