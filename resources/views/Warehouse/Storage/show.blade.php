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
            设备管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="/warehouse/storage"> 库房管理</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">设备列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('warehouse/storage/scanInBatch')}}" class="btn btn-default btn-lg btn-flat">入库</a>
                    <a href="{{url('warehouse/report/scanInBatch')}}?type=OUT" class="btn btn-default btn-lg btn-flat">出所</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <theader>
                        <tr>
                            <th>唯一编号</th>
                            <th>入库时间</th>
                            <th>仓库位置</th>
                            <th>安装位置</th>
                        </tr>
                    </theader>
                    <tbody>
                    @foreach($entireInstances as $entireInstance)
                        <tr>
                            <td><a href="{{url('search',$entireInstance->identity_code)}}">{{$entireInstance->identity_code}}</a></td>
                            <td>{{$entireInstance->updated_at}}</td>
                            <td>{{"$entireInstance->warehouse_name $entireInstance->location_unique_code"}}</td>
                            <td>{{"$entireInstance->maintain_station_name
$entireInstance->maintain_location_code
$entireInstance->crossroad_number
$entireInstance->open_direction
$entireInstance->line_name
$entireInstance->said_rod
$entireInstance->to_direction
$entireInstance->traction"}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entireInstances->hasPages())
                <div class="box-footer">
                    {{ $entireInstances->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(function(){
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
