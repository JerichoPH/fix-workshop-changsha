@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            台账管理
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">{{ $sceneWorkshopName }}</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                </div>
            </div>
            <div class="box-body">
                @foreach($stations as $su => $sn)
                    <div class="col-md-4">
                        <a href="javascript:" style="color: black;">
                            <div class="box box-success">
                                <div class="box-header">
                                    <i class="fa fa-text-width"></i>
                                    <h3 class="box-title">{{ $sn }} <small id="spanSceneWorkshopTitle_{{ $su }}"></small></h3>
                                </div>
                                <div class="box-body">
                                    <div class="chart" id="echartsMaintain_{{ $su }}" style="height: 300px;"></div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="box-footer">
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>名称</th>
                            <th>总数</th>
                            <th>上道使用</th>
                            <th>现场备品</th>
                        </tr>
                        </thead>
                        <tbody id="tbodyMaintain"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $tbodyMaintain = $('#tbodyMaintain');

        /**
         * 生成台账图表
         */
        function fnMakeMaintainECharts() {
            let stationStatistics = JSON.parse('{!! $stationAsJson !!}');
            let categoryStatistics = JSON.parse('{!! $categoryAsJson !!}');
            console.log(stationStatistics);
            console.log(categoryStatistics);

            // 图表
            $.each(stationStatistics, function (su, data) {
                let echartsMaintain = echarts.init(document.getElementById(`echartsMaintain_${su}`));
                echartsMaintain.showLoading();
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
                            data: [`上道使用：${installed}`, `现场备品：${installing}`,]
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
                            radius: '55%',
                            center: ['50%', '60%'],
                            data: [{
                                value: installed,
                                name: `上道使用：${installed}`,
                            }, {
                                value: installing,
                                name: `现场备品：${installing}`,
                            },]
                        }]
                    };
                };
                $(`#spanSceneWorkshopTitle_${su}`).text(`总数：${data['statistics']['device_total']}`);
                echartsMaintain.setOption(makeOption(
                    data['name'],
                    data['statistics'].hasOwnProperty('INSTALLED') ? data['statistics']['INSTALLED'] : 0,
                    data['statistics'].hasOwnProperty('INSTALLING') ? data['statistics']['INSTALLING'] : 0,
                ));
                echartsMaintain.on('click', function (params) {
                    location.href = `/report/maintainEntireInstances?station_unique_code=${su}`;
                });
                echartsMaintain.hideLoading();
            });

            // 表格
            let html = '';
            $.each(categoryStatistics, function (cu, data) {
                html += '<tr>';
                html += `<td>${data['name']}</td>`;
                html += `<td>${data['statistics']['device_total']}</td>`;
                html += `<td>${data['statistics']['INSTALLED']}</td>`;
                html += `<td>${data['statistics']['INSTALLING']}</td>`;
                html += '</tr>';
            });
            $tbodyMaintain.html(html);
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnMakeMaintainECharts();  // 生成台账图表
        });
    </script>
@endsection
