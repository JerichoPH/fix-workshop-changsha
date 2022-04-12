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
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li class="active">资产管理</li>--}}
{{--    </ol>--}}
</section>
<section class="content">
    @include('Layout.alert')
    {{--资产管理--}}
    <div class="row" id="divProperty">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">资产管理</h3>
                </div>
                <div class="box-body chart-responsive form-horizontal">
                    <div id="echartsProperty" style="height: 400px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">资产管理（种类）</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive table-responsive-lg table-responsive-sm">
                        <table class="table table-condensed table-hover" style="font-size: 9px;">
                            <thead>
                                <tr>
                                    <th>种类</th>
                                    @foreach($propertyFactoryNames as $factoryName)
                                    <th>{{ $factoryName }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($propertyCategoryNames as $index => $category_name)
                                <tr>
                                    <td>{{ $category_name }}</td>
                                    @foreach($propertyFactoryNames as $factory_name)
                                    <td>
                                        @if($propertyWithFactory[$factory_name][$index] > 0)
                                        <a
                                            href="{{ url('report/propertyCategory',$propertyCategoriesFlip[$category_name]) }}">{{ $propertyWithFactory[$factory_name][$index] }}</a>
                                        @else
                                        {{ $propertyWithFactory[$factory_name][$index] }}
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
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
    /**
     * 资产管理
     */
    function fnMakePropertyChart() {
        let propertyFactoryNames = JSON.parse('{!! $propertyFactoryNamesAsJson !!}');
        let propertyWithFactory = JSON.parse('{!! $propertyWithFactoryAsJson !!}');
        let propertyCategoryNames = JSON.parse('{!! $propertyCategoryNamesAsJson !!}');

        let series = [];
        let i = 0;

        $.each(propertyWithFactory, function (idx, item) {
            series.push(item ? {
                name: idx,
                type: 'bar',
                stack: '总数',
                data: item,
            } : {});
            i++;
        });

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
            legend: {data: propertyFactoryNames,},
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
                data: propertyCategoryNames,
            }],
            yAxis: [{type: 'value',}],
            series: series,
        };

        let echartsProperty = echarts.init(document.getElementById('echartsProperty'));
        echartsProperty.setOption(option);
        echartsProperty.on('click', function (params) {
            categories = JSON.parse('{!! collect(json_decode($propertyCategoriesAsJson),true)->flip()->toJson() !!}');
            location.href = `{{url('report/propertyCategory')}}/${categories[params.name]}`;
        });
    }

    $(function(){
        fnMakePropertyChart();
    });
</script>
@endsection
