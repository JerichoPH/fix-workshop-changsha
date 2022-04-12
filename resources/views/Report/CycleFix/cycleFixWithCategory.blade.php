@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            报表
            <small>周期修 - 预览</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li class="active">周期修 - 预览</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--周期修--}}
        <div class="row" id="divCycleFix">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-sm-9 col-md-9">
                                <h3>周期修 <small>预览</small></h3>
                            </div>
                            <div class="col-sm-3 col-md-3">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <label style="font-weight: normal;">
                                            <input type="radio" value="year" id="rdoCycleFixYear" onclick="fnChangeCycleFixDateType('year')"> 年视图
                                        </label>&nbsp;&nbsp;
                                        <label style="font-weight: normal; display:none;">
                                            <input type="radio" value="year" id="rdoCycleFixMonth" onclick="fnChangeCycleFixDateType('month')"> 月视图
                                        </label>
                                    </div>
                                    <select id="selCycleFixDate" class="form-control select2" style="width:100%;" onchange="fnChangeCycleFixDate(this.value)"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive form-horizontal">
                        <div id="echartsCycleFix" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tabCategory" data-toggle="tab">种类</a></li>
                        <li><a href="#tabAccount" data-toggle="tab">人员</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="active tab-pane" id="tabCategory">
                            <div class="post">
                                <div class="table-responsive">
                                    <table class="table table-striped table-condensed text-sm">
                                        <thead id="theadCategory"></thead>
                                        <tbody id="tbodyCategory"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="tabAccount">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let cycleFixYears = [];
        let cycleFixMonths = [];
        let $selCycleFixDate = $('#selCycleFixDate');
        let $rdoCycleFixYear = $('#rdoCycleFixYear');
        let $rdoCycleFixMonth = $('#rdoCycleFixMonth');
        let $theadCategory = $('#theadCategory');
        let $tbodyCategory = $('#tbodyCategory');

        /**
         * 补零
         */
        function strPad(num, n = 2) {
            let len = num.toString().length;
            while (len < n) {
                num = "0" + num;
                len++;
            }
            return num;
        }

        /**
         * 周期修表格
         */
        function fnMakeCycleFixTable(dateType = null, date = null) {
            let theadHtml = '';
            let tbodyHtml = '';
            let categories = {};
            let categoriesAsFlip = {};
            let year = null;
            let tmp = {};

            switch (dateType) {
                default:
                case 'year':
                    year = date;
                    break;
                case 'month':
                    date = date.split('-');
                    year = date[0];
                    break;
            }

            $.ajax({
                url: `{{ url('report/cycleFix/reportForCategoryWithYear',$currentCategoryUniqueCode) }}`,
                type: 'get',
                data: {year},
                async: true,
                success: function (res) {
                    let {year, statistics, entireInstancesWithModel, entireInstancesForFixedWithModel, missions} = res;

                    // 格式化数据
                    let tmp = [];
                    $.each(statistics, function (month, statistic) {
                        $.each(statistic, function (mu, m) {
                            if (!categories.hasOwnProperty(mu)) categories[mu] = m['name'];
                            if (!categoriesAsFlip.hasOwnProperty(m['name'])) categoriesAsFlip[m['name']] = mu;
                            if (!tmp.hasOwnProperty(mu)) tmp[mu] = {name: m['name'], statistics: {}};
                            if (!tmp[mu]['statistics'].hasOwnProperty(month)) tmp[mu]['statistics'][month] = {statistics: {}, stations: {}};
                            tmp[mu]['statistics'][month]['statistics'] = m['statistics'];
                            tmp[mu]['statistics'][month]['stations'] = m['stations'];
                        });
                    });

                    // 生成表头
                    theadHtml = '<tr><th>型号</th><th>轮休<br>周期</th><th>工区管内<br>设备总数</th><th>管内器材<br>成品总数<th>合计</th>';
                    for (let i = 1; i < 13; i++) {
                        theadHtml += `<th>${year}-${strPad(i)}</th>`;
                    }
                    theadHtml += '</tr>';
                    $theadCategory.html(theadHtml);

                    // 生成表格
                    $.each(categories, function (mu, mn) {
                        let countColPlan = 0;
                        let countColFixed = 0;
                        let countColMission = 0;
                        tbodyHtml += `<tr><td>${mn}</td>
<td>${entireInstancesWithModel.hasOwnProperty(mu) ? entireInstancesWithModel[mu]['fcv'] : 0}</td>
<td>${entireInstancesWithModel.hasOwnProperty(mu) ? entireInstancesWithModel[mu]['aggregate'] : 0}</td>
<td>${entireInstancesForFixedWithModel.hasOwnProperty(mu) ? entireInstancesForFixedWithModel[mu]['aggregate'] : 0}</td>`;
                        let monthHtml = '';
                        for (let i = 1; i < 13; i++) {
                            let month = strPad(i);
                            let plan = tmp.hasOwnProperty(mu) ? (tmp[mu]['statistics'].hasOwnProperty(month) ? tmp[mu]['statistics'][month]['statistics']['plan_device_count'] : 0) : 0;
                            let mission = missions.hasOwnProperty(mu) ? (missions[mu].hasOwnProperty(month) ? parseInt(missions[mu][month]['aggregate']) : 0) : 0;
                            let fixed = tmp.hasOwnProperty(mu) ? (tmp[mu]['statistics'].hasOwnProperty(month) ? tmp[mu]['statistics'][month]['statistics']['fixed_device_count'] : 0) : 0;
                            countColPlan += plan;
                            countColFixed += fixed;
                            countColMission += mission;
                            monthHtml += `<td><span>计划：<a href="/report/cycleFixWithEntireModelAsPlan/${mu.substr(0, 5)}?date={{ request('date') }}-${month}" target="_blank">${plan}</a></span><br>`;
                            monthHtml += `<span>任务：${mission}</span><br>`;
                            monthHtml += `<span>完成：${fixed}</span>`;
                            let stationTmp = [];
                            if (tmp[mu]['statistics'].hasOwnProperty(month)) {
                                if (tmp[mu]['statistics'][month].hasOwnProperty('stations')) {
                                    monthHtml += '<hr>';
                                    $.each(tmp[mu]['statistics'][month]['stations'], function (su, s) {
                                        stationTmp.push(`${s['name']}：${s['statistics']['plan_device_count']}`);
                                    });
                                    monthHtml += stationTmp.join('<br>');
                                }
                            }
                            monthHtml += '</td>';
                        }
                        tbodyHtml += `<td><span>计划：${countColPlan}</span><br><span>任务：${countColMission}</span><br><span>完成：${countColFixed}</span></td>` + monthHtml + '</tr>';
                    });
                    $tbodyCategory.html(tbodyHtml);
                },
                error: function (err) {
                    {{--console.log(`{{ url('reportData')  }} fail:`, err);--}}
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 周期修图表
         */
        function fnMakeCycleFixChart(dateType = null, date = null) {
            let cycleFixECharts = echarts.init(document.getElementById('echartsCycleFix'));
            let dateHtml = '';
            let year = null;

            switch (dateType) {
                default:
                case 'year':
                    year = date;
                    break;
                case 'month':
                    date = date.split('-');
                    year = date[0];
                    break;
            }

            cycleFixECharts.showLoading();
            $.ajax({
                url: `{{ url('report/cycleFix/reportForCategoryWithYear',$currentCategoryUniqueCode) }}`,
                type: 'get',
                data: {year},
                async: true,
                success: function (res) {
                    {{--console.log(`{{ url('reportData')  }} success:`, res);--}}
                    let {year, statistics, cycleFixYears, cycleFixMonths, missions} = res;

                    let legendData = ['任务', '计划', '检修'];
                    let models = {};
                    let modelsAsFlip = {};
                    let tmp = {mission: {}, plan: {}, fixed: {}};
                    $.each(statistics, (wai, workArea) => {
                        $.each(workArea, (mu, m) => {
                            models[mu] = m['name'];
                            modelsAsFlip[m['name']] = mu;
                            if (!tmp['mission'].hasOwnProperty(mu)) tmp['mission'][mu] = 0;
                            if (missions.hasOwnProperty(mu)) {
                                $.each(missions[mu], function (month, mission) {
                                    let aggregate = parseInt(mission['aggregate']);
                                    tmp['mission'][mu] += aggregate;
                                });
                            }
                            if (!tmp['plan'].hasOwnProperty(mu)) tmp['plan'][mu] = 0;
                            tmp['plan'][mu] += m['statistics']['plan_device_count'];
                            if (!tmp['fixed'].hasOwnProperty(mu)) tmp['fixed'][mu] = 0;
                            tmp['fixed'][mu] += m['statistics']['fixed_device_count'];
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
                    for (let idx in models) {
                        categoryTmp.push(models[idx]);
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
                                    html += `${params[0].seriesName}：${params[0].value}<br>
${params[1].seriesName}：${params[1].value}<br>
任务完成率：${params[0].value === 0 ? params[2].value / 1 : parseFloat(((params[2].value / params[0].value) * 100).toFixed(2))}%<br>
计划完成率：${params[1].value === 0 ? params[2].value / 1 : parseFloat(((params[2].value / params[1].value) * 100).toFixed(2))}%<br>
检修总计：${params[2].value}`
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
                                show: false,
                                position: 'top'
                            },
                        }, {
                            name: '计划',
                            type: 'bar',
                            data: planTmp,
                            label: {
                                show: false,
                                position: 'top'
                            },
                        }, {
                            name: '检修',
                            type: 'bar',
                            data: fixedTmp,
                            label: {
                                show: false,
                                position: 'top'
                            },
                        }]
                    };
                    cycleFixECharts.setOption(option);
                    cycleFixECharts.hideLoading();

                    if (cycleFixYears) {
                        for (let idx in cycleFixYears) {
                            dateHtml += `<option value="${cycleFixYears[idx]}" ${year === cycleFixYears[idx] ? 'selected' : ''}>${cycleFixYears[idx]}</option>`;
                        }
                    }
                    $selCycleFixDate.html(dateHtml);

                    $rdoCycleFixYear.prop('checked', true);
                    $rdoCycleFixMonth.prop('checked', false);

                    cycleFixECharts.on('click', function (params) {
                        let categoryUniqueCode = modelsAsFlip[params['name']] ? modelsAsFlip[params['name']] : '';
                        if (categoryUniqueCode) location.href = `/report/cycleFixWithCategory/${categoryUniqueCode}`;
                    });
                },
                error: function (err) {
                    {{--console.log(`{{ url('reportData')  }} fail:`, err);--}}
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        $(function () {
            fnMakeCycleFixChart('year', '{{ request('date', date('Y')) }}');  // 周期修报表
            fnMakeCycleFixTable('year', '{{ request('date', date('Y')) }}');  // 周期修表格
        });

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

                    fnMakeCycleFixChart(type, cycleFixYears[currentDate]);  // 刷新周期修图表
                    fnMakeCycleFixTable(type, cycleFixYears[currentDate]);  // 刷新周期修表格
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

                    fnMakeCycleFixChart(type, cycleFixMonths[currentDate]);  // 刷新周期修图表
                    fnMakeCycleFixTable(type, cycleFixMonths[currentDate]); // 刷新周期修表格
                    break;
            }
        }

        /**
         * 切换周期修报告日期
         */
        function fnChangeCycleFixDate(cycleFixDate) {
            fnMakeCycleFixChart($rdoCycleFixYear.prop('checked') ? 'year' : 'month', cycleFixDate);
            fnMakeCycleFixTable($rdoCycleFixYear.prop('checked') ? 'year' : 'month', cycleFixDate);
        }
    </script>
@endsection
