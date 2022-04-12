@extends('Layout.index')
@section('style')
<!-- Select2 -->
<link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
<!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
<link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        库房管理
        <small>列表</small>
    </h1>
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li class="active">列表</li>--}}
{{--    </ol>--}}
</section>
<section class="content">
    @include('Layout.alert')

    {{--图表--}}
    <div class="row" id="divProperty">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">库房管理</h3>
                </div>
                <div class="box-body chart-responsive form-horizontal">
                    <div id="echartsProperty" style="height: 300px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{--表格--}}
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">库房列表</h3>
            {{--右侧最小化按钮--}}
            <div class="box-tools pull-right"></div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-condensed" id="table">
                <theader>
                    <tr>
                        <th>名称</th>
                        <th>数量</th>
                    </tr>
                </theader>
                <tbody>
                    @foreach($entireModelCount as $parentName => $c1)
                    <tr class="bg-blue-active">
                        <td>{{$parentName}}</td>
                        <td>{{$c1}}</td>
                    </tr>
                    @foreach($subModelCount[$parentName] as $subName => $c2)
                    <tr onclick="location.href=`{{url('warehouse/storage',$subName)}}`">
                        <td>{{$subName}}</td>
                        <td>{{$c2}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
@section('script')
<script>
    /**
     * 生成超期使用
     */
     fnMakeScrapedChart = () => {
        scrapedCategoryNames = JSON.parse('{!! $scrapedCategoryNames !!}');
        series = [{
                name: '总数',
                type: 'bar',
                data: []
            },
            {
                name: '超期',
                type: 'bar',
                data: []
            },
            {
                name: '超期使用率',
                type: 'bar',
                data: []
            }
        ];
        $.each(JSON.parse('{!! $scrapedWithCategory !!}'), function (index, item) {
            series[0]['data'].push(item.entireInstance);
            series[1]['data'].push(item.scraped);
            series[2]['data'].push(item.rate);
        });

        var echartsScraped = echarts.init(document.getElementById('echartsScraped'));
        option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow',
                    label: {
                        show: true
                    }
                }
            },
            calculable: true,
            legend: {
                data: ['总数', '超期使用'],
                itemGap: 5
            },
            grid: {
                top: '12%',
                left: '1%',
                right: '10%',
                containLabel: true
            },
            xAxis: [{
                type: 'category',
                data: scrapedCategoryNames
            }],
            yAxis: [{
                type: 'value',
                name: '',
                axisLabel: {
                    formatter: function (a) {
                        a = +a;
                        return isFinite(a) ?
                            echarts.format.addCommas(+a / 1000) :
                            '';
                    }
                }
            }],
            dataZoom: [{
                    show: true,
                    start: 0,
                    end: 10
                },
                {
                    type: 'inside',
                    start: 94,
                    end: 100
                },
                {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '80%',
                    showDataShadow: false,
                    left: '93%'
                }
            ],
            series: series
        };
        echartsScraped.setOption(option);
        echartsScraped.on('click', function (params) {
            scrapedCategories = JSON.parse('{!! $scrapedCategories !!}');
            // console.log(scrapedCategories, params.name, scrapedCategories[params.name]);
            location.href = `{{url('report/scrapedWithCategory')}}/${scrapedCategories[params.name]}`;
        });
    };

    $(function () {
        if ($('.select2').length > 0) $('.select2').select2();
        if ($('.select2').length > 0) $('.select2').select2();

        if (document.getElementById('table')) {
            $('#table').DataTable({
                'paging': false,
                'lengthChange': false,
                'searching': false,
                'ordering': true,
                'info': false,
                'autoWidth': false
            });
        };

        $('#reservation').daterangepicker();
    });
</script>
@endsection
